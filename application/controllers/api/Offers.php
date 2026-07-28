<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public read API for site offers (WordPress + shared widget).
 */
class Offers extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('offer_model');
    }

    /** GET /api/offers/active */
    public function active()
    {
        $this->_apply_cors();

        if ($this->input->method() === 'options') {
            return $this->output->set_status_header(204);
        }

        if (!$this->db->table_exists('site_offers')) {
            return $this->_json(array('ok' => TRUE, 'offers' => array()), 200, TRUE);
        }

        $this->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
        $cache_key = 'offers_active_public';
        $payload = $this->cache->get($cache_key);
        if ($payload === FALSE || !is_array($payload)) {
            $offers = $this->offer_model->list_active_public();
            $payload = array('ok' => TRUE, 'offers' => $offers);
            $this->cache->save($cache_key, $payload, 60);
        }
        return $this->_json($payload, 200, TRUE);
    }

    protected function _apply_cors()
    {
        $origin = $this->input->get_request_header('Origin', TRUE);
        $allowed = $this->config->item('offer_cors_origins');
        if (!is_array($allowed)) {
            $allowed = array();
        }

        if ($origin && in_array($origin, $allowed, TRUE)) {
            header('Access-Control-Allow-Origin: '.$origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
        }
    }

    protected function _json(array $payload, $code = 200, $cacheable = FALSE)
    {
        if ($cacheable) {
            header('Cache-Control: public, max-age=60');
        }
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
