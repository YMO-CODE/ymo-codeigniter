<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$pricing_tiers = isset($pricing_tiers) ? $pricing_tiers : array();
$faq = isset($faq) ? $faq : array();
$body_faq_html = isset($body_faq_html) ? $body_faq_html : '';
$has_pricing = is_array($pricing_tiers) && $pricing_tiers !== array();
$has_faq = is_array($faq) && $faq !== array();
$has_body_faq = trim((string) $body_faq_html) !== '';
?>
<?php if ($has_pricing || $has_faq || $has_body_faq): ?>
<div class="ymo-supplement-stack">
    <?php if ($has_pricing): ?>
        <?= marketing_render_pricing_section_html($pricing_tiers); ?>
    <?php endif; ?>
    <?php if ($has_body_faq): ?>
        <?= $body_faq_html; ?>
    <?php endif; ?>
    <?php if ($has_faq): ?>
        <?php $this->load->view('marketing/partials/faq_section', array(
            'title' => 'Popular questions',
            'items' => $faq,
        )); ?>
    <?php endif; ?>
</div>
<?php endif; ?>
