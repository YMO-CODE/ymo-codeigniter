<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$pricing_tiers = isset($pricing_tiers) ? $pricing_tiers : array();
?>
<?php if ($pricing_tiers !== array()): ?>
    <?= marketing_render_pricing_section_html($pricing_tiers); ?>
<?php endif; ?>
