<?php defined('BASEPATH') OR exit('No direct script access allowed');
$chips_after_cta = !empty($hero['chips_after_cta']);
$render_chips = function () use ($hero) {
    if (empty($hero['chips'])) {
        return;
    }
    ?>
    <div class="ymo-hero__chips-wrap">
        <?php if (!empty($hero['chips_label'])): ?>
            <span class="ymo-hero__chips-label"><?= html_escape($hero['chips_label']); ?></span>
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
        <span class="ymo-hero__eyebrow">
            <?php if (!empty($hero['eyebrow_icon'])): ?>
                <span class="mi mi-sm" aria-hidden="true"><?= html_escape($hero['eyebrow_icon']); ?></span>
            <?php endif; ?>
            <?= html_escape($hero['eyebrow']); ?>
        </span>
    <?php endif; ?>
    <h1 class="ymo-hero__title mb-3"><?= html_escape($hero['h1']); ?></h1>
    <?php if (!empty($hero['lead'])): ?>
        <p class="ymo-hero__lead md-body-lg mb-4"><?= html_escape($hero['lead']); ?></p>
    <?php endif; ?>
    <?php $this->load->view('marketing/partials/hero_trust_proof', array('hero' => $hero)); ?>
    <?php if (!empty($hero['badges'])): ?>
        <div class="ymo-hero__trust mb-3">
            <?php foreach ($hero['badges'] as $badge): ?>
                <span class="ymo-hero__badge"><?= html_escape($badge); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!$chips_after_cta): ?>
        <?php $render_chips(); ?>
    <?php endif; ?>
    <div class="ymo-hero__actions d-flex flex-wrap gap-2<?= $chips_after_cta ? '' : ' mt-2'; ?>">
        <?php if (!empty($hero['cta_primary'])): ?>
            <?php $btn = $hero['cta_primary']; ?>
            <a href="<?= html_escape($btn['href']); ?>" class="<?= html_escape(isset($btn['class']) ? $btn['class'] : 'md-btn md-btn--filled md-btn--lg'); ?>">
                <?php if (!empty($btn['icon'])): ?>
                    <span class="mi mi-leading"><?= html_escape($btn['icon']); ?></span>
                <?php endif; ?>
                <?= html_escape($btn['label']); ?>
            </a>
        <?php endif; ?>
        <?php if (!empty($hero['cta_secondary'])): ?>
            <?php $btn = $hero['cta_secondary']; ?>
            <a href="<?= html_escape($btn['href']); ?>" class="<?= html_escape(isset($btn['class']) ? $btn['class'] : 'md-btn md-btn--outlined md-btn--lg'); ?>">
                <?= html_escape($btn['label']); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php if ($chips_after_cta): ?>
        <div class="mt-4">
            <?php $render_chips(); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($hero['phone_href']) && !empty($hero['phone']) && !empty($hero['show_phone'])): ?>
        <a class="ymo-hero__phone mt-3" href="<?= html_escape($hero['phone_href']); ?>">
            <span class="mi" aria-hidden="true">call</span>
            <?= html_escape($hero['phone']); ?>
        </a>
    <?php endif; ?>
</div>
