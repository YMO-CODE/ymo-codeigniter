<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GET /geo/city — the visitor's city for the header switcher and city hints.
 *
 * Returns {"city":"indore","name":"Indore","source":"ip"} or {"city":null,...}.
 * Private and never cached, so marketing pages themselves stay cacheable.
 */
class Geo extends CI_Controller
{
    public function city()
    {
        $this->load->library('city_geo');
        $hit = $this->city_geo->detect();

        $out = array('city' => NULL, 'name' => NULL, 'source' => NULL);
        if ($hit !== NULL) {
            $city = marketing_city_by_slug($hit['slug']);
            $out = array(
                'city'   => $hit['slug'],
                'name'   => $city ? $city['name'] : ucfirst($hit['slug']),
                'source' => $hit['source'],
            );
        }

        $this->output
            ->set_status_header(200)
            ->set_header('Cache-Control: private, no-store, max-age=0')
            ->set_header('X-Robots-Tag: noindex, nofollow')
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($out));
    }
}
