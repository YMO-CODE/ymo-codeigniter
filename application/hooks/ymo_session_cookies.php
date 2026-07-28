<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reconcile duplicate auth cookies and expire legacy host-only variants
 * before CodeIgniter reads $_COOKIE for the session driver.
 */
function ymo_hook_reconcile_session_cookies()
{
    if (defined('STDIN') || PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }

    require_once APPPATH.'config/ymo_host.php';

    $domain = ymo_resolve_cookie_domain();
    if ($domain === '') {
        return;
    }

    ymo_reconcile_duplicate_auth_cookies();
    ymo_expire_host_only_auth_cookies($domain);
}
