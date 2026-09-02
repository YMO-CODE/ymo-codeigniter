<?php defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$brand = $ci->config->item('ymo_brand_name');
$phone = $ci->config->item('ymo_support_phone');
$mail  = $ci->config->item('ymo_support_email');
$trust = function_exists('marketing_trust_config') ? marketing_trust_config() : array();
$instagram = !empty($trust['instagram_url']) ? $trust['instagram_url'] : '';
$linkedin  = 'https://www.linkedin.com/company/yours-mechanic-online/';
$footer_sections = function_exists('marketing_footer_extra_link_sections') ? marketing_footer_extra_link_sections() : array();
$footer_city_sections = array();
$footer_other_sections = array();
foreach ($footer_sections as $section) {
    if (!empty($section['type']) && $section['type'] === 'city_areas') {
        $footer_city_sections[] = $section;
    } else {
        $footer_other_sections[] = $section;
    }
}
?>
<footer class="ymo-footer">
    <div class="container">
        <div class="ymo-footer-main">
            <div class="ymo-footer-brand">
                <?= function_exists('marketing_brand_logo_html') ? marketing_brand_logo_html(array('class' => 'ymo-footer-logo mb-3', 'width' => 132, 'height' => 48, 'lazy' => TRUE)) : '<img src="'.html_escape(base_url('assets/img/logo.png')).'" alt="'.html_escape($brand).'" class="ymo-footer-logo mb-3" width="132" height="48" loading="lazy" decoding="async">'; ?>
                <p class="ymo-footer-tagline mb-0">Periodic service, AC repair, denting &amp; polishing - book online in minutes.</p>
            </div>

            <nav class="ymo-footer-nav" aria-label="Footer navigation">
                <div class="ymo-footer-nav-col">
                    <p class="ymo-footer-heading">Company</p>
                    <ul class="ymo-footer-links">
                        <li><a href="<?= site_url('about-us'); ?>">About</a></li>
                        <li><a href="<?= site_url('contact-us'); ?>">Contact</a></li>
                        <li><a href="<?= site_url('why-choose-ymo'); ?>">Why choose YMO</a></li>
                        <li><a href="<?= site_url('privacy-policy'); ?>">Privacy policy</a></li>
                    </ul>
                </div>
                <div class="ymo-footer-nav-col">
                    <p class="ymo-footer-heading">Cities &amp; brands</p>
                    <ul class="ymo-footer-links">
                        <li><a href="<?= site_url('locations/pune'); ?>">Pune</a></li>
                        <li><a href="<?= site_url('locations/indore'); ?>">Indore</a></li>
                        <li><a href="<?= site_url('locations/nashik'); ?>">Nashik</a></li>
                        <li><a href="<?= site_url('brands'); ?>">All brands</a></li>
                    </ul>
                </div>
                <?php foreach ($footer_other_sections as $section): ?>
                <div class="ymo-footer-nav-col">
                    <p class="ymo-footer-heading"><?= html_escape($section['title']); ?></p>
                    <ul class="ymo-footer-links">
                        <?php foreach ($section['links'] as $link): ?>
                        <li><a href="<?= site_url($link['slug']); ?>"><?= html_escape($link['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </nav>

            <div class="ymo-footer-aside">
                <p class="ymo-footer-heading">Need a hand?</p>
                <ul class="ymo-footer-links ymo-footer-links--contact">
                    <li><a href="tel:<?= html_escape(preg_replace('/[^+\d]/', '', $phone)); ?>"><?= html_escape($phone); ?></a></li>
                    <li><a href="mailto:<?= html_escape($mail); ?>"><?= html_escape($mail); ?></a></li>
                </ul>
                <?php if ($instagram !== ''): ?>
                <p class="ymo-footer-social">
                    <a href="<?= html_escape($instagram); ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
                    <span aria-hidden="true"> · </span>
                    <a href="<?= html_escape($linkedin); ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                </p>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= html_escape(ymo_booking_url('quick-book')); ?>" class="md-btn md-btn--filled md-btn--sm">Quick book</a>
                    <a href="<?= html_escape(ymo_booking_url('packages')); ?>" class="md-btn md-btn--tonal md-btn--sm">Packages</a>
                </div>
            </div>
        </div>

        <?php if ($footer_city_sections !== array()): ?>
        <details class="ymo-footer-areas">
            <summary class="ymo-footer-areas__summary">Browse service areas in Pune, Indore &amp; Nashik</summary>
            <div class="ymo-footer-areas__body">
                <div class="row g-3 g-lg-4">
                    <?php foreach ($footer_city_sections as $section): ?>
                    <div class="col-md-4">
                        <p class="ymo-footer-areas__city"><?= html_escape($section['title']); ?></p>
                        <ul class="ymo-footer-links ymo-footer-links--cols-2">
                            <?php foreach ($section['links'] as $link): ?>
                            <li class="<?= !empty($link['featured']) ? 'ymo-footer-links__hub' : ''; ?>">
                                <a href="<?= site_url($link['slug']); ?>"><?= html_escape($link['label']); ?></a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>
        <?php endif; ?>

        <div class="ymo-footer-bottom">
            <span>&copy; <?= date('Y'); ?> <?= html_escape($brand); ?>. All rights reserved.</span>
        </div>
    </div>
</footer>
