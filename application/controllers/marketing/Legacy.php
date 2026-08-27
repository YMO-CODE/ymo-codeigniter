<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Legacy extends Marketing_Controller
{
    /** 404 handler on marketing host — try legacy WP redirects first. */
    public function go()
    {
        marketing_enforce_canonical_path();
        $path = marketing_normalize_path($this->uri->uri_string());
        $resolved = marketing_resolve_page_path($path);
        if ($resolved['key'] !== '') {
            if ($resolved['redirect']
                && $resolved['canonical'] !== '' && $resolved['canonical'] !== $path) {
                marketing_redirect_to($resolved['canonical'], 301);
            }
            // Fallback when slug exists in config but was not in the route table.
            require_once APPPATH.'controllers/marketing/Pages.php';
            $pages = new Pages();
            $pages->show();
            return;
        }

        $target = marketing_lookup_redirect($path);
        if ($target === NULL || $target === '') {
            $target = marketing_lookup_redirect(marketing_ascii_slug($path));
        }
        if ($target !== NULL) {
            if ($target === '') {
                redirect(site_url('/'), 'location', 301);
            }
            marketing_redirect_to($target, 301);
        }
        show_404();
    }
}
