<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Invalidate stale admin sessions after a deploy (storage/.session_epoch bump).
 */
function ymo_hook_enforce_deploy_session()
{
    if (defined('STDIN')) {
        return;
    }
    $ci = &get_instance();
    if (!$ci->input || $ci->input->is_cli_request()) {
        return;
    }
    ymo_enforce_deploy_session();
}
