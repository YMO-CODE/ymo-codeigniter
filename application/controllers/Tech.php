<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Technician phone app at /tech (admin host). One page + a small JSON API.
 * Sign in: mobile + 4-digit PIN; a 30-day cookie (only its hash is stored) keeps the phone signed in.
 * POSTs need the X-YMO-Tech header (cross-site pages can't send it) and the SameSite=Strict cookie.
 */
class Tech extends CI_Controller
{
    const COOKIE = 'ymo_tech';

    /** @var array|null signed-in technician */
    protected $tech = NULL;

    public function __construct()
    {
        parent::__construct();
        if (function_exists('ymo_load_db_settings')) {
            ymo_load_db_settings();
        }
        $this->load->model(array('crm_technician_model', 'crm_work_order_model'));
        $this->load->helper('review');
    }

    // ---- pages ----------------------------------------------------------------

    public function index()
    {
        $this->output->set_header('Cache-Control: no-cache');
        $this->load->view('tech/app', array(
            'base'    => rtrim(admin_url('tech'), '/'),
            'version' => $this->_version(),
        ));
    }

    public function sw()
    {
        $base = rtrim(admin_url('tech'), '/');
        $scope = parse_url($base, PHP_URL_PATH) ?: '/tech';
        $this->output
            ->set_content_type('application/javascript')
            ->set_header('Service-Worker-Allowed: '.$scope)
            ->set_header('Cache-Control: no-cache')
            ->set_output($this->load->view('tech/sw_js', array('base' => $base, 'version' => $this->_version()), TRUE));
    }

    public function manifest()
    {
        $base = rtrim(admin_url('tech'), '/');
        $this->output->set_content_type('application/manifest+json')->set_output(json_encode(array(
            'name'             => 'YMO Workshop',
            'short_name'       => 'YMO Jobs',
            'start_url'        => $base,
            'scope'            => parse_url($base, PHP_URL_PATH) ?: '/tech',
            'display'          => 'standalone',
            'background_color' => '#f4f6f5',
            'theme_color'      => '#2d6a4f',
            'icons'            => array(array('src' => $base.'/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any')),
        ), JSON_UNESCAPED_SLASHES));
    }

    public function icon()
    {
        $this->output->set_content_type('image/svg+xml')->set_header('Cache-Control: public, max-age=604800')->set_output(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" rx="20" fill="#2d6a4f"/>'
            .'<path d="M62 22a14 14 0 0 0-17 17L24 60a6 6 0 0 0 8.5 8.5l21-21A14 14 0 0 0 71 30l-8 8-7-1-1-7z" fill="#fff"/></svg>'
        );
    }

    /** GET photo (thumbnail with ?t=1), for the signed-in technician. */
    public function photo($photo_id)
    {
        if (!$this->_auth(FALSE)) {
            set_status_header(401);
            return;
        }
        $p = $this->crm_work_order_model->find_photo($photo_id);
        $wo = $p ? $this->crm_work_order_model->find($p['work_order_id']) : NULL;
        if (!$p || !$wo || !$this->crm_work_order_model->technician_can_see($this->tech, $wo)) {
            show_404();
        }
        $this->load->library('job_photo_service');
        $this->job_photo_service->stream($p, (bool) $this->input->get('t'));
    }

    // ---- API --------------------------------------------------------------------

    public function api_login()
    {
        $this->_post_guard();
        $in = $this->_json_in();
        list($t, $err) = $this->crm_technician_model->check_login((string) ($in['mobile'] ?? ''), (string) ($in['pin'] ?? ''));
        if (!$t) {
            return $this->_json(array('ok' => FALSE, 'error' => $err), 401);
        }
        $token = $this->crm_technician_model->start_session($t['id'], (string) $this->input->user_agent());
        $this->_set_cookie($token, time() + Crm_technician_model::SESSION_DAYS * 86400);
        $this->tech = $t;
        return $this->_json(array('ok' => TRUE, 'me' => $this->_me()));
    }

    public function api_logout()
    {
        $this->_post_guard();
        $this->crm_technician_model->end_session((string) $this->input->cookie(self::COOKIE));
        $this->_set_cookie('', time() - 3600);
        return $this->_json(array('ok' => TRUE));
    }

    public function api_me()
    {
        if (!$this->_auth()) {
            return;
        }
        return $this->_json(array('ok' => TRUE, 'me' => $this->_me()));
    }

    public function api_lang()
    {
        $this->_post_guard();
        if (!$this->_auth()) {
            return;
        }
        $lang = (string) ($this->_json_in()['lang'] ?? '');
        if (isset(Crm_technician_model::LANGS[$lang])) {
            $this->crm_technician_model->update($this->tech['id'], array('lang' => $lang));
        }
        return $this->_json(array('ok' => TRUE));
    }

    public function api_jobs()
    {
        if (!$this->_auth()) {
            return;
        }
        $scope = $this->input->get('scope') === 'workshop' ? 'workshop' : 'mine';
        $rows = $this->crm_work_order_model->jobs_for_technician($this->tech, $scope);
        $out = array();
        foreach ($rows as $r) {
            $out[] = $this->_job_summary($r);
        }
        return $this->_json(array('ok' => TRUE, 'scope' => $scope, 'jobs' => $out, 'at' => date('c')));
    }

    public function api_job($id)
    {
        if (!($wo = $this->_job_or_fail($id, FALSE))) {
            return;
        }
        return $this->_json(array('ok' => TRUE, 'job' => $this->_job_detail($wo)));
    }

    /** POST {done: bool, uid}: tick / untick a line. Sets the state (not a toggle), so retries are safe. */
    public function api_line($id, $line_id)
    {
        $this->_post_guard();
        if (!($wo = $this->_job_or_fail($id, TRUE))) {
            return;
        }
        $in = $this->_json_in();
        $uid = $this->_uid($in);
        if ($uid !== NULL && $this->crm_work_order_model->uid_seen($uid)) {
            return $this->_json(array('ok' => TRUE, 'dup' => TRUE));
        }
        $line = $this->crm_work_order_model->find_line($id, $line_id);
        if (!$line) {
            return $this->_json(array('ok' => FALSE, 'error' => 'line_gone'), 404);
        }
        $done = !empty($in['done']);
        if ((bool) $line['done_at'] !== $done) {
            $this->crm_work_order_model->set_line_done($id, $line_id, $done, 0, (int) $this->tech['id']);
        }
        $this->crm_work_order_model->log($id, 0, 'line', ($done ? 'Done: ' : 'Not done: ').$line['name'], (int) $this->tech['id'], $uid);
        return $this->_json(array('ok' => TRUE));
    }

    /** POST {to, note, uid}: start / hold / resume / ready. */
    public function api_status($id)
    {
        $this->_post_guard();
        if (!($wo = $this->_job_or_fail($id, TRUE))) {
            return;
        }
        $in = $this->_json_in();
        $uid = $this->_uid($in);
        $to = (string) ($in['to'] ?? '');
        if ($uid !== NULL && $this->crm_work_order_model->uid_seen($uid)) {
            return $this->_json(array('ok' => TRUE, 'dup' => TRUE));
        }
        if ($wo['status'] === $to) {   // already there (e.g. advisor did it first)
            return $this->_json(array('ok' => TRUE, 'job' => $this->_job_detail($wo)));
        }
        $this->load->library('work_order_flow');
        list($ok, $msg) = $this->work_order_flow->change($wo, $to, mb_substr(trim((string) ($in['note'] ?? '')), 0, 200), 0, (int) $this->tech['id']);
        if (!$ok) {
            return $this->_json(array('ok' => FALSE, 'error' => 'not_allowed', 'message' => $msg, 'job' => $this->_job_detail($wo)), 409);
        }
        if ($uid !== NULL) {
            // Remember the phone's action id so a retry is not applied twice.
            $this->db->insert('crm_work_order_events', array(
                'work_order_id' => (int) $id, 'technician_id' => (int) $this->tech['id'], 'type' => 'sync',
                'detail' => '', 'client_uid' => $uid, 'created_at' => date('Y-m-d H:i:s'),
            ));
        }
        return $this->_json(array('ok' => TRUE, 'job' => $this->_job_detail($this->crm_work_order_model->find($id))));
    }

    /** POST {text, uid}: a note for the advisor (extra work found, waiting for something…). */
    public function api_note($id)
    {
        $this->_post_guard();
        if (!($wo = $this->_job_or_fail($id, FALSE))) {
            return;
        }
        $in = $this->_json_in();
        $text = mb_substr(trim((string) ($in['text'] ?? '')), 0, 500);
        if ($text === '') {
            return $this->_json(array('ok' => FALSE, 'error' => 'empty'), 422);
        }
        $this->crm_work_order_model->log($id, 0, 'tech_note', $text, (int) $this->tech['id'], $this->_uid($in));
        return $this->_json(array('ok' => TRUE));
    }

    /** POST multipart: photo, kind, uid. */
    public function api_photo($id)
    {
        $this->_post_guard();
        if (!($wo = $this->_job_or_fail($id, FALSE))) {
            return;
        }
        $this->load->library('job_photo_service');
        $files = $this->job_photo_service->files_from_upload('photo');
        if (!$files) {
            return $this->_json(array('ok' => FALSE, 'error' => 'no_file'), 422);
        }
        $uid = $this->_uid($this->input->post());
        $kind = (string) $this->input->post('kind');
        $existing = $uid !== NULL ? $this->crm_work_order_model->find_photo_by_uid($uid) : NULL;
        list($pid, $err) = $this->job_photo_service->save((int) $id, $files[0], $kind, 0, (int) $this->tech['id'], $uid);
        if (!$pid) {
            // Storage trouble is temporary: tell the phone to keep the photo and retry.
            return $this->_json(array('ok' => FALSE, 'error' => 'store_failed', 'message' => $err), 503);
        }
        if (!$existing) {
            $this->crm_work_order_model->log($id, 0, 'photo', 'Added a photo ('.(Crm_work_order_model::PHOTO_KINDS[$kind] ?? 'During work').')', (int) $this->tech['id']);
        }
        return $this->_json(array('ok' => TRUE, 'photo' => array('id' => $pid, 'kind' => $kind)));
    }

    // ---- helpers ------------------------------------------------------------------

    protected function _version()
    {
        $files = array(VIEWPATH.'tech/app.php', VIEWPATH.'tech/sw_js.php');
        $m = 0;
        foreach ($files as $f) {
            $m = max($m, (int) @filemtime($f));
        }
        return (string) $m;
    }

    protected function _me()
    {
        $ws = ymo_review_workshops();
        return array(
            'id'       => (int) $this->tech['id'],
            'name'     => $this->tech['name'],
            'lang'     => $this->tech['lang'],
            'workshop' => $this->tech['workshop'],
            'workshop_label' => $this->tech['workshop'] !== '' && isset($ws[$this->tech['workshop']]) ? $ws[$this->tech['workshop']]['label'] : '',
        );
    }

    protected function _job_summary(array $r)
    {
        return array(
            'id'       => (int) $r['id'],
            'ro'       => $r['ro_number'],
            'status'   => $r['status'],
            'car'      => $r['vehicle_number'],
            'model'    => trim($r['vehicle_make'].' '.$r['vehicle_variant']),
            'bay'      => $r['bay'],
            'due'      => $r['expected_delivery_at'] ? date('Y-m-d\TH:i:s', strtotime($r['expected_delivery_at'])) : NULL,
            'lines'    => (int) ($r['line_count'] ?? 0),
            'done'     => (int) ($r['done_count'] ?? 0),
        );
    }

    protected function _job_detail(array $wo)
    {
        $id = (int) $wo['id'];
        $lines = array();
        foreach ($this->crm_work_order_model->lines($id) as $l) {
            $lines[] = array(
                'id'    => (int) $l['id'],
                'type'  => $l['type'],
                'name'  => $l['name'],
                'desc'  => (string) $l['description'],
                'qty'   => rtrim(rtrim(number_format((float) $l['qty'], 2, '.', ''), '0'), '.'),
                'done'  => !empty($l['done_at']),
                'by'    => (string) ($l['done_by_tech_name'] ?? ''),
                'mine'  => (int) $l['technician_id'] === (int) $this->tech['id'],
                'tech'  => (string) ($l['technician_name'] ?? ''),
            );
        }
        $photos = array();
        foreach ($this->crm_work_order_model->photos($id) as $p) {
            $photos[] = array('id' => (int) $p['id'], 'kind' => $p['kind'], 'by' => (string) ($p['tech_name'] ?: $p['admin_name']));
        }
        $notes = array();
        foreach ($this->crm_work_order_model->events($id, 80) as $e) {
            if (in_array($e['type'], array('note', 'tech_note'), TRUE)) {
                $notes[] = array(
                    'at'   => date('Y-m-d\TH:i:s', strtotime($e['created_at'])),
                    'text' => $e['detail'],
                    'by'   => (string) ($e['tech_name'] ?: $e['admin_name']),
                    'from' => $e['type'] === 'note' ? 'advisor' : 'tech',
                );
            }
        }
        return $this->_job_summary($wo) + array(
            'complaint'    => (string) $wo['complaint'],
            'instructions' => (string) $wo['instructions'],
            'odometer'     => $wo['odometer_km'] !== NULL ? (int) $wo['odometer_km'] : NULL,
            'advisor'      => (string) $wo['advisor_name'],
            'techs'        => array_column($this->crm_work_order_model->technicians($id), 'name'),
            'moves'        => Crm_work_order_model::TECH_MOVES[$wo['status']] ?? array(),
            'closed'       => in_array($wo['status'], array('delivered', 'returned'), TRUE),
            'items'        => $lines,
            'photos'       => $photos,
            'notes'        => array_slice($notes, 0, 20),
            'lines'        => count($lines),
            'done'         => count(array_filter($lines, function ($l) { return $l['done']; })),
        );
    }

    /** @return array|null job card, or NULL after sending the error */
    protected function _job_or_fail($id, $must_be_open)
    {
        if (!$this->_auth()) {
            return NULL;
        }
        $wo = $this->crm_work_order_model->find($id);
        if (!$wo || !$this->crm_work_order_model->technician_can_see($this->tech, $wo)) {
            $this->_json(array('ok' => FALSE, 'error' => 'not_found'), 404);
            return NULL;
        }
        if ($must_be_open && in_array($wo['status'], array('delivered', 'returned'), TRUE)) {
            $this->_json(array('ok' => FALSE, 'error' => 'closed'), 409);
            return NULL;
        }
        return $wo;
    }

    protected function _auth($send = TRUE)
    {
        $this->tech = $this->crm_technician_model->from_token((string) $this->input->cookie(self::COOKIE));
        if (!$this->tech && $send) {
            $this->_json(array('ok' => FALSE, 'error' => 'signed_out'), 401);
        }
        return (bool) $this->tech;
    }

    protected function _post_guard()
    {
        if ($this->input->method() !== 'post' || $this->input->get_request_header('X-YMO-Tech') !== '1') {
            $this->_json(array('ok' => FALSE, 'error' => 'bad_request'), 400);
            $this->output->_display();
            exit;
        }
    }

    protected function _uid($in)
    {
        $u = is_array($in) ? (string) ($in['uid'] ?? '') : '';
        return preg_match('/^[A-Za-z0-9_-]{8,40}$/', $u) ? $u : NULL;
    }

    protected function _json_in()
    {
        $j = json_decode((string) $this->input->raw_input_stream, TRUE);
        return is_array($j) ? $j : array();
    }

    protected function _json(array $data, $code = 200)
    {
        $this->output->set_status_header($code)->set_content_type('application/json')
            ->set_header('Cache-Control: no-store')->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return NULL;
    }

    protected function _set_cookie($value, $expires)
    {
        $path = parse_url(admin_url('tech'), PHP_URL_PATH) ?: '/tech';
        setcookie(self::COOKIE, $value, array(
            'expires'  => $expires,
            'path'     => $path,
            'secure'   => is_https(),
            'httponly' => TRUE,
            'samesite' => 'Strict',
        ));
    }
}
