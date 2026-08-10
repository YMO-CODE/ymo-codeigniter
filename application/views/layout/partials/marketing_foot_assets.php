<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$mk_js_v = (int) @filemtime(FCPATH.'assets/js/ymo-marketing.js');
$bs_js_v = (int) @filemtime(FCPATH.'assets/vendor/bootstrap/js/bootstrap.bundle.min.js');
$bs_js_url = base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js?v='.$bs_js_v);
?>
<script src="<?= base_url('assets/js/ymo-marketing.js?v='.$mk_js_v); ?>" defer></script>
<?php
$ci = &get_instance();
$livechat_license = (int) $ci->config->item('livechat_license');
if ($livechat_license > 0) {
    $ci->load->view('layout/partials/livechat', array('defer' => TRUE));
}
?>
