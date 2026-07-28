<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="ymo-city-hint" role="region" aria-label="City suggestion">
    <div class="container d-flex align-items-center justify-content-between gap-3 flex-wrap py-2">
        <p class="ymo-city-hint-text mb-0 md-body-md">
            <span class="mi mi-sm ymo-city-hint-icon" aria-hidden="true">location_on</span>
            Serving <?= html_escape($city_hint['name']); ?> -
            <a class="ymo-city-hint-link" href="<?= html_escape(ymo_public_nav_url($city_hint['hub_path'])); ?>">
                View car services in <?= html_escape($city_hint['name']); ?>
            </a>
        </p>
        <button type="button"
                class="ymo-city-hint-dismiss md-btn md-btn--text md-btn--sm"
                data-city-hint-dismiss
                aria-label="Dismiss city suggestion">
            <span class="mi mi-sm" aria-hidden="true">close</span>
            Not now
        </button>
    </div>
</div>
