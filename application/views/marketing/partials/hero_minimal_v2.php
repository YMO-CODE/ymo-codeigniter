<?php defined('BASEPATH') OR exit('No direct script access allowed');
$hero_panel = $hero;
$hero_panel['trust_proof'] = array('show' => FALSE);
?>
<section class="ymo-hero ymo-hero--minimal-v2 ymo-hero--v2-radical ymo-hero--v2-radical-minimal ymo-hero--v2-radical-subpage" aria-label="Page hero">
    <div class="ymo-v2-hero-radical">
        <div class="ymo-v2-hero-radical__stage ymo-v2-hero-radical__stage--solo">
            <div class="ymo-v2-hero-radical__copy ymo-v2-hero-radical__copy--full">
                <div class="container">
                <div class="ymo-v2-hero-radical__copy-inner">
                    <?php if (!empty($hero_panel['eyebrow'])): ?>
                        <span class="ymo-hero__eyebrow ymo-v2-eyebrow ymo-v2-eyebrow--on-dark">
                            <?php if (!empty($hero_panel['eyebrow_icon'])): ?>
                                <span class="mi mi-sm" aria-hidden="true"><?= html_escape($hero_panel['eyebrow_icon']); ?></span>
                            <?php endif; ?>
                            <?= html_escape($hero_panel['eyebrow']); ?>
                        </span>
                    <?php endif; ?>
                    <h1 class="ymo-hero__title ymo-v2-hero-display ymo-v2-hero-display--sub mb-3"><?= html_escape($hero_panel['h1']); ?></h1>
                    <?php if (!empty($hero_panel['lead'])): ?>
                        <p class="ymo-hero__lead ymo-v2-hero-lead md-body-lg mb-4"><?= html_escape($hero_panel['lead']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($hero_panel['badges'])): ?>
                        <div class="ymo-v2-hero-pills mb-4">
                            <?php foreach ($hero_panel['badges'] as $badge): ?>
                                <span class="ymo-v2-hero-pill"><?= html_escape($badge); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($hero_panel['show_cta']) && (!empty($hero_panel['cta_primary']) || !empty($hero_panel['cta_secondary']) || !empty($hero_panel['cta_quick_book']))): ?>
                        <?php $this->load->view('marketing/partials/hero_actions', array(
                            'hero'          => $hero_panel,
                            'actions_class' => 'ymo-hero__actions ymo-v2-hero-cta d-flex flex-wrap align-items-center gap-2',
                        )); ?>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
        <?php $this->load->view('marketing/partials/hero_v2_metrics_foot', array(
            'hero'       => $hero,
            'foot_class' => 'container ymo-v2-hero-foot ymo-v2-hero-foot--radical-home',
        )); ?>
    </div>
</section>
