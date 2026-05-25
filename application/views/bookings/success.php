<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="md-card-elevated text-center">
                <span class="mi mi-xl mi-fill" style="font-size:72px;color:#16a34a;">check_circle</span>
                <h1 class="h3 mb-2 mt-2">Booking confirmed!</h1>
                <p class="ymo-muted">Your reference number is <strong class="text-dark"><?= html_escape($booking['reference']); ?></strong>.</p>

                <hr class="ymo-divider">

                <dl class="row text-start small">
                    <dt class="col-4 text-muted"><span class="mi mi-sm mi-leading">build</span>Service</dt>
                    <dd class="col-8"><?= html_escape($booking['package_name']); ?></dd>

                    <dt class="col-4 text-muted"><span class="mi mi-sm mi-leading">directions_car</span>Vehicle</dt>
                    <dd class="col-8"><?= html_escape($booking['vehicle_make']); ?> <?= html_escape($booking['vehicle_variant']); ?> · <?= html_escape($booking['vehicle_number']); ?></dd>

                    <?php if (!empty($booking['preferred_date'])): ?>
                        <dt class="col-4 text-muted"><span class="mi mi-sm mi-leading">event</span>Preferred date</dt>
                        <dd class="col-8"><?= html_escape(date('d M Y', strtotime($booking['preferred_date']))); ?></dd>
                    <?php endif; ?>
                </dl>

                <p class="ymo-muted small">
                    We've sent a confirmation to <strong><?= html_escape($booking['user_email']); ?></strong> and texted
                    <strong>+91 <?= html_escape(substr($booking['user_mobile'], -10)); ?></strong>. Our team will call shortly to schedule pick-up.
                </p>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?= site_url('account/bookings'); ?>" class="btn btn-primary">
                        <span class="mi mi-leading">event_note</span>View my bookings
                    </a>
                    <a href="<?= site_url('/'); ?>" class="btn btn-outline-primary">
                        <span class="mi mi-leading">home</span>Back to home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
