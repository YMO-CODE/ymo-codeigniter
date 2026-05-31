<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = !empty($contact);
$action  = $is_edit ? admin_url('contacts/'.$contact['id'].'/edit') : admin_url('contacts/new');
$cancel_url = $is_edit ? admin_url('contacts/'.$contact['id']) : admin_url('contacts');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ymo-card">
            <h2 class="h4 mb-4"><?= $is_edit ? 'Edit contact' : 'New contact'; ?></h2>
            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?= form_open($action); ?>
            <div class="row g-3">
                <div class="col-md-6"><div class="form-floating"><input class="form-control" name="name" required value="<?= html_escape(set_value('name', $contact['name'] ?? '')); ?>"><label>Name *</label></div></div>
                <div class="col-md-6"><div class="form-floating"><input class="form-control" name="mobile" value="<?= html_escape(set_value('mobile', $contact['mobile'] ?? '')); ?>"><label>Mobile</label></div></div>
                <div class="col-md-6"><div class="form-floating"><input class="form-control" type="email" name="email" value="<?= html_escape(set_value('email', $contact['email'] ?? '')); ?>"><label>Email</label></div></div>
                <div class="col-md-6"><div class="form-floating"><input class="form-control" name="company" value="<?= html_escape(set_value('company', $contact['company'] ?? '')); ?>" placeholder=" "><label>Workshop</label></div></div>
                <div class="col-12"><div class="form-floating"><textarea class="form-control" name="notes" style="height:100px"><?= html_escape(set_value('notes', $contact['notes'] ?? '')); ?></textarea><label>Notes</label></div></div>
                <div class="col-12">
                    <label class="form-label small">Tags</label>
                    <?php foreach ($tags as $t): ?>
                        <label class="form-check form-check-inline small">
                            <input type="checkbox" name="tag_ids[]" value="<?= (int) $t['id']; ?>" <?= in_array((int)$t['id'], $tag_ids, TRUE) ? 'checked' : ''; ?>>
                            <?= html_escape($t['name']); ?>
                        </label>
                    <?php endforeach; ?>
                    <input class="form-control form-control-sm mt-1" name="new_tag" placeholder="Or add new tag name">
                </div>
                <div class="col-12">
                    <label class="form-check small"><input type="checkbox" name="email_opt_out" value="1" <?= !empty($contact['email_opt_out']) ? 'checked' : ''; ?>> Email opt-out</label>
                    <label class="form-check small ms-3"><input type="checkbox" name="sms_opt_out" value="1" <?= !empty($contact['sms_opt_out']) ? 'checked' : ''; ?>> SMS opt-out</label>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="<?= $cancel_url; ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
