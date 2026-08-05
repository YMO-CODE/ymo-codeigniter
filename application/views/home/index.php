<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<section class="ymo-booking-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <p class="text-uppercase small fw-bold text-warning mb-2"><span class="mi mi-sm mi-leading">verified</span>Trusted car servicing</p>
                <h1 class="mb-3">Book your car servicing online in under 2 minutes.</h1>
                <p class="lead mb-4">
                    Periodic service, AC repair, denting &amp; polishing - done by expert
                    technicians with transparent pricing. Pick a package, add your car,
                    we'll take it from there.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= site_url('packages'); ?>" class="btn btn-primary btn-lg">
                        <span class="mi mi-leading">build</span>View packages
                    </a>
                    <?php if (!$this->user): ?>
                        <a href="<?= site_url('quick-book'); ?>" class="btn btn-warning btn-lg text-dark">
                            <span class="mi mi-leading">send</span>Quick book - no login
                        </a>
                        <a href="<?= site_url('signup'); ?>" class="btn btn-outline-light btn-lg">
                            <span class="mi mi-leading">person_add</span>Sign up free
                        </a>
                    <?php else: ?>
                        <a href="<?= site_url('account/bookings'); ?>" class="btn btn-outline-light btn-lg">
                            <span class="mi mi-leading">event_note</span>My bookings
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="text-center mb-5">
        <p class="text-uppercase small fw-bold text-primary mb-1"><span class="mi mi-sm mi-leading">flag</span>How it works</p>
        <h2 class="mb-2">Three steps. No paperwork.</h2>
        <p class="ymo-muted">Pick a package, add your car, confirm. We'll call you to schedule pick-up.</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="md-card-elevated h-100 text-center">
                <span class="mi mi-xl" style="color:var(--ymo-primary);">build</span>
                <div class="display-6 text-primary fw-bold mt-2">1</div>
                <h5>Choose a package</h5>
                <p class="ymo-muted mb-0">Pick from periodic, premium, or AC service - clear pricing, no surprises.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="md-card-elevated h-100 text-center">
                <span class="mi mi-xl" style="color:var(--ymo-primary);">directions_car</span>
                <div class="display-6 text-primary fw-bold mt-2">2</div>
                <h5>Add your vehicle</h5>
                <p class="ymo-muted mb-0">Your make, variant, registration number - saved for next time.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="md-card-elevated h-100 text-center">
                <span class="mi mi-xl" style="color:var(--ymo-primary);">event_available</span>
                <div class="display-6 text-primary fw-bold mt-2">3</div>
                <h5>Book &amp; relax</h5>
                <p class="ymo-muted mb-0">We confirm by SMS &amp; email, and remind you when your next service is due.</p>
            </div>
        </div>
    </div>

    <div class="text-center mb-4">
        <h2 class="mb-2">Popular packages</h2>
        <p class="ymo-muted">Tap a package to start a booking.</p>
    </div>
    <div class="row g-4">
        <?php foreach ($packages as $p): ?>
            <div class="col-md-6 col-lg-4">
                <div class="md-card-elevated ymo-package-card">
                    <h4 class="mb-2"><?= html_escape($p['name']); ?></h4>
                    <div class="price mb-2">&#8377; <?= number_format((float) $p['price']); ?></div>
                    <p class="ymo-muted small"><?= html_escape($p['summary']); ?></p>
                    <ul>
                        <?php foreach (array_slice($p['features'], 0, 5) as $f): ?>
                            <li><?= html_escape($f); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= site_url('book/'.$p['slug']); ?>" class="btn btn-primary mt-auto">
                        <span class="mi mi-leading">event_available</span>Book now
                    </a>
                    <a href="<?= site_url('quick-book?package='.rawurlencode($p['slug'])); ?>" class="btn btn-link btn-sm mt-2 px-0">
                        Request a callback instead
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$this->user): ?>
    <div class="md-card-filled text-center py-4 px-3 mt-5">
        <h2 class="h4 mb-2">Don't want to create an account?</h2>
        <p class="ymo-muted mb-3">Fill in your details once - our team will call you to confirm your booking.</p>
        <a href="<?= site_url('quick-book'); ?>" class="btn btn-primary btn-lg">
            <span class="mi mi-leading">send</span>Quick book - no login
        </a>
    </div>
    <?php endif; ?>
</section>
