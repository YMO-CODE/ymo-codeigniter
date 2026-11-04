<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$show_arrow = !empty($show_arrow);
$icon = isset($svc['icon']) ? $svc['icon'] : '';
$title = isset($svc['title']) ? $svc['title'] : '';
$teaser = isset($svc['teaser']) ? $svc['teaser'] : '';
$as_heading = !isset($as_heading) || $as_heading;
?>
<span class="ymo-v2-card__icon" aria-hidden="true">
    <span class="mi"><?= html_escape($icon); ?></span>
</span>
<?php if ($as_heading): ?>
<h3 class="ymo-v2-card__title md-title-md"><?= html_escape($title); ?></h3>
<?php else: ?>
<span class="ymo-v2-card__title md-title-md d-block"><?= html_escape($title); ?></span>
<?php endif; ?>
<p class="ymo-v2-card__teaser md-body-md"><?= html_escape($teaser); ?></p>
<?php if ($show_arrow): ?>
    <span class="ymo-v2-card__arrow mi" aria-hidden="true">arrow_forward</span>
<?php endif; ?>
