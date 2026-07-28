<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['adapter']    = 'file';
$config['backup']     = 'dummy';
$config['key_prefix'] = 'ymo_';
$config['cache_path'] = dirname(FCPATH).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
