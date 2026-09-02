<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$faq_title = isset($title) ? $title : 'Popular questions';
$faq_items = isset($items) ? $items : array();
?>
<?php if ($faq_items !== array()): ?>
    <?= marketing_render_faq_section_html($faq_title, $faq_items); ?>
<?php endif; ?>
