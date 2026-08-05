<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<section class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">
            <h1 class="md-headline-md mb-3"><?= html_escape(isset($h1) ? $h1 : 'Contact us'); ?></h1>
            <p class="md-body-lg mb-4">Send us your enquiry and our team will call you back.</p>

            <?php if (validation_errors()): ?>
                <div class="alert alert-danger mb-3" role="alert"><?= validation_errors('<div>', '</div>'); ?></div>
            <?php endif; ?>

            <?= form_open(site_url('contact-us'), array('class' => 'md-card-elevated p-4')); ?>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="contact-name" name="name" placeholder="Name" required maxlength="120" value="<?= html_escape(set_value('name')); ?>">
                    <label for="contact-name">Name</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="tel" class="form-control" id="contact-mobile" name="mobile" placeholder="Mobile" required maxlength="20" value="<?= html_escape(set_value('mobile')); ?>">
                    <label for="contact-mobile">Mobile</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="contact-email" name="email" placeholder="Email" maxlength="160" value="<?= html_escape(set_value('email')); ?>">
                    <label for="contact-email">Email <span class="ymo-muted fw-normal">(optional)</span></label>
                </div>
                <div class="form-floating mb-4">
                    <textarea class="form-control" id="contact-message" name="message" placeholder="Message" style="min-height:120px" required maxlength="2000"><?= html_escape(set_value('message')); ?></textarea>
                    <label for="contact-message">Message</label>
                </div>
                <button type="submit" class="md-btn md-btn--filled">
                    <span class="mi mi-leading">send</span>Send enquiry
                </button>
            <?= form_close(); ?>
        </div>
        <div class="col-lg-5">
            <div class="md-card-elevated p-4 h-100">
                <h2 class="md-title-lg mb-2">Or book directly</h2>
                <p class="md-body-md mb-4">Skip the form - choose a package and book in under 2 minutes.</p>
                <a href="<?= html_escape($booking_url); ?>" class="md-btn md-btn--outlined w-100 mb-3">
                    <span class="mi mi-leading">event_available</span>Book online
                </a>
                <a href="<?= html_escape(ymo_booking_url('quick-book')); ?>" class="md-btn md-btn--filled w-100 mb-4">
                    <span class="mi mi-leading">send</span>Quick book — no login
                </a>
                <hr class="ymo-divider">
                <p class="md-body-md mb-2"><span class="md-label-lg d-block mb-1">Phone</span><a href="tel:<?= html_escape(preg_replace('/[^+\d]/', '', $phone)); ?>"><?= html_escape($phone); ?></a></p>
                <p class="md-body-md mb-0"><span class="md-label-lg d-block mb-1">Email</span><a href="mailto:<?= html_escape($email); ?>"><?= html_escape($email); ?></a></p>
            </div>
        </div>
    </div>
</section>
