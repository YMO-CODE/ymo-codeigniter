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
