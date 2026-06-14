<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'crm_report_model', 'crm_lead_model', 'crm_contact_model', 'crm_integration_model',
        ));
    }

    public function index()
    {
        $this->require_perm('reports.view');
        $from = $this->input->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to   = $this->input->get('to') ?: date('Y-m-d');

        $this->render('admin/reports/index', array(
            'title'        => 'CRM Reports',
            'from'         => $from,
            'to'           => $to,
            'sources'      => $this->crm_report_model->lead_source_report($from, $to),
            'conversion'   => $this->crm_report_model->conversion_summary($from, $to),
            'followups'    => $this->crm_report_model->followup_report($from, $to),
            'campaigns'    => $this->crm_report_model->campaign_report(),
            'team'         => $this->crm_report_model->team_performance($from, $to),
            'locality'     => $this->crm_report_model->locality_report($from, $to),
            'revenue'      => $this->crm_report_model->revenue_summary($from, $to),
            'service_due'  => $this->crm_report_model->service_due_report(25),
            'webhooks'     => crm_can('integrations.manage')
                ? $this->crm_integration_model->recent(15) : array(),
        ));
    }

    public function export_leads()
    {
        $this->require_perm('reports.view');
        $rows = $this->crm_lead_model->export_all();
        $data = array();
        foreach ($rows as $r) {
            $data[] = array(
                $r['name'], $r['mobile'], $r['email'], $r['source_label'],
                crm_lead_stage_label($r['stage']), $r['status'], $r['created_at'],
            );
        }
        crm_csv_download('crm-leads-'.date('Y-m-d').'.csv',
            array('Name', 'Mobile', 'Email', 'Source', 'Stage', 'Status', 'Created'),
            $data
        );
    }

    public function export_service_due()
    {
        $this->require_perm('reports.view');
        $rows = $this->crm_report_model->service_due_report(500);
        $data = array();
        foreach ($rows as $r) {
            $data[] = array($r['name'], $r['mobile'], $r['company'], $r['due_at']);
        }
        crm_csv_download('crm-service-due-'.date('Y-m-d').'.csv',
            array('Name', 'Mobile', 'Workshop', 'Due'),
            $data
        );
    }

    public function export_revenue()
    {
        $this->require_perm('reports.view');
        $from = $this->input->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to   = $this->input->get('to') ?: date('Y-m-d');
        $rev = $this->crm_report_model->revenue_summary($from, $to);
        $data = array();
        foreach ($rev['by_workshop'] as $r) {
            $data[] = array($r['locality'], $r['revenue'], $r['invoices']);
        }
        crm_csv_download('crm-revenue-'.date('Y-m-d').'.csv',
            array('Workshop', 'Revenue', 'Invoices'),
            $data
        );
    }
}
