<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ymo_css_v = (int) @filemtime(FCPATH.'assets/css/ymo.css');
$bs_css_v  = (int) @filemtime(FCPATH.'assets/vendor/bootstrap/css/bootstrap.min.css');
$fonts_poppins = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap';
$fonts_icons   = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css?v='.$bs_css_v); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/ymo.css?v='.$ymo_css_v); ?>">
<link rel="preload" href="<?= html_escape($fonts_poppins); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="<?= html_escape($fonts_icons); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="<?= html_escape($fonts_poppins); ?>">
    <link rel="stylesheet" href="<?= html_escape($fonts_icons); ?>">
</noscript>
