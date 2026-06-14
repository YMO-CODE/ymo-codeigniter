<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_report_model extends CI_Model
{
    public function lead_source_report($from = NULL, $to = NULL)
    {
        $this->db
            ->select('s.label, s.slug, COUNT(l.id) AS total,
                      SUM(CASE WHEN l.status = "converted" THEN 1 ELSE 0 END) AS converted', FALSE)
            ->from('crm_leads l')
            ->join('crm_lead_sources s', 's.id = l.source_id')
            ->where('l.deleted_at IS NULL', NULL, FALSE);
        $this->_date_range('l.created_at', $from, $to);
        return $this->db->group_by('s.id')->order_by('total', 'DESC')->get()->result_array();
    }

    public function conversion_summary($from = NULL, $to = NULL)
    {
        $this->db->from('crm_leads l')->where('l.deleted_at IS NULL', NULL, FALSE);
        $this->_date_range('l.created_at', $from, $to);
        $total = (int) $this->db->count_all_results();

        $this->db->from('crm_leads l')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->where('l.status', 'converted');
        $this->_date_range('l.created_at', $from, $to);
        $converted = (int) $this->db->count_all_results();

        return array(
            'total'     => $total,
            'converted' => $converted,
            'rate'      => $total > 0 ? round($converted / $total * 100, 1) : 0,
        );
    }

    public function locality_report($from = NULL, $to = NULL)
    {
        $customers = $this->db
            ->select('COALESCE(NULLIF(TRIM(c.company), ""), "(No workshop)") AS locality, COUNT(*) AS customers', FALSE)
            ->from('crm_contacts c')
            ->where('c.deleted_at IS NULL', NULL, FALSE)
            ->group_by('locality')
            ->order_by('customers', 'DESC')
            ->get()
            ->result_array();

        $leads = $this->db
            ->select('COALESCE(NULLIF(TRIM(l.company), ""), "(No workshop)") AS locality, COUNT(*) AS leads', FALSE)
            ->from('crm_leads l')
            ->where('l.deleted_at IS NULL', NULL, FALSE);
        $this->_date_range('l.created_at', $from, $to);
        $leads = $this->db->group_by('locality')->order_by('leads', 'DESC')->get()->result_array();

        $map = array();
        foreach ($customers as $r) {
            $map[$r['locality']] = array('locality' => $r['locality'], 'customers' => (int) $r['customers'], 'leads' => 0);
        }
        foreach ($leads as $r) {
            if (!isset($map[$r['locality']])) {
                $map[$r['locality']] = array('locality' => $r['locality'], 'customers' => 0, 'leads' => 0);
            }
            $map[$r['locality']]['leads'] = (int) $r['leads'];
        }
        usort($map, function ($a, $b) {
            return ($b['customers'] + $b['leads']) <=> ($a['customers'] + $a['leads']);
        });
        return array_values($map);
    }

    public function revenue_summary($from = NULL, $to = NULL)
    {
        if (!$this->db->table_exists('booking_invoices')) {
            return array('total' => 0, 'count' => 0, 'by_workshop' => array());
        }
        $this->db
            ->select('SUM(i.grand_total) AS total, COUNT(*) AS invoice_count', FALSE)
            ->from('booking_invoices i')
            ->join('bookings b', 'b.id = i.booking_id', 'inner');
        $this->_date_range('i.created_at', $from, $to);
        $row = $this->db->get()->row_array();

        $this->db
            ->select('COALESCE(NULLIF(TRIM(c.company), ""), "(No workshop)") AS locality,
                      SUM(i.grand_total) AS revenue, COUNT(*) AS invoices', FALSE)
            ->from('booking_invoices i')
            ->join('bookings b', 'b.id = i.booking_id', 'inner')
            ->join('crm_contacts c', 'c.user_id = b.user_id AND c.deleted_at IS NULL', 'left');
        $this->_date_range('i.created_at', $from, $to);
        $by_workshop = $this->db->group_by('locality')->order_by('revenue', 'DESC')->get()->result_array();

        return array(
            'total'       => (float) ($row['total'] ?? 0),
            'count'       => (int) ($row['invoice_count'] ?? 0),
            'by_workshop' => $by_workshop,
        );
    }

    public function service_due_report($limit = 50)
    {
        $this->load->model('crm_contact_model');
        return $this->crm_contact_model->list_service_due($limit);
    }

    public function followup_report($from = NULL, $to = NULL)
    {
        $this->db->from('crm_tasks t');
        $this->_date_range('t.created_at', $from, $to);
        $created = (int) $this->db->count_all_results();

        $this->db->from('crm_tasks t')->where('t.status', 'done');
        $this->_date_range('t.completed_at', $from, $to);
        $done = (int) $this->db->count_all_results();

        $overdue = (int) $this->db
            ->where('status', 'pending')
            ->where('due_at <', date('Y-m-d H:i:s'))
            ->count_all_results('crm_tasks');

        return array('created' => $created, 'done' => $done, 'overdue' => $overdue);
    }

    public function campaign_report()
    {
        return $this->db
            ->select('c.id, c.name, c.channel, c.status, c.scheduled_at, c.created_at,
                      SUM(CASE WHEN r.status = "sent" THEN 1 ELSE 0 END) AS sent,
                      SUM(CASE WHEN r.status = "failed" THEN 1 ELSE 0 END) AS failed,
                      COUNT(r.id) AS total_recipients', FALSE)
            ->from('crm_campaigns c')
            ->join('crm_campaign_recipients r', 'r.campaign_id = c.id', 'left')
            ->group_by('c.id')
            ->order_by('c.created_at', 'DESC')
            ->limit(20)
            ->get()
            ->result_array();
    }

    public function team_performance($from = NULL, $to = NULL)
    {
        $admins = $this->db
            ->select('id, name')
            ->where('is_active', 1)
            ->get('admin_users')
            ->result_array();
        $out = array();
        foreach ($admins as $a) {
            $this->db->from('crm_leads l')
                ->where('l.assigned_to', (int) $a['id'])
                ->where('l.deleted_at IS NULL', NULL, FALSE);
            $this->_date_range('l.created_at', $from, $to);
            $leads = (int) $this->db->count_all_results();

            $this->db->from('crm_leads l')
                ->where('l.assigned_to', (int) $a['id'])
                ->where('l.status', 'converted')
                ->where('l.deleted_at IS NULL', NULL, FALSE);
            $this->_date_range('l.created_at', $from, $to);
            $converted = (int) $this->db->count_all_results();

            $this->db->from('crm_tasks t')
                ->where('t.assignee_admin_id', (int) $a['id'])
                ->where('t.status', 'done');
            $this->_date_range('t.completed_at', $from, $to);
            $tasks_done = (int) $this->db->count_all_results();

            $out[] = array(
                'admin_id'   => (int) $a['id'],
                'name'       => $a['name'],
                'leads'      => $leads,
                'converted'  => $converted,
                'tasks_done' => $tasks_done,
            );
        }
        usort($out, function ($x, $y) { return $y['converted'] <=> $x['converted']; });
        return $out;
    }

    public function dashboard_snapshot($admin_id = NULL)
    {
        if (!$this->db->table_exists('crm_leads')) {
            return NULL;
        }
        $this->load->model(array('crm_task_model', 'crm_lead_model', 'crm_contact_model'));

        $tasks_today = $this->crm_task_model->paginate(array(
            'status'   => 'pending',
            'due'      => 'today',
            'mine'     => $admin_id ? 1 : 0,
            'admin_id' => $admin_id,
        ), 8, 0)['rows'];

        return array(
            'open_leads'       => (int) $this->db->where('status', 'open')->where('deleted_at IS NULL', NULL, FALSE)->count_all_results('crm_leads'),
            'tasks_today'      => $this->crm_task_model->due_today_count($admin_id),
            'tasks_overdue'    => $this->crm_task_model->overdue_count($admin_id),
            'hot_leads'        => $this->crm_lead_model->count_hot_open($admin_id),
            'hot_leads_list'   => $this->crm_lead_model->list_by_stage_bucket('hot_lead', 8, $admin_id),
            'week_callbacks'   => $this->crm_lead_model->list_by_stage_bucket('followup_next_week', 8, $admin_id),
            'month_callbacks'  => $this->crm_lead_model->list_by_stage_bucket('followup_next_month', 8, $admin_id),
            'tasks_today_list' => $tasks_today,
            'service_due'      => $this->crm_contact_model->list_service_due(8),
            'service_due_count'=> $this->crm_contact_model->count_service_due(),
        );
    }

    protected function _date_range($column, $from, $to)
    {
        if ($from) {
            $this->db->where($column.' >=', $from.' 00:00:00');
        }
        if ($to) {
            $this->db->where($column.' <=', $to.' 23:59:59');
        }
    }
}
