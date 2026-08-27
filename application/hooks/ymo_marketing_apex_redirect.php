<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 301 apex (non-www) marketing host → configured www origin.
 * Safety net when traffic reaches PHP before nginx apex→www redirect.
 */
function ymo_hook_redirect_marketing_apex_to_www()
{
    require_once dirname(__DIR__).'/config/ymo_host.php';

    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }

    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }

    if (!function_exists('getenv')) {
        return;
    }

    $marketing = getenv('YMO_MARKETING_APP_URL');
    if ($marketing === FALSE || trim((string) $marketing) === '') {
        return;
    }

    $www_host = ymo_host_part_from_full_url($marketing);
    if ($www_host === '' || strpos($www_host, 'www.') !== 0) {
        return;
    }

    $incoming = ymo_request_host_normalized();
    $apex = substr($www_host, 4);
    if ($incoming === '' || !hash_equals($apex, $incoming)) {
        return;
    }

    if (ymo_is_admin_host_request()) {
        return;
    }

    $public = getenv('YMO_PUBLIC_APP_URL');
    if ($public === FALSE || trim((string) $public) === '') {
        $public = getenv('YMO_APP_URL');
    }
    $booking_host = ymo_host_part_from_full_url($public);
    if ($booking_host !== '' && hash_equals($booking_host, $incoming)) {
        return;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $target = rtrim(ymo_sanitize_external_app_url($marketing), '/').$uri;

    header('Location: '.$target, TRUE, 301);
    exit;
}
