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
 *   $this->sms_gateway->build_msg91_payload($template_key, $mobile, $vars);
 *
 * MSG91 variable names in templates are often auto-named var1, var2 — see
 * `sms_msg91_var_keys` in config/ymo.php (override via YMO_MSG91_VARKEYS_*).
 */
class Sms_gateway
{
    /** @var CI_Controller */
    protected $CI;
    /** @var string|null */
    protected $last_error = NULL;

    /** @var array<string, string[]> Semantic app keys sent per template (order matters). */
    protected static $var_order = array(
        'otp'                => array('otp'),
        'booking_confirmed'  => array('name', 'ref'),
        'booking_status'     => array('ref', 'status'),
        'service_reminder'   => array('name', 'vehicle'),
        'review_request'     => array('name', 'ref'),
        'invoice_sent'       => array('ref', 'total'),
        'referral_credit'    => array('amount', 'ref'),
        'crm_campaign'       => array('msg'),
    );

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
        return $this->_dispatch($mobile, $template_key, $templates[$template_key], $vars);
    }

    public function send_otp($mobile, $code)
    {
        return $this->send_template($mobile, 'otp', array('otp' => $code));
    }

    /**
     * Build MSG91 Flow API payload (for debugging via cli/sms preview).
     *
     * @return array<string, mixed>
     */
    public function build_msg91_payload($template_key, $mobile, array $vars)
    {
        $templates = (array) $this->CI->config->item('sms_templates');
        $flow_id = isset($templates[$template_key]) ? $templates[$template_key] : '';
        $mapped = $this->_map_msg91_vars($template_key, $vars);

        $payload = array(
            'flow_id'    => $flow_id,
            'recipients' => array(array_merge(
                array('mobiles' => $this->_normalize_mobile($mobile)),
                $mapped
            )),
        );
        $sender = trim((string) $this->CI->config->item('sms_msg91_sender'));
        if ($sender !== '') {
            $payload['sender'] = $sender;
        }
        $route = trim((string) $this->CI->config->item('sms_msg91_route'));
        if ($route !== '') {
            $payload['route'] = $route;
        }
        return $payload;
    }

    // --- driver dispatch ----------------------------------------------------

    protected function _dispatch($mobile, $template_key, $template_id, array $vars)
    {
        $driver = $this->CI->config->item('sms_driver') ?: 'msg91';
        $method = '_send_'.preg_replace('/[^a-z0-9_]/', '', strtolower($driver));
        if (!method_exists($this, $method)) {
            $this->last_error = "Unknown SMS driver: $driver";
            return FALSE;
        }
        return $this->$method($this->_normalize_mobile($mobile), $template_key, $template_id, $vars);
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
     * Map semantic app vars (ref, status, …) to MSG91 API keys (var1, var2, …).
     *
     * @return array<string, string>
     */
    protected function _map_msg91_vars($template_key, array $vars)
    {
        $order = isset(self::$var_order[$template_key])
            ? self::$var_order[$template_key]
            : array_keys($vars);
        $keys = $this->_msg91_api_keys($template_key, count($order));

        $out = array();
        foreach ($order as $i => $sem_key) {
            if (!array_key_exists($sem_key, $vars)) {
                continue;
            }
            $api_key = isset($keys[$i]) ? $keys[$i] : $sem_key;
            $out[$api_key] = (string) $vars[$sem_key];
        }
        return $out;
    }

    /** @return string[] */
    protected function _msg91_api_keys($template_key, $count)
    {
        $custom = (array) $this->CI->config->item('sms_msg91_var_keys');
        if (!empty($custom[$template_key]) && is_array($custom[$template_key])) {
            return array_values($custom[$template_key]);
        }
        if ($template_key === 'otp') {
            return array('otp');
        }
        $keys = array();
        for ($i = 1; $i <= $count; $i++) {
            $keys[] = 'var'.$i;
        }
        return $keys;
    }

    /**
     * MSG91 Flow API (https://docs.msg91.com/flow-api).
     */
    protected function _send_msg91($mobile, $template_key, $template_id, array $vars)
    {
        $authkey = $this->CI->config->item('sms_msg91_authkey');
        if (empty($authkey)) {
            $this->last_error = 'MSG91 authkey not set.';
            log_message('error', '[sms] '.$this->last_error);
            if (ENVIRONMENT !== 'production') {
                log_message('debug', sprintf('[sms-stub] to=%s template=%s vars=%s',
                    $mobile, $template_id, json_encode($vars)));
                return TRUE;
            }
            return FALSE;
        }

        $mapped = $this->_map_msg91_vars($template_key, $vars);
        $payload = array(
            'flow_id'    => $template_id,
            'recipients' => array(array_merge(array('mobiles' => $mobile), $mapped)),
        );
        $sender = trim((string) $this->CI->config->item('sms_msg91_sender'));
        if ($sender !== '') {
            $payload['sender'] = $sender;
        }
        $route = trim((string) $this->CI->config->item('sms_msg91_route'));
        if ($route !== '') {
            $payload['route'] = $route;
        }

        log_message('info', '[sms] msg91 '.$template_key.' payload='.json_encode($payload));

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
