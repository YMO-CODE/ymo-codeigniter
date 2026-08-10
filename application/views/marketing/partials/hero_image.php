<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (empty($src)) {
    return;
}
$alt = isset($alt) ? (string) $alt : '';
$class = isset($class) ? (string) $class : '';
$width = isset($width) ? (int) $width : 960;
$height = isset($height) ? (int) $height : 720;
$priority = !empty($priority);
$raw = (string) $src;
if (strpos($raw, 'http://') !== 0 && strpos($raw, 'https://') !== 0 && strpos($raw, '//') !== 0
    && strpos($raw, 'assets/') !== 0 && strpos($raw, '/assets/') !== 0
    && function_exists('marketing_hero_image_url')) {
    $raw = marketing_hero_image_url($raw);
}
$webp = function_exists('marketing_image_webp_url') ? marketing_image_webp_url($raw) : '';
$fallback = $raw;
$display = $webp !== '' ? $webp : (function_exists('marketing_image_preferred_url') ? marketing_image_preferred_url($raw) : $raw);
$responsive = ($priority && $webp !== '' && function_exists('marketing_image_responsive_srcset'))
    ? marketing_image_responsive_srcset($webp)
    : array('src' => $display, 'srcset' => '', 'sizes' => '100vw');
$img_src = $responsive['src'] !== '' ? $responsive['src'] : $display;
$decoding = $priority ? 'sync' : 'async';
$loading = $priority ? 'eager' : 'lazy';
$attrs = 'class="'.html_escape($class).'"'
    .' alt="'.html_escape($alt).'"'
    .' width="'.(int) $width.'"'
    .' height="'.(int) $height.'"'
    .' decoding="'.html_escape($decoding).'"'
    .' loading="'.html_escape($loading).'"'
    .($priority ? ' fetchpriority="high"' : '');
if ($responsive['srcset'] !== '') {
    $attrs .= ' srcset="'.html_escape($responsive['srcset']).'" sizes="'.html_escape($responsive['sizes']).'"';
}
?>
<img src="<?= html_escape($img_src); ?>" <?= $attrs; ?>>
