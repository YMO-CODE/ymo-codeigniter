<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (empty($hero) || !is_array($hero) || empty($hero['h1'])) {
    return;
}

$type = isset($hero['type']) ? (string) $hero['type'] : 'minimal';
if ($type === 'home') {
    $partial = 'marketing/partials/hero_home';
} elseif ($type === 'service' || $type === 'hub' || $type === 'locality') {
    $partial = 'marketing/partials/hero_split';
} else {
    $partial = 'marketing/partials/hero_minimal';
}

$this->load->view($partial, array('hero' => $hero));
