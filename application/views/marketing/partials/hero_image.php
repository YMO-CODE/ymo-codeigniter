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
$fallback = $raw;
$webp = function_exists('marketing_image_webp_url') ? marketing_image_webp_url($raw) : '';
$display = $webp !== '' ? $webp : (function_exists('marketing_image_preferred_url') ? marketing_image_preferred_url($raw) : $raw);
$attrs = 'class="'.html_escape($class).'"'
    .' alt="'.html_escape($alt).'"'
    .' width="'.(int) $width.'"'
    .' height="'.(int) $height.'"'
    .' decoding="async"'
    .($priority ? ' fetchpriority="high" loading="eager"' : ' loading="lazy"');
?>
<?php if ($webp !== ''): ?>
<picture>
    <source srcset="<?= html_escape($webp); ?>" type="image/webp">
    <img src="<?= html_escape($fallback); ?>" <?= $attrs; ?>>
</picture>
<?php else: ?>
<img src="<?= html_escape($display); ?>" <?= $attrs; ?>>
<?php endif; ?>
