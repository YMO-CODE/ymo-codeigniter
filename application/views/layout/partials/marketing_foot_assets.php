<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$mk_js_v = (int) @filemtime(FCPATH.'assets/js/ymo-marketing.js');
$bs_js_v = (int) @filemtime(FCPATH.'assets/vendor/bootstrap/js/bootstrap.bundle.min.js');
$bs_js_url = base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js?v='.$bs_js_v);
?>
<script src="<?= base_url('assets/js/ymo-marketing.js?v='.$mk_js_v); ?>" defer></script>
<script src="<?= base_url('assets/js/ymo-city.js?v='.(int) @filemtime(FCPATH.'assets/js/ymo-city.js')); ?>" defer></script>
<?php $this->load->view('layout/partials/form_submit_lock'); ?>
<?php
$ci = &get_instance();
$livechat_license = (int) $ci->config->item('livechat_license');
if ($livechat_license > 0) {
    $ci->load->view('layout/partials/livechat', array('defer' => TRUE));
}
if ($ci->config->item('ymochat_enabled')):
    $ymc_wa = function_exists('marketing_business_whatsapp_url') ? marketing_business_whatsapp_url('Hi YMO, I would like to book a car service') : 'https://wa.me/917744065904';
?>
<script>window.YMOCHAT = <?= json_encode(array('api' => rtrim(site_url('api/ymochat'), '/').'/', 'wa' => $ymc_wa, 'agent' => (string) $ci->config->item('ymochat_agent_name')), JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="<?= base_url('assets/js/ymochat.js?v='.(int) @filemtime(FCPATH.'assets/js/ymochat.js')); ?>" defer></script>
<?php endif; ?>
