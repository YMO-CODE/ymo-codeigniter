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

    // --- Inbox -------------------------------------------------------------

    /** Is the v7 read-tracking column there? (Inbox still lists messages without it.) */
    public function inbox_ready()
    {
        static $ok = NULL;
        if ($ok === NULL) {
            $ok = $this->table_ready() && $this->db->field_exists('read_at', self::TABLE);
        }
        return $ok;
    }

    public function unread_count()
    {
        if (!$this->inbox_ready()) {
            return 0;
        }
        return (int) $this->db->from(self::TABLE)->where('direction', 'in')
            ->where('read_at IS NULL', NULL, FALSE)->count_all_results();
    }

    /**
     * One row per customer who has written to us, newest activity first.
     *
     * @return array{rows: array, total: int}
     */
    public function conversations($only_unread = FALSE, $q = '', $limit = 30, $offset = 0)
    {
        if (!$this->table_ready()) {
            return array('rows' => array(), 'total' => 0);
        }
        $unread_sql = $this->inbox_ready() ? "SUM(m.direction = 'in' AND m.read_at IS NULL)" : '0';
        $build = function () use ($unread_sql, $only_unread, $q) {
            $this->db->select("m.phone, MAX(m.id) AS last_id, MAX(m.created_at) AS last_at, $unread_sql AS unread, MAX(CASE WHEN m.direction = 'in' THEN m.created_at END) AS last_in", FALSE)
                ->from(self::TABLE.' m')
                ->group_by('m.phone')
                ->having('last_in IS NOT NULL', NULL, FALSE);
            if ($only_unread) {
                $this->db->having('unread > 0', NULL, FALSE);
            }
            $digits = preg_replace('/\D+/', '', (string) $q);
            if ($digits !== '') {
                $this->db->like('m.phone', $digits);
            }
        };
        $build();
        $total = $this->db->count_all_results('', TRUE);
        $build();
        // count_all_results on a grouped query counts groups via a subquery in CI3.
        $rows = $this->db->order_by('last_at', 'DESC')->order_by('last_id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result_array();
        if (!$rows) {
            return array('rows' => array(), 'total' => (int) $total);
        }
        $last = array();
        foreach ($this->db->where_in('id', array_column($rows, 'last_id'))->get(self::TABLE)->result_array() as $m) {
            $last[(int) $m['id']] = $m;
        }
        foreach ($rows as &$r) {
            $r['last'] = $last[(int) $r['last_id']] ?? NULL;
            $r['unread'] = (int) $r['unread'];
        }
        return array('rows' => $rows, 'total' => (int) $total);
    }

    /** The whole conversation with one number, oldest first (latest $limit messages). */
    public function thread($phone, $limit = 150)
    {
        if (!$this->table_ready()) {
            return array();
        }
        $rows = $this->db->where('phone', crm_whatsapp_e164($phone))
            ->order_by('id', 'DESC')->limit((int) $limit)->get(self::TABLE)->result_array();
        return array_reverse($rows);
    }

    /**
     * Full text of a template message as the customer saw it. The log keeps only
     * the template name and its values ("Shilpa", or "booking_status: YMO-1 | In Progress");
     * the approved wording comes from the synced template. NULL when unknown.
     */
    public function render_template(array $m)
    {
        static $tpl = array();
        if (($m['kind'] ?? '') !== 'template' || empty($m['template_name'])) {
            return NULL;
        }
        $name = (string) $m['template_name'];
        if (!array_key_exists($name, $tpl)) {
            $tpl[$name] = $this->db->table_exists('crm_wa_templates')
                ? $this->db->select('body_text')->where('name', $name)->order_by('id', 'ASC')->limit(1)->get('crm_wa_templates')->row_array()
                : NULL;
        }
        if (empty($tpl[$name]['body_text'])) {
            return NULL;
        }
        $raw = (string) $m['body'];
        $raw = preg_replace('/^TEST: /', '', $raw);
        $raw = preg_replace('/^[a-z_]+: /', '', $raw); // booking updates log "event: values"
        $params = $raw === '' ? array() : explode(' | ', $raw);
        return preg_replace_callback('/\{\{(\d+)\}\}/', function ($x) use ($params) {
            $i = (int) $x[1] - 1;
            return isset($params[$i]) ? $params[$i] : $x[0];
        }, (string) $tpl[$name]['body_text']);
    }

    public function mark_read($phone, $admin_id)
    {
        if (!$this->inbox_ready()) {
            return 0;
        }
        $this->db->where('phone', crm_whatsapp_e164($phone))->where('direction', 'in')
            ->where('read_at IS NULL', NULL, FALSE)
            ->update(self::TABLE, array('read_at' => date('Y-m-d H:i:s'), 'read_by' => $admin_id ? (int) $admin_id : NULL));
        return $this->db->affected_rows();
    }

    public function mark_all_read($admin_id)
    {
        if (!$this->inbox_ready()) {
            return 0;
        }
        $this->db->where('direction', 'in')->where('read_at IS NULL', NULL, FALSE)
            ->update(self::TABLE, array('read_at' => date('Y-m-d H:i:s'), 'read_by' => $admin_id ? (int) $admin_id : NULL));
        return $this->db->affected_rows();
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
