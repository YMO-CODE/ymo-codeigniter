<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$brand = $ci->config->item('ymo_brand_name');
$phone = $ci->config->item('ymo_support_phone');
$mail  = $ci->config->item('ymo_support_email');
$trust = function_exists('marketing_trust_config') ? marketing_trust_config() : array();
$instagram = !empty($trust['instagram_url']) ? $trust['instagram_url'] : '';
$linkedin  = 'https://www.linkedin.com/company/your-mechanic-online/';
?>
<footer class="ymo-footer">
    <div class="container py-2">
        <div class="row gy-4">
            <div class="col-lg-4">
                <?= function_exists('marketing_brand_logo_html') ? marketing_brand_logo_html(array('class' => 'ymo-footer-logo mb-3', 'width' => 140, 'height' => 52, 'lazy' => TRUE)) : '<img src="'.html_escape(base_url('assets/img/logo.png')).'" alt="'.html_escape($brand).'" class="ymo-footer-logo mb-3" width="140" height="52" loading="lazy" decoding="async">'; ?>
                <p class="md-body-md mb-0">Periodic service, AC repair, denting &amp; polishing - book online in minutes.</p>
            </div>
            <div class="col-6 col-lg-2">
                <p class="ymo-footer-heading">Company</p>
                <ul class="list-unstyled md-body-md mb-0">
                    <li class="mb-2"><a href="<?= site_url('about-us'); ?>">About</a></li>
                    <li class="mb-2"><a href="<?= site_url('contact-us'); ?>">Contact</a></li>
                    <li class="mb-2"><a href="<?= site_url('why-choose-ymo'); ?>">Why choose YMO</a></li>
                    <li><a href="<?= site_url('privacy-policy'); ?>">Privacy policy</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <p class="ymo-footer-heading">Cities &amp; brands</p>
                <ul class="list-unstyled md-body-md mb-0">
                    <li class="mb-2"><a href="<?= site_url('locations/pune'); ?>">Car servicing in Pune</a></li>
                    <li class="mb-2"><a href="<?= site_url('locations/indore'); ?>">Car servicing in Indore</a></li>
                    <li class="mb-2"><a href="<?= site_url('locations/nashik'); ?>">Car servicing in Nashik</a></li>
                    <li><a href="<?= site_url('brands'); ?>">All brands</a></li>
                </ul>
            </div>
            <?php foreach (function_exists('marketing_footer_extra_link_sections') ? marketing_footer_extra_link_sections() : array() as $section): ?>
            <div class="col-6 col-lg-2">
                <p class="ymo-footer-heading"><?= html_escape($section['title']); ?></p>
                <ul class="list-unstyled md-body-md mb-0">
                    <?php foreach ($section['links'] as $i => $link): ?>
                    <li class="<?= ($i < count($section['links']) - 1) ? 'mb-2' : ''; ?>">
                        <a href="<?= site_url($link['slug']); ?>"><?= html_escape($link['label']); ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
            <div class="col-6 col-lg-2">
                <p class="ymo-footer-heading">Book online</p>
                <ul class="list-unstyled md-body-md mb-0">
                    <li class="mb-2"><a href="<?= html_escape(ymo_booking_url('packages')); ?>">Packages</a></li>
                    <li class="mb-2"><a href="<?= html_escape(ymo_booking_url('quick-book')); ?>">Quick book</a></li>
                    <li><a href="<?= html_escape(ymo_booking_url('signup')); ?>">Sign up</a></li>
                </ul>
            </div>
            <div class="col-12 col-lg-3">
                <p class="ymo-footer-heading">Need a hand?</p>
                <p class="md-body-md mb-0 ymo-footer-contact">
                    <a href="tel:<?= html_escape(preg_replace('/[^+\d]/', '', $phone)); ?>"><?= html_escape($phone); ?></a><br>
                    <a href="mailto:<?= html_escape($mail); ?>"><?= html_escape($mail); ?></a>
                </p>
                <?php if ($instagram !== ''): ?>
                <p class="md-body-md mb-0 mt-2">
                    <a href="<?= html_escape($instagram); ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
                    · <a href="<?= html_escape($linkedin); ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="ymo-footer-bottom d-flex justify-content-between flex-wrap gap-2 mt-4 pt-3">
            <span class="md-body-md mb-0">&copy; <?= date('Y'); ?> <?= html_escape($brand); ?>. All rights reserved.</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= html_escape(ymo_booking_url('quick-book')); ?>" class="md-btn md-btn--tonal md-btn--sm">Quick book</a>
                <a href="<?= html_escape(ymo_booking_url('packages')); ?>" class="md-btn md-btn--tonal md-btn--sm">Book a service</a>
            </div>
        </div>
    </div>
</footer>
