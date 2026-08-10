<?php defined('BASEPATH') OR exit('No direct script access allowed');
$image = !empty($hero['image']) && is_array($hero['image']) ? $hero['image'] : NULL;
?>
<section class="ymo-hero ymo-hero--home" aria-label="Page hero">
    <?php if ($image): ?>
        <img
            class="ymo-hero__bg"
            src="<?= html_escape(function_exists('marketing_image_preferred_url') ? marketing_image_preferred_url($image['src']) : $image['src']); ?>"
            alt="<?= html_escape($image['alt']); ?>"
            fetchpriority="high"
            loading="eager"
            decoding="async"
            width="1920"
            height="720"
        >
    <?php endif; ?>
    <div class="ymo-hero__scrim"></div>
    <div class="container ymo-hero__inner">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="ymo-hero__title mb-3"><?= html_escape($hero['h1']); ?></h1>
                <?php if (!empty($hero['lead'])): ?>
                    <p class="ymo-hero__lead md-body-lg mb-4"><?= html_escape($hero['lead']); ?></p>
                <?php endif; ?>
                <?php $this->load->view('marketing/partials/hero_trust_proof', array('hero' => $hero)); ?>
                <?php if (!empty($hero['badges'])): ?>
                    <div class="ymo-hero__trust mb-4">
                        <?php foreach ($hero['badges'] as $badge): ?>
                            <span class="ymo-hero__badge"><?= html_escape($badge); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php $this->load->view('marketing/partials/hero_actions', array(
                    'hero'          => $hero,
                    'actions_class' => 'ymo-hero__actions d-flex flex-wrap gap-2',
                )); ?>
            </div>
        </div>
    </div>
</section>
