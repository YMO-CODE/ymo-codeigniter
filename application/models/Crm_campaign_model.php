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
        $wa_blocked = $channel === 'whatsapp' ? $this->_whatsapp_opted_out_numbers() : array();
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
