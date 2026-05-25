<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Issues and verifies one-time codes for signup, login and password reset.
 *
 *   $r = $this->otp_service->issue('sms', '+919876543210', 'signup');
 *   if ($r['ok']) { // code sent }
 *
 *   $r = $this->otp_service->verify('sms', '+919876543210', 'signup', $code_from_user);
 *   if ($r['ok']) { // code accepted, mark user verified }
 *
 * Storage is in `otp_codes` (hashed). Rate limiting is enforced both per
 * destination and per IP using the same table.
 */
class Otp_service
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('otp_model');
        $this->CI->load->library('sms_gateway');
        $this->CI->load->library('mailer');
    }

    /**
     * Generate, store and dispatch an OTP code.
     *
     * @return array ['ok' => bool, 'reason' => string|null, 'cooldown' => int|null]
     */
    public function issue($channel, $destination, $purpose)
    {
        $destination = $this->_normalize_destination($channel, $destination);
        if (!$this->_is_valid_destination($channel, $destination)) {
            return $this->_fail('Invalid destination.');
        }

        // Rate limit: hourly per destination
        $perDest = $channel === 'sms'
            ? (int) $this->CI->config->item('otp_mobile_hourly_limit')
            : 10;
        $count = $this->CI->otp_model->count_recent(array(
            'channel'     => $channel,
            'destination' => $destination,
            'purpose'     => $purpose,
        ), 3600);
        if ($count >= $perDest) {
            return $this->_fail('Too many OTP requests for this '.($channel === 'sms' ? 'mobile' : 'email').'. Try again in an hour.');
        }

        // Rate limit: hourly per IP
        $ip = $this->CI->input->ip_address();
        $perIp = (int) $this->CI->config->item('otp_ip_hourly_limit');
        $ipCount = $this->CI->otp_model->count_recent(array('ip_address' => $ip), 3600);
        if ($ipCount >= $perIp) {
            return $this->_fail('Too many OTP requests from this network. Please slow down.');
        }

        // Resend cooldown — find latest unused code
        $cooldown = (int) $this->CI->config->item('otp_resend_cooldown');
        $existing = $this->CI->otp_model->find_active($channel, $destination, $purpose);
        if ($existing) {
            $age = time() - strtotime($existing['created_at']);
            if ($age < $cooldown) {
                return $this->_fail('Please wait before requesting another code.', $cooldown - $age);
            }
        }

        $code   = $this->_generate_code();
        $ttl    = (int) $this->CI->config->item('otp_ttl_seconds');

        $this->CI->otp_model->create(array(
            'channel'     => $channel,
            'destination' => $destination,
            'purpose'     => $purpose,
            'code_hash'   => password_hash($code, PASSWORD_BCRYPT),
            'expires_at'  => date('Y-m-d H:i:s', time() + $ttl),
            'ip_address'  => $ip,
        ));

        $sent = $this->_dispatch($channel, $destination, $code, $purpose);
        if (!$sent) {
            return $this->_fail('Could not send the verification code right now. Please try again.');
        }

        return array('ok' => TRUE, 'reason' => NULL, 'cooldown' => $cooldown);
    }

    /**
     * Verify a code submitted by the user.
     *
     * @return array ['ok' => bool, 'reason' => string|null]
     */
    public function verify($channel, $destination, $purpose, $code)
    {
        $destination = $this->_normalize_destination($channel, $destination);
        $code = trim((string) $code);
        if ($code === '' || !ctype_digit($code)) {
            return $this->_fail('Enter the numeric code from the message.');
        }

        // Dev escape hatch — '000000' always passes outside production.
        if (ENVIRONMENT !== 'production'
            && $this->CI->config->item('dev_auto_verify_otp')
            && $code === '000000') {
            return array('ok' => TRUE, 'reason' => NULL);
        }

        $row = $this->CI->otp_model->find_active($channel, $destination, $purpose);
        if (!$row) {
            return $this->_fail('No active code found. Request a new one.');
        }

        $maxAttempts = (int) $this->CI->config->item('otp_max_attempts');
        if ((int) $row['attempts'] >= $maxAttempts) {
            return $this->_fail('Too many incorrect attempts. Request a new code.');
        }

        $this->CI->otp_model->increment_attempt($row['id']);

        if (!password_verify($code, $row['code_hash'])) {
            return $this->_fail('That code does not match. Please re-check the message.');
        }

        $this->CI->otp_model->mark_used($row['id']);
        return array('ok' => TRUE, 'reason' => NULL);
    }

    // --- internals ----------------------------------------------------------

    protected function _generate_code()
    {
        $len = max(4, min(8, (int) $this->CI->config->item('otp_length')));
        $max = (int) str_repeat('9', $len);
        $min = (int) ('1'.str_repeat('0', $len - 1));
        $n   = function_exists('random_int') ? random_int($min, $max) : mt_rand($min, $max);
        return (string) $n;
    }

    protected function _normalize_destination($channel, $destination)
    {
        if ($channel === 'sms') {
            $digits = preg_replace('/\D+/', '', $destination);
            if (strlen($digits) === 10) {
                $digits = '91'.$digits;
            }
            return $digits;
        }
        return strtolower(trim($destination));
    }

    protected function _is_valid_destination($channel, $destination)
    {
        if ($channel === 'sms') {
            return (bool) preg_match('/^91\d{10}$/', $destination);
        }
        return filter_var($destination, FILTER_VALIDATE_EMAIL) !== FALSE;
    }

    protected function _dispatch($channel, $destination, $code, $purpose)
    {
        $brand = $this->CI->config->item('ymo_brand_name');
        if ($channel === 'sms') {
            return $this->CI->sms_gateway->send_otp($destination, $code);
        }
        $subject = $brand.' verification code';
        $html = $this->CI->load->view('emails/otp', array(
            'code'    => $code,
            'purpose' => $purpose,
            'minutes' => max(1, (int) round(((int) $this->CI->config->item('otp_ttl_seconds')) / 60)),
        ), TRUE);
        return $this->CI->mailer->send($destination, $subject, $html);
    }

    protected function _fail($reason, $cooldown = NULL)
    {
        return array('ok' => FALSE, 'reason' => $reason, 'cooldown' => $cooldown);
    }
}
