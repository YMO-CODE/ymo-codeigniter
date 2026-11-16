<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp message log (outbound notifications / campaign sends / booking
 * photo updates, plus inbound customer messages) with delivery status from
 * Meta webhooks.
 */
class Crm_wa_message_model extends CI_Model
{
    const TABLE = 'crm_wa_messages';

    /** @var bool|null */
    protected $ready = NULL;

    public function table_ready()
    {
        if ($this->ready === NULL) {
            $this->ready = $this->db->table_exists(self::TABLE);
        }
        return $this->ready;
    }

    /**
     * @param array $row phone, direction, kind, template_name, body, media_path,
     *                   context_type, context_id, status, error, wa_message_id, admin_id
     */
    public function log(array $row)
    {
        if (!$this->table_ready()) {
            return 0;
        }
        $allowed = array('wa_message_id', 'phone', 'direction', 'kind', 'template_name', 'body',
            'media_path', 'context_type', 'context_id', 'status', 'error', 'admin_id');
        $data = array_intersect_key($row, array_flip($allowed));
        $data['phone'] = crm_whatsapp_e164($data['phone'] ?? '');
        if (isset($data['error']) && $data['error'] !== NULL) {
            $data['error'] = substr((string) $data['error'], 0, 500);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        $this->db->insert(self::TABLE, $data);
        return (int) $this->db->insert_id();
    }

    /** Record a webhook delivery receipt; never downgrades read -> delivered. */
    public function apply_status($wa_message_id, $status, $error = '')
    {
        if (!$this->table_ready() || $wa_message_id === '') {
            return 0;
        }
        $rank = array('queued' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4);
        if (!isset($rank[$status])) {
            return 0;
        }
        $row = $this->db->get_where(self::TABLE, array('wa_message_id' => $wa_message_id))->row_array();
        if (!$row) {
            return 0;
        }
        $current = $rank[$row['status']] ?? 0;
        if ($status !== 'failed' && $current >= $rank[$status]) {
            return 0;
        }
        $patch = array('status' => $status, 'updated_at' => date('Y-m-d H:i:s'));
        if ($error !== '') {
            $patch['error'] = substr($error, 0, 500);
        }
        $this->db->where('id', (int) $row['id'])->update(self::TABLE, $patch);
        return 1;
    }

    /** Timestamp of the customer's latest inbound message (24h free-form window). */
    public function last_inbound_at($phone)
    {
        if (!$this->table_ready()) {
            return NULL;
        }
        $row = $this->db
            ->select('created_at')
            ->where('phone', crm_whatsapp_e164($phone))
            ->where('direction', 'in')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get(self::TABLE)
            ->row_array();
        return $row ? $row['created_at'] : NULL;
    }

    public function in_service_window($phone)
    {
        $at = $this->last_inbound_at($phone);
        return $at && strtotime($at) > time() - 23 * 3600 - 50 * 60;
    }

    public function find($id)
    {
        if (!$this->table_ready()) {
            return NULL;
        }
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    /**
     * Thread for a booking: everything sent in that booking's context plus
     * the customer's inbound messages since the booking was created.
     */
    public function thread_for_booking($booking_id, $phone, $since, $limit = 50)
    {
        if (!$this->table_ready()) {
            return array();
        }
        $phone = crm_whatsapp_e164($phone);
        $this->db->group_start()
                ->group_start()
                    ->where('context_type', 'booking')
                    ->where('context_id', (int) $booking_id)
                ->group_end();
        if ($phone !== '') {
            $this->db->or_group_start()
                    ->where('phone', $phone)
                    ->where('direction', 'in')
                    ->where('created_at >=', $since ?: date('Y-m-d H:i:s', strtotime('-30 days')))
                ->group_end();
        }
        $this->db->group_end();
        return $this->db
            ->order_by('id', 'DESC')
            ->limit((int) $limit)
            ->get(self::TABLE)
            ->result_array();
    }
}
