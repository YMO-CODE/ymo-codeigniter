<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GET /media/gbp/{id}/{hash} — photo of a CRM-written Google post, fetched by
 * Google when the post is published. The hash makes the URL unguessable.
 */
class Gbp_media extends CI_Controller
{
    public function show($id = 0, $hash = '')
    {
        $hash = preg_replace('/[^a-f0-9]/', '', strtolower((string) $hash));
        $this->load->model('gbp_post_model');
        $row = $hash !== '' ? $this->gbp_post_model->image((int) $id, $hash) : NULL;
        if (!$row || $row['image'] === NULL || $row['image'] === '') {
            show_404();
            return;
        }
        $this->output->set_content_type('image/jpeg')
            ->set_header('Cache-Control: public, max-age=31536000, immutable')
            ->set_header('X-Robots-Tag: noindex')
            ->set_output($row['image']);
    }
}
