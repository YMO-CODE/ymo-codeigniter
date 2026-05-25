<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
*/

$hook['pre_controller'] = array(
    'class'    => '',
    'function' => 'ymo_hook_redirect_legacy_admin_path',
    'filename' => 'ymo_legacy_admin_redirect.php',
    'filepath' => 'hooks',
    'params'   => array(),
);
