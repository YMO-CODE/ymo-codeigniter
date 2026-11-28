<?php defined('BASEPATH') OR exit('No direct script access allowed');
$S = Crm_work_order_model::STATUSES;
$badge = array('draft' => 'bg-secondary', 'in_progress' => 'bg-primary', 'on_hold' => 'bg-warning text-dark', 'ready' => 'bg-info text-dark', 'delivered' => 'bg-success', 'returned' => 'bg-dark');
$url = function (array $set = array()) use ($tab, $ws, $q) {
    $qs = array_merge(array('tab' => $tab === 'open' ? NULL : $tab, 'ws' => $ws, 'q' => $q), $set);
    $qs = array_filter($qs, function ($v) { return $v !== NULL && $v !== ''; });
    return admin_url('job-cards'.($qs ? '?'.http_build_query($qs) : ''));
};
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <?php if (count($workshops) > 1): ?>
    <select class="form-select form-select-sm" style="width:auto" onchange="location.href=this.value" aria-label="Workshop">
        <option value="<?= html_escape($url(array('ws' => NULL, 'p' => NULL))); ?>">All workshops</option>
        <?php foreach ($workshops as $slug => $w): ?>
            <option value="<?= html_escape($url(array('ws' => $slug, 'p' => NULL))); ?>" <?= $ws === $slug ? 'selected' : ''; ?>><?= html_escape($w['label']); ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <form method="get" action="<?= admin_url('job-cards'); ?>" class="flex-grow-1" style="min-width:12rem;max-width:24rem">
        <?php if ($tab !== 'open'): ?><input type="hidden" name="tab" value="<?= html_escape($tab); ?>"><?php endif; ?>
        <?php if ($ws !== ''): ?><input type="hidden" name="ws" value="<?= html_escape($ws); ?>"><?php endif; ?>
        <input class="form-control form-control-sm" type="search" name="q" value="<?= html_escape($q); ?>" placeholder="RO number, customer, mobile or car number…">
    </form>
    <?php if (!empty($can_edit)): ?>
    <a href="<?= admin_url('job-cards/new'); ?>" class="btn btn-sm btn-primary ms-auto"><span class="mi mi-sm mi-leading">add</span>New job card</a>
    <?php endif; ?>
</div>

<div class="ymo-tabs mb-3">
    <a href="<?= $url(array('tab' => NULL, 'p' => NULL)); ?>" class="<?= $tab === 'open' ? 'active' : ''; ?>">Open <span class="count"><?= (int) $counts['open']; ?></span></a>
    <?php foreach ($S as $k => $label): ?>
        <a href="<?= $url(array('tab' => $k, 'p' => NULL)); ?>" class="<?= $tab === $k ? 'active' : ''; ?>"><?= $label; ?> <span class="count"><?= (int) $counts[$k]; ?></span></a>
    <?php endforeach; ?>
    <a href="<?= $url(array('tab' => 'all', 'p' => NULL)); ?>" class="<?= $tab === 'all' ? 'active' : ''; ?>">All <span class="count"><?= (int) $counts['all']; ?></span></a>
</div>

<div class="ymo-card p-0" id="list">
    <?php if (!$rows): ?>
        <p class="small ymo-muted p-3 mb-0"><?= $q !== '' ? 'No job card matches "'.html_escape($q).'".' : 'No job cards here yet.'; ?></p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="ymo-table mb-0">
        <thead><tr><th>Job card</th><th>Customer &amp; car</th><th class="d-none d-md-table-cell">Advisor</th><th class="d-none d-lg-table-cell">Opened</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr style="cursor:pointer" onclick="if(!event.target.closest('a'))location.href='<?= admin_url('job-cards/'.(int) $r['id']); ?>'">
                <td>
                    <a href="<?= admin_url('job-cards/'.(int) $r['id']); ?>" class="fw-semibold text-reset text-decoration-none"><?= html_escape($r['ro_number']); ?></a><br>
                    <span class="badge <?= $badge[$r['status']] ?? 'bg-light'; ?>"><?= $S[$r['status']] ?? $r['status']; ?></span>
                    <?php if ($r['workshop'] !== '' && isset($workshops[$r['workshop']])): ?><span class="small ymo-muted ms-1"><?= html_escape($workshops[$r['workshop']]['label']); ?></span><?php endif; ?>
                </td>
                <td>
                    <strong><?= html_escape($r['customer_name']); ?></strong> <span class="small ymo-muted"><?= html_escape($r['customer_mobile']); ?></span><br>
                    <span class="small"><?= html_escape($r['vehicle_number']); ?> · <?= html_escape(trim($r['vehicle_make'].' '.$r['vehicle_variant'])); ?></span>
                    <?php if (!empty($r['booking_reference'])): ?><span class="badge bg-light text-dark border ms-1"><?= html_escape($r['booking_reference']); ?></span><?php endif; ?>
                </td>
                <td class="small d-none d-md-table-cell"><?= html_escape($r['advisor_name'] ?: '—'); ?></td>
                <td class="small d-none d-lg-table-cell"><?= html_escape(date('d M, H:i', strtotime($r['created_at']))); ?>
                    <?php if (!empty($r['expected_delivery_at']) && !in_array($r['status'], array('delivered', 'returned'), TRUE)): ?>
                        <br><span class="<?= strtotime($r['expected_delivery_at']) < time() ? 'text-danger' : 'ymo-muted'; ?>">Due <?= html_escape(date('d M, H:i', strtotime($r['expected_delivery_at']))); ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-end">&#8377; <?= number_format((float) $r['grand_total'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div class="px-1"><?= crm_list_pager($pager); ?></div>
