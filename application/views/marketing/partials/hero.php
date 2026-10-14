<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (empty($hero) || !is_array($hero) || empty($hero['h1'])) {
    return;
}

$type = isset($hero['type']) ? (string) $hero['type'] : 'minimal';
$theme_v2 = !empty($marketing_theme_v2);

if ($theme_v2) {
    if ($type === 'home') {
        $partial = 'marketing/partials/hero_home_v2';
    } elseif (function_exists('marketing_hero_v2_uses_split_layout') && marketing_hero_v2_uses_split_layout($hero)) {
        $partial = 'marketing/partials/hero_split_v2';
    } else {
        $partial = 'marketing/partials/hero_minimal_v2';
    }
} elseif ($type === 'home') {
    $partial = 'marketing/partials/hero_home';
} elseif (in_array($type, array('service', 'hub', 'locality', 'brand'), TRUE)) {
    $partial = 'marketing/partials/hero_split';
} else {
    $partial = 'marketing/partials/hero_minimal';
}

$this->load->view($partial, array('hero' => $hero));
