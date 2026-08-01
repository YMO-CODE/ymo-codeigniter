<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (empty($hero) || !is_array($hero) || empty($hero['h1'])) {
    return;
}

$type = isset($hero['type']) ? (string) $hero['type'] : 'minimal';
if ($type === 'home') {
    $partial = 'marketing/partials/hero_home';
} elseif (in_array($type, array('service', 'hub', 'locality', 'brand'), TRUE)) {
    $partial = 'marketing/partials/hero_split';
} else {
    $partial = 'marketing/partials/hero_minimal';
}

$this->load->view($partial, array('hero' => $hero));
