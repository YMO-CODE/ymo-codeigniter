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

<div class="ymo-v2-page ymo-v2-page--after-hero">
<section class="ymo-v2-section ymo-v2-section--page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="<?= !empty($body) || !empty($service_catalog) ? 'col-12 ymo-v2-prose-wide' : 'col-lg-8 ymo-v2-prose'; ?>">
                <?php if (!empty($quick_answer) && empty($hero_ctx['lead_in_hero'])): ?>
                    <p class="ymo-v2-lead md-body-lg mb-4"><?= html_escape($quick_answer); ?></p>
                <?php elseif (empty($hero_ctx['lead_in_hero']) && !empty($intro)): ?>
                    <p class="ymo-v2-lead md-body-lg ymo-muted mb-4"><?= html_escape($intro); ?></p>
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
                        <h2 class="ymo-v2-display-sm mb-4"><?= html_escape($catalog_heading); ?></h2>
                    <?php endif; ?>
                    <div class="mb-5">
                        <?php $this->load->view('marketing/partials/service_cards_grid', array('services' => $catalog_services)); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($body)): ?>
                    <div class="marketing-body ymo-v2-body my-4">
                        <?= $body; ?>
                    </div>
                <?php endif; ?>
                <?php $this->load->view('marketing/partials/supplemental_sections', array(
                    'pricing_tiers' => isset($pricing_tiers) ? $pricing_tiers : array(),
                    'faq'           => isset($faq) ? $faq : array(),
                    'body_faq_html' => isset($body_faq_html) ? $body_faq_html : '',
                    'aside_html'    => isset($aside_html) ? $aside_html : '',
                )); ?>
                <?php if (!empty($page_type) && $page_type === 'service' && function_exists('marketing_warranty_badge_html')): ?>
                    <div class="ymo-v2-inline-note my-3">
                        <?= marketing_warranty_badge_html(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="ymo-v2-cta-band ymo-v2-cta-band--card ymo-v2-cta-band--compact">
    <div class="container">
        <div class="ymo-v2-cta-band__panel md-card-filled text-center">
            <p class="md-body-md mb-3">Book your service online - same trusted team, faster scheduling.</p>
            <div class="ymo-v2-cta-band__actions d-flex flex-wrap justify-content-center gap-2">
                <a href="<?= html_escape($booking_url); ?>" class="md-btn md-btn--filled">
                    <span class="mi mi-leading">event_available</span>Book now
                </a>
                <a href="<?= html_escape(ymo_booking_url('quick-book')); ?>" class="md-btn md-btn--tonal">
                    <span class="mi mi-leading">send</span>Quick book - no login
                </a>
            </div>
            <p class="md-body-md ymo-muted mt-3 mb-0">Serving Pune, Indore, and Nashik with free doorstep pick-up.</p>
        </div>
    </div>
</section>
</div>
