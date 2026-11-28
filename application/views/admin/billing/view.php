<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return Work_order_customer_money::fmt($n); };
$num = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); };
$S = Crm_billing_model::STATUS;
$badge = array('unpaid' => 'bg-danger', 'partly_paid' => 'bg-warning text-dark', 'paid' => 'bg-success', 'cancelled' => 'bg-secondary');
$open = in_array($inv['status'], array('unpaid', 'partly_paid'), TRUE);
$edit = !empty($can_edit) && $inv['status'] !== 'cancelled';
$inter = $inv['gst_type'] === 'inter';
?>
<a href="<?= admin_url('invoices'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>Invoices</a>
<div class="ymo-card mt-2 mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-3">
        <div>
            <h2 class="h4 mb-1"><?= html_escape($inv['invoice_number']); ?> <span class="badge <?= $badge[$inv['status']]; ?> align-middle" style="font-size:.75rem"><?= $S[$inv['status']]; ?></span></h2>
            <div><strong><?= html_escape($inv['bill_name']); ?></strong> · <?= html_escape($inv['bill_mobile']); ?><?= $inv['bill_gstin'] !== '' ? ' · GSTIN '.html_escape($inv['bill_gstin']) : ''; ?></div>
            <div class="small"><?= html_escape($inv['vehicle']); ?></div>
            <div class="small ymo-muted mt-1">Dated <?= html_escape(date('d M Y', strtotime($inv['invoice_date']))); ?> · <?= $inter ? 'IGST' : 'CGST + SGST'; ?>
                <?php if ($wo): ?> · Job card <a href="<?= admin_url('job-cards/'.(int) $wo['id']); ?>"><?= html_escape($wo['ro_number']); ?></a><?php endif; ?>
                <?php if ($inv['sent_at']): ?> · Sent on WhatsApp <?= html_escape(date('d M, H:i', strtotime($inv['sent_at']))); ?><?php endif; ?>
                <?php if ((int) $inv['reminders']): ?> · <?= (int) $inv['reminders']; ?> reminder<?= $inv['reminders'] > 1 ? 's' : ''; ?>, last <?= html_escape(date('d M', strtotime($inv['last_reminder_at']))); ?><?php endif; ?>
            </div>
        </div>
        <div class="text-end">
            <div class="h3 mb-0"><?= $fmt($inv['grand_total']); ?></div>
            <?php if ($inv['status'] !== 'cancelled'): ?><div class="<?= (float) $inv['balance'] > 0 ? 'text-danger fw-semibold' : 'text-success'; ?>"><?= (float) $inv['balance'] > 0 ? $fmt($inv['balance']).' due' : 'Fully paid'; ?></div><?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary mt-2" href="<?= admin_url('invoices/'.(int) $inv['id'].'/pdf'); ?>" target="_blank"><span class="mi mi-sm mi-leading">picture_as_pdf</span>PDF</a>
        </div>
    </div>
    <?php if ($edit): ?>
    <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top align-items-center">
        <?= form_open(admin_url('invoices/'.(int) $inv['id'].'/send'), array('class' => 'd-inline', 'onsubmit' => "return confirm('Send the invoice to the customer on WhatsApp?');")); ?><input type="hidden" name="kind" value="invoice"><button class="btn btn-sm btn-success"><span class="mi mi-sm mi-leading">send</span>Send invoice on WhatsApp</button><?= form_close(); ?>
        <?php if ($open): ?>
        <?= form_open(admin_url('invoices/'.(int) $inv['id'].'/send'), array('class' => 'd-inline', 'onsubmit' => "return confirm('Send a payment reminder on WhatsApp?');")); ?><input type="hidden" name="kind" value="reminder"><button class="btn btn-sm btn-outline-success">Payment reminder</button><?= form_close(); ?>
        <?php endif; ?>
        <span class="small ms-md-2">Pay page: <a href="<?= html_escape($pay_url); ?>" target="_blank"><?= html_escape(preg_replace('#^https?://#', '', $pay_url)); ?></a>
            <button type="button" class="btn btn-sm btn-link p-0 ms-1" onclick="navigator.clipboard.writeText(<?= html_escape(json_encode($pay_url)); ?>);this.textContent='Copied'">Copy</button></span>
    </div>
    <?php if (!$upi_ok): ?><div class="alert alert-warning small mt-3 mb-0">No UPI ID set yet, so the invoice and pay page show no QR. Add it in <a href="<?= admin_url('billing-setup'); ?>">Billing setup</a>.</div><?php endif; ?>
    <?php endif; ?>
</div>

<div class="row g-3">
<div class="col-lg-8">
    <div class="ymo-card p-0 mb-3">
        <div class="table-responsive">
        <table class="ymo-table mb-0">
            <thead><tr><th>Item</th><th class="d-none d-md-table-cell">HSN/SAC</th><th class="text-end">Qty × rate</th><th class="text-end d-none d-md-table-cell">Taxable</th><th class="text-end">GST</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($lines as $l): ?>
                <tr><td><?= html_escape($l['name']); ?><?= (float) $l['discount_pct'] > 0 ? ' <span class="small ymo-muted">'.$num($l['discount_pct']).'% off</span>' : ''; ?></td>
                    <td class="small d-none d-md-table-cell"><?= html_escape($l['hsn_sac']); ?></td>
                    <td class="text-end small"><?= $num($l['qty']); ?> × <?= $fmt($l['rate']); ?></td>
                    <td class="text-end d-none d-md-table-cell"><?= $fmt($l['taxable']); ?></td>
                    <td class="text-end small"><?= $num($l['tax_pct']); ?>%</td>
                    <td class="text-end"><?= $fmt($l['amount']); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="px-3 py-2 border-top">
            <table class="ms-auto small" style="min-width:16rem">
                <tr><td class="ymo-muted pe-3">Taxable value</td><td class="text-end"><?= $fmt($inv['subtotal']); ?></td></tr>
                <?php if ($inter): ?><tr><td class="ymo-muted">IGST</td><td class="text-end"><?= $fmt($inv['igst']); ?></td></tr>
                <?php else: ?><tr><td class="ymo-muted">CGST</td><td class="text-end"><?= $fmt($inv['cgst']); ?></td></tr><tr><td class="ymo-muted">SGST</td><td class="text-end"><?= $fmt($inv['sgst']); ?></td></tr><?php endif; ?>
                <?php if ((float) $inv['round_off'] != 0): ?><tr><td class="ymo-muted">Round off</td><td class="text-end"><?= $fmt($inv['round_off']); ?></td></tr><?php endif; ?>
                <tr class="fw-semibold" style="font-size:1rem"><td>Total</td><td class="text-end"><?= $fmt($inv['grand_total']); ?></td></tr>
            </table>
        </div>
    </div>

    <div class="ymo-card mb-3" id="payments">
        <h3 class="h6">Payments</h3>
        <?php if (!$payments): ?><p class="small ymo-muted">No payment recorded yet.</p><?php endif; ?>
        <ul class="list-unstyled small mb-2">
        <?php foreach ($payments as $p): ?>
            <li class="py-1 border-bottom d-flex flex-wrap gap-2 align-items-center <?= $p['voided_at'] ? 'text-decoration-line-through ymo-muted' : ''; ?>">
                <strong><?= $fmt($p['amount']); ?></strong>
                <span><?= html_escape(Crm_billing_model::MODES[$p['mode']] ?? $p['mode']); ?><?= $p['account_name'] ? ' → '.html_escape($p['account_name']) : ''; ?></span>
                <span class="ymo-muted"><?= html_escape(date('d M Y, H:i', strtotime($p['paid_at']))); ?><?= $p['reference'] !== '' ? ' · Ref '.html_escape($p['reference']) : ''; ?><?= $p['created_by_name'] ? ' · '.html_escape($p['created_by_name']) : ''; ?></span>
                <?php if ($p['note'] !== ''): ?><span class="ymo-muted">· <?= html_escape($p['note']); ?></span><?php endif; ?>
                <?php if (!$p['voided_at'] && !empty($can_edit)): ?>
                <?= form_open(admin_url('invoices/'.(int) $inv['id'].'/payments/'.(int) $p['id'].'/void'), array('class' => 'd-inline ms-auto', 'onsubmit' => "return confirm('Remove this payment? Only if it was entered by mistake.');")); ?><button class="btn btn-sm btn-link text-danger p-0">Remove</button><?= form_close(); ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php if ($edit && $open): ?>
        <?php $this->load->view('admin/billing/_payment_form', array('inv' => $inv, 'accounts' => $accounts, 'back' => '')); ?>
        <?php endif; ?>
    </div>
</div>
<div class="col-lg-4">
    <?php if ($edit): ?>
    <div class="ymo-card mb-3">
        <h3 class="h6">Invoice date</h3>
        <?= form_open(admin_url('invoices/'.(int) $inv['id'].'/date'), array('class' => 'd-flex gap-2')); ?>
            <input class="form-control form-control-sm" type="date" name="invoice_date" value="<?= html_escape($inv['invoice_date']); ?>">
            <button class="btn btn-sm btn-outline-secondary">Change</button>
        <?= form_close(); ?>
    </div>
    <div class="ymo-card mb-3">
        <h3 class="h6">Cancel invoice</h3>
        <p class="small ymo-muted">For a wrong invoice. The number is not reused; raise a new one from the job card after fixing it.</p>
        <?= form_open(admin_url('invoices/'.(int) $inv['id'].'/cancel'), array('onsubmit' => "return confirm('Cancel invoice ".html_escape($inv['invoice_number'])."?');")); ?>
            <input class="form-control form-control-sm mb-2" name="reason" maxlength="200" placeholder="Reason, e.g. wrong part price" required>
            <button class="btn btn-sm btn-outline-danger">Cancel invoice</button>
        <?= form_close(); ?>
    </div>
    <?php elseif ($inv['status'] === 'cancelled'): ?>
    <div class="ymo-card mb-3"><h3 class="h6">Cancelled</h3><p class="small mb-0"><?= html_escape(date('d M Y', strtotime($inv['cancelled_at']))); ?><?= $inv['cancel_reason'] !== '' ? ': '.html_escape($inv['cancel_reason']) : ''; ?></p></div>
    <?php endif; ?>
</div>
</div>
