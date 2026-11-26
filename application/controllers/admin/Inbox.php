<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp Inbox: every conversation on the CRM's WhatsApp number in one place,
 * newest first, with unread counts and a reply box. Opening a conversation marks
 * it read. Who the person is (lead, customer, booking) is shown beside the chat.
 */
class Inbox extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('crm_wa_message_model', 'crm_lead_model', 'crm_contact_model', 'crm_lead_activity_model'));
        $this->load->library(array('crm_messaging', 'audit'));
    }

    /** List of conversations; ?phone= opens one beside the list. */
    public function index($phone = '')
    {
        $this->require_perm('leads.view');
        $show   = $this->input->get('show') === 'all' ? 'all' : 'unread';
        $q      = trim((string) $this->input->get('q'));
        $src    = (string) $this->input->get('src');
        $src    = in_array($src, array('ads', 'website', 'direct'), TRUE) ? $src : '';
        $page   = max(1, (int) $this->input->get('page'));
        $per    = 30;
        $phone  = crm_whatsapp_e164($phone);
        $unread = $this->crm_wa_message_model->unread_count();
        // With nothing unread, "Unread" would be empty: show everything instead.
        if ($show === 'unread' && !$unread && $this->input->get('show') === NULL) {
            $show = 'all';
        }

        $list = $this->crm_wa_message_model->conversations($show === 'unread', $q, $per, ($page - 1) * $per, $src);
        $sources = $this->crm_wa_message_model->latest_sources(array_column($list['rows'], 'phone'));
        foreach ($list['rows'] as &$r) {
            $r['who'] = $this->_who($r['phone']);
            $r['source'] = isset($sources[$r['phone']]) ? $sources[$r['phone']] : NULL;
            if (!empty($r['last'])) {
                $r['last']['rendered'] = $this->crm_wa_message_model->render_template($r['last']);
            }
        }
        unset($r);

        $data = array(
            'title'   => 'Inbox',
            'show'    => $show,
            'q'       => $q,
            'src'     => $src,
            'src_counts' => $this->crm_wa_message_model->source_counts(),
            'page'    => $page,
            'pages'   => max(1, (int) ceil($list['total'] / $per)),
            'rows'    => $list['rows'],
            'unread'  => $unread,
            'ready'   => $this->crm_wa_message_model->inbox_ready(),
            'phone'   => $phone,
            'can_reply' => crm_can('leads.edit') && $this->crm_messaging->can_send_whatsapp(),
        );

        if ($phone !== '') {
            $this->crm_wa_message_model->mark_read($phone, $this->admin['id']);
            $data['thread']    = $this->crm_wa_message_model->thread($phone);
            foreach ($data['thread'] as &$m) {
                $m['rendered'] = $this->crm_wa_message_model->render_template($m);
            }
            unset($m);
            $data['who']       = $this->_who($phone);
            $data['sources']   = $this->crm_wa_message_model->sources_for($phone, 10);
            $data['in_window'] = $this->crm_wa_message_model->in_service_window($phone);
            $data['unread']    = $this->crm_wa_message_model->unread_count();
            foreach ($data['rows'] as &$r) {
                if ($r['phone'] === $phone) { $r['unread'] = 0; }
            }
            unset($r);
        }
        $this->render('admin/inbox/index', $data);
    }

    public function view($phone)
    {
        $this->index($phone);
    }

    /** POST inbox/{phone}/reply — free text, only inside WhatsApp's 24-hour window. */
    public function reply($phone)
    {
        $this->require_perm('leads.edit');
        $phone = crm_whatsapp_e164($phone);
        $back = admin_url('inbox/'.$phone.$this->_keep_qs());
        $text = trim((string) $this->input->post('body'));
        if ($phone === '' || $text === '') {
            $this->flash('error', 'Type a message first.');
            redirect($back);
        }
        if (mb_strlen($text) > 4096) {
            $this->flash('error', 'That message is too long for WhatsApp (4096 characters).');
            redirect($back);
        }
        if (!$this->crm_wa_message_model->in_service_window($phone)) {
            $this->flash('warning', 'More than 24 hours since they last wrote, so WhatsApp only allows approved templates. Call them instead, or send a campaign template.');
            redirect($back);
        }

        $res = $this->crm_messaging->send_whatsapp_text($phone, $text);
        $who = $this->_who($phone);
        $lead_id = !empty($who['lead']) ? (int) $who['lead']['id'] : 0;
        $this->crm_wa_message_model->log(array(
            'wa_message_id' => $res['message_id'] ?? NULL,
            'phone'         => $phone,
            'direction'     => 'out',
            'kind'          => 'text',
            'body'          => $text,
            'context_type'  => $lead_id ? 'lead' : NULL,
            'context_id'    => $lead_id ?: NULL,
            'status'        => !empty($res['ok']) ? 'sent' : 'failed',
            'error'         => !empty($res['ok']) ? NULL : $this->crm_messaging->last_error(),
            'admin_id'      => (int) $this->admin['id'],
        ));
        if (empty($res['ok'])) {
            $this->flash('error', 'Not sent: '.($this->crm_messaging->last_error() ?: 'WhatsApp refused the message.'));
            redirect($back);
        }
        if ($lead_id) {
            $this->crm_lead_activity_model->add($lead_id, $this->admin['id'], 'whatsapp', $text, array(
                'direction' => 'outbound', 'channel' => 'whatsapp', 'provider' => 'whatsapp',
                'message_id' => $res['message_id'] ?? NULL, 'sender_id' => $phone, 'text' => $text,
            ));
        }
        $this->audit->log('admin', $this->admin['id'], 'crm.inbox.reply', 'crm_wa_message', 0, array('phone' => $phone));
        redirect($back);
    }

    /** POST inbox/read-all */
    public function read_all()
    {
        $this->require_perm('leads.view');
        $n = $this->crm_wa_message_model->mark_all_read($this->admin['id']);
        $this->flash('success', $n ? 'Marked '.$n.' message'.($n === 1 ? '' : 's').' as read.' : 'Nothing was unread.');
        redirect(admin_url('inbox?show=all'));
    }

    /** GET inbox/unread — for the sidebar badge, polled every 30 seconds. */
    public function unread()
    {
        $this->require_perm('leads.view');
        $this->output->set_content_type('application/json')
            ->set_header('Cache-Control: no-store')
            ->set_output(json_encode(array('unread' => $this->crm_wa_message_model->unread_count())));
    }

    // --- internals ---------------------------------------------------------

    /** Lead, customer and active booking for a WhatsApp number. */
    protected function _who($phone)
    {
        static $cache = array();
        if (isset($cache[$phone])) {
            return $cache[$phone];
        }
        $mobile10 = substr(preg_replace('/\D+/', '', $phone), -10);
        $lead = $this->db->select('l.id, l.name, l.stage, l.assigned_to, a.name AS assignee_name', FALSE)
            ->from('crm_leads l')->join('admin_users a', 'a.id = l.assigned_to', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->group_start()
                ->group_start()->where('l.external_provider', 'whatsapp')->where('l.external_lead_id', $phone)->group_end()
                ->or_like('l.mobile', $mobile10, 'before')
            ->group_end()
            ->order_by('l.id', 'DESC')->limit(1)->get()->row_array();
        $contact = $this->crm_contact_model->find_by_mobile($mobile10);
        $booking = NULL;
        if ($contact && !empty($contact['user_id'])) {
            $booking = $this->db->select('id, reference, status')->from('bookings')
                ->where('user_id', (int) $contact['user_id'])
                ->where_in('status', array('pending', 'confirmed', 'in_progress'))
                ->order_by('id', 'DESC')->limit(1)->get()->row_array();
        }
        $name = $contact['name'] ?? ($lead['name'] ?? '');
        return $cache[$phone] = array(
            'name'    => $name,
            'mobile'  => $mobile10,
            'lead'    => $lead ?: NULL,
            'contact' => $contact ?: NULL,
            'booking' => $booking ?: NULL,
        );
    }

    protected function _keep_qs()
    {
        $qs = array_intersect_key($_GET, array_flip(array('show', 'q', 'page')));
        return $qs ? '?'.http_build_query($qs) : '';
    }
}
