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

    // --- One follow-up system (CRM v6) ----------------------------------

    /** The pending follow-up task for a lead, if any. */
    public function pending_for_lead($lead_id)
    {
        return $this->db
            ->where('lead_id', (int) $lead_id)
            ->where('status', 'pending')
            ->order_by('due_at', 'ASC')
            ->limit(1)
            ->get(self::TABLE)
            ->row_array();
    }

    /**
     * Create or move the single follow-up for a lead and mirror the date on
     * crm_leads.next_follow_up_at. $due = NULL clears it.
     *
     * @return int|null Task id
     */
    public function set_lead_followup($lead_id, $due, $assignee_id, $created_by = NULL, $note = NULL)
    {
        $lead = $this->db->get_where('crm_leads', array('id' => (int) $lead_id))->row_array();
        if (!$lead) {
            return NULL;
        }
        $existing = $this->pending_for_lead($lead_id);

        if (!$due) {
            if ($existing) {
                $this->mark_skipped((int) $existing['id']);
            }
            $this->db->where('id', (int) $lead_id)->update('crm_leads', array('next_follow_up_at' => NULL));
            return NULL;
        }

        $due = date('Y-m-d H:i:s', is_numeric($due) ? (int) $due : strtotime(str_replace('T', ' ', (string) $due)));
        $assignee_id = (int) ($assignee_id ?: ($lead['assigned_to'] ?: $created_by));
        if ($assignee_id <= 0) {
            return NULL;
        }

        if ($existing) {
            $patch = array('due_at' => $due, 'assignee_admin_id' => $assignee_id, 'reminder_sent_at' => NULL);
            if ($note !== NULL && $note !== '') {
                $patch['notes'] = $note;
            }
            $this->update((int) $existing['id'], $patch);
            $task_id = (int) $existing['id'];
        } else {
            $task_id = $this->create(array(
                'title'               => 'Follow up: '.$lead['name'],
                'due_at'              => $due,
                'status'              => 'pending',
                'priority'            => (int) $lead['priority'],
                'lead_id'             => (int) $lead_id,
                'contact_id'          => $lead['converted_contact_id'] ? (int) $lead['converted_contact_id'] : NULL,
                'assignee_admin_id'   => $assignee_id,
                'created_by_admin_id' => $created_by ? (int) $created_by : NULL,
                'notes'               => $note !== '' ? $note : NULL,
            ));
        }
        $this->db->where('id', (int) $lead_id)->update('crm_leads', array('next_follow_up_at' => $due));
        return $task_id;
    }

    /** Lead booked/lost: stop chasing it. */
    public function close_lead_followups($lead_id)
    {
        $this->db->where('lead_id', (int) $lead_id)->where('status', 'pending')
            ->update(self::TABLE, array('status' => 'skipped'));
        $this->db->where('id', (int) $lead_id)->update('crm_leads', array('next_follow_up_at' => NULL));
    }

    /** After a task is done/skipped/moved, keep the lead's date in step. */
    public function sync_lead_date($task_id)
    {
        $task = $this->find($task_id);
        if (!$task || empty($task['lead_id'])) {
            return;
        }
        $next = $this->pending_for_lead((int) $task['lead_id']);
        $this->db->where('id', (int) $task['lead_id'])->update('crm_leads', array(
            'next_follow_up_at' => $next ? $next['due_at'] : NULL,
        ));
    }

    /**
     * "My day": pending follow-ups grouped as overdue / today / upcoming.
     *
     * @return array{overdue:array,today:array,upcoming:array}
     */
    public function my_day($admin_id = NULL, $upcoming_days = 7)
    {
        $today_start = date('Y-m-d 00:00:00');
        $today_end   = date('Y-m-d 23:59:59');
        $until       = date('Y-m-d 23:59:59', strtotime('+'.(int) $upcoming_days.' days'));

        $this->db
            ->select('t.*, a.name AS assignee_name, l.name AS lead_name, l.mobile AS lead_mobile, l.stage AS lead_stage,
                      c.name AS contact_name, c.mobile AS contact_mobile', FALSE)
            ->from(self::TABLE.' t')
            ->join('admin_users a', 'a.id = t.assignee_admin_id', 'left')
            ->join('crm_leads l', 'l.id = t.lead_id', 'left')
            ->join('crm_contacts c', 'c.id = t.contact_id', 'left')
            ->where('t.status', 'pending')
            ->where('t.due_at <=', $until)
            ->order_by('t.due_at', 'ASC')
            ->limit(200);
        if ($admin_id) {
            $this->db->where('t.assignee_admin_id', (int) $admin_id);
        }
        $rows = $this->db->get()->result_array();

        $out = array('overdue' => array(), 'today' => array(), 'upcoming' => array());
        foreach ($rows as $r) {
            if ($r['due_at'] < $today_start) {
                $out['overdue'][] = $r;
            } elseif ($r['due_at'] <= $today_end) {
                $out['today'][] = $r;
            } else {
                $out['upcoming'][] = $r;
            }
        }
        return $out;
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
