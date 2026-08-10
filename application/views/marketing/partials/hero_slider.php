<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (empty($slides) || !is_array($slides)) {
    return;
}

$slides = array_values(array_filter($slides, function ($slide) {
    return is_array($slide) && !empty($slide['src']);
}));
if ($slides === array()) {
    return;
}

$slider_id   = !empty($slider_id) ? (string) $slider_id : 'ymo-hero-slider';
$interval_ms = isset($interval_ms) ? (int) $interval_ms : 6000;
$variant     = !empty($variant) ? (string) $variant : 'hero-media';
$multi       = count($slides) > 1;
?>
<div class="ymo-hero-slider ymo-hero-slider--<?= html_escape($variant); ?> carousel slide<?= $multi ? '' : ' ymo-hero-slider--single'; ?> h-100"
     id="<?= html_escape($slider_id); ?>"
    <?php if ($multi): ?>
     data-bs-interval="<?= (int) $interval_ms; ?>"
     data-bs-pause="hover"
     <?php endif; ?>
     aria-label="Hero image gallery">
    <?php if ($multi): ?>
        <div class="carousel-indicators ymo-hero-slider__indicators">
            <?php foreach ($slides as $i => $slide): ?>
                <button type="button"
                        data-bs-target="#<?= html_escape($slider_id); ?>"
                        data-bs-slide-to="<?= (int) $i; ?>"
                        class="<?= $i === 0 ? 'active' : ''; ?>"
                        aria-current="<?= $i === 0 ? 'true' : 'false'; ?>"
                        aria-label="Slide <?= (int) ($i + 1); ?> of <?= count($slides); ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="carousel-inner h-100">
        <?php foreach ($slides as $i => $slide):
            $alt = isset($slide['alt']) ? (string) $slide['alt'] : 'Your Mechanic Online';
            ?>
            <div class="carousel-item h-100<?= $i === 0 ? ' active' : ''; ?>">
                <?php $this->load->view('marketing/partials/hero_image', array(
                    'src'      => $slide['src'],
                    'alt'      => $alt,
                    'class'    => 'ymo-hero__photo d-block w-100 h-100',
                    'width'    => 960,
                    'height'   => 720,
                    'priority' => ($i === 0),
                )); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($multi): ?>
        <button class="carousel-control-prev ymo-hero-slider__control" type="button"
                data-bs-target="#<?= html_escape($slider_id); ?>" data-bs-slide="prev">
            <span class="ymo-hero-slider__control-icon mi" aria-hidden="true">chevron_left</span>
            <span class="visually-hidden">Previous slide</span>
        </button>
        <button class="carousel-control-next ymo-hero-slider__control" type="button"
                data-bs-target="#<?= html_escape($slider_id); ?>" data-bs-slide="next">
            <span class="ymo-hero-slider__control-icon mi" aria-hidden="true">chevron_right</span>
            <span class="visually-hidden">Next slide</span>
        </button>
    <?php endif; ?>
</div>
