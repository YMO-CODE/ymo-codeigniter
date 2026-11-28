<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return Work_order_customer_money::fmt($n); }; ?>
<a href="<?= admin_url('invoices'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>Invoices</a>
<form method="get" action="<?= admin_url('payments'); ?>" class="d-flex flex-wrap gap-2 align-items-end my-3">
    <div><label class="small ymo-muted">From</label><input class="form-control form-control-sm" type="date" name="from" value="<?= html_escape($from); ?>"></div>
    <div><label class="small ymo-muted">To</label><input class="form-control form-control-sm" type="date" name="to" value="<?= html_escape($to); ?>"></div>
    <button class="btn btn-sm btn-outline-secondary">Show</button>
</form>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3"><div class="ymo-card h-100"><div class="small ymo-muted">Received</div><div class="h3 mb-0"><?= $fmt($total); ?></div><div class="small ymo-muted"><?= count($rows); ?> payment<?= count($rows) === 1 ? '' : 's'; ?></div></div></div>
    <?php foreach ($by_acc as $name => $amt): ?>
    <div class="col-sm-6 col-lg-3"><div class="ymo-card h-100"><div class="small ymo-muted"><?= html_escape($name); ?></div><div class="h5 mb-0"><?= $fmt($amt); ?></div></div></div>
    <?php endforeach; ?>
</div>
<div class="ymo-card p-0">
    <?php if (!$rows): ?><p class="small ymo-muted p-3 mb-0">No payments in this period.</p><?php else: ?>
    <div class="table-responsive"><table class="ymo-table mb-0">
        <thead><tr><th>When</th><th>Amount</th><th>How / account</th><th class="d-none d-md-table-cell">Reference</th><th>Invoice</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td class="small"><?= html_escape(date('d M, H:i', strtotime($r['paid_at']))); ?></td>
                <td class="fw-semibold"><?= $fmt($r['amount']); ?></td>
                <td class="small"><?= html_escape(Crm_billing_model::MODES[$r['mode']] ?? $r['mode']); ?><?= $r['account_name'] ? ' → '.html_escape($r['account_name']) : ''; ?></td>
                <td class="small d-none d-md-table-cell"><?= html_escape($r['reference']); ?></td>
                <td class="small"><?php if ($r['invoice_id']): ?><a href="<?= admin_url('invoices/'.(int) $r['invoice_id']); ?>">Open</a><?php else: ?>Advance<?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
