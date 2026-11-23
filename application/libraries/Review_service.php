<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google reviews: ask customers for one (WhatsApp → SMS fallback, + email for
 * bookings) and the daily check that answers new reviews.
 *
 * Daily check rules (config/reviews.php):
 *   5★  → public reply posted automatically
 *   4★  → public reply posted automatically + alert with why a star was lost
 *   1–3★ → no automatic reply; alert the team to handle it personally
 */
class Review_service
{
    protected $CI;
    public $last_error = '';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper(array('review', 'ymo'));
        $this->CI->load->model('gbp_review_model');
    }

    // =====================================================================
    // Asking for reviews
    // =====================================================================

    /**
     * Send a review request to one customer.
     *
     * @param array $opts booking_id, admin_id, source (auto|manual|cron), email, force (ignore 30-day repeat guard)
     * @return array{ok:bool, channel:string, message:string}
     */
    public function ask($mobile, $name, $workshop, array $opts = array())
    {
        $mobile = function_exists('ymo_indian_mobile') ? ymo_indian_mobile($mobile) : preg_replace('/\D/', '', (string) $mobile);
        if ($mobile === '') {
            return array('ok' => FALSE, 'channel' => '', 'message' => 'No valid mobile number.');
        }
        $all = ymo_review_workshops();
        $workshop = isset($all[$workshop]) ? $workshop : 'hinjewadi';
        $first = trim((string) strtok(trim((string) $name), ' '));
        $first = $first !== '' ? mb_convert_case($first, MB_CASE_TITLE) : 'there';

        if (empty($opts['force'])) {
            $last = $this->CI->gbp_review_model->last_request_for($mobile);
            if ($last && strtotime($last['created_at']) > strtotime('-30 days')) {
                return array('ok' => FALSE, 'channel' => '', 'message' => 'Already asked on '.date('d M', strtotime($last['created_at'])).'; not sending again within 30 days.');
            }
        }

        $this->CI->load->library('customer_notifier');
        $vars = array(
            'name'           => $first,
            'ref'            => isset($opts['ref']) ? (string) $opts['ref'] : '',
            'workshop'       => $workshop,
            'workshop_label' => $all[$workshop]['label'],
            'review_link'    => ymo_review_short_url($workshop),
        );
        $ok = $this->CI->customer_notifier->notify($mobile, 'review_request', $vars, array(
            'context'    => 'review.'.(isset($opts['source']) ? $opts['source'] : 'manual'),
            'booking_id' => isset($opts['booking_id']) ? (int) $opts['booking_id'] : 0,
            'admin_id'   => isset($opts['admin_id']) ? (int) $opts['admin_id'] : NULL,
            'force_whatsapp' => TRUE,
        ));
        $channel = (string) $this->CI->customer_notifier->last_channel();
        $err = $ok ? NULL : (string) $this->CI->customer_notifier->last_error();

        $mail_ok = FALSE;
        if (!empty($opts['email']) && !empty($opts['booking'])) {
            $this->CI->load->library('mailer');
            $this->CI->load->helper('marketing');
            $mail_ok = $this->CI->mailer->send_view($opts['email'], 'How did we do? - '.$opts['booking']['reference'],
                'emails/review_request', array('booking' => $opts['booking'], 'review_link' => ymo_review_short_url($workshop)));
        }

        $this->CI->gbp_review_model->log_request(array(
            'booking_id' => !empty($opts['booking_id']) ? (int) $opts['booking_id'] : NULL,
            'mobile'     => $mobile,
            'name'       => mb_substr((string) $name, 0, 120),
            'workshop'   => $workshop,
            'channel'    => trim($channel.($mail_ok ? '+email' : ''), '+'),
            'status'     => ($ok || $mail_ok) ? 'sent' : 'failed',
            'error'      => $err ? mb_substr($err, 0, 255) : NULL,
            'source'     => isset($opts['source']) ? (string) $opts['source'] : 'manual',
            'admin_id'   => isset($opts['admin_id']) ? (int) $opts['admin_id'] : NULL,
        ));

        if ($ok || $mail_ok) {
            $via = $channel === 'whatsapp' ? 'WhatsApp' : ($channel === 'sms' ? 'SMS' : '');
            $via = trim($via.($mail_ok ? ($via ? ' + email' : 'email') : ''));
            return array('ok' => TRUE, 'channel' => $channel, 'message' => 'Review request sent by '.$via.' ('.$all[$workshop]['label'].' link).');
        }
        return array('ok' => FALSE, 'channel' => '', 'message' => 'Could not send: '.($err ?: 'unknown error'));
    }

    /** Ask the customer of a completed booking (auto on completion, cron, or the booking page button). */
    public function ask_for_booking(array $booking, $workshop = '', array $opts = array())
    {
        if ($workshop === '') {
            $workshop = $this->workshop_for_booking($booking);
        }
        if (empty($opts['force']) && $this->CI->gbp_review_model->requested_for_booking((int) $booking['id'])) {
            return array('ok' => FALSE, 'channel' => '', 'message' => 'Review request already sent for this booking.');
        }
        $opts['booking_id'] = (int) $booking['id'];
        $opts['ref'] = $booking['reference'];
        $opts['booking'] = $booking;
        $opts['email'] = !empty($booking['user_email']) ? $booking['user_email'] : '';
        $res = $this->ask($booking['user_mobile'], $booking['user_name'], $workshop, $opts);
        if ($res['ok']) {
            // Close the scheduled reminder so the cron does not ask again.
            $this->CI->load->model('reminder_model');
            $pending = $this->CI->reminder_model->find_pending_review_for_booking((int) $booking['id']);
            if ($pending) {
                $this->CI->reminder_model->mark_sent($pending['id']);
            }
        }
        return $res;
    }

    public function workshop_for_booking(array $booking)
    {
        $city = ''; $area = '';
        if (!empty($booking['user_id'])) {
            $u = $this->CI->db->select('city, area')->get_where('users', array('id' => (int) $booking['user_id']), 1)->row_array();
            if ($u) {
                $city = (string) $u['city'];
                $area = (string) $u['area'];
            }
        }
        return ymo_review_workshop_for($city, $area);
    }

    // =====================================================================
    // Daily check of Google reviews
    // =====================================================================

    /**
     * @return array summary counts + messages
     */
    public function daily_check()
    {
        $sum = array('locations' => 0, 'seen' => 0, 'new' => 0, 'replied' => 0, 'alerted' => 0, 'failed' => 0, 'errors' => array());
        $this->CI->gbp_review_model->ensure_schema();
        $this->CI->load->library(array('gbp_api', 'review_responder'));
        $api = $this->CI->gbp_api;
        if (!$api->connection() || !$api->client()) {
            $sum['errors'][] = 'Google not connected.';
            return $sum;
        }
        $locs = (array) $this->CI->gbp_review_model->setting('gbp_locations', array());
        if (!$locs) {
            $sum['errors'][] = 'No listings selected on the Google reviews page.';
            return $sum;
        }
        $max_replies = (int) ymo_review_setting('review_max_replies_per_run', 25);
        $max_age = (int) ymo_review_setting('review_auto_reply_max_age_days', 400);
        $auto_stars = (array) ymo_review_setting('review_auto_reply_stars', array(4, 5));
        $alert_stars = (array) ymo_review_setting('review_alert_stars', array(1, 2, 3, 4));
        $workshops = ymo_review_workshops();
        $alerts = array();

        foreach ($locs as $loc) {
            if (empty($loc['path']) || empty($loc['workshop'])) {
                continue;
            }
            $sum['locations']++;
            $reviews = $api->reviews($loc['path'], 100);
            if ($reviews === NULL) {
                $sum['errors'][] = $loc['title'].': '.$api->error;
                continue;
            }
            $area = isset($workshops[$loc['workshop']]['area']) ? $workshops[$loc['workshop']]['area'] : 'our workshop';
            foreach ($reviews as $r) {
                $sum['seen']++;
                $row = $this->CI->gbp_review_model->upsert(array(
                    'review_id' => $r['review_id'], 'workshop' => $loc['workshop'], 'location_name' => $loc['path'],
                    'author' => $r['author'], 'stars' => $r['stars'], 'comment' => $r['comment'],
                    'review_time' => $r['review_time'], 'reply_text' => $r['reply_text'], 'reply_time' => $r['reply_time'],
                ));
                if (!$row || $row['action'] !== 'new') {
                    continue;
                }
                $sum['new']++;
                $stars = (int) $row['stars'];
                $old = $row['review_time'] && strtotime($row['review_time']) < strtotime('-'.$max_age.' days');

                if (in_array($stars, $auto_stars, TRUE) && !$old && $sum['replied'] < $max_replies) {
                    $out = $this->CI->review_responder->respond($row, $area);
                    if ($api->reply($r['name'], $out['reply'])) {
                        $sum['replied']++;
                        $patch = array('action' => 'auto_replied', 'reply_text' => $out['reply'], 'reply_time' => date('Y-m-d H:i:s'), 'error' => NULL);
                        if (in_array($stars, $alert_stars, TRUE)) {
                            $patch['reason'] = $out['reason'];
                            $patch['action'] = 'alerted';
                            $alerts[] = array_merge($row, $patch);
                        }
                        $this->CI->gbp_review_model->mark($row['id'], $patch);
                    } else {
                        $sum['failed']++;
                        $this->CI->gbp_review_model->mark($row['id'], array('action' => 'failed', 'error' => mb_substr($api->error, 0, 255)));
                    }
                } elseif (in_array($stars, $alert_stars, TRUE) && !in_array($stars, $auto_stars, TRUE)) {
                    // 1–3★: the team replies personally.
                    $reason = $this->CI->review_responder->reason($row);
                    $patch = array('action' => 'alerted', 'reason' => $reason);
                    $this->CI->gbp_review_model->mark($row['id'], $patch);
                    $alerts[] = array_merge($row, $patch);
                } else {
                    $this->CI->gbp_review_model->mark($row['id'], array('action' => $old ? 'old' : 'recorded'));
                }
            }
        }

        foreach ($alerts as $a) {
            $this->_alert($a);
            $this->CI->gbp_review_model->mark($a['id'], array('alerted_at' => date('Y-m-d H:i:s')));
            $sum['alerted']++;
        }
        $this->CI->gbp_review_model->save_setting('gbp_last_check', array('at' => date('c'), 'summary' => $sum));
        return $sum;
    }

    /** Phone push (CRM app) + email to the admin notify address. */
    protected function _alert(array $r)
    {
        $workshops = ymo_review_workshops();
        $where = isset($workshops[$r['workshop']]['label']) ? $workshops[$r['workshop']]['label'] : $r['workshop'];
        $stars = (int) $r['stars'];
        $title = str_repeat('★', $stars).str_repeat('☆', 5 - $stars).' '.$where.' · '.$r['author'];
        if ($stars === 4) {
            $body = 'Replied automatically. Why not 5★: '.($r['reason'] ?: 'not stated');
        } else {
            $body = 'Needs your reply. '.($r['comment'] !== '' ? '"'.mb_substr($r['comment'], 0, 120).'"' : 'No comment left.');
        }
        $url = admin_url('reviews?r='.(int) $r['id']);

        $this->CI->load->model('ymochat_model');
        if (method_exists($this->CI->ymochat_model, 'push_alert')) {
            $this->CI->ymochat_model->push_alert($title, mb_substr($body, 0, 200), $url, 'review-'.(int) $r['id']);
        }

        $to = $this->CI->config->item('ymo_admin_notify');
        if ($to) {
            $this->CI->load->library('mailer');
            $html = '<p><strong>'.html_escape($title).'</strong></p>'
                .'<p>'.nl2br(html_escape($r['comment'] !== '' ? $r['comment'] : '(no comment)')).'</p>'
                .($stars === 4 ? '<p><strong>Why not 5★:</strong> '.html_escape($r['reason'] ?: 'not stated').'</p>'
                    .'<p><strong>Our reply (posted):</strong> '.html_escape((string) $r['reply_text']).'</p>'
                    : '<p><strong>This review was not answered automatically.</strong> Please reply personally.</p>')
                .'<p><a href="'.html_escape($url).'">Open in the CRM</a></p>';
            $subject = ($stars === 4 ? '4★ review at ' : $stars.'★ review needs a reply – ').$where.' · '.$r['author'];
            $this->CI->mailer->send($to, $subject, '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1d2026">'.$html.'</div>');
        }
    }
}
