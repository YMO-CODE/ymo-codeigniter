<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Friendly 404 (and other error) screens — wraps them in our layout so the
 * site still feels consistent when something goes wrong.
 */
class Errors extends MY_Controller
{
    public function show_404()
    {
        $this->output->set_status_header(404);
        $this->render('errors/not_found', array('title' => 'Page not found'));
    }
}
