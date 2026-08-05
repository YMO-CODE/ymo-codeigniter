<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci    = &get_instance();
$brand = $ci->config->item('ymo_brand_name');
$mark  = ymo_marketing_url('');
$phone = $ci->config->item('ymo_support_phone');
$mail  = $ci->config->item('ymo_support_email');
?>
<footer class="ymo-footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4">
                <img src="<?= base_url('assets/img/logo.png'); ?>" alt="<?= html_escape($brand); ?>" class="ymo-footer-logo mb-3" width="140" height="52" loading="lazy" decoding="async">
                <p class="small mb-2">
                    Your trusted online car servicing platform - periodic service,
                    AC repair, denting &amp; polishing, doorstep pick-up &amp; drop.
                </p>
                <p class="small mb-0">
                    <a href="tel:<?= html_escape(preg_replace('/[^+\d]/', '', $phone)); ?>"><?= html_escape($phone); ?></a><br>
                    <a href="mailto:<?= html_escape($mail); ?>"><?= html_escape($mail); ?></a>
                </p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Company</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= html_escape($mark); ?>">Home</a></li>
                    <li><a href="<?= html_escape(ymo_marketing_url('about-us')); ?>">About</a></li>
                    <li><a href="<?= html_escape(ymo_marketing_url('contact-us')); ?>">Contact</a></li>
                    <li><a href="<?= html_escape(ymo_marketing_url('privacy-policy')); ?>">Privacy policy</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6>Booking</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= site_url('packages'); ?>">Packages</a></li>
                    <li><a href="<?= site_url('quick-book'); ?>">Quick book</a></li>
                    <li><a href="<?= site_url('signup'); ?>">Sign up</a></li>
                    <li><a href="<?= site_url('login'); ?>">Sign in</a></li>
                </ul>
            </div>
            <div class="col-12 col-lg-3">
                <h6>Need a hand?</h6>
                <p class="small mb-0">
                    Call <a href="tel:<?= html_escape(preg_replace('/[^+\d]/', '', $phone)); ?>"><?= html_escape($phone); ?></a>
                    Mon–Sat, 9 AM to 8 PM. Roadside assistance available in serviceable areas.
                </p>
            </div>
        </div>
        <div class="ymo-footer-bottom d-flex justify-content-between flex-wrap">
            <span>&copy; <?= date('Y'); ?> <?= html_escape($brand); ?>. All rights reserved.</span>
            <span>Made with care.</span>
        </div>
    </div>
</footer>
