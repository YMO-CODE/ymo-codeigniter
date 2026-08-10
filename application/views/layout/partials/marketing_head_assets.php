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
$fonts_icons   = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap';

$critical_css = '';
$critical_file = FCPATH.'assets/css/marketing-critical-shell.min.css';
if (!is_file($critical_file)) {
    $critical_file = FCPATH.'assets/css/marketing-critical-shell.css';
}
if (is_file($critical_file)) {
    $critical_css = trim((string) file_get_contents($critical_file));
}
?>
<?php if ($critical_css !== ''): ?>
<style><?= $critical_css; ?></style>
<?php endif; ?>
<link rel="preload" href="<?= html_escape($bs_css_url); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="<?= html_escape($ymo_css_url); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="<?= html_escape($mk_css_url); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
<link rel="stylesheet" href="<?= html_escape($bs_css_url); ?>">
<link rel="stylesheet" href="<?= html_escape($ymo_css_url); ?>">
<link rel="stylesheet" href="<?= html_escape($mk_css_url); ?>">
</noscript>
<link rel="preload" href="<?= html_escape($fonts_icons); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
<link rel="stylesheet" href="<?= html_escape($fonts_icons); ?>">
</noscript>
