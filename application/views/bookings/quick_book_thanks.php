<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="ymo-card text-center">
                <span class="mi text-success d-block mb-3" style="font-size:3rem;">check_circle</span>
                <h1 class="h3 mb-2">Request received</h1>
                <p class="ymo-muted mb-4">Thanks for your details. Our team will call you shortly to confirm your service booking.</p>
                <?php if (!empty($phone)): ?>
                    <p class="md-body-md mb-4">
                        Need help sooner? Call us at
                        <a href="tel:<?= html_escape(preg_replace('/[^+\d]/', '', $phone)); ?>"><?= html_escape($phone); ?></a>
                    </p>
                <?php endif; ?>
                <a href="<?= site_url('packages'); ?>" class="btn btn-primary">
                    <span class="mi mi-leading">build</span>View service packages
                </a>
            </div>
        </div>
    </div>
</div>
