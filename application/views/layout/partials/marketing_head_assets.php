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
<style>
.ymo-marketing .ymo-hero--home{position:relative;overflow:hidden;min-height:clamp(320px,42vw,520px);display:flex;align-items:center;background:#212529;color:#fff}
.ymo-marketing .ymo-hero--home .ymo-hero__bg,.ymo-marketing .ymo-hero--home picture{position:absolute;inset:0;width:100%;height:100%}
.ymo-marketing .ymo-hero--home .ymo-hero__bg,.ymo-marketing .ymo-hero--home picture img{width:100%;height:100%;object-fit:cover}
.ymo-marketing .ymo-hero--home .ymo-hero__scrim{position:absolute;inset:0;z-index:1;background:linear-gradient(135deg,rgba(17,20,24,.82),rgba(17,20,24,.55))}
.ymo-marketing .ymo-hero--home .ymo-hero__inner{position:relative;z-index:2;padding:clamp(3rem,8vw,5rem) 0 clamp(2.75rem,6vw,4.25rem);width:100%}
.ymo-marketing .ymo-hero--home .ymo-hero__title{font-size:clamp(1.875rem,3.5vw,2.75rem);font-weight:700;color:#fff}
</style>
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
