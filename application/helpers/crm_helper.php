<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('crm_refresh_permissions')) {
    /**
     * Load CRM permission keys into session admin payload (once per session flag).
     */
    function crm_refresh_permissions()
    {
        $ci = &get_instance();
        if (!$ci->session) {
            return;
        }
        $admin = $ci->session->userdata('admin');
        if (empty($admin['id'])) {
            return;
        }
        if (!empty($admin['crm_perms_loaded'])) {
            return;
        }
        if (!isset($ci->crm_rbac_model)) {
            $ci->load->model('crm_rbac_model');
        }
        $keys = $ci->crm_rbac_model->permission_keys_for_admin($admin);
        $admin['crm_permissions'] = $keys;
        $admin['crm_perms_loaded'] = 1;
        $slug = $ci->crm_rbac_model->crm_role_slug_for_admin($admin);
        if ($slug) {
            $admin['crm_role_slug'] = $slug;
        }
        $ci->session->set_userdata('admin', $admin);
    }
}

if (!function_exists('crm_can')) {
    /**
     * @param string $perm_key e.g. leads.view
     */
    function crm_can($perm_key)
    {
        $ci = &get_instance();
        if (!$ci->session) {
            return FALSE;
        }
        $admin = $ci->session->userdata('admin');
        if (empty($admin['id'])) {
            return FALSE;
        }
        if (empty($admin['crm_perms_loaded'])) {
            crm_refresh_permissions();
            $admin = $ci->session->userdata('admin');
        }
        $perms = isset($admin['crm_permissions']) ? (array) $admin['crm_permissions'] : array();
        if (in_array('*', $perms, TRUE)) {
            return TRUE;
        }
        return in_array($perm_key, $perms, TRUE);
    }
}

if (!function_exists('crm_slug')) {
    /** @param string $str */
    function crm_slug($str)
    {
        $s = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $str), '-'));
        return $s !== '' ? $s : 'tag';
    }
}

if (!function_exists('crm_verify_webhook_hmac')) {
    /**
     * Verify X-CRM-Signature header (sha256 HMAC of raw body).
     */
    function crm_verify_webhook_hmac($raw_body, $signature_header)
    {
        $ci = &get_instance();
        $secret = $ci->config->item('crm_webhook_hmac_secret');
        if ($secret === '' || $secret === NULL) {
            return ENVIRONMENT !== 'production';
        }
        if (!$signature_header) {
            return FALSE;
        }
        $expected = hash_hmac('sha256', $raw_body, $secret);
        $given = preg_replace('/^sha256=/', '', trim($signature_header));
        return hash_equals($expected, $given);
    }
}

if (!function_exists('crm_csv_download')) {
    /**
     * Stream a CSV download and exit.
     *
     * @param string   $filename
     * @param string[] $headers
     * @param array[]  $rows
     */
    function crm_csv_download($filename, array $headers, array $rows)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
