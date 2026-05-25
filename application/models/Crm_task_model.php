<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_task_model extends CI_Model
{
    const TABLE = 'crm_tasks';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_detailed($id)
    {
        return $this->db
            ->select('t.*, a.name AS assignee_name, l.name AS lead_name, c.name AS contact_name', FALSE)
            ->from(self::TABLE.' t')
            ->join('admin_users a', 'a.id = t.assignee_admin_id', 'left')
            ->join('crm_leads l', 'l.id = t.lead_id', 'left')
            ->join('crm_contacts c', 'c.id = t.contact_id', 'left')
            ->where('t.id', (int) $id)
            ->get()
            ->row_array();
    }

    public function paginate(array $filters, $limit, $offset)
    {
        $this->_apply_filters($filters);
        $rows = $this->db
            ->select('t.*, a.name AS assignee_name, l.name AS lead_name, c.name AS contact_name', FALSE)
            ->from(self::TABLE.' t')
            ->join('admin_users a', 'a.id = t.assignee_admin_id', 'left')
            ->join('crm_leads l', 'l.id = t.lead_id', 'left')
            ->join('crm_contacts c', 'c.id = t.contact_id', 'left')
            ->order_by('t.due_at', 'ASC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();

        $this->_apply_filters($filters);
        $total = (int) $this->db->count_all_results(self::TABLE.' t');

        return array('rows' => $rows, 'total' => $total);
    }

    public function due_today_count($admin_id = NULL)
    {
        $this->db->where('status', 'pending')
            ->where('DATE(due_at) = CURDATE()', NULL, FALSE);
        if ($admin_id) {
            $this->db->where('assignee_admin_id', (int) $admin_id);
        }
        return (int) $this->db->count_all_results(self::TABLE);
    }

    public function overdue_count($admin_id = NULL)
    {
        $this->db->where('status', 'pending')
            ->where('due_at <', date('Y-m-d H:i:s'));
        if ($admin_id) {
            $this->db->where('assignee_admin_id', (int) $admin_id);
        }
        return (int) $this->db->count_all_results(self::TABLE);
    }

    /** Pending tasks due within reminder window, not yet reminded. */
    public function due_for_reminder($hours_ahead, $limit = 50)
    {
        $now = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', time() + (int) $hours_ahead * 3600);
        return $this->db
            ->select('t.*, a.email AS assignee_email, a.name AS assignee_name', FALSE)
            ->from(self::TABLE.' t')
            ->join('admin_users a', 'a.id = t.assignee_admin_id', 'left')
            ->where('t.status', 'pending')
            ->where('t.due_at >=', $now)
            ->where('t.due_at <=', $until)
            ->where('t.reminder_sent_at IS NULL', NULL, FALSE)
            ->order_by('t.due_at', 'ASC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    public function mark_reminder_sent($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'reminder_sent_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $payload)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, $payload);
    }

    public function mark_done($id)
    {
        $this->update($id, array(
            'status'       => 'done',
            'completed_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function mark_skipped($id)
    {
        $this->update($id, array('status' => 'skipped'));
    }

    protected function _apply_filters(array $filters)
    {
        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        if (!empty($filters['assignee_admin_id'])) {
            $this->db->where('t.assignee_admin_id', (int) $filters['assignee_admin_id']);
        }
        if (!empty($filters['mine']) && !empty($filters['admin_id'])) {
            $this->db->where('t.assignee_admin_id', (int) $filters['admin_id']);
        }
        if (!empty($filters['due'])) {
            if ($filters['due'] === 'today') {
                $this->db->where('DATE(t.due_at) = CURDATE()', NULL, FALSE);
            } elseif ($filters['due'] === 'overdue') {
                $this->db->where('t.due_at <', date('Y-m-d H:i:s'));
                $this->db->where('t.status', 'pending');
            }
        }
        if (!empty($filters['lead_id'])) {
            $this->db->where('t.lead_id', (int) $filters['lead_id']);
        }
        if (!empty($filters['contact_id'])) {
            $this->db->where('t.contact_id', (int) $filters['contact_id']);
        }
    }
}
