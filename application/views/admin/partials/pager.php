<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Small pager for lists inside admin pages.
 * Vars: $page (1-based), $pages, $param (query key, e.g. 'p'), $anchor (#id to jump back to),
 *       $path (admin path, e.g. 'reviews'), optional $total + $noun for the "x of y" note.
 * Other query parameters (filters, other lists' pages) are kept.
 */
$page = max(1, (int) $page);
$pages = max(1, (int) $pages);
$total = isset($total) ? (int) $total : NULL;
$noun = isset($noun) ? $noun : '';
$per = isset($per) ? (int) $per : 0;
$href = function ($n) use ($param, $anchor, $path) {
    $qs = $_GET;
    if ($n <= 1) {
        unset($qs[$param]);
    } else {
        $qs[$param] = $n;
    }
    unset($qs['edit'], $qs['r']);
    return admin_url($path.($qs ? '?'.http_build_query($qs) : '')).($anchor ? '#'.$anchor : '');
};
?>
<?php if ($pages > 1 || ($total !== NULL && $total > 0)): ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
    <?php if ($total !== NULL && $per > 0): ?>
        <span class="small ymo-muted"><?= ($page - 1) * $per + 1; ?>–<?= min($total, $page * $per); ?> of <?= $total; ?><?= $noun !== '' ? ' '.html_escape($noun) : ''; ?></span>
    <?php else: ?><span></span><?php endif; ?>
    <?php if ($pages > 1): ?>
    <nav aria-label="Pages"><ul class="pagination pagination-sm flex-wrap mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $page <= 1 ? '#' : html_escape($href($page - 1)); ?>" aria-label="Previous">‹</a></li>
        <?php foreach (crm_pagination_items($page, $pages, 5) as $it): ?>
            <?php if ($it === 'ellipsis'): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php else: ?>
                <li class="page-item <?= (int) $it === $page ? 'active' : ''; ?>"><a class="page-link" href="<?= html_escape($href((int) $it)); ?>"><?= (int) $it; ?></a></li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $pages ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $page >= $pages ? '#' : html_escape($href($page + 1)); ?>" aria-label="Next">›</a></li>
    </ul></nav>
    <?php endif; ?>
</div>
<?php endif; ?>
