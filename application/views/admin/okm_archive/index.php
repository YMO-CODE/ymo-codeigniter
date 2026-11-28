<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (!empty($empty)): ?>
<div class="ymo-card"><p class="mb-0 small ymo-muted">Nothing has been imported from OkMechanic yet.</p></div>
<?php return; endif;
$url = function (array $set = array()) use ($type, $q) {
    $qs = array_filter(array_merge(array('t' => $type, 'q' => $q), $set), function ($v) { return $v !== NULL && $v !== ''; });
    return admin_url('okmechanic-archive'.($qs ? '?'.http_build_query($qs) : ''));
};
$money = function ($n) { return $n === NULL ? '' : '₹'.number_format((float) $n, 2); };
?>
<p class="small ymo-muted mb-3">Everything brought over from OkMechanic that doesn't have its own screen here yet. Customers, cars, job cards, invoices and payments are already in their normal places; this is read-only.</p>

<div class="ymo-tabs mb-3" id="list">
    <?php foreach ($types as $k => $label): ?>
    <a href="<?= $url(array('t' => $k, 'q' => NULL, 'p' => NULL)); ?>" class="<?= $type === $k ? 'active' : ''; ?>"><?= html_escape($label); ?> <span class="count"><?= (int) ($counts[$k] ?? 0); ?></span></a>
    <?php endforeach; ?>
</div>

<form method="get" action="<?= admin_url('okmechanic-archive'); ?>" class="mb-3" style="max-width:24rem">
    <input type="hidden" name="t" value="<?= html_escape($type); ?>">
    <input class="form-control form-control-sm" type="search" name="q" value="<?= html_escape($q); ?>" placeholder="Number, name, car, status…">
</form>

<div class="ymo-card p-0">
    <?php if (!$rows): ?>
        <p class="small ymo-muted p-3 mb-0">Nothing here.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="ymo-table mb-0">
        <thead><tr><th>No.</th><th>Details</th><th class="d-none d-md-table-cell">Status</th><th class="d-none d-md-table-cell">Date</th><th class="d-none d-md-table-cell">Job card</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="text-nowrap"><a class="fw-semibold" href="<?= admin_url('okmechanic-archive/'.(int) $r['id']); ?>"><?= html_escape($r['number'] !== '' ? $r['number'] : 'View'); ?></a></td>
                <td class="small"><?= html_escape($r['title']); ?></td>
                <td class="small d-none d-md-table-cell"><?= html_escape($r['status']); ?></td>
                <td class="small d-none d-md-table-cell text-nowrap"><?= $r['doc_date'] ? html_escape(date('d M Y', strtotime($r['doc_date']))) : ''; ?></td>
                <td class="small d-none d-md-table-cell"><?php if ($r['ro_number']): ?><a href="<?= admin_url('job-cards/'.(int) $r['work_order_id']); ?>"><?= html_escape($r['ro_number']); ?></a><?php endif; ?></td>
                <td class="text-end small text-nowrap"><?= $money($r['amount']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div class="px-1"><?= crm_list_pager($pager); ?></div>
