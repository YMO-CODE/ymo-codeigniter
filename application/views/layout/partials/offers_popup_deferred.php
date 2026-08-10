<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$offers_css_v = (int) @filemtime(FCPATH.'assets/css/ymo-offers.css');
$offers_js_v  = (int) @filemtime(FCPATH.'assets/js/ymo-offers.js');
?>
<link rel="stylesheet" href="<?= base_url('assets/css/ymo-offers.css?v='.$offers_css_v); ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?= base_url('assets/css/ymo-offers.css?v='.$offers_css_v); ?>"></noscript>
<script>window.YMO_OFFERS = { apiUrl: <?= json_encode(site_url('api/offers/active'), JSON_UNESCAPED_SLASHES); ?> };</script>
<script data-defer-load="<?= html_escape(base_url('assets/js/ymo-offers.js?v='.$offers_js_v)); ?>"></script>
