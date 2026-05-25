<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lightweight settings page — wraps the `settings` key/value table.
 * Keys we expose here can be tuned without redeploying.
 */
class Settings extends Admin_Controller
{
    protected $editable = array(
        'reminder_months'         => array('label' => 'Service reminder window (months)', 'type' => 'int',  'min' => 1, 'max' => 24),
        'reminder_review_days'    => array('label' => 'Days after completion to allow review request', 'type' => 'int', 'min' => 0, 'max' => 90),
        'allow_login_otp'         => array('label' => 'Allow customers to login with OTP', 'type' => 'bool'),
        'require_email_otp'       => array('label' => 'Require email OTP during signup',   'type' => 'bool'),
        'booking_notify_email'    => array('label' => 'Booking notification email',        'type' => 'email'),
    );

    public function index()
    {
        $this->require_perm('settings.manage');
        if ($this->input->method() === 'post') {
            $this->_save();
            $this->flash('success', 'Settings saved.');
            redirect(admin_url('settings'));
        }
        $this->render('admin/settings', array(
            'title'    => 'Settings',
            'editable' => $this->editable,
            'values'   => $this->_load_values(),
        ));
    }

    protected function _load_values()
    {
        $rows = $this->db->get('settings')->result_array();
        $map  = array();
        foreach ($rows as $r) { $map[$r['setting_key']] = $r['setting_value']; }
        return $map;
    }

    protected function _save()
    {
        foreach ($this->editable as $key => $meta) {
            $value = $this->input->post($key);
            if ($meta['type'] === 'bool') {
                $value = $value ? '1' : '0';
            } elseif ($meta['type'] === 'int') {
                $value = (string) max(($meta['min'] ?? PHP_INT_MIN), min(($meta['max'] ?? PHP_INT_MAX), (int) $value));
            } elseif ($meta['type'] === 'email') {
                $value = filter_var(trim($value), FILTER_VALIDATE_EMAIL) ?: '';
            } else {
                $value = trim((string) $value);
            }
            $exists = (bool) $this->db->where('setting_key', $key)->count_all_results('settings');
            if ($exists) {
                $this->db->where('setting_key', $key)->update('settings', array('setting_value' => $value));
            } else {
                $this->db->insert('settings', array('setting_key' => $key, 'setting_value' => $value));
            }
        }
    }
}
