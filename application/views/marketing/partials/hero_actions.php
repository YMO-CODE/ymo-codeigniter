<?php defined('BASEPATH') OR exit('No direct script access allowed');
$actions_class = isset($actions_class) ? $actions_class : 'ymo-hero__actions d-flex flex-wrap gap-2';
$has_cta = !empty($hero['cta_primary']) || !empty($hero['cta_secondary']) || !empty($hero['cta_quick_book']);
if (!$has_cta) {
    return;
}
?>
<div class="<?= html_escape($actions_class); ?>">
    <?php if (!empty($hero['cta_primary'])): ?>
        <?php $btn = $hero['cta_primary']; ?>
        <a href="<?= html_escape($btn['href']); ?>" class="<?= html_escape(isset($btn['class']) ? $btn['class'] : 'md-btn md-btn--filled md-btn--lg'); ?>">
            <?php if (!empty($btn['icon'])): ?>
                <span class="mi mi-leading"><?= html_escape($btn['icon']); ?></span>
            <?php endif; ?>
            <?= html_escape($btn['label']); ?>
        </a>
    <?php endif; ?>
    <?php if (!empty($hero['cta_secondary'])): ?>
        <?php $btn = $hero['cta_secondary']; ?>
        <a href="<?= html_escape($btn['href']); ?>" class="<?= html_escape(isset($btn['class']) ? $btn['class'] : 'md-btn md-btn--outlined md-btn--lg'); ?>">
            <?= html_escape($btn['label']); ?>
        </a>
    <?php endif; ?>
    <?php if (!empty($hero['cta_quick_book'])): ?>
        <?php $btn = $hero['cta_quick_book']; ?>
        <a href="<?= html_escape($btn['href']); ?>" class="<?= html_escape(isset($btn['class']) ? $btn['class'] : 'md-btn md-btn--tonal md-btn--lg'); ?>">
            <?php if (!empty($btn['icon'])): ?>
                <span class="mi mi-leading"><?= html_escape($btn['icon']); ?></span>
            <?php endif; ?>
            <?= html_escape($btn['label']); ?>
        </a>
    <?php endif; ?>
</div>
