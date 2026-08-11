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

    public function due_scheduled($limit = 5)
    {
        return $this->db
            ->where('status', 'scheduled')
            ->where('scheduled_at <=', date('Y-m-d H:i:s'))
            ->order_by('scheduled_at', 'ASC')
            ->limit((int) $limit)
            ->get(self::TABLE)
            ->result_array();
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

    public function mark_recipient($id, $status, $error = NULL)
    {
        $patch = array('status' => $status);
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

    protected function _contacts_to_recipients(array $contacts, $channel)
    {
        $out = array();
        foreach ($contacts as $c) {
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
