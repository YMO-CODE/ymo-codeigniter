<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal Google Cloud Storage client for the job card photo bucket.
 * Authenticates as the VM's service account through the metadata server, so no
 * key file is stored anywhere. Needs the VM's Storage access scope set to read/write.
 *
 * Env: YMO_GCS_BUCKET (default ymo-crm-jobcard-photos).
 * Local testing only: YMO_GCS_FAKE_DIR=/some/dir stores objects in that folder instead.
 */
class Gcs_client
{
    const DEFAULT_BUCKET = 'ymo-crm-jobcard-photos';
    const METADATA_TOKEN_URL = 'http://169.254.169.254/computeMetadata/v1/instance/service-accounts/default/token';

    protected $bucket;
    protected $fake_dir;
    protected $last_error = '';
    protected static $token = NULL;
    protected static $token_exp = 0;

    public function __construct()
    {
        $env = function ($k) { return function_exists('ymo_env') ? ymo_env($k) : getenv($k); };
        $this->bucket = (string) ($env('YMO_GCS_BUCKET') ?: self::DEFAULT_BUCKET);
        $fake = (string) $env('YMO_GCS_FAKE_DIR');
        $this->fake_dir = $fake !== '' ? rtrim($fake, '/') : '';
    }

    public function last_error()
    {
        return $this->last_error;
    }

    public function bucket()
    {
        return $this->bucket;
    }

    /** @return bool */
    public function put($name, $bytes, $content_type = 'image/jpeg')
    {
        $this->last_error = '';
        if ($this->fake_dir !== '') {
            $path = $this->_fake_path($name);
            @mkdir(dirname($path), 0775, TRUE);
            return file_put_contents($path, $bytes) !== FALSE;
        }
        $url = 'https://storage.googleapis.com/upload/storage/v1/b/'.rawurlencode($this->bucket)
             .'/o?uploadType=media&name='.rawurlencode($name);
        list($code, $body) = $this->_request('POST', $url, $bytes, array('Content-Type: '.$content_type));
        if ($code >= 200 && $code < 300) {
            return TRUE;
        }
        $this->last_error = 'Storage upload failed ('.$code.')'.$this->_err_hint($code, $body);
        log_message('error', '[gcs] put '.$name.': '.$code.' '.substr((string) $body, 0, 300));
        return FALSE;
    }

    /** @return string|null object bytes */
    public function get($name)
    {
        $this->last_error = '';
        if ($this->fake_dir !== '') {
            $path = $this->_fake_path($name);
            return is_file($path) ? file_get_contents($path) : NULL;
        }
        $url = 'https://storage.googleapis.com/storage/v1/b/'.rawurlencode($this->bucket).'/o/'.rawurlencode($name).'?alt=media';
        list($code, $body) = $this->_request('GET', $url);
        if ($code === 200) {
            return $body;
        }
        $this->last_error = 'Storage read failed ('.$code.')';
        if ($code !== 404) {
            log_message('error', '[gcs] get '.$name.': '.$code.' '.substr((string) $body, 0, 300));
        }
        return NULL;
    }

    /** @return bool */
    public function delete($name)
    {
        if ($this->fake_dir !== '') {
            $path = $this->_fake_path($name);
            return !is_file($path) || @unlink($path);
        }
        $url = 'https://storage.googleapis.com/storage/v1/b/'.rawurlencode($this->bucket).'/o/'.rawurlencode($name);
        list($code) = $this->_request('DELETE', $url);
        return ($code >= 200 && $code < 300) || $code === 404;
    }

    // --- internals -----------------------------------------------------------

    protected function _fake_path($name)
    {
        return $this->fake_dir.'/'.str_replace(array('..', '\\'), '', $name);
    }

    protected function _err_hint($code, $body)
    {
        if ($code === 403 && stripos((string) $body, 'scope') !== FALSE) {
            return ': the server is not allowed to write to Cloud Storage (VM access scope).';
        }
        if ($code === 403) {
            return ': permission denied on the bucket.';
        }
        if ($code === 0) {
            return ': could not reach Google Cloud Storage.';
        }
        return '';
    }

    protected function _token()
    {
        if (self::$token !== NULL && self::$token_exp > time() + 60) {
            return self::$token;
        }
        $cache = rtrim(sys_get_temp_dir(), '/').'/ymo_gcs_token.json';
        if (is_file($cache)) {
            $c = json_decode((string) @file_get_contents($cache), TRUE);
            if (!empty($c['t']) && (int) $c['e'] > time() + 60) {
                self::$token = $c['t'];
                self::$token_exp = (int) $c['e'];
                return self::$token;
            }
        }
        $ch = curl_init(self::METADATA_TOKEN_URL);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => array('Metadata-Flavor: Google'),
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_PROXY          => '',
        ));
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $j = $code === 200 ? json_decode((string) $body, TRUE) : NULL;
        if (empty($j['access_token'])) {
            $this->last_error = 'Could not get a Google token from the server (metadata '.$code.').';
            log_message('error', '[gcs] metadata token failed: '.$code);
            return NULL;
        }
        self::$token = $j['access_token'];
        self::$token_exp = time() + (int) ($j['expires_in'] ?? 300);
        @file_put_contents($cache, json_encode(array('t' => self::$token, 'e' => self::$token_exp)), LOCK_EX);
        @chmod($cache, 0600);
        return self::$token;
    }

    /** @return array [http code, body] */
    protected function _request($method, $url, $body = NULL, array $headers = array())
    {
        $token = $this->_token();
        if ($token === NULL) {
            return array(0, '');
        }
        $headers[] = 'Authorization: Bearer '.$token;
        $ch = curl_init($url);
        $opt = array(
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 30,
        );
        if ($body !== NULL) {
            $opt[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opt);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 401) {
            // Token expired early: drop the cache once.
            self::$token = NULL;
            @unlink(rtrim(sys_get_temp_dir(), '/').'/ymo_gcs_token.json');
        }
        return array($code, $res === FALSE ? '' : $res);
    }
}
