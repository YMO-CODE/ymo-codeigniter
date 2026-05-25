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

        $summary = $this->booking_model->counts_summary();
        $summary['reminders_week']  = $this->reminder_model->due_this_week_count();
        $summary['recent_bookings'] = $this->booking_model->paginate(array(), 8, 0)['rows'];
        $summary['crm'] = $this->crm_report_model->dashboard_snapshot(
            isset($this->admin['id']) ? (int) $this->admin['id'] : NULL
        );

        $this->render('admin/dashboard', array(
            'title'   => 'Dashboard',
            'summary' => $summary,
        ));
    }
}
