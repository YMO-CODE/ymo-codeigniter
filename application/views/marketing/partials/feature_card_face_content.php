<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (!empty($icon)): ?>
    <span class="ymo-v2-card__icon" aria-hidden="true">
        <span class="mi"><?= html_escape($icon); ?></span>
    </span>
<?php endif; ?>
<?php if (!empty($title)): ?>
    <h3 class="ymo-v2-card__title md-title-md"><?= html_escape($title); ?></h3>
<?php endif; ?>
<?php if (!empty($body)): ?>
    <p class="ymo-v2-card__teaser md-body-md"><?= html_escape($body); ?></p>
<?php endif; ?>
