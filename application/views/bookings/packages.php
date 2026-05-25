<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="text-center mb-5">
        <p class="text-uppercase small fw-bold text-primary mb-1"><span class="mi mi-sm mi-leading">build</span>Service packages</p>
        <h1 class="h2 mb-2">Pick the right package for your car.</h1>
        <p class="ymo-muted">All packages include doorstep pick-up &amp; drop within serviceable areas.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($packages as $p): ?>
            <div class="col-md-6 col-lg-4">
                <div class="md-card-elevated ymo-package-card">
                    <h3 class="h4 mb-2"><?= html_escape($p['name']); ?></h3>
                    <div class="price mb-2">&#8377; <?= number_format((float) $p['price']); ?></div>
                    <p class="ymo-muted small"><?= html_escape($p['summary']); ?></p>
                    <ul>
                        <?php foreach ($p['features'] as $f): ?>
                            <li><?= html_escape($f); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= site_url('book/'.$p['slug']); ?>" class="btn btn-primary mt-auto">
                        <span class="mi mi-leading">event_available</span>Book now
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
