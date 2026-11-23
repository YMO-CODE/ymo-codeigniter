<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * YMOChat website API (public, same-site only). Called by assets/js/ymochat.js.
 *
 *  POST /api/ymochat/beat     page view / heartbeat; returns new chat messages
 *  POST /api/ymochat/send     visitor sends a chat message
 *  POST /api/ymochat/contact  visitor leaves name + mobile → CRM lead
 *
 * Bodies are JSON. Visitors are identified by a random id kept in their browser.
 */
class Ymochat extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ymochat_model');
        // These calls never need the session; release its lock so push alerts
        // (sent below) can't slow down the visitor's page loads.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function beat()
    {
        if (!$this->_guard()) {
            return;
        }
        $in = $this->_body();
        $v = $this->ymochat_model->beat($in, $this->input->ip_address(), (string) $this->input->user_agent());
        if (!$v) {
            return $this->_json(array('ok' => FALSE));
        }
        if (!empty($in['evt']) && is_string($in['evt'])) {
            $this->ymochat_model->event((int) $v['id'], $in['evt']);
        }
        $this->_json($this->_state($v, isset($in['after']) ? (int) $in['after'] : 0));
        if (!empty($v['is_new_visit'])) {
            $this->ymochat_model->push_notify('visit', $v);
        }
    }

    public function send()
    {
        if (!$this->_guard()) {
            return;
        }
        $in = $this->_body();
        $v = $this->ymochat_model->visitor_by_vid(isset($in['vid']) ? $in['vid'] : '');
        if (!$v) {
            return $this->_json(array('ok' => FALSE, 'error' => 'unknown'));
        }
        $res = $this->ymochat_model->visitor_message($v, isset($in['text']) ? $in['text'] : '');
        if (!is_int($res)) {
            return $this->_json(array('ok' => FALSE, 'error' => $res));
        }
        // A phone number typed into the chat is as good as the contact form.
        $text = (string) $in['text'];
        if (empty($v['mobile']) && preg_match('/(?:\+?91[\s-]*)?([6-9](?:[\s-]*\d){9})/', $text, $m)) {
            $mobile = ymo_indian_mobile($m[0]);
            if ($mobile !== '') {
                $this->ymochat_model->save_contact($v, '', $mobile);
            }
        }
        $this->_json($this->_state($v, isset($in['after']) ? (int) $in['after'] : 0));
        $this->ymochat_model->push_notify('chat', $v, $text);
    }

    public function contact()
    {
        if (!$this->_guard()) {
            return;
        }
        $in = $this->_body();
        $v = $this->ymochat_model->visitor_by_vid(isset($in['vid']) ? $in['vid'] : '');
        $mobile = ymo_indian_mobile(isset($in['mobile']) ? $in['mobile'] : '');
        if (!$v || $mobile === '') {
            return $this->_json(array('ok' => FALSE, 'error' => $v ? 'mobile' : 'unknown'));
        }
        $name = isset($in['name']) ? (string) $in['name'] : '';
        $this->ymochat_model->save_contact($v, $name, $mobile);
        $this->ymochat_model->system_message((int) $v['id'], 'Thanks'.($name !== '' ? ' '.trim(strip_tags($name)) : '')
            .'! We will call you on '.substr($mobile, 0, 5).' '.substr($mobile, 5).' shortly.');
        $v = $this->ymochat_model->visitor((int) $v['id']);
        $this->_json($this->_state($v, isset($in['after']) ? (int) $in['after'] : 0));
        $this->ymochat_model->push_notify('contact', $v, trim(strip_tags($name)).($name !== '' ? ' · ' : '').$mobile.' – call them back');
    }

    /** GET /ymochat.webmanifest — lets the CRM be installed on a phone's home screen. */
    public function manifest()
    {
        $start = admin_url('ymochat');
        $icon = function ($size, $purpose) {
            return array('src' => base_url('assets/img/crm-icon-'.$size.'.png'), 'sizes' => (int) $size.'x'.(int) $size, 'type' => 'image/png', 'purpose' => $purpose);
        };
        $this->output->set_content_type('application/manifest+json', 'utf-8')
            ->set_header('Cache-Control: public, max-age=3600')
            ->set_output(json_encode(array(
                'id'               => '/ymo-crm',
                'name'             => 'YMO CRM',
                'short_name'       => 'YMO CRM',
                'description'      => 'YMO CRM with live website visitor alerts (YMOChat).',
                'start_url'        => $start,
                'scope'            => '/',
                'display'          => 'standalone',
                'orientation'      => 'portrait',
                'background_color' => '#1d2026',
                'theme_color'      => '#1b5e20',
                'icons'            => array($icon(192, 'any'), $icon(512, 'any'), $icon('512m', 'maskable')),
            ), JSON_UNESCAPED_SLASHES));
    }

    // --- internals -------------------------------------------------------------

    protected function _state(array $v, $after)
    {
        $this->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
        $seen = (int) $this->cache->get('ymochat_agent_seen');
        $ist = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $hour = (int) $ist->format('G');
        return array(
            'ok'           => TRUE,
            'msgs'         => $this->ymochat_model->messages((int) $v['id'], $after, 200, FALSE),
            'known'        => !empty($v['mobile']),
            'agent_online' => $seen > 0 && (time() - $seen) < 90,
            'open_hours'   => $hour >= 9 && $hour < 20,
        );
    }

    /** POST only, from our own pages. */
    protected function _guard()
    {
        $this->output->set_header('Cache-Control: no-store');
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            return FALSE;
        }
        $origin = (string) $this->input->get_request_header('Origin', TRUE);
        if ($origin !== '') {
            $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
            $mine = strtolower((string) $this->input->server('HTTP_HOST'));
            $mine = preg_replace('/:\d+$/', '', $mine);
            if ($host !== $mine) {
                $this->output->set_status_header(403);
                return FALSE;
            }
        }
        return TRUE;
    }

    protected function _body()
    {
        $raw = (string) file_get_contents('php://input');
        if (strlen($raw) > 20000) {
            return array();
        }
        $in = json_decode($raw, TRUE);
        return is_array($in) ? $in : array();
    }

    protected function _json(array $data)
    {
        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($data));
    }
}
