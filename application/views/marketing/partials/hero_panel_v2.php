<?php defined('BASEPATH') OR exit('No direct script access allowed');
$chips_after_cta = !empty($hero['chips_after_cta']);
$render_chips = function () use ($hero) {
    if (empty($hero['chips'])) {
        return;
    }
    ?>
    <div class="ymo-hero__chips-wrap">
        <?php if (!empty($hero['chips_label'])): ?>
            <span class="ymo-hero__chips-label ymo-v2-hero-chips-label"><?= html_escape($hero['chips_label']); ?></span>
        <?php endif; ?>
        <div class="ymo-hero__chips">
            <?php foreach ($hero['chips'] as $chip): ?>
                <a href="<?= html_escape($chip['href']); ?>" class="md-chip md-chip--sm"><?= html_escape($chip['label']); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
};
?>
<div class="ymo-hero__panel">
    <?php if (!empty($hero['eyebrow'])): ?>
        <span class="ymo-hero__eyebrow ymo-v2-eyebrow ymo-v2-eyebrow--on-dark">
            <?php if (!empty($hero['eyebrow_icon'])): ?>
                <span class="mi mi-sm" aria-hidden="true"><?= html_escape($hero['eyebrow_icon']); ?></span>
            <?php endif; ?>
            <?= html_escape($hero['eyebrow']); ?>
        </span>
    <?php endif; ?>
    <h1 class="ymo-hero__title ymo-v2-hero-display ymo-v2-hero-display--sub mb-3"><?= html_escape($hero['h1']); ?></h1>
    <?php if (!empty($hero['lead'])): ?>
        <p class="ymo-hero__lead ymo-v2-hero-lead md-body-lg mb-4"><?= html_escape($hero['lead']); ?></p>
    <?php endif; ?>
    <?php if (!empty($hero['badges'])): ?>
        <div class="ymo-v2-hero-pills mb-4">
            <?php foreach ($hero['badges'] as $badge): ?>
                <span class="ymo-v2-hero-pill"><?= html_escape($badge); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!$chips_after_cta): ?>
        <?php $render_chips(); ?>
    <?php endif; ?>
    <?php $this->load->view('marketing/partials/hero_actions', array(
        'hero'          => $hero,
        'actions_class' => 'ymo-hero__actions ymo-v2-hero-cta d-flex flex-wrap align-items-center gap-2'.($chips_after_cta ? '' : ' mt-2'),
    )); ?>
    <?php if ($chips_after_cta): ?>
        <div class="mt-4">
            <?php $render_chips(); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($hero['phone_href']) && !empty($hero['phone']) && !empty($hero['show_phone'])): ?>
        <a class="ymo-hero__phone ymo-v2-hero-phone mt-3" href="<?= html_escape($hero['phone_href']); ?>">
            <span class="mi" aria-hidden="true">call</span>
            <?= html_escape($hero['phone']); ?>
        </a>
    <?php endif; ?>
</div>
