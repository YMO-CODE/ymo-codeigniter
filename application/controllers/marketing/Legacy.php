<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Legacy extends Marketing_Controller
{
    /** 404 handler on marketing host — try legacy WP redirects first. */
    public function go()
    {
        $path = marketing_normalize_path($this->uri->uri_string());
        $resolved = marketing_resolve_page_path($path);
        if ($resolved['key'] !== '' && $resolved['redirect']
            && $resolved['canonical'] !== '' && $resolved['canonical'] !== $path) {
            marketing_redirect_to($resolved['canonical'], 301);
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
