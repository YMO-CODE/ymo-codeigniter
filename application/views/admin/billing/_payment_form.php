<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Narrow (job card side column) or wide (invoice page) layout.
$narrow = $back === 'job';
$c = function ($wide, $small = 'col-6') use ($narrow) { return $narrow ? $small : $wide; };
?>
<?= form_open(admin_url('invoices/'.(int) $inv['id'].'/payment'), array('class' => 'row g-2 align-items-end border rounded p-2 mx-0', 'autocomplete' => 'off')); ?>
    <?php if ($back !== ''): ?><input type="hidden" name="back" value="<?= html_escape($back); ?>"><?php endif; ?>
    <div class="col-12 small fw-semibold">Record a payment</div>
    <div class="<?= $c('col-6 col-md-3'); ?>"><label class="small ymo-muted">Amount &#8377;</label><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" max="<?= html_escape((string) $inv['balance']); ?>" name="amount" value="<?= html_escape((string) $inv['balance']); ?>" required></div>
    <div class="<?= $c('col-6 col-md-3'); ?>"><label class="small ymo-muted">How</label>
        <select class="form-select form-select-sm" name="mode"><?php foreach (Crm_billing_model::MODES as $k => $l): ?><option value="<?= $k; ?>" <?= $k === 'upi' ? 'selected' : ''; ?>><?= $l; ?></option><?php endforeach; ?></select></div>
    <div class="<?= $c('col-6 col-md-3'); ?>"><label class="small ymo-muted">Into account</label>
        <select class="form-select form-select-sm" name="account_id"><?php foreach ($accounts as $a): ?><option value="<?= (int) $a['id']; ?>" <?= $a['kind'] === 'bank' ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option><?php endforeach; ?></select></div>
    <div class="<?= $c('col-6 col-md-3'); ?>"><label class="small ymo-muted">Date</label><input class="form-control form-control-sm" type="<?= $narrow ? 'date' : 'datetime-local'; ?>" name="paid_at" value="<?= $narrow ? date('Y-m-d') : date('Y-m-d\TH:i'); ?>"></div>
    <div class="<?= $c('col-6 col-md-4', 'col-12'); ?>"><label class="small ymo-muted">UPI / bank ref (UTR)</label><input class="form-control form-control-sm" name="reference" maxlength="80" placeholder="optional"></div>
    <div class="<?= $c('col-6 col-md-5', 'col-12'); ?>"><label class="small ymo-muted">Note</label><input class="form-control form-control-sm" name="note" maxlength="255" placeholder="optional"></div>
    <div class="<?= $c('col-12 col-md-3', 'col-12'); ?> text-end"><button class="btn btn-sm btn-primary w-100">Save payment</button></div>
<?= form_close(); ?>
