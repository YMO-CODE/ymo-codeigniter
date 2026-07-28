<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($services) || !is_array($services)) { return; } ?>

<div class="row g-4 ymo-service-catalog">
    <?php foreach ($services as $svc): ?>
        <div class="col-md-6 col-lg-4">
            <a href="<?= site_url($svc['slug']); ?>" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">
                <span class="mi mi-xl md-icon-primary"><?= html_escape($svc['icon']); ?></span>
                <h3 class="md-title-md mt-3 mb-1 text-dark"><?= html_escape($svc['title']); ?></h3>
                <p class="md-body-md mb-0"><?= html_escape($svc['teaser']); ?></p>
            </a>
        </div>
    <?php endforeach; ?>
</div>
