<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal Web Push sender (RFC 8030 / 8291 aes128gcm / 8292 VAPID) using only
 * OpenSSL + cURL, so no Composer package is needed.
 *
 * Keys: generated once and kept in the settings table (key ymochat_vapid).
 * Deleting that row makes every phone turn alerts on again.
 */
class Web_push
{
    const SUBJECT = 'mailto:support@yourmechaniconline.com';
    const SPKI_P256_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    protected $keys = NULL;
    protected $jwt_cache = array();

    /** Public VAPID key (base64url, 65-byte uncompressed point) for pushManager.subscribe(). */
    public function public_key()
    {
        $k = $this->keys();
        return $k ? $k['public'] : '';
    }

    /**
     * Send one notification to many subscriptions in parallel.
     *
     * @param array  $subs  rows with id, endpoint, p256dh, auth
     * @param array  $data  JSON payload for the service worker
     * @return array id => HTTP status (0 = network error)
     */
    public function send_many(array $subs, array $data, $ttl = 300, $urgency = 'high')
    {
        $out = array();
        if (!$subs || !$this->keys() || !function_exists('curl_multi_init')) {
            return $out;
        }
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $mh = curl_multi_init();
        $handles = array();
        foreach ($subs as $s) {
            $req = $this->build_request($s, $payload, $ttl, $urgency);
            if (!$req) {
                $out[$s['id']] = 400;
                continue;
            }
            $ch = curl_init($s['endpoint']);
            curl_setopt_array($ch, array(
                CURLOPT_POST           => TRUE,
                CURLOPT_POSTFIELDS     => $req['body'],
                CURLOPT_HTTPHEADER     => $req['headers'],
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 5,
            ));
            curl_multi_add_handle($mh, $ch);
            $handles[(int) $s['id']] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 0.5);
            }
        } while ($running && $status === CURLM_OK);
        foreach ($handles as $id => $ch) {
            $out[$id] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($out[$id] >= 400) {
                log_message('info', 'Web push '.$out[$id].' for subscription #'.$id.': '.substr((string) curl_multi_getcontent($ch), 0, 200));
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return $out;
    }

    /**
     * Encrypted body + headers for one subscription (public for tests).
     *
     * @return array{body:string,headers:array}|null
     */
    public function build_request(array $sub, $payload, $ttl = 300, $urgency = 'high')
    {
        $ua_pub = self::b64url_decode($sub['p256dh']);
        $auth   = self::b64url_decode($sub['auth']);
        $parts  = parse_url($sub['endpoint']);
        if (strlen($ua_pub) !== 65 || $ua_pub[0] !== "\x04" || strlen($auth) < 16 || empty($parts['host'])
            || (isset($parts['scheme']) && $parts['scheme'] !== 'https')) {
            return NULL;
        }
        $body = $this->encrypt($payload, $ua_pub, $auth);
        if ($body === NULL) {
            return NULL;
        }
        $aud = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $jwt = $this->vapid_jwt($aud);
        if ($jwt === NULL) {
            return NULL;
        }
        return array(
            'body' => $body,
            'headers' => array(
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: '.(int) $ttl,
                'Urgency: '.$urgency,
                'Authorization: vapid t='.$jwt.', k='.$this->public_key(),
            ),
        );
    }

    // --- RFC 8291 ---------------------------------------------------------------

    protected function encrypt($plaintext, $ua_pub, $auth)
    {
        $eph = openssl_pkey_new(array('curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC));
        if (!$eph) {
            return NULL;
        }
        $d = openssl_pkey_get_details($eph);
        $as_pub = "\x04".str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT).str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $peer = openssl_pkey_get_public(self::raw_to_pem($ua_pub));
        if (!$peer) {
            return NULL;
        }
        $secret = openssl_pkey_derive($peer, $eph, 32);
        if ($secret === FALSE || strlen($secret) !== 32) {
            return NULL;
        }
        $ikm   = hash_hkdf('sha256', $secret, 32, "WebPush: info\0".$ua_pub.$as_pub, $auth);
        $salt  = random_bytes(16);
        $cek   = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
        $tag = '';
        $ct = openssl_encrypt($plaintext."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($ct === FALSE) {
            return NULL;
        }
        return $salt.pack('N', 4096).chr(65).$as_pub.$ct.$tag;
    }

    // --- RFC 8292 ---------------------------------------------------------------

    protected function vapid_jwt($aud)
    {
        if (isset($this->jwt_cache[$aud]) && $this->jwt_cache[$aud]['exp'] > time() + 600) {
            return $this->jwt_cache[$aud]['jwt'];
        }
        $k = $this->keys();
        if (!$k) {
            return NULL;
        }
        $exp = time() + 12 * 3600;
        $head = self::b64url_encode(json_encode(array('typ' => 'JWT', 'alg' => 'ES256')));
        $claims = self::b64url_encode(json_encode(array('aud' => $aud, 'exp' => $exp, 'sub' => self::SUBJECT)));
        $der = '';
        if (!openssl_sign($head.'.'.$claims, $der, $k['private_pem'], OPENSSL_ALGO_SHA256)) {
            return NULL;
        }
        $raw = self::der_sig_to_raw($der);
        if ($raw === NULL) {
            return NULL;
        }
        $jwt = $head.'.'.$claims.'.'.self::b64url_encode($raw);
        $this->jwt_cache[$aud] = array('jwt' => $jwt, 'exp' => $exp);
        return $jwt;
    }

    /** Why keys() failed, for the CRM screen. */
    public $error = '';

    protected function keys()
    {
        if ($this->keys !== NULL) {
            return $this->keys;
        }
        $this->keys = FALSE;
        $CI = &get_instance();
        $db = isset($CI->db) && $CI->db->table_exists('settings') ? $CI->db : NULL;

        // 1. Database (settings table) – survives deploys, no folder permissions needed.
        if ($db) {
            $row = $db->select('setting_value')->get_where('settings', array('setting_key' => 'ymochat_vapid'), 1)->row_array();
            $k = $row ? json_decode((string) $row['setting_value'], TRUE) : NULL;
            if (is_array($k) && !empty($k['private_pem']) && !empty($k['public'])) {
                return $this->keys = $k;
            }
        }
        // 2. Older file location (or YMO_VAPID_FILE).
        $file = self::key_file();
        $k = is_file($file) ? json_decode((string) @file_get_contents($file), TRUE) : NULL;
        if (!(is_array($k) && !empty($k['private_pem']) && !empty($k['public']))) {
            // 3. First use: create a key pair.
            $pk = @openssl_pkey_new(array('curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC));
            if (!$pk) {
                $this->error = 'openssl: '.(string) openssl_error_string();
                log_message('error', 'Web push: cannot create VAPID keys: '.$this->error);
                return FALSE;
            }
            $pem = '';
            if (!openssl_pkey_export($pk, $pem)) {
                $this->error = 'openssl export: '.(string) openssl_error_string();
                log_message('error', 'Web push: cannot export VAPID key: '.$this->error);
                return FALSE;
            }
            $d = openssl_pkey_get_details($pk);
            $pub = "\x04".str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT).str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
            $k = array('private_pem' => $pem, 'public' => self::b64url_encode($pub), 'created_at' => date('c'));
        }
        if ($db) {
            // INSERT IGNORE + read back: if two requests race, both end up with the same keys.
            $db->query('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)', array('ymochat_vapid', json_encode($k)));
            $row = $db->select('setting_value')->get_where('settings', array('setting_key' => 'ymochat_vapid'), 1)->row_array();
            $saved = $row ? json_decode((string) $row['setting_value'], TRUE) : NULL;
            if (is_array($saved) && !empty($saved['public'])) {
                return $this->keys = $saved;
            }
        } elseif (@file_put_contents($file, json_encode($k), LOCK_EX) !== FALSE) {
            @chmod($file, 0600);
            return $this->keys = $k;
        }
        $this->error = 'could not save keys (settings table and '.$file.' not writable)';
        log_message('error', 'Web push: '.$this->error);
        return FALSE;
    }

    public static function key_file()
    {
        $env = getenv('YMO_VAPID_FILE');
        if ($env !== FALSE && trim((string) $env) !== '') {
            return trim((string) $env);
        }
        return rtrim(FCPATH, '/\\').'/../storage/ymochat_vapid.json';
    }

    // --- helpers ------------------------------------------------------------------

    public static function b64url_encode($s)
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public static function b64url_decode($s)
    {
        $s = strtr((string) $s, '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad) {
            $s .= str_repeat('=', 4 - $pad);
        }
        return (string) base64_decode($s, TRUE);
    }

    protected static function raw_to_pem($raw)
    {
        $der = hex2bin(self::SPKI_P256_PREFIX).$raw;
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    /** ECDSA DER (SEQUENCE{INTEGER r, INTEGER s}) → 64-byte r||s. */
    protected static function der_sig_to_raw($der)
    {
        $pos = 0;
        if (ord($der[$pos++]) !== 0x30) {
            return NULL;
        }
        $len = ord($der[$pos++]);
        if ($len & 0x80) {
            $pos += $len & 0x7f;
        }
        $out = '';
        for ($i = 0; $i < 2; $i++) {
            if (ord($der[$pos++]) !== 0x02) {
                return NULL;
            }
            $l = ord($der[$pos++]);
            $int = substr($der, $pos, $l);
            $pos += $l;
            $int = ltrim($int, "\0");
            if (strlen($int) > 32) {
                return NULL;
            }
            $out .= str_pad($int, 32, "\0", STR_PAD_LEFT);
        }
        return $out;
    }
}
