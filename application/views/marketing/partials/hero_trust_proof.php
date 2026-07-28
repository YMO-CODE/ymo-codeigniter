<?php defined('BASEPATH') OR exit('No direct script access allowed');
$proof = !empty($hero['trust_proof']) && is_array($hero['trust_proof']) ? $hero['trust_proof'] : NULL;
if (!$proof || empty($proof['show'])) {
    return;
}
$variant = isset($proof['variant']) ? (string) $proof['variant'] : 'light';
$is_dark = ($variant === 'dark');
?>
<div class="ymo-hero__proof ymo-hero__proof--<?= $is_dark ? 'dark' : 'light'; ?>" aria-label="Customer ratings">
    <?php if (!empty($proof['rating'])): ?>
        <span class="ymo-hero__proof-item ymo-hero__proof-rating">
            <span class="mi" aria-hidden="true">star</span>
            <?= html_escape($proof['rating']); ?>
        </span>
    <?php endif; ?>
    <?php if (!empty($proof['review_count'])): ?>
        <span class="ymo-hero__proof-item"><?= html_escape($proof['review_count']); ?></span>
    <?php endif; ?>
    <?php if (!empty($proof['years'])): ?>
        <span class="ymo-hero__proof-item"><?= html_escape($proof['years']); ?></span>
    <?php endif; ?>
    <?php if (!empty($proof['cities'])): ?>
        <span class="ymo-hero__proof-item"><?= html_escape($proof['cities']); ?></span>
    <?php endif; ?>
</div>
