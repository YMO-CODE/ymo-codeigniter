<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cloudflare R2 (S3-compatible) object storage, plain PHP + curl (AWS SigV4).
 *
 * Two buckets:
 *   public  – served at R2_PUBLIC_URL (media.yourmechaniconline.com): offer images,
 *             vehicle photos, Google post photos.
 *   private – never public; read through the app (auth checked) or short presigned
 *             links: invoices, résumés, WhatsApp photos/PDFs, DB backups.
 *
 * Stored paths keep the bucket in a prefix so the DB says where a file lives:
 *   r2/<key>   public bucket
 *   r2p/<key>  private bucket
 *   anything else = a local file (uploads/... under public/, storage/... beside it)
 *
 * Env (server .env): R2_ACCOUNT_ID, R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY,
 *   R2_PUBLIC_BUCKET (ymo-public-media), R2_PRIVATE_BUCKET (ymo-private),
 *   R2_PUBLIC_URL (https://media.yourmechaniconline.com).
 * Without them enabled() is FALSE and callers keep files on the server as before.
 */
class Media_store
{
    public $error = '';
    /** Fixed unix time for tests; NULL = now. */
    public $clock = NULL;

    protected $account = '';
    protected $key_id = '';
    protected $secret = '';
    protected $buckets = array();
    protected $public_url = '';
    protected $region = 'auto';

    public function __construct(array $cfg = array())
    {
        $env = function ($k, $d = '') use ($cfg) {
            if (isset($cfg[$k])) {
                return (string) $cfg[$k];
            }
            $v = getenv($k);
            return ($v === FALSE || trim((string) $v) === '') ? $d : trim((string) $v);
        };
        $this->account    = $env('R2_ACCOUNT_ID');
        $this->key_id     = $env('R2_ACCESS_KEY_ID');
        $this->secret     = $env('R2_SECRET_ACCESS_KEY');
        $this->buckets    = array(
            'public'  => $env('R2_PUBLIC_BUCKET', 'ymo-public-media'),
            'private' => $env('R2_PRIVATE_BUCKET', 'ymo-private'),
        );
        $this->public_url = rtrim($env('R2_PUBLIC_URL', 'https://media.yourmechaniconline.com'), '/');
        $this->region     = $env('R2_REGION', 'auto');
        $endpoint = $env('R2_ENDPOINT');
        $this->endpoint = $endpoint !== '' ? rtrim($endpoint, '/') : ($this->account !== '' ? 'https://'.$this->account.'.r2.cloudflarestorage.com' : '');
    }

    protected $endpoint = '';

    public function enabled()
    {
        return $this->endpoint !== '' && $this->key_id !== '' && $this->secret !== '';
    }

    /* ---------- stored-path helpers ---------- */

    /** @return array{0:string|null,1:string} [scope|null, key] — scope NULL means local file. */
    public static function parse($path)
    {
        $path = ltrim((string) $path, '/');
        if (strpos($path, 'r2/') === 0) {
            return array('public', substr($path, 3));
        }
        if (strpos($path, 'r2p/') === 0) {
            return array('private', substr($path, 4));
        }
        return array(NULL, $path);
    }

    public static function path_for($scope, $key)
    {
        return ($scope === 'public' ? 'r2/' : 'r2p/').ltrim($key, '/');
    }

    public function public_url($key)
    {
        return $this->public_url.'/'.$this->encode_key($key);
    }

    /* ---------- object operations ---------- */

    /** Upload bytes. @return string|null stored path (r2/… or r2p/…) */
    public function put($scope, $key, $body, $content_type = 'application/octet-stream', array $extra = array())
    {
        $key = ltrim($key, '/');
        $headers = array_merge(array('content-type' => $content_type), $extra);
        $r = $this->request('PUT', $scope, $key, array(), (string) $body, $headers);
        return $r && $r['code'] >= 200 && $r['code'] < 300 ? self::path_for($scope, $key) : NULL;
    }

    public function put_file($scope, $key, $file, $content_type = 'application/octet-stream', array $extra = array())
    {
        $body = @file_get_contents($file);
        if ($body === FALSE) {
            $this->error = 'Cannot read '.basename($file);
            return NULL;
        }
        return $this->put($scope, $key, $body, $content_type, $extra);
    }

    /** @return string|null object bytes */
    public function get($scope, $key)
    {
        $r = $this->request('GET', $scope, ltrim($key, '/'));
        return $r && $r['code'] === 200 ? $r['body'] : NULL;
    }

    public function exists($scope, $key)
    {
        $r = $this->request('HEAD', $scope, ltrim($key, '/'));
        return $r && $r['code'] === 200;
    }

    public function delete($scope, $key)
    {
        $r = $this->request('DELETE', $scope, ltrim($key, '/'));
        return $r && ($r['code'] === 204 || $r['code'] === 200 || $r['code'] === 404);
    }

    /**
     * List objects under a prefix (up to $max).
     * @return array<int,array{key:string,size:int,modified:string}>|null
     */
    public function list_objects($scope, $prefix = '', $max = 1000)
    {
        $out = array();
        $token = NULL;
        do {
            $q = array('list-type' => '2', 'prefix' => $prefix, 'max-keys' => (string) min(1000, $max));
            if ($token) {
                $q['continuation-token'] = $token;
            }
            $r = $this->request('GET', $scope, '', $q);
            if (!$r || $r['code'] !== 200) {
                return NULL;
            }
            $xml = @simplexml_load_string($r['body']);
            if (!$xml) {
                $this->error = 'Bad list response';
                return NULL;
            }
            foreach ($xml->Contents as $c) {
                $out[] = array('key' => (string) $c->Key, 'size' => (int) $c->Size, 'modified' => (string) $c->LastModified);
            }
            $token = ((string) $xml->IsTruncated === 'true') ? (string) $xml->NextContinuationToken : NULL;
        } while ($token && count($out) < $max);
        return $out;
    }

    /** Short-lived GET link for a private object. */
    public function presign($scope, $key, $ttl = 600, $download_name = NULL)
    {
        $key = ltrim($key, '/');
        $bucket = $this->buckets[$scope];
        $host = $this->host();
        $t = $this->clock !== NULL ? (int) $this->clock : time();
        $amz_date = gmdate('Ymd\THis\Z', $t);
        $date = gmdate('Ymd', $t);
        $scope_str = $date.'/'.$this->region.'/s3/aws4_request';
        $q = array(
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $this->key_id.'/'.$scope_str,
            'X-Amz-Date'          => $amz_date,
            'X-Amz-Expires'       => (string) max(1, min(604800, (int) $ttl)),
            'X-Amz-SignedHeaders' => 'host',
        );
        if ($download_name !== NULL) {
            $q['response-content-disposition'] = 'inline; filename="'.str_replace('"', '', $download_name).'"';
        }
        $uri = '/'.$bucket.'/'.$this->encode_key($key);
        $canonical = "GET\n".$uri."\n".$this->canonical_query($q)."\nhost:".$host."\n\nhost\nUNSIGNED-PAYLOAD";
        $sts = "AWS4-HMAC-SHA256\n".$amz_date."\n".$scope_str."\n".hash('sha256', $canonical);
        $q['X-Amz-Signature'] = hash_hmac('sha256', $sts, $this->signing_key($date));
        return $this->endpoint.$uri.'?'.$this->canonical_query($q);
    }

    /* ---------- signing ---------- */

    protected function request($method, $scope, $key, array $query = array(), $body = '', array $headers = array())
    {
        $this->error = '';
        if (!$this->enabled()) {
            $this->error = 'R2 is not configured (R2_* settings missing).';
            return NULL;
        }
        if (!isset($this->buckets[$scope])) {
            $this->error = 'Unknown bucket scope '.$scope;
            return NULL;
        }
        $host = $this->host();
        $uri = '/'.$this->buckets[$scope].($key !== '' ? '/'.$this->encode_key($key) : '');
        $t = $this->clock !== NULL ? (int) $this->clock : time();
        $amz_date = gmdate('Ymd\THis\Z', $t);
        $date = gmdate('Ymd', $t);
        $payload_hash = hash('sha256', (string) $body);

        $h = array();
        foreach ($headers as $k => $v) {
            $h[strtolower($k)] = trim((string) $v);
        }
        $h['host'] = $host;
        $h['x-amz-content-sha256'] = $payload_hash;
        $h['x-amz-date'] = $amz_date;
        ksort($h);
        $canon_headers = '';
        foreach ($h as $k => $v) {
            $canon_headers .= $k.':'.$v."\n";
        }
        $signed = implode(';', array_keys($h));
        $qs = $this->canonical_query($query);
        $canonical = $method."\n".$uri."\n".$qs."\n".$canon_headers."\n".$signed."\n".$payload_hash;
        $scope_str = $date.'/'.$this->region.'/s3/aws4_request';
        $sts = "AWS4-HMAC-SHA256\n".$amz_date."\n".$scope_str."\n".hash('sha256', $canonical);
        $sig = hash_hmac('sha256', $sts, $this->signing_key($date));
        $auth = 'AWS4-HMAC-SHA256 Credential='.$this->key_id.'/'.$scope_str.', SignedHeaders='.$signed.', Signature='.$sig;

        $send = array('Authorization: '.$auth);
        foreach ($h as $k => $v) {
            if ($k !== 'host') {
                $send[] = $k.': '.$v;
            }
        }
        $ch = curl_init($this->endpoint.$uri.($qs !== '' ? '?'.$qs : ''));
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => $send,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 120,
        ));
        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        }
        if ($method === 'PUT' || $method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $body);
        }
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === FALSE) {
            $this->error = 'R2 connection failed: '.$err;
            log_message('error', '[media_store] '.$this->error);
            return NULL;
        }
        if ($code >= 300 && !($method === 'DELETE' && $code === 404) && !($method === 'HEAD' && $code === 404)) {
            $msg = '';
            if (preg_match('/<Code>([^<]+)<\/Code>/', (string) $resp, $m)) {
                $msg = $m[1];
            }
            $this->error = 'R2 '.$method.' '.$key.' failed: HTTP '.$code.($msg !== '' ? ' '.$msg : '');
            log_message('error', '[media_store] '.$this->error);
        }
        return array('code' => $code, 'body' => (string) $resp);
    }

    /** Host header value as curl sends it (with the port when it isn't the default). */
    protected function host()
    {
        $u = parse_url($this->endpoint);
        $h = $u['host'];
        if (!empty($u['port']) && !(($u['scheme'] === 'https' && (int) $u['port'] === 443) || ($u['scheme'] === 'http' && (int) $u['port'] === 80))) {
            $h .= ':'.$u['port'];
        }
        return $h;
    }

    protected function signing_key($date)
    {
        $k = hash_hmac('sha256', $date, 'AWS4'.$this->secret, TRUE);
        $k = hash_hmac('sha256', $this->region, $k, TRUE);
        $k = hash_hmac('sha256', 's3', $k, TRUE);
        return hash_hmac('sha256', 'aws4_request', $k, TRUE);
    }

    protected function encode_key($key)
    {
        return implode('/', array_map('rawurlencode', explode('/', ltrim((string) $key, '/'))));
    }

    protected function canonical_query(array $q)
    {
        if (!$q) {
            return '';
        }
        ksort($q, SORT_STRING);
        $parts = array();
        foreach ($q as $k => $v) {
            $parts[] = rawurlencode($k).'='.rawurlencode((string) $v);
        }
        return implode('&', $parts);
    }
}
