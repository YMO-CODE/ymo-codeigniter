<?php defined('BASEPATH') OR exit('No direct script access allowed');
$image = !empty($hero['image']) && is_array($hero['image']) ? $hero['image'] : NULL;
$slides = !empty($hero['slides']) && is_array($hero['slides']) ? $hero['slides'] : NULL;
$type = isset($hero['type']) ? (string) $hero['type'] : 'hub';
$modifiers = array('ymo-hero--split');
if ($type === 'service' || $type === 'brand') {
    $modifiers[] = 'ymo-hero--service';
} elseif ($type === 'locality') {
    $modifiers[] = 'ymo-hero--locality';
}
?>
<section class="ymo-hero <?= html_escape(implode(' ', $modifiers)); ?>" aria-label="Page hero">
    <div class="container ymo-hero__inner">
        <div class="ymo-hero__split">
            <?php $this->load->view('marketing/partials/hero_panel', array('hero' => $hero)); ?>
            <?php if ($slides && count($slides) > 1): ?>
                <div class="ymo-hero__media">
                    <?php $this->load->view('marketing/partials/hero_slider', array(
                        'slides'      => $slides,
                        'interval_ms' => isset($hero['slider_interval_ms']) ? (int) $hero['slider_interval_ms'] : 6000,
                        'slider_id'   => !empty($hero['slider_id']) ? $hero['slider_id'] : 'ymo-hero-slider',
                        'variant'     => 'hero-media',
                    )); ?>
                </div>
            <?php elseif ($image): ?>
                <div class="ymo-hero__media">
                    <img
                        class="ymo-hero__photo"
                        src="<?= html_escape(function_exists('marketing_image_preferred_url') ? marketing_image_preferred_url($image['src']) : $image['src']); ?>"
                        alt="<?= html_escape($image['alt']); ?>"
                        fetchpriority="high"
                        loading="eager"
                        decoding="async"
                        width="960"
                        height="720"
                    >
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
