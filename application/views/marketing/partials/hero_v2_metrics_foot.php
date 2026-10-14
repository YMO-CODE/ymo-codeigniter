<?php defined('BASEPATH') OR exit('No direct script access allowed');
$trust_cfg = function_exists('marketing_trust_config') ? marketing_trust_config() : array();
if (empty($trust_cfg['show_in_hero'])) {
    return;
}
$foot_class = isset($foot_class) ? trim((string) $foot_class) : 'container ymo-v2-hero-foot ymo-v2-hero-foot--radical-home';
if ($foot_class === '') {
    $foot_class = 'container ymo-v2-hero-foot';
}
?>
<div class="<?= html_escape($foot_class); ?>">
    <?php $this->load->view('marketing/partials/hero_trust_metrics_v2', array(
        'hero' => isset($hero) && is_array($hero) ? $hero : array(),
    )); ?>
</div>
