<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="ymo-hero ymo-hero--minimal" aria-label="Page hero">
    <div class="container">
        <div class="ymo-hero__inner">
            <h1 class="ymo-hero__title mb-2"><?= html_escape($hero['h1']); ?></h1>
            <?php if (!empty($hero['lead'])): ?>
                <p class="ymo-hero__lead md-body-lg mb-3"><?= html_escape($hero['lead']); ?></p>
            <?php endif; ?>
            <?php $this->load->view('marketing/partials/hero_trust_proof', array('hero' => $hero)); ?>
            <?php if (!empty($hero['show_cta']) && (!empty($hero['cta_primary']) || !empty($hero['cta_secondary']))): ?>
                <div class="ymo-hero__actions d-flex flex-wrap gap-2 mt-3">
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
            <?php endif; ?>
        </div>
    </div>
</section>
