<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
*/

$hook['pre_system'] = array(
    'class'    => '',
    'function' => 'ymo_hook_reconcile_session_cookies',
    'filename' => 'ymo_session_cookies.php',
    'filepath' => 'hooks',
    'params'   => array(),
);

$hook['pre_controller'] = array(
    'class'    => '',
    'function' => 'ymo_hook_redirect_legacy_admin_path',
    'filename' => 'ymo_legacy_admin_redirect.php',
    'filepath' => 'hooks',
    'params'   => array(),
);

$hook['post_controller_constructor'][] = array(
    'class'    => '',
    'function' => 'ymo_hook_enforce_deploy_session',
    'filename' => 'ymo_session_deploy.php',
    'filepath' => 'hooks',
    'params'   => array(),
);
