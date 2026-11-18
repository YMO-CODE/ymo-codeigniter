<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Crm_wa_autoreply — instant answers to inbound WhatsApp messages so the team
 * only has to make calls, not chat.
 *
 *   - "Book service" / "Book now" / "YES" / "book"   → thank-you with the call
 *     time and the no-sign-in booking link; lead gets high priority and a
 *     follow-up call on the owner's My day.
 *   - STOP / "Stop promotions"                        → confirms the opt-out.
 *   - Anything else                                   → one acknowledgement per
 *     12 hours with the booking link, so a chatty customer isn't spammed.
 *
 * Replies are free-form text, which WhatsApp allows (no charge, no template)
 * because the customer messaged first. Off with CRM_WHATSAPP_AUTO_REPLY=0.
 */
class Crm_wa_autoreply
{
    /** Quick-reply button texts and short replies that mean "I want to book". */
    const BOOK_WORDS = array('book service', 'book now', 'book check-up', 'book checkup', 'book', 'yes', 'yes please',
        'interested', 'i am interested', 'book a service', 'book car service');

    const OPEN_HOUR  = 9;   // calls from 09:00
    const CLOSE_HOUR = 19;  // last promise of "within 2 hours" at 17:00

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('crm_messaging');
        $this->CI->load->model(array('crm_wa_message_model', 'crm_task_model', 'crm_lead_activity_model'));
    }

    public function enabled()
    {
        return getenv('CRM_WHATSAPP_AUTO_REPLY') !== '0' && $this->CI->crm_messaging->can_send_whatsapp();
    }

    /** What kind of message this is: 'book', 'opt_out' or 'other'. */
    public function intent($text)
    {
        if (crm_whatsapp_is_opt_out($text)) {
            return 'opt_out';
        }
        $t = strtolower(trim(preg_replace('/[^a-zA-Z\- ]+/', ' ', (string) $text)));
        $t = preg_replace('/\s+/', ' ', $t);
        return in_array($t, self::BOOK_WORDS, TRUE) ? 'book' : 'other';
    }

    /**
     * Handle one inbound message after it has been logged and turned into a lead.
     *
     * @return string|null what was sent ('book', 'opt_out', 'ack') or NULL
     */
    public function handle($phone, $name, $text, $lead_id = 0)
    {
        if (!$this->enabled() || trim((string) $phone) === '') {
            return NULL;
        }
        $intent = $this->intent($text);
        $first  = $this->_first_name($name);

        if ($intent === 'opt_out') {
            if ($this->_replied_recently($phone, 'auto_opt_out', 24)) {
                return NULL;
            }
            $this->_send($phone, "Done, you won't get offers from us any more. You'll still receive updates about your own bookings.\n\nTo book a service anytime: ".$this->booking_link('optout'),
                'auto_opt_out', $lead_id);
            return 'opt_out';
        }

        if ($intent === 'book') {
            if ($this->_replied_recently($phone, 'auto_book', 1)) {
                return NULL;
            }
            $due = $this->callback_time();
            $this->_send($phone,
                'Thanks'.($first ? ' '.$first : '')."! 🙌 Our team will call you ".$this->callback_phrase($due)." to fix a slot.\n\n"
                ."Prefer to book yourself? It takes a minute, no sign-in needed:\n".$this->booking_link('book_button'),
                'auto_book', $lead_id);
            if ($lead_id) {
                $this->_queue_call($lead_id, $due, $text);
                // The one instant email the team gets: someone wants a service.
                $this->CI->load->helper('crm');
                crm_notify_admin_new_lead($lead_id, 'WhatsApp - wants to book', array(
                    'name'    => $name !== '' ? $name : '+'.$phone,
                    'mobile'  => '+'.ltrim((string) $phone, '+'),
                    'message' => 'Tapped "'.trim((string) $text).'" on WhatsApp. They were told the team will call '.$this->callback_phrase($due).'.',
                ));
            }
            return 'book';
        }

        if ($this->_replied_recently($phone, 'auto_ack', 12)) {
            return NULL;
        }
        $this->_send($phone,
            'Hi'.($first ? ' '.$first : '').", thanks for messaging Your Mechanic Online! Our team will get back to you shortly.\n\n"
            ."To book a doorstep car service right away (no sign-in needed):\n".$this->booking_link('chat'),
            'auto_ack', $lead_id);
        return 'ack';
    }

    /** Public no-sign-in booking page, tagged so bookings show up per source. */
    public function booking_link($content)
    {
        $base = rtrim((string) ($this->CI->config->item('ymo_app_url') ?: 'https://booking.yourmechaniconline.com'), '/');
        return $base.'/quick-book?utm_source=whatsapp&utm_medium=auto_reply&utm_content='.rawurlencode($content);
    }

    /** When the team should call: +2h in working hours, else 10:00 next working morning. */
    public function callback_time($now = NULL)
    {
        $now = $now ?: time();
        $h = (int) date('G', $now);
        if ($h >= self::OPEN_HOUR && $h < self::CLOSE_HOUR - 2) {
            return $now + 2 * 3600;
        }
        $day = $h < self::OPEN_HOUR ? date('Y-m-d', $now) : date('Y-m-d', strtotime('+1 day', $now));
        return strtotime($day.' 10:00:00');
    }

    public function callback_phrase($due, $now = NULL)
    {
        $now = $now ?: time();
        if ($due - $now <= 2 * 3600 + 60) {
            return 'within 2 hours';
        }
        return date('Y-m-d', $due) === date('Y-m-d', $now) ? 'this morning by 10 am' : 'tomorrow morning by 10 am';
    }

    // --- internals ---------------------------------------------------------

    protected function _send($phone, $body, $kind, $lead_id)
    {
        $res = $this->CI->crm_messaging->send_whatsapp_text($phone, $body);
        $this->CI->crm_wa_message_model->log(array(
            'wa_message_id' => $res['message_id'],
            'phone'         => $phone,
            'direction'     => 'out',
            'kind'          => $kind,
            'body'          => $body,
            'context_type'  => $lead_id ? 'lead' : NULL,
            'context_id'    => $lead_id ? (int) $lead_id : NULL,
            'status'        => $res['ok'] ? 'sent' : 'failed',
            'error'         => $res['ok'] ? NULL : $this->CI->crm_messaging->last_error(),
        ));
        if ($lead_id && $res['ok']) {
            $this->CI->crm_lead_activity_model->add((int) $lead_id, NULL, 'whatsapp', 'Auto-reply sent: '.$body,
                array('direction' => 'outbound', 'channel' => 'whatsapp', 'provider' => 'whatsapp', 'message_id' => $res['message_id'], 'text' => $body));
        }
        if (!$res['ok']) {
            log_message('error', '[wa] auto-reply '.$kind.' to '.$phone.' failed: '.$this->CI->crm_messaging->last_error());
        }
        return $res['ok'];
    }

    /** Has this kind of auto-reply gone to this number in the last $hours? */
    protected function _replied_recently($phone, $kind, $hours)
    {
        if (!$this->CI->crm_wa_message_model->table_ready()) {
            return FALSE;
        }
        $digits = preg_replace('/\D+/', '', (string) $phone);
        return (bool) $this->CI->db->from('crm_wa_messages')
            ->where('direction', 'out')
            ->where('kind', $kind)
            ->where('status !=', 'failed')
            ->like('phone', substr($digits, -10), 'before')
            ->where('created_at >=', date('Y-m-d H:i:s', time() - $hours * 3600))
            ->count_all_results();
    }

    /** High priority + a call on the owner's My day (falls back to the first active admin). */
    protected function _queue_call($lead_id, $due, $text)
    {
        $lead = $this->CI->db->get_where('crm_leads', array('id' => (int) $lead_id))->row_array();
        if (!$lead || in_array($lead['stage'], array('booked', 'lost'), TRUE)) {
            return;
        }
        $this->CI->db->where('id', (int) $lead_id)->update('crm_leads', array('priority' => 1));

        $owner = (int) $lead['assigned_to'];
        if (!$owner) {
            $env_owner = (int) getenv('CRM_WHATSAPP_LEAD_OWNER');
            $row = $this->CI->db->select('id')->from('admin_users')->where('is_active', 1)
                ->order_by($env_owner ? 'id = '.$env_owner : 'id', $env_owner ? 'DESC' : 'ASC', FALSE)
                ->limit(1)->get()->row_array();
            $owner = $row ? (int) $row['id'] : 0;
            if ($owner) {
                $this->CI->db->where('id', (int) $lead_id)->update('crm_leads', array('assigned_to' => $owner));
            }
        }
        if ($owner) {
            $this->CI->crm_task_model->set_lead_followup($lead_id, $due, $owner, NULL,
                'Tapped "'.trim((string) $text).'" on WhatsApp. Call to fix a slot.');
        }
    }

    protected function _first_name($name)
    {
        $name = trim((string) $name);
        if ($name === '' || preg_match('/^\+?\d[\d\s]*$/', $name)) {
            return '';
        }
        $first = preg_split('/\s+/', $name)[0];
        return mb_substr($first, 0, 20);
    }
}
