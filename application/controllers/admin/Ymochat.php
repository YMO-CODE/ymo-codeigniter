<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * YMOChat: who is on the website right now (location, IP, pages, ad search)
 * and live chat with them. The CRM polls /ymochat/pulse on every page so the
 * team gets an alert the moment someone lands on the site.
 */
class Ymochat extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ymochat_model');
    }

    public function index()
    {
        $this->require_perm('leads.view');
        $this->ymochat_model->ensure_schema();
        $this->render('admin/ymochat/index', array(
            'title'    => 'YMOChat',
            'can_chat' => crm_can('leads.edit'),
            'open_id'  => (int) $this->input->get('v'),
        ));
    }

    /** GET ymochat/feed — everyone online plus the last 24 hours. */
    public function feed()
    {
        $this->require_perm('leads.view');
        $this->_agent_seen();
        $this->_json(array('visitors' => $this->ymochat_model->board(24), 'now' => date('Y-m-d H:i:s')));
    }

    /** GET ymochat/pulse — small, for the alert bell on every CRM page. */
    public function pulse()
    {
        $this->require_perm('leads.view');
        $this->_agent_seen();
        $this->_json($this->ymochat_model->pulse());
    }

    /** GET ymochat/{id}?after=&read=1 — one visitor: details, pages, chat. */
    public function thread($id)
    {
        $this->require_perm('leads.view');
        $v = $this->ymochat_model->visitor($id);
        if (!$v) {
            return $this->_json(array('ok' => FALSE), 404);
        }
        if ($this->input->get('read')) {
            $this->ymochat_model->mark_read((int) $v['id']);
            $v['unread_agent'] = 0;
        }
        $this->_json(array(
            'ok'      => TRUE,
            'visitor' => $this->ymochat_model->present($v),
            'pages'   => $this->ymochat_model->pageviews((int) $v['id']),
            'msgs'    => $this->ymochat_model->messages((int) $v['id'], (int) $this->input->get('after')),
            'lead_url'=> !empty($v['lead_id']) ? admin_url('leads/'.(int) $v['lead_id']) : '',
        ));
    }

    /** POST ymochat/{id}/send (XHR only) — message from the team; opens the chat on their screen. */
    public function send($id)
    {
        $this->require_perm('leads.edit');
        if ($this->input->method() !== 'post' || $this->input->get_request_header('X-Requested-With') !== 'XMLHttpRequest') {
            return $this->_json(array('ok' => FALSE), 400);
        }
        $origin = (string) $this->input->get_request_header('Origin', TRUE);
        if ($origin !== '' && strtolower((string) parse_url($origin, PHP_URL_HOST)) !== strtolower(preg_replace('/:\d+$/', '', (string) $this->input->server('HTTP_HOST')))) {
            return $this->_json(array('ok' => FALSE), 403);
        }
        $v = $this->ymochat_model->visitor($id);
        if (!$v) {
            return $this->_json(array('ok' => FALSE), 404);
        }
        $in = json_decode((string) file_get_contents('php://input'), TRUE);
        $text = is_array($in) && isset($in['text']) ? (string) $in['text'] : (string) $this->input->post('text');
        $msg_id = $this->ymochat_model->agent_message((int) $v['id'], $text, (int) $this->admin['id']);
        if (!$msg_id) {
            return $this->_json(array('ok' => FALSE, 'error' => 'empty'));
        }
        $this->_agent_seen();
        $after = is_array($in) && isset($in['after']) ? (int) $in['after'] : 0;
        $this->_json(array('ok' => TRUE, 'msgs' => $this->ymochat_model->messages((int) $v['id'], $after)));
    }

    protected function _agent_seen()
    {
        $this->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
        $this->cache->save('ymochat_agent_seen', time(), 300);
    }

    protected function _json(array $data, $status = 200)
    {
        $this->output->set_status_header($status)->set_content_type('application/json', 'utf-8')
            ->set_header('Cache-Control: no-store')->set_output(json_encode($data));
    }
}
