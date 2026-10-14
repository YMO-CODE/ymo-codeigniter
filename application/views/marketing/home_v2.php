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

<div class="ymo-v2-home ymo-v2-home--after-radical">
<section class="ymo-v2-section ymo-v2-section--tight ymo-v2-section--surface">
    <div class="container">
        <div class="ymo-v2-band ymo-v2-spotlight">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <span class="md-label-lg d-block mb-2">Premium &amp; luxury cars</span>
                    <h2 class="ymo-v2-display-sm mb-2">Mercedes, BMW, Audi &amp; more</h2>
                    <p class="ymo-v2-prose mb-0">Workshop-grade luxury car servicing in Pune - specialist technicians, genuine-spec care, and free pick-up for premium brands.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= site_url('premium-luxury-car-service-pune'); ?>" class="md-btn md-btn--filled">Luxury car service</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ymo-v2-section">
    <div class="container">
        <div class="ymo-v2-section-head">
            <div>
                <h2 class="ymo-v2-display-sm mb-1">Popular car services</h2>
                <p class="ymo-v2-prose mb-0">Periodic service, AC repair, denting, polishing, and more.</p>
            </div>
            <a href="<?= site_url('services'); ?>" class="md-btn md-btn--outlined md-btn--sm">All services</a>
        </div>
        <?php
        $home_orbit_count = count($services);
        $home_orbit_ring  = $home_orbit_count >= 4;
        ?>
        <div class="ymo-v2-card-orbit-viewport<?= $home_orbit_ring ? ' ymo-v2-card-orbit-viewport--active' : ''; ?>">
            <div class="row g-4 ymo-v2-card-grid<?= $home_orbit_ring ? ' ymo-v2-card-grid--orbit' : ''; ?>"<?= $home_orbit_ring ? ' style="--orbit-count: '.(int) $home_orbit_count.';"' : ''; ?>>
                <?php foreach ($services as $i => $svc): ?>
                    <div class="col-md-6 col-lg-4 d-flex"<?= $home_orbit_ring ? ' style="--orbit-i: '.(int) $i.';"' : ''; ?>>
                        <?php $this->load->view('marketing/partials/service_card_link', array('svc' => $svc)); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($city_strip)): ?>
<section class="ymo-v2-section ymo-v2-section--surface">
    <div class="container">
        <div class="ymo-v2-band">
            <h2 class="ymo-v2-display-sm mb-2">We serve Pune, Indore &amp; Nashik</h2>
            <p class="ymo-v2-prose mb-3">Free doorstep pick-up and drop across all three cities - browse services and neighbourhoods on each city page.</p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($city_strip as $city): ?>
                    <a href="<?= site_url($city['hub_path']); ?>" class="md-chip md-chip--filled">Car servicing in <?= html_escape($city['name']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($brand_cards)): ?>
<section class="ymo-v2-section">
    <div class="container">
        <div class="ymo-v2-section-head">
            <div>
                <h2 class="ymo-v2-display-sm mb-1">Brands we service</h2>
                <p class="ymo-v2-prose mb-0">Maruti, Hyundai, Tata, Honda, and more - across Pune, Indore &amp; Nashik.</p>
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
    </div>
</section>
<?php endif; ?>

<section class="ymo-v2-section">
    <div class="container ymo-v2-steps">
        <h2 class="ymo-v2-display-sm mb-3">How it works</h2>
        <p class="ymo-v2-prose mb-4">Book online, get free pick-up, stay updated on WhatsApp, and receive your car back with invoice.</p>
        <?= function_exists('marketing_how_it_works_steps_html') ? marketing_how_it_works_steps_html() : ''; ?>
        <p class="ymo-v2-prose mt-3 mb-0"><a href="<?= site_url('how-it-works'); ?>">Full guide — how YMO works</a></p>
    </div>
</section>

<section class="ymo-v2-section">
    <div class="container">
        <div class="ymo-v2-section-head">
            <div>
                <h2 class="ymo-v2-display-sm mb-1">What customers say</h2>
                <p class="ymo-v2-prose mb-0">Recent feedback from Google reviews.</p>
            </div>
            <a href="<?= site_url('reviews'); ?>" class="md-btn md-btn--outlined md-btn--sm">All reviews</a>
        </div>
        <?= function_exists('marketing_render_reviews_cards') ? marketing_render_reviews_cards(3) : ''; ?>
    </div>
</section>

<section class="ymo-v2-section">
    <div class="container">
        <h2 class="ymo-v2-display-sm mb-4">Why choose YMO?</h2>
        <div class="row g-4 ymo-v2-card-grid ymo-v2-benefits">
            <?php foreach ($benefits as $benefit): ?>
                <div class="col-md-4 d-flex">
                    <?php $this->load->view('marketing/partials/feature_card_static', array(
                        'icon'  => $benefit['icon'],
                        'title' => $benefit['title'],
                        'body'  => $benefit['body'],
                    )); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="ymo-v2-cta-band ymo-v2-cta-band--card">
    <div class="container">
        <div class="ymo-v2-cta-band__panel md-card-filled text-center">
            <h2 class="md-title-lg mb-2">Ready to book?</h2>
            <p class="md-body-md mb-3">Pick a package, add your car, confirm - we call you to schedule pick-up.</p>
            <div class="ymo-v2-cta-band__actions d-flex flex-wrap justify-content-center gap-2">
                <a href="<?= html_escape($booking_url); ?>" class="md-btn md-btn--filled md-btn--lg">View packages &amp; book</a>
                <a href="<?= html_escape(ymo_booking_url('quick-book')); ?>" class="md-btn md-btn--tonal md-btn--lg">
                    <span class="mi mi-leading">send</span>Quick book - no login
                </a>
            </div>
            <p class="md-body-md mt-3 mb-0"><a href="<?= site_url('why-choose-ymo'); ?>">Why choose YMO over a doorstep mechanic?</a></p>
        </div>
    </div>
</section>
</div>
