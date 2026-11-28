<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_part = $type === 'part';
$url = function (array $set = array()) use ($type, $f, $q) {
    $qs = array_merge(array('type' => $type, 'f' => $f, 'q' => $q), $set);
    $qs = array_filter($qs, function ($v) { return $v !== NULL && $v !== ''; });
    return admin_url('catalogue'.($qs ? '?'.http_build_query($qs) : ''));
};
$money = function ($v) {
    return $v === NULL ? NULL : '&#8377; '.number_format((float) $v, ((float) $v == floor((float) $v)) ? 0 : 2);
};
?>
<style>
.cat-name { font-weight: 600; }
.cat-desc { max-width: 46rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cat-noprice { font-size: .8rem; }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="btn-group btn-group-sm" role="group" aria-label="Type">
        <a href="<?= $url(array('type' => 'service', 'f' => NULL, 'q' => NULL, 'p' => NULL)); ?>" class="btn <?= !$is_part ? 'btn-primary' : 'btn-outline-secondary'; ?>">Services <span class="opacity-75"><?= (int) $counts['service']; ?></span></a>
        <a href="<?= $url(array('type' => 'part', 'f' => NULL, 'q' => NULL, 'p' => NULL)); ?>" class="btn <?= $is_part ? 'btn-primary' : 'btn-outline-secondary'; ?>">Spare parts <span class="opacity-75"><?= (int) $counts['part']; ?></span></a>
    </div>
    <form method="get" action="<?= admin_url('catalogue'); ?>" class="flex-grow-1" style="min-width:12rem;max-width:24rem">
        <input type="hidden" name="type" value="<?= $type; ?>">
        <?php if ($f !== ''): ?><input type="hidden" name="f" value="<?= html_escape($f); ?>"><?php endif; ?>
        <input class="form-control form-control-sm" type="search" name="q" value="<?= html_escape($q); ?>" placeholder="Search <?= $is_part ? 'part name or barcode' : 'service name'; ?>…">
    </form>
    <?php if (!empty($can_edit)): ?>
    <a href="<?= admin_url('catalogue/new?type='.$type); ?>" class="btn btn-sm btn-primary ms-auto">
        <span class="mi mi-sm mi-leading">add</span>New <?= $is_part ? 'part' : 'service'; ?>
    </a>
    <?php endif; ?>
</div>

<div class="ymo-tabs mb-3">
    <a href="<?= $url(array('f' => NULL, 'p' => NULL)); ?>" class="<?= $f === '' ? 'active' : ''; ?>">Active <span class="count"><?= (int) $counts[$type]; ?></span></a>
    <a href="<?= $url(array('f' => 'no_price', 'p' => NULL)); ?>" class="<?= $f === 'no_price' ? 'active' : ''; ?>" title="No fixed rate: the price is typed on each job">Price set on job <span class="count"><?= (int) $counts[$type.'_no_price']; ?></span></a>
    <a href="<?= $url(array('f' => 'hidden', 'p' => NULL)); ?>" class="<?= $f === 'hidden' ? 'active' : ''; ?>">Hidden <span class="count"><?= (int) $counts[$type.'_hidden']; ?></span></a>
</div>

<div class="ymo-card p-0" id="items">
    <?php if (!$rows): ?>
        <p class="small ymo-muted p-3 mb-0"><?= $q !== '' ? 'Nothing matches "'.html_escape($q).'".' : ($f === 'hidden' ? 'Nothing hidden.' : 'No '.($is_part ? 'parts' : 'services').' yet.'); ?></p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="ymo-table mb-0">
        <thead><tr>
            <th><?= $is_part ? 'Part' : 'Service'; ?></th>
            <?php if ($is_part): ?><th class="d-none d-lg-table-cell">Barcode</th><th class="text-end d-none d-md-table-cell">Purchase</th><?php endif; ?>
            <th class="text-end d-none d-md-table-cell">Rate</th>
            <th class="d-none d-md-table-cell">Tax</th>
            <?php if ($is_part): ?><th class="text-end d-none d-md-table-cell">Stock</th><?php endif; ?>
            <th class="text-end"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <div class="cat-name"><?php if (!empty($can_edit)): ?><a href="<?= admin_url('catalogue/'.(int) $r['id'].'/edit'); ?>" class="text-reset text-decoration-none"><?= html_escape($r['name']); ?></a><?php else: ?><?= html_escape($r['name']); ?><?php endif; ?></div>
                    <?php if (!empty($r['description'])): ?><div class="cat-desc small ymo-muted" title="<?= html_escape($r['description']); ?>"><?= html_escape($r['description']); ?></div><?php endif; ?>
                    <?php if ($r['category'] !== ''): ?><span class="badge bg-light text-dark border"><?= html_escape($r['category']); ?></span><?php endif; ?>
                    <div class="d-md-none small mt-1"><?= $money($r['rate']) ?: '<span class="ymo-muted">price on job</span>'; ?><?= $r['tax_pct'] !== NULL ? ' · '.rtrim(rtrim(number_format((float) $r['tax_pct'], 2), '0'), '.').'% GST '.((int) $r['tax_inclusive'] ? 'incl.' : 'extra') : ''; ?></div>
                </td>
                <?php if ($is_part): ?>
                    <td class="small ymo-muted d-none d-lg-table-cell"><?= html_escape($r['code']); ?></td>
                    <td class="text-end small d-none d-md-table-cell"><?= $money($r['purchase_rate']) ?: '<span class="ymo-muted">—</span>'; ?></td>
                <?php endif; ?>
                <td class="text-end d-none d-md-table-cell"><?= $money($r['rate']) ?: '<span class="ymo-muted cat-noprice">on job</span>'; ?></td>
                <td class="small d-none d-md-table-cell"><?= $r['tax_pct'] === NULL ? '<span class="ymo-muted">—</span>' : rtrim(rtrim(number_format((float) $r['tax_pct'], 2), '0'), '.').'% '.((int) $r['tax_inclusive'] ? 'incl.' : 'excl.'); ?></td>
                <?php if ($is_part): ?>
                    <td class="text-end small d-none d-md-table-cell"><?= rtrim(rtrim(number_format((float) $r['stock'], 2), '0'), '.'); ?> <span class="ymo-muted"><?= html_escape($r['unit']); ?></span></td>
                <?php endif; ?>
                <td class="text-end text-nowrap">
                    <?php if (!empty($can_edit)): ?>
                    <a href="<?= admin_url('catalogue/'.(int) $r['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary"><span class="mi mi-sm mi-leading">edit</span>Edit</a>
                    <?= form_open(admin_url('catalogue/'.(int) $r['id'].'/toggle'), array('class' => 'd-inline')); ?>
                        <button class="btn btn-sm btn-link text-<?= (int) $r['is_active'] ? 'secondary' : 'success'; ?>" title="<?= (int) $r['is_active'] ? 'Hide from pickers (kept on old jobs)' : 'Show again'; ?>"><?= (int) $r['is_active'] ? 'Hide' : 'Show'; ?></button>
                    <?= form_close(); ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div class="px-1"><?= crm_list_pager($pager); ?></div>
