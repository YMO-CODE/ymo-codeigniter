<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$show_arrow = !empty($show_arrow);
$icon = isset($svc['icon']) ? $svc['icon'] : '';
$title = isset($svc['title']) ? $svc['title'] : '';
$teaser = isset($svc['teaser']) ? $svc['teaser'] : '';
?>
<span class="ymo-v2-card__icon" aria-hidden="true">
    <span class="mi"><?= html_escape($icon); ?></span>
</span>
<h3 class="ymo-v2-card__title md-title-md"><?= html_escape($title); ?></h3>
<p class="ymo-v2-card__teaser md-body-md"><?= html_escape($teaser); ?></p>
<?php if ($show_arrow): ?>
    <span class="ymo-v2-card__arrow mi" aria-hidden="true">arrow_forward</span>
<?php endif; ?>
