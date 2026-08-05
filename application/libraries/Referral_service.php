<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer referral programme - codes, validation, completion credits, notifications.
 */
class Referral_service
{
    /** @var CI_Controller */
    protected $CI;

    /** @var string|null */
    protected $last_error = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model(array('user_model', 'referral_model', 'booking_model'));
    }

    public function last_error()
    {
        return $this->last_error;
    }

    public function is_enabled()
    {
        return (bool) $this->CI->config->item('referral_enabled');
    }

    /**
     * Ensure the user has a shareable referral code; create if missing.
     *
     * @return string|null Code e.g. YMO-A3K7M2
     */
    public function ensure_user_code($user_id)
    {
        if (!$this->is_enabled()) {
            return NULL;
        }
        if (!$this->_table_ready()) {
            return NULL;
        }

        $user = $this->CI->user_model->find((int) $user_id);
        if (!$user) {
            return NULL;
        }
        if (!empty($user['referral_code'])) {
            return (string) $user['referral_code'];
        }

        $code = $this->_generate_unique_code();
        $this->CI->user_model->update((int) $user_id, array('referral_code' => $code));
        return $code;
    }

    /**
     * Validate a referral code for a booking customer.
     *
     * @return array|null Referrer user row on success
     */
    public function validate_for_booking($code, $referred_user_id)
    {
        $this->last_error = NULL;
        if (!$this->is_enabled()) {
            $this->last_error = 'Referral programme is not available.';
            return NULL;
        }
        if (!$this->_table_ready()) {
            $this->last_error = 'Referral programme is not set up yet.';
            return NULL;
        }

        $code = $this->_normalize_code($code);
        if ($code === '') {
            return NULL;
        }

        $referred_user_id = (int) $referred_user_id;
        $referrer = $this->CI->user_model->find_by_referral_code($code);
        if (!$referrer) {
            $this->last_error = 'That referral code is not valid.';
            return NULL;
        }
        if ((int) $referrer['id'] === $referred_user_id) {
            $this->last_error = 'You cannot use your own referral code.';
            return NULL;
        }
        if (empty($referrer['is_active'])) {
            $this->last_error = 'That referral code is not active.';
            return NULL;
        }
        if ($this->CI->referral_model->referred_user_has_active_or_completed($referred_user_id)) {
            $this->last_error = 'You have already used a referral code on a previous booking.';
            return NULL;
        }

        return $referrer;
    }

    /**
     * Attach a pending referral to a newly created booking.
     *
     * @return int|null referral id
     */
    public function attach_to_booking($booking_id, $code, $referred_user_id)
    {
        $referrer = $this->validate_for_booking($code, $referred_user_id);
        if (!$referrer) {
            return NULL;
        }

        $referred = $this->CI->user_model->find((int) $referred_user_id);
        if (!$referred) {
            $this->last_error = 'Customer not found.';
            return NULL;
        }

        if ($this->CI->referral_model->find_by_booking((int) $booking_id)) {
            $this->last_error = 'This booking already has a referral attached.';
            return NULL;
        }

        return $this->CI->referral_model->create(array(
            'referrer_user_id'       => (int) $referrer['id'],
            'referred_user_id'       => (int) $referred_user_id,
            'referral_code'          => $this->_normalize_code($code),
            'referred_phone'         => (string) $referred['mobile'],
            'booking_id'             => (int) $booking_id,
            'status'                 => 'pending',
            'referrer_credit_amount' => 0,
            'referred_credit_amount' => 0,
        ));
    }

    /**
     * Complete referral when admin marks booking as completed.
     */
    public function complete_for_booking($booking_id)
    {
        if (!$this->is_enabled() || !$this->_table_ready()) {
            return FALSE;
        }

        $referral = $this->CI->referral_model->find_by_booking((int) $booking_id);
        if (!$referral || $referral['status'] !== 'pending') {
            return FALSE;
        }

        $referrer_credit = (float) $this->CI->config->item('referral_credit_referrer');
        $referred_credit = (float) $this->CI->config->item('referral_credit_referred');

        $this->CI->referral_model->mark_completed(
            (int) $referral['id'],
            $referrer_credit,
            $referred_credit
        );

        $detailed = $this->CI->referral_model->find_detailed((int) $referral['id']);
        if ($detailed) {
            $this->_notify_completion($detailed);
        }

        return TRUE;
    }

    /**
     * Cancel pending referral when booking is cancelled.
     */
    public function cancel_for_booking($booking_id)
    {
        if (!$this->_table_ready()) {
            return FALSE;
        }
        $referral = $this->CI->referral_model->find_by_booking((int) $booking_id);
        if (!$referral || $referral['status'] !== 'pending') {
            return FALSE;
        }
        $this->CI->referral_model->mark_cancelled((int) $referral['id']);
        return TRUE;
    }

    /** @return array{completed:int,pending:int} */
    public function stats_for_user($user_id)
    {
        if (!$this->_table_ready()) {
            return array('completed' => 0, 'pending' => 0);
        }
        return $this->CI->referral_model->stats_for_referrer((int) $user_id);
    }

    protected function _notify_completion(array $referral)
    {
        $this->CI->load->library(array('sms_gateway', 'mailer', 'crm_messaging'));

        $ref_amt = number_format((float) $referral['referrer_credit_amount'], 0);
        $red_amt = number_format((float) $referral['referred_credit_amount'], 0);
        $booking_ref = isset($referral['booking_reference']) ? $referral['booking_reference'] : '';

        // Referrer
        $referrer_ok = $this->_notify_party(
            (int) $referral['id'],
            'referrer',
            $referral['referrer_mobile'],
            $referral['referrer_email'],
            strtok($referral['referrer_name'], ' '),
            $ref_amt,
            $booking_ref,
            'You earned Rs'.$ref_amt.' referral credit - booking '.$booking_ref.' is complete.'
        );

        // Referred customer
        $referred_ok = $this->_notify_party(
            (int) $referral['id'],
            'referred',
            $referral['referred_mobile'],
            $referral['referred_email'],
            strtok($referral['referred_name'], ' '),
            $red_amt,
            $booking_ref,
            'Your referral discount of Rs'.$red_amt.' is confirmed for booking '.$booking_ref.'.'
        );

        if ($referrer_ok) {
            $this->CI->referral_model->mark_referrer_notified((int) $referral['id']);
        }
        if ($referred_ok) {
            $this->CI->referral_model->mark_referred_notified((int) $referral['id']);
        }
    }

    protected function _notify_party($referral_id, $role, $mobile, $email, $name, $amount, $booking_ref, $plain_message)
    {
        $vars = array(
            'name'   => $name,
            'amount' => $amount,
            'ref'    => $booking_ref,
        );

        $sms_ok = $this->CI->sms_gateway->send_template($mobile, 'referral_credit', $vars);
        if (!$sms_ok && ENVIRONMENT !== 'production') {
            log_message('debug', '[referral] SMS stub '.$role.': '.$plain_message);
            $sms_ok = TRUE;
        }

        $mail_ok = FALSE;
        if ($email) {
            $mail_ok = $this->CI->mailer->send(
                $email,
                'Referral credit confirmed - Your Mechanic Online',
                '<p>Hi '.htmlspecialchars($name).',</p><p>'.htmlspecialchars($plain_message).'</p>'
                .'<p>We will apply this credit on your next service invoice.</p>',
                strip_tags($plain_message)
            );
        }

        if (!$sms_ok && $this->CI->crm_messaging->can_send_whatsapp()) {
            $e164 = '+91'.preg_replace('/\D/', '', $mobile);
            if (strlen(preg_replace('/\D/', '', $mobile)) === 10) {
                $this->CI->crm_messaging->send_whatsapp_text($e164, $plain_message);
            }
        }

        return $sms_ok || $mail_ok;
    }

    protected function _generate_unique_code()
    {
        $attempts = 0;
        do {
            $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $code   = 'YMO-'.$suffix;
            $exists = $this->CI->user_model->find_by_referral_code($code);
            $attempts++;
        } while ($exists && $attempts < 20);

        return $code;
    }

    protected function _normalize_code($code)
    {
        $code = strtoupper(trim((string) $code));
        $code = preg_replace('/\s+/', '', $code);
        return $code;
    }

    protected function _table_ready()
    {
        return $this->CI->db->table_exists('referrals')
            && $this->CI->db->field_exists('referral_code', 'users');
    }
}
