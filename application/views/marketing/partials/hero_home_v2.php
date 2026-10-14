<?php defined('BASEPATH') OR exit('No direct script access allowed');

$image = !empty($hero['image']) && is_array($hero['image']) ? $hero['image'] : NULL;

$hero_panel = $hero;

if (function_exists('marketing_hero_trust_proof')) {

    $hero_panel['trust_proof'] = marketing_hero_trust_proof('light');

}

?>

<section class="ymo-hero ymo-hero--home-v2 ymo-hero--v2-radical" aria-label="Page hero">

    <div class="ymo-v2-hero-radical">

        <div class="ymo-v2-hero-radical__stage">

            <div class="ymo-v2-hero-radical__copy">

                <div class="ymo-v2-hero-radical__copy-inner">

                    <h1 class="ymo-hero__title ymo-v2-hero-display mb-3"><?= html_escape($hero_panel['h1']); ?></h1>

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

                    <?php $this->load->view('marketing/partials/hero_actions', array(

                        'hero'          => $hero_panel,

                        'actions_class' => 'ymo-hero__actions ymo-v2-hero-cta d-flex flex-wrap align-items-center gap-2',

                    )); ?>

                </div>

            </div>

            <?php if ($image): ?>

                <div class="ymo-v2-hero-radical__media">

                    <div class="ymo-v2-hero-radical__media-scrim"></div>

                    <?php $this->load->view('marketing/partials/hero_image', array(

                        'src'      => $image['src'],

                        'alt'      => $image['alt'],

                        'class'    => 'ymo-v2-hero-radical__photo',

                        'width'    => 1920,

                        'height'   => 1080,

                        'priority' => TRUE,

                    )); ?>

                </div>

            <?php endif; ?>

        </div>

        <?php $this->load->view('marketing/partials/hero_v2_metrics_foot', array(

            'hero'       => $hero_panel,

            'foot_class' => 'container ymo-v2-hero-foot ymo-v2-hero-foot--radical-home',

        )); ?>

    </div>

</section>

