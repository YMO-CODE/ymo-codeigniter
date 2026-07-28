<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$hero_page = isset($page) && is_array($page) ? $page : array();
if (!isset($hero_page['h1']) && isset($h1)) {
    $hero_page['h1'] = $h1;
}
if (!isset($hero_page['intro']) && isset($intro)) {
    $hero_page['intro'] = $intro;
}
if (!isset($hero_page['quick_answer']) && isset($quick_answer)) {
    $hero_page['quick_answer'] = $quick_answer;
}
if (!isset($hero_page['page_type']) && isset($page_type)) {
    $hero_page['page_type'] = $page_type;
}
if (!isset($hero_page['city_slug']) && isset($city_slug)) {
    $hero_page['city_slug'] = $city_slug;
}
if (!isset($hero_page['service_key']) && isset($service_key)) {
    $hero_page['service_key'] = $service_key;
}
if (!isset($hero_page['og_image']) && isset($og_image)) {
    $hero_page['og_image'] = $og_image;
}

$hero_ctx = marketing_hero_context(
    isset($canonical_path) ? $canonical_path : '',
    $hero_page,
    isset($booking_url) ? $booking_url : ''
);
echo ymo_marketing_render_hero(
    isset($canonical_path) ? $canonical_path : '',
    $hero_page,
    isset($booking_url) ? $booking_url : ''
);
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="<?= !empty($body) || !empty($service_catalog) ? 'col-12' : 'col-lg-8'; ?>">
            <?php if (!empty($quick_answer) && empty($hero_ctx['lead_in_hero'])): ?>
                <div class="md-card-filled p-3 mb-3 ymo-quick-answer-block">
                    <p class="md-body-md mb-0"><?= html_escape($quick_answer); ?></p>
                </div>
            <?php elseif (empty($hero_ctx['lead_in_hero']) && !empty($intro)): ?>
                <p class="md-body-lg ymo-muted mb-4"><?= html_escape($intro); ?></p>
            <?php endif; ?>
            <?php if (!empty($service_catalog)): ?>
                <?php
                $catalog_heading = !empty($service_catalog_heading)
                    ? $service_catalog_heading
                    : '';
                $catalog_city = !empty($service_catalog_city) ? $service_catalog_city : 'pune';
                $catalog_services = marketing_service_catalog_cards($catalog_city);
                ?>
                <?php if ($catalog_heading !== ''): ?>
                    <h2 class="md-headline-md mb-4"><?= html_escape($catalog_heading); ?></h2>
                <?php endif; ?>
                <div class="mb-5">
                    <?php $this->load->view('marketing/partials/service_cards_grid', array('services' => $catalog_services)); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($body)): ?>
                <div class="marketing-body my-4">
                    <?= $body; ?>
                </div>
            <?php endif; ?>
            <div class="md-card-filled p-4 my-4">
                <p class="md-body-md mb-3">Book your service online - same trusted team, faster scheduling.</p>
                <a href="<?= html_escape($booking_url); ?>" class="md-btn md-btn--filled">
                    <span class="mi mi-leading">event_available</span>Book now
                </a>
            </div>
            <p class="small ymo-muted mb-0">Serving Pune, Indore, and Nashik with free doorstep pick-up.</p>
        </div>
    </div>
</section>
