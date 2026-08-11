<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI SMS smoke tests for MSG91 Flow setup.
 *
 * Host (repo root):  php public/index.php cli/sms test otp 9876543210
 * Docker (app svc):  php index.php cli/sms test otp 9876543210
 */
class Sms extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        ymo_load_db_settings();
        $this->load->library('sms_gateway');
    }

    public function config()
    {
        $templates = (array) $this->config->item('sms_templates');
        $auth = $this->config->item('sms_msg91_authkey');
        echo "driver:      ".($this->config->item('sms_driver') ?: 'msg91')."\n";
        echo "authkey:     ".($auth !== '' ? substr($auth, 0, 4).'…'.substr($auth, -4) : '(empty)')."\n";
        echo "sender:      ".$this->config->item('sms_msg91_sender')."\n";
        echo "route:       ".$this->config->item('sms_msg91_route')."\n";
        foreach ($templates as $key => $flow_id) {
            $status = ($flow_id !== '') ? $flow_id : '(missing — set YMO_TPL_* in .env)';
            echo "template $key: $status\n";
        }
    }

    /**
     * @param string $template_key Config key e.g. otp, booking_confirmed
     * @param string $mobile       10-digit Indian mobile
     */
    public function test($template_key = 'otp', $mobile = '')
    {
        $template_key = trim((string) $template_key);
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            echo "Usage: php index.php cli/sms test TEMPLATE MOBILE  (inside Docker)\n";
            echo "  e.g. php index.php cli/sms test otp 9876543210\n";
            return;
        }

        $vars = $this->_sample_vars($template_key);
        echo "Sending template '$template_key' to $mobile …\n";
        $ok = $this->sms_gateway->send_template($mobile, $template_key, $vars);
        if ($ok) {
            echo "OK — check the handset (and MSG91 dashboard reports).\n";
            return;
        }
        echo "FAIL — ".$this->sms_gateway->last_error()."\n";
        exit(1);
    }

    /** Send a smoke test for every configured template. */
    public function test_all($mobile = '')
    {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            echo "Usage: php index.php cli/sms test_all MOBILE\n";
            return;
        }

        $keys = array(
            'otp', 'booking_confirmed', 'booking_status', 'service_reminder',
            'review_request', 'invoice_sent', 'referral_credit', 'crm_campaign',
        );
        $templates = (array) $this->config->item('sms_templates');
        $failed = 0;

        foreach ($keys as $key) {
            if (empty($templates[$key])) {
                echo "SKIP $key — not configured in .env\n";
                continue;
            }
            echo "→ $key … ";
            $ok = $this->sms_gateway->send_template($mobile, $key, $this->_sample_vars($key));
            if ($ok) {
                echo "OK\n";
            } else {
                echo "FAIL — ".$this->sms_gateway->last_error()."\n";
                $failed++;
            }
            sleep(2);
        }

        if ($failed > 0) {
            exit(1);
        }
        echo "All configured templates sent.\n";
    }

    /** @return array<string, string> */
    protected function _sample_vars($template_key)
    {
        switch ($template_key) {
            case 'otp':
                return array('otp' => (string) random_int(100000, 999999));
            case 'booking_confirmed':
                return array('name' => 'Test', 'ref' => 'YMO-TEST-001', 'package' => 'Periodic Service');
            case 'booking_status':
                return array('ref' => 'YMO-TEST-001', 'status' => 'Confirmed');
            case 'service_reminder':
                return array('name' => 'Test', 'vehicle' => 'MH12AB1234');
            case 'review_request':
                return array('name' => 'Test', 'ref' => 'YMO-TEST-001');
            case 'invoice_sent':
                return array('ref' => 'YMO-TEST-001', 'total' => '4850');
            case 'referral_credit':
                return array('name' => 'Test', 'amount' => '500', 'ref' => 'YMO-TEST-001');
            case 'crm_campaign':
                return array('msg' => 'MSG91 smoke test from YMO CLI');
            default:
                return array();
        }
    }
}
