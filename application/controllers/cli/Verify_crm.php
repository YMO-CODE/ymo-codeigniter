<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRM v3 automated verification (mirrors UI checklist).
 *
 *   php public/index.php cli/verify_crm v3
 */
class Verify_crm extends CI_Controller
{
    /** @var int */
    private $passed = 0;

    /** @var int */
    private $failed = 0;

    /** @var string[] */
    private $failures = array();

    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        $this->load->helper('crm');
    }

    public function v3()
    {
        echo "CRM v3 verification\n";
        echo str_repeat('=', 50) . "\n\n";

        $this->_section('1. Navigation and routes');
        $this->_check_nav_and_routes();

        $this->_section('2. Lead pipeline stages');
        $this->_check_stage_logic();
        $this->_check_pipeline_views();

        $this->_section('3. Customers');
        $this->_check_customers();

        $this->_section('4. Online accounts');
        $this->_check_online_accounts();

        $this->_section('5. Dashboard CRM panel');
        $this->_check_dashboard();

        $this->_section('6. Reports');
        $this->_check_reports();

        $this->_section('7. Database (migration)');
        $this->_check_database();

        $this->_section('8. Webhook payload parsers');
        $this->_check_webhook_helpers();

        $this->_check_two_way_chat();

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "PASSED: {$this->passed}  FAILED: {$this->failed}\n";
        if ($this->failures) {
            echo "\nFailures:\n";
            foreach ($this->failures as $f) {
                echo "  - {$f}\n";
            }
            exit(1);
        }
        echo "All checks passed.\n";
        exit(0);
    }

    private function _section($title)
    {
        echo "\n{$title}\n" . str_repeat('-', strlen($title)) . "\n";
    }

    private function _pass($msg)
    {
        $this->passed++;
        echo "  [OK] {$msg}\n";
    }

    private function _fail($msg)
    {
        $this->failed++;
        $this->failures[] = $msg;
        echo "  [FAIL] {$msg}\n";
    }

    private function _assert($cond, $msg)
    {
        if ($cond) {
            $this->_pass($msg);
        } else {
            $this->_fail($msg);
        }
    }

    private function _file_contains($path, $needle, $label)
    {
        $full = FCPATH . '../' . ltrim($path, '/');
        if (!is_readable($full)) {
            $this->_fail("{$label}: file missing ({$path})");
            return FALSE;
        }
        $ok = strpos(file_get_contents($full), $needle) !== FALSE;
        $this->_assert($ok, $label);
        return $ok;
    }

    private function _check_nav_and_routes()
    {
        $nav = FCPATH . '../application/views/layout/partials/admin_nav.php';
        $crm = FCPATH . '../application/views/layout/partials/admin_crm_nav.php';
        $routes = FCPATH . '../application/config/routes.php';

        foreach (array(
            array($nav, 'Pipeline', 'Nav: Pipeline link'),
            array($nav, "admin_url('customers')", 'Nav: Customers link'),
            array($nav, "admin_url('online-accounts')", 'Nav: Online accounts link'),
            array($nav, 'source=cold_call', 'Nav: Cold calling shortcut'),
            array($nav, 'source=offline_marketing', 'Nav: Offline marketing shortcut'),
            array($nav, 'source=instagram', 'Nav: Instagram shortcut'),
            array($nav, 'source=referral', 'Nav: Referral shortcut'),
            array($crm, "admin_url('reports')", 'Nav: Reports link'),
            array($crm, "admin_url('tasks')", 'Nav: Follow-ups link'),
        ) as $row) {
            $this->_file_contains($row[0], $row[1], $row[2]);
        }

        $routes_content = file_get_contents($routes);
        foreach (array(
            "customers'",
            "online-accounts'",
            "leads/pipeline'",
            "contacts'",
            "reports/export/revenue'",
            "reports/export/service-due'",
        ) as $route_key) {
            $this->_assert(strpos($routes_content, $route_key) !== FALSE, "Route defined: {$route_key}");
        }

        $this->_file_contains(
            'application/views/admin/contacts/index.php',
            'New customer',
            'Customers index uses customer terminology'
        );
    }

    private function _check_stage_logic()
    {
        $this->load->model('crm_lead_model');
        $m = $this->crm_lead_model;

        $today = date('Y-m-d');
        $cases = array(
            array(date('Y-m-d H:i:s', strtotime('-1 day')), 'hot_lead', 'Past date → Hot Lead'),
            array($today . ' 10:00:00', 'hot_lead', 'Today → Hot Lead'),
            array(date('Y-m-d H:i:s', strtotime('+3 days')), 'followup_next_week', '+3 days → Next Week'),
            array(date('Y-m-d H:i:s', strtotime('+7 days')), 'followup_next_week', '+7 days → Next Week'),
            array(date('Y-m-d H:i:s', strtotime('+15 days')), 'followup_next_month', '+15 days → Next Month'),
            array(date('Y-m-d H:i:s', strtotime('+30 days')), 'followup_next_month', '+30 days → Next Month'),
            array(date('Y-m-d H:i:s', strtotime('+45 days')), 'later', '+45 days → Later'),
            array(NULL, 'later', 'No date → Later'),
        );

        foreach ($cases as $c) {
            $got = $m->compute_stage_from_followup($c[0]);
            $this->_assert($got === $c[1], "{$c[2]} (got {$got})");
        }

        $stages = crm_lead_stages();
        $expected = array(
            'hot_lead', 'warm_lead', 'followup_next_week', 'followup_next_month',
            'later', 'quote_sent', 'lost',
        );
        $this->_assert(array_keys($stages) === $expected, 'crm_lead_stages() has 7 stages in order');

        $manual = crm_lead_manual_stages();
        $this->_assert($manual === array('warm_lead', 'quote_sent', 'lost'), 'Manual stages: warm, quote_sent, lost');
    }

    private function _check_pipeline_views()
    {
        $this->_file_contains('application/views/admin/leads/pipeline.php', 'ymo-pipeline', 'Pipeline kanban view');
        $this->_file_contains('application/views/admin/leads/form.php', 'next_follow_up_at', 'Lead form: next follow-up field');
        $this->_file_contains('application/views/admin/leads/form.php', 'crm_lead_manual_stages', 'Lead form: manual stage dropdown');
        $this->_file_contains('application/views/admin/leads/view.php', 'Convert to customer', 'Lead detail: convert button');
        $this->_file_contains('application/views/admin/leads/index.php', 'leads/pipeline', 'Lead list: pipeline link');

        $form = file_get_contents(FCPATH . '../application/views/admin/leads/form.php');
        $this->_assert(strpos($form, 'Workshop') !== FALSE, 'Lead form labels Workshop (not Company)');
    }

    private function _check_customers()
    {
        $index = file_get_contents(FCPATH . '../application/views/admin/contacts/index.php');
        foreach (array('Active', 'Due for service', 'Inactive', 'VIP', 'contacts-bulk-bar', 'bulk_apply_workshop') as $needle) {
            $this->_assert(strpos($index, $needle) !== FALSE, "Customers index: {$needle}");
        }

        $this->_file_contains('application/views/admin/contacts/view.php', 'Link online account', 'Customer detail: link online account');
        $this->_file_contains('application/views/admin/contacts/form.php', 'Cancel', 'Customer form: Cancel button');
        $this->_file_contains('application/views/admin/contacts/form.php', 'Workshop', 'Customer form: Workshop label');

        $ctrl = file_get_contents(FCPATH . '../application/controllers/admin/Contacts.php');
        $this->_assert(strpos($ctrl, 'function bulk_edit') !== FALSE, 'Contacts controller: bulk_edit');
        $this->_assert(strpos($ctrl, 'function link_user') !== FALSE, 'Contacts controller: link_user');
        $this->_assert(strpos($ctrl, "'vip'") !== FALSE, 'Contacts controller: VIP segment');
    }

    private function _check_online_accounts()
    {
        $path = FCPATH . '../application/controllers/admin/Online_accounts.php';
        $this->_assert(is_readable($path), 'Online_accounts controller exists');
        $this->_file_contains('application/views/admin/online_accounts/index.php', 'online accounts', 'Online accounts index view');
    }

    private function _check_dashboard()
    {
        $this->_file_contains('application/views/admin/dashboard.php', 'dashboard_crm_panel', 'Dashboard loads CRM panel');
        $this->_file_contains('application/views/admin/dashboard_crm_panel.php', "Today's CRM actions", 'CRM panel title');
        $this->_file_contains('application/views/admin/dashboard_crm_panel.php', 'hot_leads', 'CRM panel: hot leads');
        $this->_file_contains('application/views/admin/dashboard_crm_panel.php', 'service_due', 'CRM panel: service due');

        if (!$this->db->table_exists('crm_leads')) {
            $this->_fail('Dashboard snapshot: crm_leads table missing (skip DB test)');
            return;
        }
        $this->load->model('crm_report_model');
        $snap = $this->crm_report_model->dashboard_snapshot();
        $this->_assert(is_array($snap), 'dashboard_snapshot() returns array');
        $this->_assert(isset($snap['hot_leads']), 'dashboard_snapshot has hot_leads count');
        $this->_assert(isset($snap['service_due_count']), 'dashboard_snapshot has service_due_count');
    }

    private function _check_reports()
    {
        $view = file_get_contents(FCPATH . '../application/views/admin/reports/index.php');
        foreach (array(
            'Locality / workshop',
            'Revenue by workshop',
            'Service due customers',
            'export/revenue',
            'export/service-due',
        ) as $needle) {
            $this->_assert(strpos($view, $needle) !== FALSE, "Reports view: {$needle}");
        }

        $ctrl = file_get_contents(FCPATH . '../application/controllers/admin/Reports.php');
        $this->_assert(strpos($ctrl, 'export_revenue') !== FALSE, 'Reports controller: export_revenue');
        $this->_assert(strpos($ctrl, 'export_service_due') !== FALSE, 'Reports controller: export_service_due');

        if (!$this->db->table_exists('crm_leads')) {
            return;
        }
        $this->load->model('crm_report_model');
        $from = date('Y-m-d', strtotime('-30 days'));
        $to = date('Y-m-d');
        $this->_assert(is_array($this->crm_report_model->locality_report($from, $to)), 'locality_report() runs');
        $this->_assert(is_array($this->crm_report_model->revenue_summary($from, $to)), 'revenue_summary() runs');
        $this->_assert(is_array($this->crm_report_model->service_due_report(5)), 'service_due_report() runs');
    }

    private function _check_database()
    {
        if (!$this->db->table_exists('crm_leads')) {
            $this->_fail('crm_leads table not found');
            return;
        }

        $this->_assert(
            $this->db->field_exists('next_follow_up_at', 'crm_leads'),
            'DB column: next_follow_up_at'
        );
        $this->_assert(
            $this->db->field_exists('stage_locked', 'crm_leads'),
            'DB column: stage_locked'
        );

        $row = $this->db->query("SHOW COLUMNS FROM crm_leads LIKE 'stage'")->row_array();
        if ($row) {
            $type = (string) $row['Type'];
            foreach (array('hot_lead', 'warm_lead', 'followup_next_week', 'followup_next_month', 'later', 'quote_sent', 'lost') as $st) {
                $this->_assert(strpos($type, $st) !== FALSE, "DB stage ENUM includes {$st}");
            }
        } else {
            $this->_fail('Could not read crm_leads.stage column');
        }

        $invalid = $this->db
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->where_not_in('stage', array_keys(crm_lead_stages()))
            ->count_all_results('crm_leads');
        $this->_assert($invalid === 0, "No leads with invalid stage (found {$invalid})");

        if ($this->db->table_exists('crm_lead_sources')) {
            $src = $this->db->where('slug', 'offline_marketing')->get('crm_lead_sources')->row_array();
            $this->_assert(!empty($src), 'offline_marketing lead source exists');
        }
    }

    private function _check_webhook_helpers()
    {
        $wa = crm_normalize_whatsapp_payloads(array(
            'from' => '919876543210',
            'text' => 'Hello',
            'message_id' => 'w1',
        ));
        $this->_assert(count($wa) === 1 && $wa[0]['from'] === '919876543210', 'WhatsApp flat payload normalizes');

        $wa_meta = crm_normalize_whatsapp_payloads(array(
            'entry' => array(array(
                'changes' => array(array(
                    'field' => 'messages',
                    'value' => array(
                        'contacts' => array(array('wa_id' => '91999', 'profile' => array('name' => 'Raj'))),
                        'messages' => array(array(
                            'from' => '91999',
                            'id' => 'm1',
                            'type' => 'text',
                            'text' => array('body' => 'Need service'),
                        )),
                    ),
                )),
            )),
        ));
        $this->_assert(!empty($wa_meta) && $wa_meta[0]['name'] === 'Raj', 'WhatsApp Meta Cloud payload normalizes');

        $this->_assert(crm_is_meta_whatsapp_cloud_payload(array(
            'entry' => array(array('changes' => array(array('field' => 'messages')))),
        )), 'crm_is_meta_whatsapp_cloud_payload detects Meta Cloud');
        $this->_assert(!crm_is_meta_whatsapp_cloud_payload(array('from' => '91999', 'text' => 'x')), 'crm_is_meta_whatsapp_cloud_payload ignores flat JSON');
        $this->_file_contains('application/controllers/api/Webhooks.php', "method() === 'get'", 'WhatsApp webhook supports Meta GET verify');

        $ig = crm_parse_meta_messaging_events(array(
            'object' => 'page',
            'entry' => array(array(
                'messaging' => array(array(
                    'sender' => array('id' => '12345'),
                    'message' => array('mid' => 'mid1', 'text' => 'Hi from IG ad'),
                )),
            )),
        ));
        $this->_assert(count($ig) === 1 && $ig[0]['text'] === 'Hi from IG ad', 'Meta messaging payload parses');

        $this->_file_contains('application/controllers/api/Webhooks.php', 'ingest_chat_message', 'Webhooks uses ingest_chat_message');
        $this->_file_contains('application/controllers/api/Webhooks.php', 'crm_parse_meta_messaging_events', 'Webhooks handles Instagram/Messenger DMs');
    }

    private function _check_two_way_chat()
    {
        $this->_section('9. Two-way chat');
        $this->_assert(is_readable(FCPATH . '../application/libraries/Crm_messaging.php'), 'Crm_messaging library exists');
        $this->_file_contains('application/controllers/admin/Leads.php', 'function send_chat', 'Leads send_chat action');
        $this->_file_contains('application/views/admin/leads/view.php', 'send-chat', 'Lead view chat compose form');
        $this->_file_contains('application/config/routes.php', 'send-chat', 'send-chat route registered');

        $wa_lead = array('external_provider' => 'whatsapp', 'source_slug' => 'whatsapp', 'mobile' => '9876543210');
        $ig_lead = array('external_provider' => 'instagram_dm', 'source_slug' => 'instagram', 'external_lead_id' => '123');
        $form_lead = array('external_provider' => 'meta', 'source_slug' => 'instagram', 'external_lead_id' => 'leadgen_1');
        $this->_assert(crm_lead_chat_channel($wa_lead) === 'whatsapp', 'crm_lead_chat_channel detects WhatsApp');
        $this->_assert(crm_lead_chat_channel($ig_lead) === 'instagram', 'crm_lead_chat_channel detects Instagram DM');
        $this->_assert(crm_lead_chat_channel($form_lead) === NULL, 'Lead form ads do not show chat panel');
    }
}
