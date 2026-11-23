<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GET /review/{workshop} — short link sent to customers (WhatsApp, SMS, email).
 * Redirects to Google's "write a review" box for that workshop's listing.
 */
class Review_link extends CI_Controller
{
    public function go($slug = 'hinjewadi')
    {
        $this->load->helper(array('review', 'marketing'));
        $slug = strtolower(preg_replace('/[^a-z]/i', '', (string) $slug));
        $all = ymo_review_workshops();
        if (!isset($all[$slug])) {
            $slug = 'hinjewadi';
        }
        log_message('info', '[review_link] click '.$slug);
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');
        $this->output->set_header('Cache-Control: no-store');
        redirect(ymo_review_google_url($slug), 'location', 302);
    }
}
