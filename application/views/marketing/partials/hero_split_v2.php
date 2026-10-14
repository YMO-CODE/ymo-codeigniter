<?php defined('BASEPATH') OR exit('No direct script access allowed');

$image = !empty($hero['image']) && is_array($hero['image']) ? $hero['image'] : NULL;

$slides = !empty($hero['slides']) && is_array($hero['slides']) ? $hero['slides'] : NULL;

$has_media = ($slides && count($slides) > 1) || $image;

$hero_panel = $hero;

$hero_panel['trust_proof'] = array('show' => FALSE);

$stage_class = 'ymo-v2-hero-radical__stage';

if (!$has_media) {

    $stage_class .= ' ymo-v2-hero-radical__stage--solo';

}

?>

<section class="ymo-hero ymo-hero--split-v2 ymo-hero--v2-radical ymo-hero--v2-radical-subpage" aria-label="Page hero">

    <div class="ymo-v2-hero-radical">

        <div class="<?= html_escape($stage_class); ?>">

            <div class="ymo-v2-hero-radical__copy<?= $has_media ? '' : ' ymo-v2-hero-radical__copy--full'; ?>">

                <div class="container">

                <div class="ymo-v2-hero-radical__copy-inner">

                    <?php $this->load->view('marketing/partials/hero_panel_v2', array('hero' => $hero_panel)); ?>

                </div>

                </div>

            </div>

            <?php if ($slides && count($slides) > 1): ?>

                <div class="ymo-v2-hero-radical__media">

                    <div class="ymo-v2-hero-radical__media-scrim"></div>

                    <div class="ymo-v2-hero-radical__media-frame ymo-v2-hero-media">

                        <?php $this->load->view('marketing/partials/hero_slider', array(

                            'slides'      => $slides,

                            'interval_ms' => isset($hero['slider_interval_ms']) ? (int) $hero['slider_interval_ms'] : 6000,

                            'slider_id'   => !empty($hero['slider_id']) ? $hero['slider_id'] : 'ymo-hero-slider',

                            'variant'     => 'hero-media',

                        )); ?>

                    </div>

                </div>

            <?php elseif ($image): ?>

                <div class="ymo-v2-hero-radical__media">

                    <div class="ymo-v2-hero-radical__media-scrim"></div>

                    <?php $this->load->view('marketing/partials/hero_image', array(

                        'src'      => $image['src'],

                        'alt'      => $image['alt'],

                        'class'    => 'ymo-v2-hero-radical__photo',

                        'width'    => 960,

                        'height'   => 720,

                        'priority' => TRUE,

                    )); ?>

                </div>

            <?php endif; ?>

        </div>

        <?php $this->load->view('marketing/partials/hero_v2_metrics_foot', array(

            'hero'       => $hero,

            'foot_class' => 'container ymo-v2-hero-foot ymo-v2-hero-foot--radical-home',

        )); ?>

    </div>

</section>

