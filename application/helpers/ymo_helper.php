<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * YMO project helpers — small, dependency-free functions usable from any
 * controller, library, model, or view.
 *
 * Autoloaded via application/config/autoload.php so they are available
 * everywhere (web requests, CLI cron, etc.) without an explicit `load->helper`.
 */

if (!function_exists('ymo_user_is_verified')) {
    /**
     * Is this user "verified enough" to proceed past the OTP gate?
     *
     * In production: their `mobile_verified_at` column must be set.
     * In development with `dev_auto_verify_otp` on: always TRUE — lets you
     * test bookings without bouncing through SMS/OTP. The dev flag is
     * hard-disabled in production, so this can never leak.
     *
     * @param array|null $user
     * @return bool
     */
    function ymo_user_is_verified($user)
    {
        if (ENVIRONMENT !== 'production') {
            $ci = &get_instance();
            if ($ci->config->item('dev_auto_verify_otp')) {
                return TRUE;
            }
        }
        return !empty($user['mobile_verified_at']);
    }
}

if (!function_exists('ymo_load_db_settings')) {
    /**
     * Overlay runtime values from the `settings` table on top of CI's config.
     *
     * The admin Settings page persists key/value rows to a DB table; without
     * this loader those rows are never read back, so `$this->config->item('foo')`
     * keeps returning the static default from `application/config/ymo.php`.
     * Call this at the top of every entry path (web + CLI) and the saved
     * values transparently win.
     *
     * Idempotent: querying the DB once per request is fine, and a static
     * guard prevents repeats if a controller chain calls this twice.
     */
    function ymo_load_db_settings()
    {
        static $loaded = FALSE;
        if ($loaded) {
            return;
        }
        $loaded = TRUE;
        $ci = &get_instance();
        if (!isset($ci->db)) {
            return;
        }
        try {
            if (!$ci->db->table_exists('settings')) {
                return;
            }
            $rows = $ci->db->get('settings')->result_array();
            foreach ($rows as $r) {
                $key = $r['setting_key'];
                $val = $r['setting_value'];
                if ($val === '' || $val === NULL) {
                    continue;
                }
                if ($val === '0' || $val === '1' || ctype_digit((string) $val)) {
                    $val = (int) $val;
                }
                $ci->config->set_item($key, $val);
            }
        } catch (Exception $e) {
            log_message('error', 'ymo_load_db_settings: '.$e->getMessage());
        }
    }
}

if (!function_exists('ymo_is_admin_host')) {
    /**
     * Is the current HTTP request served on the configured admin hostname?
     * Always FALSE when YMO_ADMIN_APP_URL is unset (single-host local dev).
     *
     * @return bool
     */
    function ymo_is_admin_host()
    {
        return function_exists('ymo_is_admin_host_request') && ymo_is_admin_host_request();
    }
}

if (!function_exists('admin_url')) {
    /**
     * Build a URL into the admin panel. On the admin subdomain it uses
     * site_url(short_path); on the booking host it uses YMO_ADMIN_APP_URL
     * (absolute). When YMO_ADMIN_APP_URL is unset, falls back to /admin/*
     * on the current origin (local dev).
     *
     * @param string $uri Path after the admin origin, e.g. "bookings/3" or "login"
     * @return string
     */
    function admin_url($uri = '')
    {
        $uri = preg_replace('#^\s+|\s+$#', '', (string) $uri);
        $uri = ltrim($uri, '/');

        $admin_base = getenv('YMO_ADMIN_APP_URL');
        if ($admin_base === FALSE || trim((string) $admin_base) === '') {
            return $uri === '' ? site_url('admin') : site_url('admin/'.$uri);
        }

        if (function_exists('ymo_is_admin_host_request') && ymo_is_admin_host_request()) {
            if ($uri === '') {
                return site_url('dashboard');
            }

            return site_url($uri);
        }

        return rtrim(trim((string) $admin_base), '/').'/'.($uri === '' ? 'dashboard' : $uri);
    }
}

if (!function_exists('ymo_admin_nav_path_normalized')) {
    /**
     * Normalized path for admin sidebar "active" highlighting: strips an
     * optional leading "admin/" when someone still hits legacy paths on
     * the admin hostname.
     *
     * @return string
     */
    function ymo_admin_nav_path_normalized()
    {
        $ci = &get_instance();
        $path = strtolower(trim((string) $ci->uri->uri_string(), '/'));
        if ($path === 'admin' || strpos($path, 'admin/') === 0) {
            $path = preg_replace('#^admin/?#', '', $path);
        }

        return trim($path, '/');
    }
}

if (!function_exists('admin_nav_active')) {
    /**
     * True when the current URI is this admin section (handles detail pages).
     *
     * @param string $section e.g. dashboard, bookings, customers, packages, settings
     * @return bool
     */
    function admin_nav_active($section)
    {
        $section = strtolower(trim((string) $section, '/'));
        $norm    = ymo_admin_nav_path_normalized();

        if ($section === 'dashboard') {
            return $norm === '' || $norm === 'dashboard';
        }

        if ($section === 'customers') {
            return ($norm === 'customers' || strpos($norm, 'customers/') === 0)
                || ($norm === 'contacts' || strpos($norm, 'contacts/') === 0);
        }

        if ($section === 'online-accounts') {
            return ($norm === 'online-accounts' || strpos($norm, 'online-accounts/') === 0);
        }

        return ($norm === $section) || (strpos($norm, $section.'/') === 0);
    }
}

if (!function_exists('ymo_deploy_session_epoch_path')) {
    /** @return string */
    function ymo_deploy_session_epoch_path()
    {
        return rtrim(FCPATH, '/\\').'/../storage/.session_epoch';
    }
}

if (!function_exists('ymo_deploy_session_epoch')) {
    /**
     * Current deploy epoch (git commit). NULL if never set (local dev).
     *
     * @return string|null
     */
    function ymo_deploy_session_epoch()
    {
        $path = ymo_deploy_session_epoch_path();
        if (!is_readable($path)) {
            return NULL;
        }
        $v = trim((string) file_get_contents($path));
        return $v !== '' ? $v : NULL;
    }
}

if (!function_exists('ymo_stamp_deploy_session')) {
    /** Tag the current session with the active deploy epoch (call after login). */
    function ymo_stamp_deploy_session()
    {
        $epoch = ymo_deploy_session_epoch();
        if ($epoch === NULL) {
            return;
        }
        $ci = &get_instance();
        if ($ci->session) {
            $ci->session->set_userdata('_deploy_epoch', $epoch);
        }
    }
}

if (!function_exists('ymo_enforce_deploy_session')) {
    /**
     * Sign out users whose session predates the latest deploy.
     * Skips unauthenticated requests and auth pages (login/signup).
     */
    function ymo_enforce_deploy_session()
    {
        $epoch = ymo_deploy_session_epoch();
        if ($epoch === NULL) {
            return;
        }

        $ci = &get_instance();
        if (!$ci->session) {
            return;
        }

        $uri = strtolower(trim((string) $ci->uri->uri_string(), '/'));
        $skip = array('login', 'logout', 'signup', 'signup/verify', 'admin/login', 'admin/logout');
        if (in_array($uri, $skip, TRUE) || strpos($uri, 'signup/') === 0) {
            return;
        }

        $has_user = !empty($ci->session->userdata('user'));
        $has_admin = !empty($ci->session->userdata('admin'));
        if (!$has_user && !$has_admin) {
            return;
        }

        if ($ci->session->userdata('_deploy_epoch') === $epoch) {
            return;
        }

        $was_admin = $has_admin;
        $ci->session->unset_userdata(array('user', 'admin', '_deploy_epoch', 'crm_import_file'));
        $ci->session->set_flashdata('info', 'You have been signed out because the app was updated. Please sign in again.');

        if ($was_admin || (function_exists('ymo_is_admin_host') && ymo_is_admin_host())) {
            redirect(admin_url('login'));
        }
        redirect(site_url('login'));
    }
}
