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
            'can_wa_balance' => function_exists('crm_can') && crm_can('settings.manage'),
            'title'    => 'Today',
            'summary'  => $summary,
            'who'      => $who,
            'can_book' => !function_exists('crm_can') || crm_can('bookings.edit'),
            'can_task' => function_exists('crm_can') && crm_can('tasks.edit'),
        ));
    }

    /** POST dashboard/wa-balance — enter the balance Meta shows, or a top-up. */
    public function wa_balance()
    {
        if (!function_exists('crm_can') || !crm_can('settings.manage') || $this->input->method() !== 'post') {
            show_error('You do not have permission to do that.', 403, 'Forbidden');
        }
        $amount = (float) str_replace(array(',', '₹', ' '), '', (string) $this->input->post('amount'));
        $mode = $this->input->post('mode') === 'topup' ? 'topup' : 'set';
        if ($amount < 0 || $amount > 10000000 || ($mode === 'topup' && $amount <= 0)) {
            $this->flash('error', 'Enter an amount in rupees.');
            redirect(admin_url('dashboard'));
        }
        $this->load->library(array('crm_wa_balance', 'audit'));
        if ($mode === 'topup') {
            if (!$this->crm_wa_balance->add_topup($amount, $this->admin['id'])) {
                $this->flash('warning', "Couldn't read spend from Meta just now, so the top-up wasn't added. Use Set balance with the Available funds figure from Meta Billing instead.");
                redirect(admin_url('dashboard'));
            }
            $this->flash('success', 'Recorded a top-up of ₹'.number_format($amount, 2).'.');
        } else {
            $this->crm_wa_balance->set_balance($amount, $this->admin['id']);
            $this->flash('success', 'WhatsApp balance set to ₹'.number_format($amount, 2).'. Spend from now on is subtracted automatically.');
        }
        $this->audit->log('admin', $this->admin['id'], 'crm.wa_balance.'.$mode, 'settings', 0, array('amount' => $amount));
        redirect(admin_url('dashboard'));
    }
}
