<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ymo-card">
            <h2 class="h4 mb-4"><?= $candidate ? 'Edit candidate' : 'Add candidate'; ?></h2>
            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?= form_open($candidate ? admin_url('recruitment/'.$candidate['id'].'/edit') : admin_url('recruitment/new')); ?>
            <div class="row g-3">
                <div class="col-md-6"><div class="form-floating"><input class="form-control" name="name" required value="<?= html_escape(set_value('name', $candidate['name'] ?? '')); ?>"><label>Name *</label></div></div>
                <div class="col-md-6"><div class="form-floating"><input class="form-control" name="position" value="<?= html_escape(set_value('position', $candidate['position'] ?? '')); ?>"><label>Position</label></div></div>
                <div class="col-md-6"><div class="form-floating"><input class="form-control" name="mobile" value="<?= html_escape(set_value('mobile', $candidate['mobile'] ?? '')); ?>"><label>Mobile</label></div></div>
                <div class="col-md-6"><div class="form-floating"><input class="form-control" type="email" name="email" value="<?= html_escape(set_value('email', $candidate['email'] ?? '')); ?>"><label>Email</label></div></div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="stage">
                            <?php foreach (array('applied','screening','interview','offer','hired','rejected') as $st): ?>
                                <option value="<?= $st; ?>" <?= set_value('stage', $candidate['stage'] ?? 'applied') === $st ? 'selected' : ''; ?>><?= ucfirst($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Stage</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="assigned_to">
                            <option value="">Unassigned</option>
                            <?php foreach ($admins as $a): ?>
                                <option value="<?= (int) $a['id']; ?>" <?= (int) set_value('assigned_to', $candidate['assigned_to'] ?? 0) === (int) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>HR assignee</label>
                    </div>
                </div>
                <div class="col-12"><div class="form-floating"><textarea class="form-control" name="notes" style="height:100px"><?= html_escape(set_value('notes', $candidate['notes'] ?? '')); ?></textarea><label>HR notes</label></div></div>
                <div class="col-12"><button class="btn btn-primary">Save</button></div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
