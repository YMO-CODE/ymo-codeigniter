<?php defined('BASEPATH') OR exit('No direct script access allowed');
$seg = json_decode($camp['segment_json'] ?? '{}', TRUE) ?: array();
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ymo-card">
            <h2 class="h4 mb-4"><?= $camp ? 'Edit campaign' : 'New campaign'; ?></h2>
            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?= form_open($camp ? admin_url('campaigns/'.$camp['id'].'/edit') : admin_url('campaigns/new')); ?>
            <div class="row g-3">
                <div class="col-md-8"><div class="form-floating"><input class="form-control" name="name" required value="<?= html_escape(set_value('name', $camp['name'] ?? '')); ?>"><label>Campaign name *</label></div></div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" name="channel">
                            <?php foreach (array('email','sms','both') as $ch): ?>
                                <option value="<?= $ch; ?>" <?= set_value('channel', $camp['channel'] ?? 'email') === $ch ? 'selected' : ''; ?>><?= strtoupper($ch); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Channel</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="segment_type">
                            <option value="all_contacts" <?= ($seg['type'] ?? '') === 'all_contacts' ? 'selected' : ''; ?>>All contacts</option>
                            <option value="leads_open" <?= ($seg['type'] ?? '') === 'leads_open' ? 'selected' : ''; ?>>Open leads</option>
                            <option value="tag" <?= ($seg['type'] ?? '') === 'tag' ? 'selected' : ''; ?>>Contacts with tag</option>
                        </select>
                        <label>Audience</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="tag_id">
                            <option value="">-</option>
                            <?php foreach ($tags as $t): ?>
                                <option value="<?= (int) $t['id']; ?>" <?= (int)($seg['tag_id'] ?? 0) === (int)$t['id'] ? 'selected' : ''; ?>><?= html_escape($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Tag (if segment = tag)</label>
                    </div>
                </div>
                <div class="col-12"><div class="form-floating"><input class="form-control" name="subject" value="<?= html_escape(set_value('subject', $camp['subject'] ?? '')); ?>"><label>Email subject</label></div></div>
                <div class="col-12"><div class="form-floating"><textarea class="form-control" name="body" style="height:160px" required><?= html_escape(set_value('body', $camp['body'] ?? '')); ?></textarea><label>Message body *</label></div></div>
                <div class="col-12"><button class="btn btn-primary">Save draft</button></div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
