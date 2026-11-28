<?php defined('BASEPATH') OR exit('No direct script access allowed');
$action = $t ? admin_url('technicians/'.(int) $t['id'].'/edit') : admin_url('technicians/new');
$v = function ($k, $d = '') use ($t) { return set_value($k, isset($t[$k]) ? (string) $t[$k] : $d); };
?>
<a href="<?= admin_url('technicians'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>Technicians</a>

<div class="ymo-card mt-3" style="max-width: 40rem;">
    <?= form_open($action, array('autocomplete' => 'off')); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small mb-1">Name *</label>
                <input class="form-control" name="name" maxlength="120" required value="<?= html_escape($v('name')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Mobile * <span class="ymo-muted">(they sign in with it)</span></label>
                <input class="form-control" name="mobile" inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" required value="<?= html_escape($v('mobile')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Workshop</label>
                <select class="form-select" name="workshop">
                    <option value="">All workshops</option>
                    <?php foreach ($workshops as $slug => $w): ?><option value="<?= html_escape($slug); ?>" <?= $v('workshop') === $slug ? 'selected' : ''; ?>><?= html_escape($w['label']); ?></option><?php endforeach; ?>
                </select>
                <div class="form-text">They see that workshop's open jobs, plus any job they are assigned to.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Phone screen language</label>
                <select class="form-select" name="lang">
                    <?php foreach (Crm_technician_model::LANGS as $k => $l): ?><option value="<?= $k; ?>" <?= $v('lang', 'hi') === $k ? 'selected' : ''; ?>><?= html_escape($l); ?></option><?php endforeach; ?>
                </select>
                <div class="form-text">They can switch it on the phone too.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">4-digit PIN <?= $t ? '<span class="ymo-muted">(leave empty to keep)</span>' : '*'; ?></label>
                <input class="form-control" name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" <?= $t ? '' : 'required'; ?> autocomplete="new-password">
                <div class="form-text"><?= $t ? 'A new PIN signs them out of their phone.' : 'Tell them the PIN in person.'; ?> 5 wrong tries lock it for 15 minutes.</div>
            </div>
            <?php if ($t): ?>
            <div class="col-md-6">
                <label class="form-label small mb-1">Status</label>
                <select class="form-select" name="is_active">
                    <option value="1" <?= (int) $t['is_active'] ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?= (int) $t['is_active'] ? '' : 'selected'; ?>>Inactive (left / not working)</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary mt-3"><?= $t ? 'Save' : 'Add technician'; ?></button>
    <?= form_close(); ?>
</div>
