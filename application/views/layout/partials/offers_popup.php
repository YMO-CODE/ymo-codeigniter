<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
if (!$ci->db->table_exists('site_offers')) {
    return;
}
$ci->load->model('offer_model');
$active_offers = $ci->offer_model->list_active_public();
if (empty($active_offers)) {
    return;
}
$bootstrap = array('ok' => TRUE, 'offers' => $active_offers);
?>
<link rel="stylesheet" href="<?= base_url('assets/css/ymo-offers.css'); ?>">
<script>window.YMO_OFFERS_BOOTSTRAP = <?= json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="<?= base_url('assets/js/ymo-offers.js'); ?>" defer></script>
