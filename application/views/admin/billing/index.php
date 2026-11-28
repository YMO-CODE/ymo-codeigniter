<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return Work_order_customer_money::fmt($n); };
$S = Crm_billing_model::STATUS;
$badge = array('unpaid' => 'bg-danger', 'partly_paid' => 'bg-warning text-dark', 'paid' => 'bg-success', 'cancelled' => 'bg-secondary');
$url = function (array $set = array()) use ($tab, $ws, $q) {
    $qs = array_filter(array_merge(array('tab' => $tab === 'collect' ? NULL : $tab, 'ws' => $ws, 'q' => $q), $set), function ($v) { return $v !== NULL && $v !== ''; });
    return admin_url('invoices'.($qs ? '?'.http_build_query($qs) : ''));
};
?>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-4"><div class="ymo-card h-100"><div class="small ymo-muted">To collect<?= $ws !== '' ? ' · '.html_escape($workshops[$ws]['label']) : ''; ?></div><div class="h3 mb-0"><?= $fmt($counts['to_collect']); ?></div><div class="small ymo-muted"><?= (int) $counts['collect']; ?> invoice<?= $counts['collect'] == 1 ? '' : 's'; ?> unpaid or part paid</div></div></div>
    <div class="col-sm-6 col-lg-8 d-flex flex-wrap align-items-center gap-2">
        <?php if (count($workshops) > 1): ?>
        <select class="form-select form-select-sm" style="width:auto" onchange="location.href=this.value" aria-label="Workshop">
            <option value="<?= html_escape($url(array('ws' => NULL, 'p' => NULL))); ?>">All workshops</option>
            <?php foreach ($workshops as $slug => $w): ?><option value="<?= html_escape($url(array('ws' => $slug, 'p' => NULL))); ?>" <?= $ws === $slug ? 'selected' : ''; ?>><?= html_escape($w['label']); ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <form method="get" action="<?= admin_url('invoices'); ?>" class="flex-grow-1" style="min-width:12rem;max-width:24rem">
            <?php if ($tab !== 'collect'): ?><input type="hidden" name="tab" value="<?= html_escape($tab); ?>"><?php endif; ?>
            <?php if ($ws !== ''): ?><input type="hidden" name="ws" value="<?= html_escape($ws); ?>"><?php endif; ?>
            <input class="form-control form-control-sm" type="search" name="q" value="<?= html_escape($q); ?>" placeholder="Invoice no., RO, customer, mobile, car…">
        </form>
        <a class="btn btn-sm btn-outline-secondary ms-auto" href="<?= admin_url('payments'); ?>"><span class="mi mi-sm mi-leading">account_balance_wallet</span>Payments received</a>
    </div>
</div>

<div class="ymo-tabs mb-3">
    <a href="<?= $url(array('tab' => NULL, 'p' => NULL)); ?>" class="<?= $tab === 'collect' ? 'active' : ''; ?>">To collect <span class="count"><?= (int) $counts['collect']; ?></span></a>
    <a href="<?= $url(array('tab' => 'paid', 'p' => NULL)); ?>" class="<?= $tab === 'paid' ? 'active' : ''; ?>">Paid <span class="count"><?= (int) $counts['paid']; ?></span></a>
    <a href="<?= $url(array('tab' => 'cancelled', 'p' => NULL)); ?>" class="<?= $tab === 'cancelled' ? 'active' : ''; ?>">Cancelled <span class="count"><?= (int) $counts['cancelled']; ?></span></a>
    <a href="<?= $url(array('tab' => 'all', 'p' => NULL)); ?>" class="<?= $tab === 'all' ? 'active' : ''; ?>">All <span class="count"><?= (int) $counts['all']; ?></span></a>
</div>

<div class="ymo-card p-0" id="list">
    <?php if (!$rows): ?>
        <p class="small ymo-muted p-3 mb-0"><?= $q !== '' ? 'No invoice matches "'.html_escape($q).'".' : ($tab === 'collect' ? 'Nothing to collect. 🎉' : 'No invoices here yet. Raise one from a job card.'); ?></p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="ymo-table mb-0">
        <thead><tr><th>Invoice</th><th>Customer &amp; car</th><th class="d-none d-md-table-cell">Date</th><th class="text-end">Total</th><th class="text-end">Due</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $age = (int) floor((time() - strtotime($r['invoice_date'])) / 86400); ?>
            <tr style="cursor:pointer" onclick="if(!event.target.closest('a'))location.href='<?= admin_url('invoices/'.(int) $r['id']); ?>'">
                <td><a href="<?= admin_url('invoices/'.(int) $r['id']); ?>" class="fw-semibold text-reset text-decoration-none"><?= html_escape($r['invoice_number']); ?></a><br>
                    <span class="badge <?= $badge[$r['status']]; ?>"><?= $S[$r['status']]; ?></span>
                    <?php if ($r['ro_number']): ?><span class="small ymo-muted ms-1"><?= html_escape($r['ro_number']); ?></span><?php endif; ?></td>
                <td><strong><?= html_escape($r['bill_name']); ?></strong> <span class="small ymo-muted"><?= html_escape($r['bill_mobile']); ?></span><br><span class="small"><?= html_escape($r['vehicle']); ?></span></td>
                <td class="small d-none d-md-table-cell"><?= html_escape(date('d M Y', strtotime($r['invoice_date']))); ?>
                    <?php if (in_array($r['status'], array('unpaid', 'partly_paid'), TRUE)): ?><br><span class="<?= $age > 7 ? 'text-danger' : 'ymo-muted'; ?>"><?= $age; ?> day<?= $age === 1 ? '' : 's'; ?> old</span><?php endif; ?>
                    <?php if ((int) $r['reminders']): ?><br><span class="ymo-muted"><?= (int) $r['reminders']; ?> reminder<?= $r['reminders'] > 1 ? 's' : ''; ?></span><?php endif; ?></td>
                <td class="text-end"><?= $fmt($r['grand_total']); ?></td>
                <td class="text-end fw-semibold"><?= $r['status'] === 'cancelled' ? '—' : $fmt($r['balance']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div class="px-1"><?= crm_list_pager($pager); ?></div>
