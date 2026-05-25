<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Canonicalize legacy /admin/* paths on the public booking hostname to the
 * admin subdomain (301) whenever YMO_ADMIN_APP_URL is configured.
 *
 * Runs at pre_controller before CI_Controller is instantiated — use only $_SERVER +
 * getenv() here (no get_instance() / helpers).
 */
function ymo_hook_redirect_legacy_admin_path()
{
    require_once dirname(__DIR__).'/config/ymo_host.php';

    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }

    $admin_origin = getenv('YMO_ADMIN_APP_URL');
    if ($admin_origin === FALSE || trim((string) $admin_origin) === '') {
        return;
    }

    if (ymo_is_admin_host_request()) {
        return;
    }

    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET') {
        return;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path === FALSE || $path === NULL || $path === '') {
        $path = '/';
    }
    $uri_full = strtolower(trim($path, '/'));

    // Strip trailing index.php/ when front controller is visible in REQUEST_URI.
    if (strpos($uri_full, 'index.php') === 0) {
        $uri_full = trim(substr($uri_full, strlen('index.php')), '/');
    }

    if ($uri_full !== 'admin' && strpos($uri_full, 'admin/') !== 0) {
        return;
    }

    $suffix = ($uri_full === 'admin')
        ? 'dashboard'
        : substr($uri_full, strlen('admin/'));

    $suffix = ($suffix !== '' && $suffix !== NULL)
        ? ltrim(str_replace('\\', '/', (string) $suffix), '/')
        : 'dashboard';

    $base   = rtrim(trim((string) $admin_origin), '/');
    $target = $base.'/'.preg_replace('#^/+#', '', $suffix);

    header('Location: '.$target, TRUE, 301);
    exit;
}
