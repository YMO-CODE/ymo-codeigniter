<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google Ads attribution endpoints.
 *
 *  POST /api/ads/wa-click                  beacon from google_tag.php when an ad
 *                                          visitor taps a WhatsApp link
 *  GET  /api/google-ads/conversions.csv    offline conversions for Google Ads
 *  GET  /api/google-ads/qualified.csv      only "CRM - Qualified lead"
 *  GET  /api/google-ads/booked.csv         only "CRM - Booking completed"
 *                                          scheduled uploads (HTTP Basic auth,
 *                                          YMO_GADS_FEED_USER / YMO_GADS_FEED_PASS)
 */
class Ads extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $this->load->model('crm_ad_click_model');
        $this->output->set_header('Cache-Control: no-store');
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');
    }

    public function wa_click()
    {
        if ($this->input->method() !== 'post' || !$this->_same_origin()) {
            return $this->output->set_status_header(403);
        }
        $raw = (string) file_get_contents('php://input');
        $in = strlen($raw) <= 4000 ? json_decode($raw, TRUE) : NULL;
        if (!is_array($in)) {
            return $this->output->set_status_header(400);
        }
        // The click comes from the visitor's own ymo_attr cookie, not the body.
        $attr = array();
        $cookie = isset($_COOKIE['ymo_attr']) ? json_decode((string) $_COOKIE['ymo_attr'], TRUE) : NULL;
        if (is_array($cookie)) {
            foreach ($cookie as $k => $v) {
                if (is_scalar($v)) {
                    $attr[(string) $k] = mb_substr(trim((string) $v), 0, 255);
                }
            }
        }
        $ok = $this->crm_ad_click_model->record_wa_click(
            isset($in['ref']) ? (string) $in['ref'] : '',
            $attr,
            isset($in['page']) ? (string) $in['page'] : '',
            $this->input->ip_address()
        );
        $this->output->set_status_header($ok ? 204 : 202);
    }

    public function conversions($type = '')
    {
        $user = (string) getenv('YMO_GADS_FEED_USER');
        $pass = (string) getenv('YMO_GADS_FEED_PASS');
        if ($user === '' || strlen($pass) < 12) {
            return show_404();
        }
        $given_user = (string) $this->input->server('PHP_AUTH_USER');
        $given_pass = (string) $this->input->server('PHP_AUTH_PW');
        if ($given_user === '' && preg_match('/^Basic\s+(.+)$/i', (string) $this->input->server('HTTP_AUTHORIZATION'), $m)) {
            $pair = explode(':', (string) base64_decode($m[1]), 2);
            $given_user = $pair[0];
            $given_pass = isset($pair[1]) ? $pair[1] : '';
        }
        if (!hash_equals($user, $given_user) || !hash_equals($pass, $given_pass)) {
            $this->output->set_header('WWW-Authenticate: Basic realm="YMO conversions"');
            return $this->output->set_status_header(401)->set_output('Unauthorized');
        }

        $lines = array(
            'Parameters:TimeZone=+0530',
            'Google Click ID,Conversion Name,Conversion Time,Conversion Value,Conversion Currency',
        );
        // qualified.csv / booked.csv: one Google Ads connection per conversion action.
        $only = array('qualified' => Crm_ad_click_model::CONV_QUALIFIED, 'booked' => Crm_ad_click_model::CONV_BOOKED);
        $type = (string) $type;
        foreach ($this->crm_ad_click_model->feed_rows(60) as $r) {
            if (isset($only[$type]) && $r['name'] !== $only[$type]) {
                continue;
            }
            $lines[] = implode(',', array(
                $this->_csv($r['gclid']),
                $this->_csv($r['name']),
                $this->_csv(date('Y-m-d H:i:s', strtotime($r['time']))),
                number_format((float) $r['value'], 2, '.', ''),
                'INR',
            ));
        }
        $this->output
            ->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: inline; filename="conversions.csv"')
            ->set_output(implode("\r\n", $lines)."\r\n");
    }

    protected function _same_origin()
    {
        $origin = (string) $this->input->get_request_header('Origin', TRUE);
        if ($origin === '') {
            return TRUE;
        }
        $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $mine = preg_replace('/:\d+$/', '', strtolower((string) $this->input->server('HTTP_HOST')));
        return $host === $mine;
    }

    protected function _csv($v)
    {
        $v = (string) $v;
        return preg_match('/[",\r\n]/', $v) ? '"'.str_replace('"', '""', $v).'"' : $v;
    }
}
