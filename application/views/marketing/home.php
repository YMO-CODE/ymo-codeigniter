<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$hero_page = array(
    'h1'           => isset($h1) ? $h1 : '',
    'intro'        => 'Affordable rates, expert mechanics, and specialised services - with free pick-up across Pune, Indore, and Nashik.',
    'page_type'    => 'home',
    'og_image'     => isset($og_image) ? $og_image : '/assets/img/marketing/revslider/main/image_01.jpg',
);
echo ymo_marketing_render_hero('', $hero_page, isset($booking_url) ? $booking_url : '');
?>

<section class="container pt-4 pb-2">
    <div class="ymo-home-luxury md-card-filled p-4 p-md-5 mb-0">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="md-label-lg d-block mb-2">Premium &amp; luxury cars</span>
                <h2 class="md-headline-md mb-2">Mercedes, BMW, Audi &amp; more</h2>
                <p class="md-body-md mb-0">Workshop-grade luxury car servicing in Pune - specialist technicians, genuine-spec care, and free pick-up for premium brands.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?= site_url('premium-luxury-car-service-pune'); ?>" class="md-btn md-btn--filled">Luxury car service</a>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h2 class="md-headline-md mb-1">Popular car services</h2>
            <p class="md-body-md mb-0">Periodic service, AC repair, denting, polishing, and more.</p>
        </div>
        <a href="<?= site_url('services'); ?>" class="md-btn md-btn--outlined md-btn--sm">All services</a>
    </div>
    <div class="row g-4">
        <?php foreach ($services as $svc): ?>
            <div class="col-md-6 col-lg-4">
                <a href="<?= site_url($svc['slug']); ?>" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">
                    <span class="mi mi-xl md-icon-primary"><?= html_escape($svc['icon']); ?></span>
                    <h3 class="md-title-md mt-3 mb-1 text-dark"><?= html_escape($svc['title']); ?></h3>
                    <p class="md-body-md mb-0"><?= html_escape($svc['teaser']); ?></p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($city_strip)): ?>
<section class="container pb-5">
    <div class="ymo-home-cities md-card-filled p-4 p-md-5">
        <h2 class="md-headline-md mb-2">We serve Pune, Indore &amp; Nashik</h2>
        <p class="md-body-md mb-3">Free doorstep pick-up and drop across all three cities - browse services and neighbourhoods on each city page.</p>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($city_strip as $city): ?>
                <a href="<?= site_url($city['hub_path']); ?>" class="md-chip md-chip--filled"><?= html_escape($city['name']); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($brand_cards)): ?>
<section class="container pb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h2 class="md-headline-md mb-1">Brands we service</h2>
            <p class="md-body-md mb-0">Maruti, Hyundai, Tata, Honda, and more - across Pune, Indore &amp; Nashik.</p>
        </div>
        <a href="<?= site_url('brands'); ?>" class="md-btn md-btn--outlined md-btn--sm">All brands</a>
    </div>
    <div class="row g-3">
        <?php foreach ($brand_cards as $brand): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= site_url($brand['slug']); ?>" class="md-chip md-chip--outlined w-100 text-center text-decoration-none d-block"><?= html_escape($brand['title']); ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="container pb-5">
    <h2 class="md-headline-md mb-4">Why choose YMO?</h2>
    <div class="row g-4">
        <?php foreach ($benefits as $benefit): ?>
            <div class="col-md-4">
                <div class="md-card-elevated h-100">
                    <span class="mi mi-xl md-icon-primary"><?= html_escape($benefit['icon']); ?></span>
                    <h3 class="md-title-md mt-3"><?= html_escape($benefit['title']); ?></h3>
                    <p class="md-body-md mb-0"><?= html_escape($benefit['body']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container pb-5">
    <div class="md-card-filled text-center py-4 px-3">
        <h2 class="md-title-lg mb-2">Ready to book?</h2>
        <p class="md-body-md mb-3">Pick a package, add your car, confirm - we call you to schedule pick-up.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="<?= html_escape($booking_url); ?>" class="md-btn md-btn--filled md-btn--lg">View packages &amp; book</a>
            <a href="<?= html_escape(ymo_booking_url('quick-book')); ?>" class="md-btn md-btn--tonal md-btn--lg">
                <span class="mi mi-leading">send</span>Quick book - no login
            </a>
        </div>
        <p class="md-body-md mt-3 mb-0"><a href="<?= site_url('why-choose-ymo'); ?>">Why choose YMO over a doorstep mechanic?</a></p>
    </div>
</section>
