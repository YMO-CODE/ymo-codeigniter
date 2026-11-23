<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google reviews: reviews that need the team, auto-replies, asking customers
 * for a review, and the Google Business Profile connection.
 */
class Reviews extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('gbp_review_model');
        $this->load->helper('review');
        $this->load->library('gbp_api');
    }

    public function index()
    {
        $this->require_perm('leads.view');
        $this->gbp_review_model->ensure_schema();
        $connected = $this->gbp_api->connection();
        $listings = (array) $this->gbp_review_model->setting('gbp_locations', array());
        $available = (array) $this->gbp_review_model->setting('gbp_locations_cache', array());
        if ($connected && !$available && crm_can('settings.manage')) {
            $available = $this->_refresh_locations();
        }
        $this->render('admin/reviews/index', array(
            'title'       => 'Google reviews',
            'counts'      => $this->gbp_review_model->counts(),
            'asked_30d'   => $this->gbp_review_model->request_counts(),
            'attention'   => $this->gbp_review_model->recent(50, 'attention'),
            'reviews'     => $this->gbp_review_model->recent(60),
            'requests'    => $this->gbp_review_model->recent_requests(25),
            'workshops'   => ymo_review_workshops(),
            'client'      => $this->gbp_api->client(),
            'client_env'  => trim((string) getenv('YMO_GBP_CLIENT_ID')) !== '',
            'connected'   => $connected,
            'listings'    => $listings,
            'available'   => $available,
            'last_check'  => $this->gbp_review_model->setting('gbp_last_check', NULL),
            'redirect_uri'=> $this->gbp_api->redirect_uri(),
            'can_setup'   => crm_can('settings.manage'),
            'can_reply'   => crm_can('leads.edit'),
            'focus'       => (int) $this->input->get('r'),
            'ai_on'       => trim((string) getenv('ANTHROPIC_API_KEY')) !== '' || trim((string) $this->config->item('anthropic_api_key')) !== '',
        ));
    }

    /** POST reviews/ask — send a review request to any customer number. */
    public function ask()
    {
        $this->require_perm('leads.edit');
        $this->_post_only();
        $this->load->library('review_service');
        $res = $this->review_service->ask(
            (string) $this->input->post('mobile'),
            (string) $this->input->post('name'),
            (string) $this->input->post('workshop'),
            array('source' => 'manual', 'admin_id' => (int) $this->admin['id'], 'force' => (bool) $this->input->post('force'))
        );
        $this->flash($res['ok'] ? 'success' : 'error', $res['message']);
        redirect(admin_url('reviews#ask'));
    }

    /** POST reviews/{id}/reply — post the team's reply to Google. */
    public function reply($id)
    {
        $this->require_perm('leads.edit');
        $this->_post_only();
        $row = $this->gbp_review_model->find($id);
        $text = trim((string) $this->input->post('text'));
        if (!$row || $text === '') {
            $this->flash('error', 'Write a reply first.');
            redirect(admin_url('reviews'));
        }
        $name = $row['location_name'].'/reviews/'.$row['review_id'];
        if (!$this->gbp_api->reply($name, $text)) {
            $this->flash('error', 'Google did not accept the reply: '.$this->gbp_api->error);
            redirect(admin_url('reviews?r='.(int) $id));
        }
        $this->gbp_review_model->mark($id, array(
            'reply_text' => $text, 'reply_time' => date('Y-m-d H:i:s'), 'action' => 'replied_manual',
            'handled_at' => date('Y-m-d H:i:s'), 'handled_by' => (int) $this->admin['id'], 'error' => NULL,
        ));
        $this->flash('success', 'Reply posted on Google.');
        redirect(admin_url('reviews'));
    }

    /** POST reviews/{id}/done — the team has dealt with it (called the customer, replied on Google, …). */
    public function done($id)
    {
        $this->require_perm('leads.edit');
        $this->_post_only();
        $this->gbp_review_model->mark($id, array('handled_at' => date('Y-m-d H:i:s'), 'handled_by' => (int) $this->admin['id']));
        $this->flash('success', 'Marked as handled.');
        redirect(admin_url('reviews'));
    }

    /** POST reviews/check — run the daily check now. */
    public function check()
    {
        $this->require_perm('settings.manage');
        $this->_post_only();
        $this->load->library('review_service');
        $s = $this->review_service->daily_check();
        $msg = sprintf('Checked %d listing(s): %d new review(s), %d auto-replied, %d alert(s).', $s['locations'], $s['new'], $s['replied'], $s['alerted']);
        $this->flash($s['errors'] ? 'warning' : 'success', $msg.($s['errors'] ? ' '.implode(' ', $s['errors']) : ''));
        redirect(admin_url('reviews'));
    }

    // --- Google connection -------------------------------------------------------------

    /** POST reviews/credentials — OAuth client id/secret from Google Cloud. */
    public function credentials()
    {
        $this->require_perm('settings.manage');
        $this->_post_only();
        $id = trim((string) $this->input->post('client_id'));
        $secret = trim((string) $this->input->post('client_secret'));
        if (!preg_match('/\.apps\.googleusercontent\.com$/', $id) || strlen($secret) < 10) {
            $this->flash('error', 'That does not look like a Google OAuth client id / secret.');
            redirect(admin_url('reviews#setup'));
        }
        $this->gbp_review_model->save_setting('gbp_oauth_client', array('id' => $id, 'secret' => $secret));
        $this->flash('success', 'Saved. Now press "Connect Google account".');
        redirect(admin_url('reviews#setup'));
    }

    public function connect()
    {
        $this->require_perm('settings.manage');
        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('gbp_oauth_state', $state);
        $url = $this->gbp_api->auth_url($state);
        if ($url === '') {
            $this->flash('error', 'Add the Google OAuth client id and secret first.');
            redirect(admin_url('reviews#setup'));
        }
        redirect($url);
    }

    public function callback()
    {
        $this->require_perm('settings.manage');
        $state = (string) $this->input->get('state');
        $expected = (string) $this->session->userdata('gbp_oauth_state');
        $this->session->unset_userdata('gbp_oauth_state');
        if ($expected === '' || !hash_equals($expected, $state)) {
            $this->flash('error', 'Google sign-in expired. Try again.');
            redirect(admin_url('reviews#setup'));
        }
        if ($this->input->get('error')) {
            $this->flash('error', 'Google sign-in cancelled: '.$this->input->get('error', TRUE));
            redirect(admin_url('reviews#setup'));
        }
        if (!$this->gbp_api->finish_auth((string) $this->input->get('code'))) {
            $this->flash('error', 'Could not connect: '.$this->gbp_api->error);
            redirect(admin_url('reviews#setup'));
        }
        $locs = $this->_refresh_locations();
        $this->_auto_map($locs);
        $this->flash($locs ? 'success' : 'warning', $locs
            ? 'Google connected. '.count($locs).' listing(s) found; check the workshop matches below.'
            : 'Google connected, but no listings came back: '.($this->gbp_api->error ?: 'the API may not be approved yet.'));
        redirect(admin_url('reviews#setup'));
    }

    public function disconnect()
    {
        $this->require_perm('settings.manage');
        $this->_post_only();
        $this->gbp_review_model->delete_setting('gbp_oauth_token');
        $this->gbp_review_model->delete_setting('gbp_locations_cache');
        $this->flash('success', 'Google disconnected.');
        redirect(admin_url('reviews#setup'));
    }

    /** POST reviews/locations — which listing is which workshop (empty = ignore). */
    public function locations()
    {
        $this->require_perm('settings.manage');
        $this->_post_only();
        if ($this->input->post('refresh')) {
            $locs = $this->_refresh_locations();
            $this->flash($locs ? 'success' : 'error', $locs ? 'Listings refreshed.' : 'Could not load listings: '.$this->gbp_api->error);
            redirect(admin_url('reviews#setup'));
        }
        $available = (array) $this->gbp_review_model->setting('gbp_locations_cache', array());
        $map = (array) $this->input->post('workshop');
        $workshops = ymo_review_workshops();
        $out = array();
        foreach ($available as $l) {
            $key = md5($l['path']);
            $w = isset($map[$key]) ? (string) $map[$key] : '';
            if ($w !== '' && isset($workshops[$w])) {
                $out[] = array('path' => $l['path'], 'title' => $l['title'], 'workshop' => $w);
            }
        }
        $this->gbp_review_model->save_setting('gbp_locations', $out);
        $this->flash('success', count($out).' listing(s) will be checked every day.');
        redirect(admin_url('reviews#setup'));
    }

    protected function _refresh_locations()
    {
        $locs = $this->gbp_api->locations();
        if (!$locs) {
            return array();
        }
        $out = array();
        foreach ($locs as $l) {
            // v4 reviews API path: accounts/{a}/locations/{l}
            $out[] = array(
                'path'     => $l['account'].'/'.$l['name'],
                'title'    => $l['title'],
                'address'  => $l['address'],
                'place_id' => $l['place_id'],
            );
        }
        $this->gbp_review_model->save_setting('gbp_locations_cache', $out);
        return $out;
    }

    /** First connection: match listings to workshops by Google place id. */
    protected function _auto_map(array $locs)
    {
        if ($this->gbp_review_model->setting('gbp_locations', NULL)) {
            return;
        }
        $by_place = array();
        foreach (ymo_review_workshops() as $slug => $w) {
            $by_place[$w['place_id']] = $slug;
        }
        $out = array();
        foreach ($locs as $l) {
            if (!empty($l['place_id']) && isset($by_place[$l['place_id']])) {
                $out[] = array('path' => $l['path'], 'title' => $l['title'], 'workshop' => $by_place[$l['place_id']]);
            }
        }
        if ($out) {
            $this->gbp_review_model->save_setting('gbp_locations', $out);
        }
    }

    protected function _post_only()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
    }
}
