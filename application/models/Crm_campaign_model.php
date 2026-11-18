<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_campaign_model extends CI_Model
{
    const TABLE = 'crm_campaigns';
    const RECIP_TABLE = 'crm_campaign_recipients';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function paginate($limit, $offset)
    {
        $rows = $this->db
            ->select('c.*, a.name AS creator_name', FALSE)
            ->from(self::TABLE.' c')
            ->join('admin_users a', 'a.id = c.created_by', 'left')
            ->order_by('c.created_at', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();
        $total = (int) $this->db->count_all_results(self::TABLE);
        return array('rows' => $rows, 'total' => $total);
    }

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $payload)
    {
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update(self::TABLE, $payload);
    }

    /**
     * Delete campaigns (recipients go with them via ON DELETE CASCADE).
     * Campaigns that are mid-send are skipped so the cron isn't cut off.
     *
     * @return array{deleted: array, skipped: array} campaign rows
     */
    public function delete_many(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return array('deleted' => array(), 'skipped' => array());
        }
        $rows = $this->db->select('id, name, status')->where_in('id', $ids)->get(self::TABLE)->result_array();
        $deleted = $skipped = array();
        foreach ($rows as $r) {
            if ($r['status'] === 'sending') {
                $skipped[] = $r;
            } else {
                $deleted[] = $r;
            }
        }
        if ($deleted) {
            $del_ids = array_map(function ($r) { return (int) $r['id']; }, $deleted);
            $this->db->where_in('campaign_id', $del_ids)->delete(self::RECIP_TABLE);
            $this->db->where_in('id', $del_ids)->delete(self::TABLE);
        }
        return array('deleted' => $deleted, 'skipped' => $skipped);
    }

    /**
     * Campaigns cron should work on: scheduled ones that are due, plus ones
     * left in 'sending' (e.g. "Send now" only dispatches the first batch
     * from the web request; cron finishes the rest).
     */
    public function due_scheduled($limit = 5)
    {
        return $this->db
            ->group_start()
                ->where('status', 'sending')
                ->or_group_start()
                    ->where('status', 'scheduled')
                    ->where('scheduled_at <=', date('Y-m-d H:i:s'))
                ->group_end()
            ->group_end()
            ->order_by('scheduled_at', 'ASC')
            ->limit((int) $limit)
            ->get(self::TABLE)
            ->result_array();
    }

    /** Tag given to customers whose number can't receive WhatsApp (Meta error 131026). */
    const NO_WA_TAG = 'No WhatsApp';

    /**
     * Recipient outcome groups shown on the campaign page, in display order.
     * Meta error codes: 131049 per-person marketing limit, 130472 Meta experiment,
     * 131000 Meta-side glitch (all worth retrying later); 131026 not on WhatsApp;
     * 131009 malformed number.
     */
    public static function outcome_groups()
    {
        return array(
            'read'       => array('label' => 'Read',                 'hint' => 'Opened the message.'),
            'delivered'  => array('label' => 'Delivered, not read',  'hint' => 'Reached the phone but not opened yet.'),
            'sent'       => array('label' => 'No receipt yet',       'hint' => 'Accepted by WhatsApp; delivery not confirmed yet.'),
            'pending'    => array('label' => 'Waiting to send',      'hint' => 'Goes out on the next cron run.'),
            'retry'      => array('label' => 'Can retry later',      'hint' => "Meta held these back (marketing limit per person, Meta test group or a Meta glitch). Not charged. Retrying after about a week usually works."),
            'no_whatsapp'=> array('label' => 'Not on WhatsApp',      'hint' => 'These numbers cannot receive WhatsApp. Call or SMS them instead.'),
            'bad_number' => array('label' => 'Wrong number',         'hint' => 'The saved mobile number is invalid. Fix it on the customer.'),
            'failed'     => array('label' => 'Other failures',       'hint' => 'See the reason column.'),
            'skipped'    => array('label' => 'Skipped',              'hint' => 'Left out when sending.'),
        );
    }

    /** First Meta error code (e.g. "131049") in an error text, or ''. */
    public static function wa_error_code($error)
    {
        return preg_match('/(?<!\d)(13\d{4})(?!\d)/', (string) $error, $m) ? $m[1] : '';
    }

    /** Which outcome group a recipient row belongs to. */
    public static function outcome_of(array $r)
    {
        if ($r['status'] === 'pending') {
            return 'pending';
        }
        if ($r['status'] === 'skipped') {
            return 'skipped';
        }
        if ($r['status'] === 'failed' || ($r['delivery_status'] ?? '') === 'failed') {
            $code = self::wa_error_code($r['error_message'] ?? '');
            if (in_array($code, array('131049', '130472', '131000'), TRUE)) {
                return 'retry';
            }
            if ($code === '131026') {
                return 'no_whatsapp';
            }
            if ($code === '131009') {
                return 'bad_number';
            }
            return 'failed';
        }
        $d = (string) ($r['delivery_status'] ?? '');
        return $d === 'read' ? 'read' : ($d === 'delivered' ? 'delivered' : 'sent');
    }

    /** Short plain-English reason for a failed recipient. */
    public static function outcome_reason(array $r)
    {
        $code = self::wa_error_code($r['error_message'] ?? '');
        $map = array(
            '131049' => 'Meta limit on marketing messages to this person',
            '130472' => 'Meta test group, marketing held back',
            '131000' => 'Meta glitch',
            '131026' => 'Not on WhatsApp',
            '131009' => 'Invalid mobile number',
            '131042' => 'WhatsApp balance was empty',
            '131050' => 'Customer stopped marketing messages',
        );
        if (isset($map[$code])) {
            return $map[$code];
        }
        return trim((string) ($r['error_message'] ?? ''));
    }

    /**
     * Recipients with their outcome group.
     * @return array{counts: array<string,int>, rows: array[]} rows only for $group ('' = all), newest first
     */
    public function recipients_by_outcome($campaign_id, $group = '')
    {
        $fields = 'id, name, mobile, email, channel, status, error_message, sent_at';
        if ($this->db->field_exists('delivery_status', self::RECIP_TABLE)) {
            $fields .= ', delivery_status, delivered_at, read_at';
        }
        $all = $this->db->select($fields)->where('campaign_id', (int) $campaign_id)
            ->order_by('id', 'ASC')->get(self::RECIP_TABLE)->result_array();
        $counts = array_fill_keys(array_keys(self::outcome_groups()), 0);
        $rows = array();
        foreach ($all as $r) {
            $r['outcome'] = self::outcome_of($r);
            $counts[$r['outcome']]++;
            if ($group === '' || $group === $r['outcome']) {
                $r['reason'] = in_array($r['outcome'], array('retry', 'no_whatsapp', 'bad_number', 'failed'), TRUE) ? self::outcome_reason($r) : '';
                $rows[] = $r;
            }
        }
        return array('counts' => $counts, 'rows' => $rows);
    }

    /** When the first message of this campaign went out (for the "wait a week" advice). */
    public function first_sent_at($campaign_id)
    {
        $row = $this->db->select_min('sent_at', 'at')->where('campaign_id', (int) $campaign_id)
            ->where('sent_at IS NOT NULL', NULL, FALSE)->get(self::RECIP_TABLE)->row_array();
        return $row['at'] ?? NULL;
    }

    /**
     * New draft that re-sends the same template only to the "Can retry later"
     * people of $camp. The audience is fixed to that list.
     */
    public function create_retry(array $camp, $admin_id)
    {
        return $this->create(array(
            'name'           => mb_substr('Retry: '.preg_replace('/^Retry: /', '', $camp['name']), 0, 160),
            'channel'        => $camp['channel'],
            'subject'        => $camp['subject'],
            'body'           => $camp['body'],
            'segment_json'   => json_encode(array('type' => 'campaign_retry', 'campaign_id' => (int) $camp['id'])),
            'wa_template_id' => $camp['wa_template_id'],
            'wa_params_json' => $camp['wa_params_json'],
            'status'         => 'draft',
            'created_by'     => $admin_id ? (int) $admin_id : NULL,
        ));
    }

    /** Tag every "Not on WhatsApp" recipient of a campaign. Returns customers tagged. */
    public function tag_no_whatsapp_for_campaign($campaign_id)
    {
        $res = $this->recipients_by_outcome($campaign_id, 'no_whatsapp');
        $n = 0;
        foreach ($res['rows'] as $r) {
            $n += $this->flag_no_whatsapp($r['mobile']);
        }
        return $n;
    }

    /** Tag the customer with this mobile "No WhatsApp". Returns 1 if a customer was found. */
    public function flag_no_whatsapp($mobile)
    {
        $c = $this->_contact_for_mobile($mobile);
        if (!$c) {
            return 0;
        }
        $this->load->model('crm_tag_model');
        $this->crm_tag_model->merge_contact_tags((int) $c['id'], array($this->crm_tag_model->find_or_create(self::NO_WA_TAG)));
        return 1;
    }

    /** They messaged us on WhatsApp, so they clearly have it: remove the tag. */
    public function clear_no_whatsapp($mobile)
    {
        $c = $this->_contact_for_mobile($mobile);
        if (!$c || !$this->db->table_exists('crm_tags')) {
            return;
        }
        $tag = $this->db->get_where('crm_tags', array('name' => self::NO_WA_TAG))->row_array();
        if ($tag) {
            $this->db->where('contact_id', (int) $c['id'])->where('tag_id', (int) $tag['id'])->delete('crm_contact_tags');
        }
    }

    protected function _contact_for_mobile($mobile)
    {
        $m10 = substr(preg_replace('/\D/', '', (string) $mobile), -10);
        if (strlen($m10) < 10) {
            return NULL;
        }
        $this->load->model('crm_contact_model');
        return $this->crm_contact_model->find_by_mobile($m10);
    }

    /** Mobiles (last 10 digits) of customers tagged "No WhatsApp". */
    protected function _no_whatsapp_numbers()
    {
        if (!$this->db->table_exists('crm_tags')) {
            return array();
        }
        $rows = $this->db->select('c.mobile')->from('crm_contacts c')
            ->join('crm_contact_tags ct', 'ct.contact_id = c.id')
            ->join('crm_tags t', 't.id = ct.tag_id')
            ->where('t.name', self::NO_WA_TAG)->get()->result_array();
        $out = array();
        foreach ($rows as $r) {
            $d = substr(preg_replace('/\D/', '', (string) $r['mobile']), -10);
            if ($d !== '') {
                $out[$d] = TRUE;
            }
        }
        return $out;
    }

    public function has_recipients($campaign_id)
    {
        return $this->db->where('campaign_id', (int) $campaign_id)->count_all_results(self::RECIP_TABLE) > 0;
    }

    /** WhatsApp delivery receipts: delivered / read / failed after send. */
    public function delivery_stats($campaign_id)
    {
        $out = array('delivered' => 0, 'read' => 0, 'failed' => 0);
        if (!$this->db->field_exists('delivery_status', self::RECIP_TABLE)) {
            return $out;
        }
        $rows = $this->db
            ->select('delivery_status, COUNT(*) AS cnt')
            ->where('campaign_id', (int) $campaign_id)
            ->where('delivery_status IS NOT NULL', NULL, FALSE)
            ->group_by('delivery_status')
            ->get(self::RECIP_TABLE)
            ->result_array();
        foreach ($rows as $r) {
            if (isset($out[$r['delivery_status']])) {
                $out[$r['delivery_status']] = (int) $r['cnt'];
            }
        }
        // "read" implies delivered.
        $out['delivered'] += $out['read'];
        return $out;
    }

    /** Apply a WhatsApp webhook receipt to a campaign recipient row. */
    public function apply_wa_status($wa_message_id, $status, $error = '')
    {
        if ($wa_message_id === '' || !$this->db->field_exists('wa_message_id', self::RECIP_TABLE)) {
            return 0;
        }
        $row = $this->db->get_where(self::RECIP_TABLE, array('wa_message_id' => $wa_message_id))->row_array();
        if (!$row) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $patch = array();
        $cur = (string) $row['delivery_status'];
        if ($status === 'delivered' && !in_array($cur, array('delivered', 'read'), TRUE)) {
            $patch = array('delivery_status' => 'delivered', 'delivered_at' => $now);
        } elseif ($status === 'read' && $cur !== 'read') {
            $patch = array('delivery_status' => 'read', 'read_at' => $now);
            if (empty($row['delivered_at'])) {
                $patch['delivered_at'] = $now;
            }
        } elseif ($status === 'failed') {
            $patch = array('delivery_status' => 'failed', 'status' => 'failed');
            if ($error !== '') {
                $patch['error_message'] = substr($error, 0, 500);
            }
            if (self::wa_error_code($error) === '131026') {
                $this->flag_no_whatsapp($row['mobile']);
            }
        } elseif ($status === 'sent' && $cur === '') {
            $patch = array('delivery_status' => 'sent');
        }
        if (!$patch) {
            return 0;
        }
        $this->db->where('id', (int) $row['id'])->update(self::RECIP_TABLE, $patch);
        return 1;
    }

    public function recipient_stats($campaign_id)
    {
        $rows = $this->db
            ->select('status, COUNT(*) AS cnt')
            ->where('campaign_id', (int) $campaign_id)
            ->group_by('status')
            ->get(self::RECIP_TABLE)
            ->result_array();
        $out = array('pending' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0);
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['cnt'];
        }
        return $out;
    }

    public function add_recipients($campaign_id, array $recipients)
    {
        foreach ($recipients as $r) {
            $this->db->insert(self::RECIP_TABLE, array(
                'campaign_id' => (int) $campaign_id,
                'channel'     => $r['channel'],
                'email'       => $r['email'] ?? '',
                'mobile'      => $r['mobile'] ?? '',
                'name'        => $r['name'] ?? '',
                'status'      => 'pending',
                'created_at'  => date('Y-m-d H:i:s'),
            ));
        }
    }

    public function pending_recipients($campaign_id, $limit = 35)
    {
        return $this->db
            ->where('campaign_id', (int) $campaign_id)
            ->where('status', 'pending')
            ->limit((int) $limit)
            ->get(self::RECIP_TABLE)
            ->result_array();
    }

    public function mark_recipient($id, $status, $error = NULL, $wa_message_id = NULL)
    {
        $patch = array('status' => $status);
        if ($wa_message_id) {
            $patch['wa_message_id'] = (string) $wa_message_id;
            $patch['delivery_status'] = 'sent';
        }
        if ($status === 'sent') {
            $patch['sent_at'] = date('Y-m-d H:i:s');
        }
        if ($error) {
            $patch['error_message'] = substr($error, 0, 500);
        }
        $this->db->where('id', (int) $id)->update(self::RECIP_TABLE, $patch);
        if ($status === 'failed' && self::wa_error_code($error) === '131026') {
            $row = $this->db->select('mobile')->get_where(self::RECIP_TABLE, array('id' => (int) $id))->row_array();
            if ($row) {
                $this->flag_no_whatsapp($row['mobile']);
            }
        }
    }

    /**
     * Build recipient list from segment_json.
     *
     * @return array[] rows with channel, email, mobile, name
     */
    public function build_recipients_from_segment(array $segment, $channel)
    {
        $type = isset($segment['type']) ? $segment['type'] : 'all_contacts';
        $contacts = array();

        if ($type === 'all_contacts' || $type === 'contacts') {
            $this->db->where('deleted_at IS NULL', NULL, FALSE);
            $contacts = $this->db->get('crm_contacts')->result_array();
        } elseif ($type === 'tag' && !empty($segment['tag_id'])) {
            $contacts = $this->db
                ->select('c.*')
                ->from('crm_contacts c')
                ->join('crm_contact_tags ct', 'ct.contact_id = c.id')
                ->where('ct.tag_id', (int) $segment['tag_id'])
                ->where('c.deleted_at IS NULL', NULL, FALSE)
                ->get()
                ->result_array();
        } elseif ($type === 'campaign_retry' && !empty($segment['campaign_id'])) {
            // Fixed audience: the "Can retry later" people of an earlier campaign.
            $res = $this->recipients_by_outcome((int) $segment['campaign_id'], 'retry');
            foreach ($res['rows'] as $r) {
                $c = $this->_contact_for_mobile($r['mobile']);
                $contacts[] = $c ?: array(
                    'name' => $r['name'], 'email' => $r['email'], 'mobile' => $r['mobile'],
                    'email_opt_out' => 0, 'sms_opt_out' => 0, 'whatsapp_opt_out' => 0,
                );
            }
            return $this->_contacts_to_recipients($contacts, $channel);
        } elseif ($type === 'leads_open') {
            $leads = $this->db
                ->where('status', 'open')
                ->where('deleted_at IS NULL', NULL, FALSE)
                ->get('crm_leads')
                ->result_array();
            foreach ($leads as $l) {
                $contacts[] = array(
                    'name'   => $l['name'],
                    'email'  => $l['email'],
                    'mobile' => $l['mobile'],
                    'email_opt_out' => 0,
                    'sms_opt_out'   => 0,
                    'whatsapp_opt_out' => 0,
                );
            }
            return $this->_contacts_to_recipients($contacts, $channel);
        }

        return $this->_contacts_to_recipients($contacts, $channel);
    }

    /**
     * Send one batch of pending recipients; mark campaign completed when done.
     */
    public function process_send_batch($campaign_id, Mailer $mailer, Sms_gateway $sms)
    {
        $camp = $this->find($campaign_id);
        if (!$camp || !in_array($camp['status'], array('scheduled', 'sending'), TRUE)) {
            return 0;
        }

        $ci = &get_instance();
        $batch = (int) $ci->config->item('crm_campaign_batch_size');
        $tpl   = $ci->config->item('crm_sms_campaign_tpl');

        $wa_tpl = NULL;
        $wa_params = array();
        if ($camp['channel'] === 'whatsapp') {
            $ci->load->library('crm_messaging');
            $ci->load->model(array('crm_wa_template_model', 'crm_wa_message_model'));
            $wa_tpl = !empty($camp['wa_template_id']) ? $ci->crm_wa_template_model->find($camp['wa_template_id']) : NULL;
            $wa_cfg = $this->wa_config($camp);
            $wa_params = $wa_cfg['params'];
            if (!$wa_tpl || $wa_tpl['status'] !== 'APPROVED' || !$ci->crm_messaging->can_send_whatsapp()) {
                // Do not burn the recipient list: leave everything pending and stop.
                $this->update($campaign_id, array('status' => 'failed'));
                log_message('error', '[crm_campaign] #'.$campaign_id.' WhatsApp template missing/not approved or WhatsApp not configured.');
                return 0;
            }
        }

        $this->update($campaign_id, array('status' => 'sending'));
        $pending = $this->pending_recipients($campaign_id, $batch);
        $sent = 0;

        foreach ($pending as $r) {
            $ok = FALSE;
            $err = '';
            if ($r['channel'] === 'email' && !empty($r['email'])) {
                $body = nl2br(htmlspecialchars($camp['body'], ENT_QUOTES, 'UTF-8'));
                $ok = $mailer->send($r['email'], $camp['subject'] ?: $camp['name'], $body);
                $err = $mailer->last_error();
            } elseif ($r['channel'] === 'sms' && !empty($r['mobile'])) {
                if ($tpl) {
                    $ok = $sms->send_template($r['mobile'], 'crm_campaign', array('msg' => $camp['body']));
                    ymo_sms_log('crm.campaign', 'crm_campaign', $r['mobile'], $ok, $sms);
                } else {
                    $ok = FALSE;
                    $err = 'CRM SMS template not configured (YMO_TPL_CRM_CAMPAIGN)';
                }
                if (!$ok && !$err) {
                    $err = $sms->last_error();
                }
            } elseif ($r['channel'] === 'whatsapp' && !empty($r['mobile'])) {
                $params = array();
                foreach (array_slice(array_pad($wa_params, (int) $wa_tpl['param_count'], ''), 0, (int) $wa_tpl['param_count']) as $p) {
                    $params[] = $this->_fill_wa_placeholders((string) $p, $r);
                }
                $res = $ci->crm_messaging->send_whatsapp_template(
                    $r['mobile'], $wa_tpl['name'], $wa_tpl['language'], $params,
                    $this->_campaign_wa_header($wa_tpl, $wa_cfg['header_link'])
                );
                $ok = $res['ok'];
                $err = $ok ? '' : ($ci->crm_messaging->last_error() ?: 'WhatsApp send failed');
                $ci->crm_wa_message_model->log(array(
                    'wa_message_id' => $res['message_id'],
                    'phone'         => $r['mobile'],
                    'direction'     => 'out',
                    'kind'          => 'template',
                    'template_name' => $wa_tpl['name'],
                    'body'          => implode(' | ', $params),
                    'context_type'  => 'campaign',
                    'context_id'    => (int) $campaign_id,
                    'status'        => $ok ? 'sent' : 'failed',
                    'error'         => $err ?: NULL,
                ));
                $this->mark_recipient($r['id'], $ok ? 'sent' : 'failed', $err ?: NULL, $res['message_id']);
                if ($ok) { $sent++; }
                continue;
            } else {
                $err = 'Missing destination';
            }
            $this->mark_recipient($r['id'], $ok ? 'sent' : 'failed', $err ?: NULL);
            if ($ok) { $sent++; }
        }

        $remaining = $this->pending_recipients($campaign_id, 1);
        $this->update($campaign_id, array(
            'status' => empty($remaining) ? 'completed' : 'sending',
        ));
        return $sent;
    }

    /**
     * Send this campaign's message to one person (the admin testing it). The
     * campaign stays a draft and no recipient rows are written.
     *
     * @return array<string, array{ok:bool,error:string}> keyed by channel
     */
    public function send_test(array $camp, $name, $mobile, $email, Mailer $mailer, Sms_gateway $sms)
    {
        $ci = &get_instance();
        $r = array('name' => (string) $name, 'mobile' => (string) $mobile, 'email' => (string) $email);
        $out = array();
        $channels = $camp['channel'] === 'both' ? array('email', 'sms') : array($camp['channel']);

        foreach ($channels as $ch) {
            if ($ch === 'email') {
                if ($email === '') { $out['email'] = array('ok' => FALSE, 'error' => 'No email address to test with.'); continue; }
                $ok = $mailer->send($email, '[TEST] '.($camp['subject'] ?: $camp['name']), nl2br(htmlspecialchars($camp['body'], ENT_QUOTES, 'UTF-8')));
                $out['email'] = array('ok' => (bool) $ok, 'error' => $ok ? '' : (string) $mailer->last_error());
            } elseif ($ch === 'sms') {
                if ($mobile === '') { $out['sms'] = array('ok' => FALSE, 'error' => 'No mobile number to test with.'); continue; }
                $ok = $ci->config->item('crm_sms_campaign_tpl') ? $sms->send_template($mobile, 'crm_campaign', array('msg' => $camp['body'])) : FALSE;
                $out['sms'] = array('ok' => (bool) $ok, 'error' => $ok ? '' : ($ci->config->item('crm_sms_campaign_tpl') ? (string) $sms->last_error() : 'CRM SMS template not configured'));
            } elseif ($ch === 'whatsapp') {
                if ($mobile === '') { $out['whatsapp'] = array('ok' => FALSE, 'error' => 'No mobile number to test with.'); continue; }
                $ci->load->library('crm_messaging');
                $ci->load->model(array('crm_wa_template_model', 'crm_wa_message_model'));
                $tpl = !empty($camp['wa_template_id']) ? $ci->crm_wa_template_model->find($camp['wa_template_id']) : NULL;
                if (!$tpl || $tpl['status'] !== 'APPROVED') {
                    $out['whatsapp'] = array('ok' => FALSE, 'error' => 'The template is missing or not approved.');
                    continue;
                }
                $cfg = $this->wa_config($camp);
                $params = array();
                foreach (array_slice(array_pad($cfg['params'], (int) $tpl['param_count'], ''), 0, (int) $tpl['param_count']) as $p) {
                    $params[] = $this->_fill_wa_placeholders((string) $p, $r);
                }
                $res = $ci->crm_messaging->send_whatsapp_template($mobile, $tpl['name'], $tpl['language'], $params,
                    $this->_campaign_wa_header($tpl, $cfg['header_link']));
                $err = $res['ok'] ? '' : ($ci->crm_messaging->last_error() ?: 'WhatsApp send failed');
                $ci->crm_wa_message_model->log(array(
                    'wa_message_id' => $res['message_id'],
                    'phone'         => $mobile,
                    'direction'     => 'out',
                    'kind'          => 'template',
                    'template_name' => $tpl['name'],
                    'body'          => 'TEST: '.implode(' | ', $params),
                    'context_type'  => 'campaign_test',
                    'context_id'    => (int) $camp['id'],
                    'status'        => $res['ok'] ? 'sent' : 'failed',
                    'error'         => $err ?: NULL,
                ));
                $out['whatsapp'] = array('ok' => (bool) $res['ok'], 'error' => $err);
            }
        }
        return $out;
    }

    /** New draft with the same channel, audience and content. */
    public function duplicate(array $camp, $admin_id)
    {
        return $this->create(array(
            'name'           => mb_substr('Copy of '.$camp['name'], 0, 160),
            'channel'        => $camp['channel'],
            'subject'        => $camp['subject'],
            'body'           => $camp['body'],
            'segment_json'   => $camp['segment_json'],
            'wa_template_id' => $camp['wa_template_id'],
            'wa_params_json' => $camp['wa_params_json'],
            'status'         => 'draft',
            'created_by'     => $admin_id ? (int) $admin_id : NULL,
        ));
    }

    /** {name} / {first_name} / {mobile} placeholders in campaign template variables. */
    protected function _fill_wa_placeholders($value, array $recipient)
    {
        $name = trim((string) ($recipient['name'] ?? ''));
        $first = $name !== '' ? strtok($name, ' ') : '';
        $out = strtr($value, array(
            '{name}'       => $name !== '' ? $name : 'there',
            '{first_name}' => $first !== '' ? $first : 'there',
            '{mobile}'     => (string) ($recipient['mobile'] ?? ''),
        ));
        return trim($out) !== '' ? $out : '-';
    }

    /**
     * Decoded wa_params_json: {"params": ["{first_name}", "20% off"], "header_link": "https://…"}
     *
     * @return array{params:string[],header_link:string}
     */
    public function wa_config(array $camp)
    {
        $raw = json_decode((string) ($camp['wa_params_json'] ?? ''), TRUE);
        if (!is_array($raw)) {
            $raw = array();
        }
        $params = isset($raw['params']) && is_array($raw['params']) ? $raw['params'] : array_values(array_filter($raw, 'is_string'));
        return array(
            'params'      => array_map('strval', $params),
            'header_link' => trim((string) ($raw['header_link'] ?? '')),
        );
    }

    /** Media header for a WhatsApp campaign template (needs a public https link). */
    protected function _campaign_wa_header(array $tpl, $link)
    {
        $type = (string) ($tpl['header_type'] ?? '');
        if (!in_array($type, array('image', 'document', 'video'), TRUE) || $link === '') {
            return NULL;
        }
        return array('type' => $type, 'link' => $link);
    }

    /** Last 10 digits of every number that replied STOP / was opted out of WhatsApp. */
    protected function _whatsapp_opted_out_numbers()
    {
        if (!$this->db->field_exists('whatsapp_opt_out', 'crm_contacts')) {
            return array();
        }
        $rows = $this->db->select('mobile')->where('whatsapp_opt_out', 1)->get('crm_contacts')->result_array();
        $out = array();
        foreach ($rows as $r) {
            $d = substr(preg_replace('/\D/', '', (string) $r['mobile']), -10);
            if ($d !== '') {
                $out[$d] = TRUE;
            }
        }
        // Numbers that sent STOP but aren't saved as contacts (e.g. open leads).
        if ($this->db->table_exists('crm_wa_messages')) {
            $words = (array) get_instance()->config->item('crm_whatsapp_opt_out_words');
            if ($words) {
                $rows = $this->db->select('phone')->distinct()
                    ->where('direction', 'in')
                    ->where('LOWER(TRIM(body)) IN ('.implode(',', array_map(array($this->db, 'escape'), $words)).')', NULL, FALSE)
                    ->get('crm_wa_messages')->result_array();
                foreach ($rows as $r) {
                    $d = substr(preg_replace('/\D/', '', (string) $r['phone']), -10);
                    if ($d !== '') {
                        $out[$d] = TRUE;
                    }
                }
            }
        }
        return $out;
    }

    protected function _contacts_to_recipients(array $contacts, $channel)
    {
        $out = array();
        $wa_seen = array();
        $wa_blocked = $channel === 'whatsapp' ? $this->_whatsapp_opted_out_numbers() + $this->_no_whatsapp_numbers() : array();
        foreach ($contacts as $c) {
            if ($channel === 'whatsapp') {
                $key = substr(preg_replace('/\D/', '', (string) ($c['mobile'] ?? '')), -10);
                if (strlen($key) < 10 || !empty($c['whatsapp_opt_out']) || isset($wa_blocked[$key]) || isset($wa_seen[$key])) {
                    continue;
                }
                $wa_seen[$key] = TRUE;
                $out[] = array(
                    'channel' => 'whatsapp',
                    'email'   => $c['email'] ?? '',
                    'mobile'  => $c['mobile'],
                    'name'    => $c['name'],
                );
                continue;
            }
            if (in_array($channel, array('email', 'both'), TRUE) && !empty($c['email']) && empty($c['email_opt_out'])) {
                $out[] = array(
                    'channel' => 'email',
                    'email'   => $c['email'],
                    'mobile'  => $c['mobile'] ?? '',
                    'name'    => $c['name'],
                );
            }
            if (in_array($channel, array('sms', 'both'), TRUE) && !empty($c['mobile']) && empty($c['sms_opt_out'])) {
                $out[] = array(
                    'channel' => 'sms',
                    'email'   => $c['email'] ?? '',
                    'mobile'  => $c['mobile'],
                    'name'    => $c['name'],
                );
            }
        }
        return $out;
    }
}
