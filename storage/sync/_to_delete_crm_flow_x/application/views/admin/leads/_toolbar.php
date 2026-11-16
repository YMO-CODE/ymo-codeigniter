<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Shared header for the leads list and board: view toggle, source chips, New lead. */
$current_source = (string) ($filters['source_slug'] ?? '');
$base = $mode === 'board' ? 'leads/pipeline' : 'leads';
$chip_url = function ($slug) use ($base) {
    $qs = $_GET;
    unset($qs['page'], $qs['source_id']);
    if ($slug === '') { unset($qs['source']); } else { $qs['source'] = $slug; }
    return admin_url($base.($qs ? '?'.http_build_query($qs) : ''));
};
$toggle_qs = $_GET;
unset($toggle_qs['page'], $toggle_qs['stage']);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <div class="btn-group btn-group-sm" role="group" aria-label="View">
        <a href="<?= admin_url('leads'.($toggle_qs ? '?'.http_build_query($toggle_qs) : '')); ?>" class="btn <?= $mode === 'list' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <span class="mi mi-sm mi-leading">list</span>List
        </a>
        <a href="<?= admin_url('leads/pipeline'.($toggle_qs ? '?'.http_build_query($toggle_qs) : '')); ?>" class="btn <?= $mode === 'board' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <span class="mi mi-sm mi-leading">view_kanban</span>Board
        </a>
    </div>
    <?php if (!empty($can_edit)): ?>
        <a href="<?= admin_url('leads/new'); ?>" class="btn btn-primary btn-sm"><span class="mi mi-sm mi-leading">add</span>New lead</a>
    <?php endif; ?>
</div>
<div class="ymo-chips mb-3">
    <a href="<?= $chip_url(''); ?>" class="<?= $current_source === '' ? 'active' : ''; ?>">All sources</a>
    <?php foreach ($sources as $s): ?>
        <a href="<?= $chip_url($s['slug']); ?>" class="<?= $current_source === $s['slug'] ? 'active' : ''; ?>"><?= html_escape($s['label']); ?></a>
    <?php endforeach; ?>
</div>
