<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ymo_css_v = (int) @filemtime(FCPATH.'assets/css/ymo.css');
$bs_marketing = FCPATH.'assets/css/bootstrap-marketing.min.css';
$bs_css_v  = is_file($bs_marketing)
    ? (int) @filemtime($bs_marketing)
    : (int) @filemtime(FCPATH.'assets/vendor/bootstrap/css/bootstrap.min.css');
$bs_css_file = is_file($bs_marketing)
    ? 'assets/css/bootstrap-marketing.min.css'
    : 'assets/vendor/bootstrap/css/bootstrap.min.css';
$fonts_poppins = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap';
$fonts_icons   = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap';
$lcp_preload = '';
if (!empty($og_image) && function_exists('marketing_hero_image_url')) {
    $lcp_preload = marketing_hero_image_url($og_image);
    if (function_exists('marketing_image_preferred_url')) {
        $lcp_preload = marketing_image_preferred_url($lcp_preload);
    }
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php if ($lcp_preload !== ''): ?>
<link rel="preload" as="image" href="<?= html_escape($lcp_preload); ?>" fetchpriority="high">
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url($bs_css_file.'?v='.$bs_css_v); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/ymo.css?v='.$ymo_css_v); ?>">
<link rel="preload" href="<?= html_escape($fonts_poppins); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="<?= html_escape($fonts_icons); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="<?= html_escape($fonts_poppins); ?>">
    <link rel="stylesheet" href="<?= html_escape($fonts_icons); ?>">
</noscript>
