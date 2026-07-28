<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CI3's default_controller only supports top-level "class/method" pairs.
 * Host-based routes use subdirectory controllers (marketing/home, admin/dashboard).
 */
class MY_Router extends CI_Router
{
    protected function _set_default_controller()
    {
        if (empty($this->default_controller))
        {
            show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
        }

        $segments = explode('/', $this->default_controller);
        if (count($segments) >= 2
            && is_dir(APPPATH.'controllers/'.$segments[0]))
        {
            if (count($segments) === 2)
            {
                $segments[] = 'index';
            }
            $this->_set_request($segments);
            return;
        }

        parent::_set_default_controller();
    }
}
