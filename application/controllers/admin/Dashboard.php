<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('booking_model', 'reminder_model', 'user_model', 'crm_report_model'));
    }

    public function index()
    {
        if ($this->db->table_exists('crm_permissions') && function_exists('crm_can')) {
            if (!crm_can('dashboard.view')) {
                if (crm_can('bookings.view')) {
                    redirect(admin_url('bookings'));
                }
                show_error('You do not have permission to access this area.', 403, 'Forbidden');
            }
        }

        $who      = $this->input->get('who') === 'all' ? 'all' : 'me';
        $admin_id = $who === 'me' && isset($this->admin['id']) ? (int) $this->admin['id'] : NULL;

        $summary = $this->booking_model->counts_summary();
        $summary['reminders_week'] = $this->reminder_model->due_this_week_count();
        $summary['board']          = $this->booking_model->work_board();
        $summary['crm']            = $this->crm_report_model->dashboard_snapshot($admin_id);

        $can_wa = function_exists('crm_can') && (crm_can('campaigns.view') || crm_can('settings.manage'));
        $wa_balance = NULL;
        if ($can_wa) {
            $this->load->library('crm_wa_balance');
            $wa_balance = $this->crm_wa_balance->summary();
        }

        $this->render('admin/dashboard', array(
            'wa_balance'     => $wa_balance,
            'title'    => 'Today',
            'summary'  => $summary,
            'who'      => $who,
            'can_book' => !function_exists('crm_can') || crm_can('bookings.edit'),
            'can_task' => function_exists('crm_can') && crm_can('tasks.edit'),
        ));
    }
}
