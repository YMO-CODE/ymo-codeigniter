<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ymo_js_v = (int) @filemtime(FCPATH.'assets/js/ymo.js');
$bs_js_v  = (int) @filemtime(FCPATH.'assets/vendor/bootstrap/js/bootstrap.bundle.min.js');
?>
<script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js?v='.$bs_js_v); ?>" defer></script>
<script src="<?= base_url('assets/js/ymo.js?v='.$ymo_js_v); ?>" defer></script>
