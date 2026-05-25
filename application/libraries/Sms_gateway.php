<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sms_gateway — thin SMS abstraction.
 *
 * Default driver is MSG91 (DLT-flow API). To swap providers, override
 * `sms_driver` in config/ymo.php and add a new `_send_xyz()` method.
 *
 * Public API:
 *   $this->sms_gateway->send_template($mobile, $template_key, $vars = []);
 *   $this->sms_gateway->send_otp($mobile, $code);
 *
 * All methods return a TRUE/FALSE-style result; details on failure are
 * available via `last_error()`.
 */
class Sms_gateway
{
    /** @var CI_Controller */
    protected $CI;
    /** @var string|null */
    protected $last_error = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function last_error()
    {
        return $this->last_error;
    }

    /**
     * Send a DLT-registered template.
     *
     * @param string $mobile         10-digit Indian mobile (with or without +91).
     * @param string $template_key   Key in config('sms_templates'), e.g. 'booking_confirmed'.
     * @param array  $vars           Template variables for substitution.
     */
    public function send_template($mobile, $template_key, array $vars = array())
    {
        $templates = (array) $this->CI->config->item('sms_templates');
        if (empty($templates[$template_key])) {
            $this->last_error = "SMS template '$template_key' is not configured.";
            log_message('error', '[sms] '.$this->last_error);
            return FALSE;
        }
        return $this->_dispatch($mobile, $templates[$template_key], $vars);
    }

    public function send_otp($mobile, $code)
    {
        return $this->send_template($mobile, 'otp', array('otp' => $code));
    }

    // --- driver dispatch ----------------------------------------------------

    protected function _dispatch($mobile, $template_id, array $vars)
    {
        $driver = $this->CI->config->item('sms_driver') ?: 'msg91';
        $method = '_send_'.preg_replace('/[^a-z0-9_]/', '', strtolower($driver));
        if (!method_exists($this, $method)) {
            $this->last_error = "Unknown SMS driver: $driver";
            return FALSE;
        }
        return $this->$method($this->_normalize_mobile($mobile), $template_id, $vars);
    }

    protected function _normalize_mobile($mobile)
    {
        $digits = preg_replace('/\D+/', '', $mobile);
        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }
        return $digits;
    }

    /**
     * MSG91 Flow API (https://docs.msg91.com/flow-api).
     */
    protected function _send_msg91($mobile, $template_id, array $vars)
    {
        $authkey = $this->CI->config->item('sms_msg91_authkey');
        if (empty($authkey)) {
            $this->last_error = 'MSG91 authkey not set.';
            log_message('error', '[sms] '.$this->last_error);
            // In development we degrade to logging so flows remain testable.
            if (ENVIRONMENT !== 'production') {
                log_message('debug', sprintf('[sms-stub] to=%s template=%s vars=%s',
                    $mobile, $template_id, json_encode($vars)));
                return TRUE;
            }
            return FALSE;
        }

        $payload = array_merge(array(
            'template_id' => $template_id,
            'mobiles'     => $mobile,
        ), $vars);

        $ch = curl_init('https://control.msg91.com/api/v5/flow/');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array(
                'authkey: '.$authkey,
                'Content-Type: application/json',
                'Accept: application/json',
            ),
            CURLOPT_TIMEOUT        => 10,
        ));

        $response = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || $http >= 400) {
            $this->last_error = "MSG91 HTTP $http: ".($err ?: $response);
            log_message('error', '[sms] '.$this->last_error);
            return FALSE;
        }

        $decoded = json_decode($response, TRUE);
        if (!is_array($decoded) || (isset($decoded['type']) && $decoded['type'] !== 'success')) {
            $this->last_error = 'MSG91 unexpected response: '.$response;
            log_message('error', '[sms] '.$this->last_error);
            return FALSE;
        }
        return TRUE;
    }
}
