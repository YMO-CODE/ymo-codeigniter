<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return Work_order_customer_money::fmt($n); }; ?>
<div class="row g-3">
<div class="col-lg-7">
    <?= form_open(admin_url('billing-setup'), array('class' => 'ymo-card', 'autocomplete' => 'off')); ?>
        <h3 class="h6">Business on the invoice</h3>
        <div class="row g-2">
            <div class="col-md-7"><label class="small ymo-muted">Legal name</label><input class="form-control form-control-sm" name="bill_legal_name" maxlength="160" value="<?= html_escape($s['bill_legal_name']); ?>" placeholder="YMO Mobility Solutions Private Limited"></div>
            <div class="col-md-5"><label class="small ymo-muted">GSTIN *</label><input class="form-control form-control-sm text-uppercase" name="bill_gstin" maxlength="15" value="<?= html_escape($s['bill_gstin']); ?>" placeholder="27ABCDE1234F1Z5"></div>
            <div class="col-md-8"><label class="small ymo-muted">Registered address</label><textarea class="form-control form-control-sm" name="bill_address" rows="2" maxlength="400"><?= html_escape($s['bill_address']); ?></textarea></div>
            <div class="col-md-4"><label class="small ymo-muted">State</label><input class="form-control form-control-sm" name="bill_state" maxlength="40" value="<?= html_escape($s['bill_state']); ?>"><div class="form-text">State code is taken from the GSTIN.</div></div>
        </div>

        <h3 class="h6 mt-4">Payments</h3>
        <div class="row g-2">
            <div class="col-md-6"><label class="small ymo-muted">UPI ID of the business bank account</label><input class="form-control form-control-sm" name="bill_upi_vpa" maxlength="80" value="<?= html_escape($s['bill_upi_vpa']); ?>" placeholder="ymomobility@icici"></div>
            <div class="col-md-6"><label class="small ymo-muted">Name shown in the UPI app</label><input class="form-control form-control-sm" name="bill_upi_name" maxlength="50" value="<?= html_escape($s['bill_upi_name']); ?>" placeholder="YMO Mobility Solutions"></div>
            <div class="col-12"><label class="small ymo-muted">Bank transfer details printed on the invoice and pay page</label><textarea class="form-control form-control-sm" name="bill_bank_text" rows="3" maxlength="400" placeholder="Bank name, account name, account number, IFSC"><?= html_escape($s['bill_bank_text']); ?></textarea>
                <div class="form-text">Use a current account in the company's name. Customers see this text as written.</div></div>
        </div>

        <h3 class="h6 mt-4">Numbering and codes</h3>
        <div class="row g-2">
            <div class="col-4 col-md-3"><label class="small ymo-muted">Prefix</label><input class="form-control form-control-sm" name="bill_inv_prefix" maxlength="10" value="<?= html_escape($s['bill_inv_prefix']); ?>"></div>
            <div class="col-8 col-md-3"><label class="small ymo-muted">Next number</label><input class="form-control form-control-sm" name="bill_inv_next" maxlength="9" value="<?= html_escape($s['bill_inv_next']); ?>"></div>
            <div class="col-6 col-md-3"><label class="small ymo-muted">SAC (labour)</label><input class="form-control form-control-sm" name="bill_sac_service" maxlength="8" value="<?= html_escape($s['bill_sac_service']); ?>"></div>
            <div class="col-6 col-md-3"><label class="small ymo-muted">HSN (parts)</label><input class="form-control form-control-sm" name="bill_hsn_part" maxlength="8" value="<?= html_escape($s['bill_hsn_part']); ?>"></div>
            <div class="col-12 small ymo-muted">Next invoice will be <strong><?= html_escape($next); ?></strong>. It never goes below a number already used.</div>
            <div class="col-12"><label class="small ymo-muted">Terms printed on the invoice</label><textarea class="form-control form-control-sm" name="bill_terms" rows="3" maxlength="800"><?= html_escape($s['bill_terms']); ?></textarea></div>
        </div>

        <h3 class="h6 mt-4">Workshops under another GST registration</h3>
        <p class="small ymo-muted">Only if a workshop has its own GSTIN (for example in another state). Leave empty to use the one above.</p>
        <?php foreach ($workshops as $slug => $w): $g = $s['ws_gst'][$slug] ?? array(); ?>
        <div class="row g-2 mb-2 align-items-end">
            <div class="col-md-2 small fw-semibold pb-1"><?= html_escape($w['label']); ?></div>
            <div class="col-md-3"><input class="form-control form-control-sm text-uppercase" name="ws_gstin_<?= $slug; ?>" maxlength="15" value="<?= html_escape($g['gstin'] ?? ''); ?>" placeholder="GSTIN"></div>
            <div class="col-md-2"><input class="form-control form-control-sm" name="ws_state_<?= $slug; ?>" maxlength="40" value="<?= html_escape($g['state'] ?? ''); ?>" placeholder="State"></div>
            <div class="col-md-5"><input class="form-control form-control-sm" name="ws_address_<?= $slug; ?>" maxlength="300" value="<?= html_escape($g['address'] ?? ''); ?>" placeholder="Address on that registration"></div>
        </div>
        <?php endforeach; ?>
        <button class="btn btn-primary mt-3">Save billing setup</button>
    <?= form_close(); ?>
</div>
<div class="col-lg-5">
    <div class="ymo-card" id="accounts">
        <h3 class="h6">Payment accounts</h3>
        <p class="small ymo-muted">Where money lands. Each payment you record goes into one of these; Payments received totals them.</p>
        <ul class="list-unstyled small">
        <?php foreach ($accounts as $a): $t = $acc_tot[(int) $a['id']] ?? NULL; ?>
            <li class="border-bottom py-2 <?= (int) $a['is_active'] ? '' : 'opacity-50'; ?>">
                <details>
                    <summary class="d-flex justify-content-between"><span><strong><?= html_escape($a['name']); ?></strong> <span class="ymo-muted">· <?= Crm_billing_model::ACC_KINDS[$a['kind']]; ?><?= (int) $a['is_active'] ? '' : ' · inactive'; ?></span></span>
                        <span class="ymo-muted"><?= $t ? $fmt($t['total']).' in 30 days' : ''; ?></span></summary>
                    <?= form_open(admin_url('billing-setup/account'), array('class' => 'row g-2 mt-1')); ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id']; ?>">
                        <div class="col-7"><input class="form-control form-control-sm" name="name" maxlength="80" value="<?= html_escape($a['name']); ?>"></div>
                        <div class="col-5"><select class="form-select form-select-sm" name="kind"><?php foreach (Crm_billing_model::ACC_KINDS as $k => $l): ?><option value="<?= $k; ?>" <?= $a['kind'] === $k ? 'selected' : ''; ?>><?= $l; ?></option><?php endforeach; ?></select></div>
                        <div class="col-12"><input class="form-control form-control-sm" name="details" maxlength="255" value="<?= html_escape($a['details']); ?>" placeholder="Note, e.g. HDFC current account"></div>
                        <div class="col-7"><select class="form-select form-select-sm" name="is_active"><option value="1" <?= (int) $a['is_active'] ? 'selected' : ''; ?>>In use</option><option value="0" <?= (int) $a['is_active'] ? '' : 'selected'; ?>>Not used any more</option></select></div>
                        <div class="col-5 text-end"><button class="btn btn-sm btn-outline-primary">Save</button></div>
                    <?= form_close(); ?>
                </details>
            </li>
        <?php endforeach; ?>
        </ul>
        <?= form_open(admin_url('billing-setup/account'), array('class' => 'row g-2')); ?>
            <input type="hidden" name="id" value="0">
            <div class="col-12 small fw-semibold">Add an account</div>
            <div class="col-7"><input class="form-control form-control-sm" name="name" maxlength="80" placeholder="e.g. HDFC current" required></div>
            <div class="col-5"><select class="form-select form-select-sm" name="kind"><?php foreach (Crm_billing_model::ACC_KINDS as $k => $l): ?><option value="<?= $k; ?>"><?= $l; ?></option><?php endforeach; ?></select></div>
            <div class="col-9"><input class="form-control form-control-sm" name="details" maxlength="255" placeholder="Note (optional)"></div>
            <div class="col-3 text-end"><button class="btn btn-sm btn-primary">Add</button></div>
        <?= form_close(); ?>
    </div>
</div>
</div>
