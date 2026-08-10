<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ymo_min = FCPATH.'assets/css/ymo.min.css';
$ymo_css_v = (int) @filemtime(is_file($ymo_min) ? $ymo_min : FCPATH.'assets/css/ymo.css');
$ymo_css_file = is_file($ymo_min) ? 'assets/css/ymo.min.css' : 'assets/css/ymo.css';
$mk_css_v  = (int) @filemtime(FCPATH.'assets/css/marketing.css');
$bs_marketing = FCPATH.'assets/css/bootstrap-marketing.min.css';
$bs_css_v  = is_file($bs_marketing)
    ? (int) @filemtime($bs_marketing)
    : (int) @filemtime(FCPATH.'assets/vendor/bootstrap/css/bootstrap.min.css');
$bs_css_file = is_file($bs_marketing)
    ? 'assets/css/bootstrap-marketing.min.css'
    : 'assets/vendor/bootstrap/css/bootstrap.min.css';
$ymo_css_url = base_url($ymo_css_file.'?v='.$ymo_css_v);
$mk_css_url  = base_url('assets/css/marketing.css?v='.$mk_css_v);
$bs_css_url  = base_url($bs_css_file.'?v='.$bs_css_v);
$fonts_poppins = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap';
$fonts_icons   = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap';
$lcp_hints = array('mobile' => '', 'desktop' => '', 'type' => 'image/webp');
$lcp_og = isset($og_image) ? $og_image : '';
if ($lcp_og === '' && isset($canonical_path) && function_exists('marketing_resolve_og_image')) {
    $lcp_og = marketing_resolve_og_image($canonical_path, isset($page) && is_array($page) ? $page : array());
}
if ($lcp_og !== '' && function_exists('marketing_lcp_preload_hints')) {
    $lcp_hints = marketing_lcp_preload_hints($lcp_og);
}
$critical_css = '';
$critical_file = FCPATH.'assets/css/marketing-critical.min.css';
if (!is_file($critical_file)) {
    $critical_file = FCPATH.'assets/css/marketing-critical.css';
}
if (is_file($critical_file)) {
    $critical_css = trim((string) file_get_contents($critical_file));
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php if ($lcp_hints['mobile'] !== ''): ?>
<link rel="preload" as="image" href="<?= html_escape($lcp_hints['mobile']); ?>" type="<?= html_escape($lcp_hints['type']); ?>" fetchpriority="high" media="(max-width: 768px)">
<?php endif; ?>
<?php if ($lcp_hints['desktop'] !== ''): ?>
<link rel="preload" as="image" href="<?= html_escape($lcp_hints['desktop']); ?>" type="<?= html_escape($lcp_hints['type']); ?>" fetchpriority="high" media="(min-width: 769px)">
<?php endif; ?>
<?php if ($critical_css !== ''): ?>
<style><?= $critical_css; ?></style>
<?php endif; ?>
<?php
$deferred_styles = array($bs_css_url, $ymo_css_url);
if ($mk_css_v) {
    $deferred_styles[] = $mk_css_url;
}
foreach ($deferred_styles as $css_url):
?>
<link rel="preload" href="<?= html_escape($css_url); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="<?= html_escape($css_url); ?>"></noscript>
<?php endforeach; ?>
<link rel="preload" href="<?= html_escape($fonts_poppins); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="<?= html_escape($fonts_icons); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="<?= html_escape($fonts_poppins); ?>">
    <link rel="stylesheet" href="<?= html_escape($fonts_icons); ?>">
</noscript>
