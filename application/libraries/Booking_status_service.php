<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Changing a booking's status, with everything that goes with it:
 * audit entry, next-service + review reminders and referral on completion,
 * referral cancel, customer WhatsApp/SMS + email notice, Google review request.
 *
 * Used by the booking page and by job cards (which move their booking along).
 */
class Booking_status_service
{
    const STATUSES = array('pending', 'confirmed', 'in_progress', 'completed', 'cancelled');

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('booking_model', 'reminder_model'));
        $this->CI->load->library(array('mailer', 'audit', 'customer_notifier'));
    }

    /**
     * @return array [bool ok, string notice for the admin] - ok FALSE only when the customer message failed.
     */
    public function change($booking_id, $new_status, $reason = NULL, $admin_id = 0, $source = 'booking')
    {
        $booking = $this->CI->booking_model->find($booking_id);
        if (!$booking || !in_array($new_status, self::STATUSES, TRUE)) {
            return array(FALSE, 'Booking not found or status not valid.');
        }
        $previous = $booking['status'];
        if ($previous === $new_status) {
            return array(TRUE, '');
        }

        $this->CI->booking_model->update_status($booking_id, $new_status, $reason);
        $this->CI->audit->log('admin', (int) $admin_id, 'booking.status_change', 'booking', (int) $booking_id, array(
            'from' => $previous, 'to' => $new_status, 'reason' => $reason, 'source' => $source,
        ));

        // Reminders + referral the first time a booking is completed
        if ($new_status === 'completed') {
            $months = (int) $this->CI->config->item('reminder_months');
            $review_days = (int) $this->CI->config->item('reminder_review_days');
            $this->CI->reminder_model->create(array(
                'booking_id'   => (int) $booking_id,
                'type'         => 'next_service',
                'channel'      => 'both',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime("+$months months")),
                'status'       => 'pending',
            ));
            $this->CI->reminder_model->create(array(
                'booking_id'   => (int) $booking_id,
                'type'         => 'review',
                'channel'      => 'both',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime("+$review_days days")),
                'status'       => 'pending',
            ));
            $this->CI->load->library('referral_service');
            $this->CI->referral_service->complete_for_booking((int) $booking_id);
        }
        if ($new_status === 'cancelled') {
            $this->CI->load->library('referral_service');
            $this->CI->referral_service->cancel_for_booking((int) $booking_id);
        }

        // Customer notice on every change after `pending`
        $notice = '';
        $ok = TRUE;
        $detailed = NULL;
        if (in_array($new_status, array('confirmed', 'in_progress', 'completed', 'cancelled'), TRUE)) {
            $detailed = $this->CI->booking_model->find_detailed($booking_id);
            list($ok, $notice) = $this->notify_customer($detailed);
        }

        // Google review request right after delivery (config/reviews.php)
        if ($new_status === 'completed') {
            $this->CI->config->load('reviews', TRUE);
            if ($this->CI->config->item('review_request_on_complete', 'reviews')) {
                $this->CI->load->library('review_service');
                $detailed = $detailed ?: $this->CI->booking_model->find_detailed($booking_id);
                $rr = $this->CI->review_service->ask_for_booking($detailed, '', array('source' => 'auto', 'admin_id' => (int) $admin_id));
                $notice .= ' Google review: '.$rr['message'];
            }
        }
        return array($ok, trim($notice));
    }

    /** @return array [bool message_ok, string notice] */
    public function notify_customer(array $booking)
    {
        $notifier = $this->CI->customer_notifier;
        $msg_ok = $notifier->notify($booking['user_mobile'], 'booking_status', array(
            'ref'    => $booking['reference'],
            'status' => ymo_sms_status_label($booking['status']),
        ), array('context' => 'admin.booking_status', 'booking_id' => (int) $booking['id']));
        $msg_error = $msg_ok ? '' : (string) $notifier->last_error();
        $channel = $notifier->last_channel() === 'whatsapp' ? 'WhatsApp' : 'SMS';

        $has_email = !ymo_is_placeholder_email($booking['user_email']);
        $mail_ok = $has_email && $this->CI->mailer->send_view(
            $booking['user_email'],
            'Booking update - '.$booking['reference'],
            'emails/booking_status',
            array('booking' => $booking)
        );

        $sent = array();
        if ($msg_ok) {
            $sent[] = $channel;
        }
        if ($mail_ok) {
            $sent[] = 'email';
        }
        $text = $sent ? 'Customer notified by '.implode(' and ', $sent).'.' : 'Customer was NOT notified.';
        if (!$msg_ok) {
            $text .= ' Message failed: '.$this->friendly_send_error($msg_error).'.';
        }
        if ($has_email && !$mail_ok) {
            $text .= ' Email failed'.($this->CI->mailer->last_error() ? ': '.$this->CI->mailer->last_error() : '').'.';
        }
        return array($msg_ok, $text);
    }

    /** Turn raw WhatsApp / SMS errors into something an admin can act on. */
    public function friendly_send_error($error)
    {
        $error = trim((string) $error);
        if ($error === '') {
            return 'unknown error';
        }
        if (strpos($error, '131042') !== FALSE) {
            $error = preg_replace('/WhatsApp:[^\/]*/', 'WhatsApp: no balance or payment method on the WhatsApp account (add funds in Meta Billing & payments) ', $error);
        }
        return rtrim(mb_strimwidth($error, 0, 300, '…'), '. ');
    }
}
