<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lightweight host detection usable from config.php and routes.php BEFORE
 * the full CI bootstrap. Used to pick base_url per Host header and branch
 * admin-only routes onto short paths (/login …) on admin.yourmechaniconline.com.
 *
 * Trusted reverse-proxy behaviour is opt-in via YMO_TRUST_PROXY_HEADERS=1
 * because X-Forwarded-Host is spoofable if clients hit Apache directly.
 */

if (!function_exists('ymo_request_host_normalized')) {
    /**
     * Request hostname lowercase, IPv6 bracket stripped, sans port — or '' on CLI.
     *
     * @return string
     */
    function ymo_request_host_normalized()
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return '';
        }
        $raw = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';

        // Only honour forwarded host when terminating proxy is trusted explicitly.
        if (function_exists('getenv')) {
            $trust = getenv('YMO_TRUST_PROXY_HEADERS');
            if ($trust !== FALSE && trim((string) $trust) !== '' && strtolower(trim((string) $trust)) !== '0') {
                $xff = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? trim((string) $_SERVER['HTTP_X_FORWARDED_HOST']) : '';
                if ($xff !== '') {
                    $first = strtolower(trim(explode(',', $xff)[0]));
                    $first = preg_replace('#^\[|\]$#', '', $first); // [::1] brackets
                    if ($first !== '') {
                        $first = preg_replace('#:\d+$#', '', $first);
                        return $first;
                    }
                }
            }
        }

        $raw = preg_replace('#^\[|\]$#', '', $raw); // [::1]:8080 bracket form
        $raw = strtolower(preg_replace('#:\d+$#', '', trim($raw)));
        return $raw;
    }
}

if (!function_exists('ymo_host_part_from_full_url')) {
    /**
     * Extract hostname portion from https://some.host/path (lowercase).
     *
     * @param string|false $full_url getenv() URL
     * @return string ''
     */
    function ymo_host_part_from_full_url($full_url)
    {
        if ($full_url === FALSE || $full_url === NULL || trim((string) $full_url) === '') {
            return '';
        }
        $h = parse_url((string) $full_url, PHP_URL_HOST);
        return $h ? strtolower((string) $h) : '';
    }
}

if (!function_exists('ymo_is_admin_host_request')) {
    /**
     * Does the inbound HTTP hostname match YMO_ADMIN_APP_URL's host component?
     * When YMO_ADMIN_APP_URL is unset → always FALSE (path-based admin only).
     *
     * @return bool
     */
    function ymo_is_admin_host_request()
    {
        if (!function_exists('getenv')) {
            return FALSE;
        }
        $configured = getenv('YMO_ADMIN_APP_URL');
        if ($configured === FALSE || trim((string) $configured) === '') {
            return FALSE;
        }
        $expect = ymo_host_part_from_full_url($configured);
        if ($expect === '') {
            return FALSE;
        }
        $incoming = ymo_request_host_normalized();
        if ($incoming === '') {
            return FALSE;
        }
        return hash_equals($expect, $incoming);
    }
}

if (!function_exists('ymo_is_marketing_host_request')) {
    /**
     * Marketing site on www (+ apex before nginx 301). Never TRUE for booking/admin hosts.
     *
     * @return bool
     */
    function ymo_is_marketing_host_request()
    {
        if (ymo_is_admin_host_request()) {
            return FALSE;
        }
        $incoming = ymo_request_host_normalized();
        if ($incoming === '') {
            return FALSE;
        }
        if (!function_exists('getenv')) {
            return FALSE;
        }
        $public = getenv('YMO_PUBLIC_APP_URL');
        if ($public === FALSE || trim((string) $public) === '') {
            $public = getenv('YMO_APP_URL');
        }
        $booking_host = ymo_host_part_from_full_url($public);
        if ($booking_host !== '' && hash_equals($booking_host, $incoming)) {
            return FALSE;
        }
        $admin = getenv('YMO_ADMIN_APP_URL');
        $admin_host = ymo_host_part_from_full_url($admin);
        if ($admin_host !== '' && hash_equals($admin_host, $incoming)) {
            return FALSE;
        }
        $marketing = getenv('YMO_MARKETING_APP_URL');
        if ($marketing === FALSE || trim((string) $marketing) === '') {
            return FALSE;
        }
        $www_host = ymo_host_part_from_full_url($marketing);
        if ($www_host !== '' && hash_equals($www_host, $incoming)) {
            return TRUE;
        }
        if ($www_host !== '' && strpos($www_host, 'www.') === 0) {
            $apex = substr($www_host, 4);
            if ($apex !== '' && hash_equals($apex, $incoming)) {
                return TRUE;
            }
        }
        return FALSE;
    }
}

if (!function_exists('ymo_trust_proxy_headers')) {
    /** @return bool */
    function ymo_trust_proxy_headers()
    {
        if (!function_exists('getenv')) {
            return FALSE;
        }
        $trust = getenv('YMO_TRUST_PROXY_HEADERS');
        return $trust !== FALSE && trim((string) $trust) !== '' && strtolower(trim((string) $trust)) !== '0';
    }
}

if (!function_exists('ymo_request_scheme')) {
    /** @return string http|https */
    function ymo_request_scheme()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return 'https';
        }
        if (ymo_trust_proxy_headers() && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto = strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
            if ($proto === 'https') {
                return 'https';
            }
        }
        return 'http';
    }
}

if (!function_exists('ymo_configured_url_looks_local')) {
    /** @return bool */
    function ymo_configured_url_looks_local($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return TRUE;
        }
        if (preg_match('#:8080(/|$)#', $url)) {
            return TRUE;
        }
        $host = ymo_host_part_from_full_url($url);
        return in_array($host, array('localhost', '127.0.0.1'), TRUE);
    }
}

if (!function_exists('ymo_request_http_host')) {
    /**
     * Raw HTTP Host header including port (e.g. localhost:8080).
     *
     * @return string
     */
    function ymo_request_http_host()
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return '';
        }
        $raw = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
        $raw = preg_replace('#^\[|\]$#', '', $raw);
        return strtolower($raw);
    }
}

if (!function_exists('ymo_resolve_base_url')) {
    /**
     * Resolve CodeIgniter base_url for the current request.
     * Behind nginx + TLS, derive https://{host}/ from proxy headers so CSS/JS
     * are not blocked as mixed content when .env still has a local :8080 URL.
     *
     * When the browser Host differs from the configured URL host (common in local
     * dev: visit localhost:8080 while .env says booking.example.com:8080), use the
     * actual request origin so assets load from the same host.
     *
     * @param string|false $configured_url
     * @return string
     */
    function ymo_resolve_base_url($configured_url)
    {
        $configured_url = trim((string) $configured_url);

        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return $configured_url !== '' ? rtrim($configured_url, '/').'/' : '';
        }

        $http_host = ymo_request_http_host();
        $host = ymo_request_host_normalized();
        if ($host === '') {
            return $configured_url !== '' ? rtrim($configured_url, '/').'/' : '';
        }

        if (ymo_trust_proxy_headers()) {
            // Behind nginx: always use the inbound Host (www) so we never 301 to apex
            // while nginx 301s apex→www (ERR_TOO_MANY_REDIRECTS).
            return ymo_request_scheme().'://'.$http_host.'/';
        }

        $configured_host = ymo_host_part_from_full_url($configured_url);
        if ($configured_host !== '' && $http_host !== '' && !hash_equals($configured_host, $host)) {
            return ymo_request_scheme().'://'.$http_host.'/';
        }

        if (ymo_configured_url_looks_local($configured_url) && ymo_request_scheme() === 'https') {
            return 'https://'.$http_host.'/';
        }

        if ($configured_url !== '') {
            return rtrim($configured_url, '/').'/';
        }

        return ymo_request_scheme().'://'.$http_host.'/';
    }
}

if (!function_exists('ymo_sanitize_external_app_url')) {
    /**
     * Canonical HTTPS origin for cross-subdomain links (booking, marketing).
     * Strips local :8080 / http dev URLs when serving production pages.
     *
     * @param string|false $url
     * @return string Origin without trailing slash, e.g. https://booking.example.com
     */
    function ymo_sanitize_external_app_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $host = ymo_host_part_from_full_url($url);
        if ($host === '') {
            return rtrim($url, '/');
        }

        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            if (ymo_configured_url_looks_local($url)) {
                return 'https://'.$host;
            }
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production' && stripos($url, 'http://') === 0) {
                return 'https://'.$host;
            }
        }

        return rtrim($url, '/');
    }
}

if (!function_exists('ymo_env')) {
    /**
     * Read env var from getenv() or $_SERVER (Apache/Docker sometimes only expose the latter).
     *
     * @param string $key
     * @param string|false $default
     * @return string|false
     */
    function ymo_env($key, $default = FALSE)
    {
        if (function_exists('getenv')) {
            $v = getenv($key);
            if ($v !== FALSE && trim((string) $v) !== '') {
                return (string) $v;
            }
        }
        if (isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
            return trim((string) $_SERVER[$key]);
        }
        return $default;
    }
}

if (!function_exists('ymo_shared_cookie_domain_for_host')) {
    /**
     * Parent domain for cross-subdomain session cookies, or '' when not applicable.
     *
     * @param string $host
     * @return string e.g. .yourmechaniconline.com
     */
    function ymo_shared_cookie_domain_for_host($host)
    {
        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return '';
        }
        $suffix = 'yourmechaniconline.com';
        if ($host === $suffix || substr($host, -(strlen($suffix) + 1)) === '.'.$suffix) {
            return '.'.$suffix;
        }
        return '';
    }
}

if (!function_exists('ymo_resolve_cookie_domain')) {
    /**
     * Cookie Domain for shared sessions across booking / www / admin subdomains.
     *
     * @return string
     */
    function ymo_resolve_cookie_domain()
    {
        $configured = ymo_env('YMO_COOKIE_DOMAIN');
        if ($configured !== FALSE && trim((string) $configured) !== '') {
            return trim((string) $configured);
        }

        $from_host = ymo_shared_cookie_domain_for_host(ymo_request_host_normalized());
        if ($from_host !== '') {
            return $from_host;
        }

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            return '.yourmechaniconline.com';
        }

        return '';
    }
}

if (!function_exists('ymo_resolve_cookie_secure')) {
    /**
     * Secure cookies when served over HTTPS (including behind nginx TLS termination).
     *
     * @return bool
     */
    function ymo_resolve_cookie_secure()
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            return TRUE;
        }
        return ymo_request_scheme() === 'https';
    }
}

if (!function_exists('ymo_auth_cookie_names')) {
    /** @return string[] */
    function ymo_auth_cookie_names()
    {
        return array('ymo_session', 'ymo_csrf_cookie');
    }
}

if (!function_exists('ymo_reconcile_duplicate_auth_cookies')) {
    /**
     * When both host-only and Domain= cookies exist, PHP may surface the empty
     * host-only value. Prefer the last value from the raw Cookie header.
     */
    function ymo_reconcile_duplicate_auth_cookies()
    {
        $raw = isset($_SERVER['HTTP_COOKIE']) ? (string) $_SERVER['HTTP_COOKIE'] : '';
        if ($raw === '') {
            return;
        }

        foreach (ymo_auth_cookie_names() as $name) {
            $values = array();
            foreach (explode(';', $raw) as $part) {
                $part = trim($part);
                if (strpos($part, $name.'=') !== 0) {
                    continue;
                }
                $value = trim(substr($part, strlen($name) + 1));
                if ($value !== '') {
                    $values[] = $value;
                }
            }

            if (count($values) > 1) {
                $_COOKIE[$name] = end($values);
            } elseif (count($values) === 1) {
                $_COOKIE[$name] = $values[0];
            }
        }
    }
}

if (!function_exists('ymo_expire_host_only_auth_cookies')) {
    /**
     * Expire pre-migration host-only cookies so Domain= cookies win on the next request.
     *
     * @param string $shared_domain e.g. .yourmechaniconline.com
     */
    function ymo_expire_host_only_auth_cookies($shared_domain = '')
    {
        if ($shared_domain === '') {
            $shared_domain = ymo_resolve_cookie_domain();
        }
        if ($shared_domain === '' || headers_sent()) {
            return;
        }

        $secure = ymo_resolve_cookie_secure();
        $expires = time() - 3600;

        foreach (ymo_auth_cookie_names() as $name) {
            if (is_php('7.3')) {
                setcookie($name, '', array(
                    'expires'  => $expires,
                    'path'     => '/',
                    'secure'   => $secure,
                    'httponly' => TRUE,
                    'samesite' => $name === 'ymo_csrf_cookie' ? 'Strict' : 'Lax',
                ));
                if (!$secure) {
                    setcookie($name, '', array(
                        'expires'  => $expires,
                        'path'     => '/',
                        'secure'   => FALSE,
                        'httponly' => TRUE,
                        'samesite' => $name === 'ymo_csrf_cookie' ? 'Strict' : 'Lax',
                    ));
                }
            } else {
                setcookie($name, '', $expires, '/', '', $secure, TRUE);
                if (!$secure) {
                    setcookie($name, '', $expires, '/', '', FALSE, TRUE);
                }
            }
        }
    }
}
