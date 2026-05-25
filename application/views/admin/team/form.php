<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = !empty($user);
$action  = $is_edit ? admin_url('team/'.$user['id'].'/edit') : admin_url('team/new');
?>
<a href="<?= admin_url('team'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>All team members
</a>

<div class="row justify-content-center mt-2">
    <div class="col-lg-8">
        <div class="ymo-card">
            <h2 class="h4 mb-4">
                <span class="mi mi-leading"><?= $is_edit ? 'edit' : 'person_add'; ?></span>
                <?= $is_edit ? 'Edit team member' : 'Add team member'; ?>
            </h2>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open($action); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="tm_name" name="name" placeholder=" " required
                                   value="<?= html_escape(set_value('name', $user['name'] ?? '')); ?>">
                            <label for="tm_name">Full name *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="tm_email" type="email" name="email" placeholder=" " required
                                   value="<?= html_escape(set_value('email', $user['email'] ?? '')); ?>">
                            <label for="tm_email">Email *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="tm_role" name="crm_role_id" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= (int) $r['id']; ?>"
                                        <?= (int) set_value('crm_role_id', $user['crm_role_id'] ?? 0) === (int) $r['id'] ? 'selected' : ''; ?>>
                                        <?= html_escape($r['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="tm_role">Role *</label>
                        </div>
                    </div>
                    <?php if ($is_edit): ?>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="tm_active" name="is_active" value="1"
                                <?= set_value('is_active', $user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tm_active">Account active</label>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="tm_password" type="password" name="password" placeholder=" "
                                   autocomplete="new-password">
                            <label for="tm_password">Password (optional)</label>
                        </div>
                        <small class="ymo-muted">Leave blank to generate a temporary password shown once after save.</small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary">
                        <span class="mi mi-sm mi-leading">save</span><?= $is_edit ? 'Save changes' : 'Create member'; ?>
                    </button>
                </div>
            <?= form_close(); ?>

            <?php if ($is_edit): ?>
            <hr class="my-4">
            <h3 class="h6 mb-3">Reset password</h3>
            <?= form_open(admin_url('team/'.$user['id'].'/reset-password')); ?>
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="tm_newpass" type="password" name="password" placeholder=" "
                                   autocomplete="new-password">
                            <label for="tm_newpass">New password (optional)</label>
                        </div>
                        <small class="ymo-muted">Leave blank to generate a random password shown once.</small>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-outline-warning">
                            <span class="mi mi-sm mi-leading">lock_reset</span>Reset password
                        </button>
                    </div>
                </div>
            <?= form_close(); ?>

            <?php if ($user['is_active']): ?>
            <hr class="my-4">
            <?= form_open(admin_url('team/'.$user['id'].'/deactivate'), array('class' => 'd-inline')); ?>
                <button class="btn btn-outline-danger" data-confirm="Deactivate this team member? They will not be able to sign in.">
                    <span class="mi mi-sm mi-leading">person_off</span>Deactivate account
                </button>
            <?= form_close(); ?>
            <?php else: ?>
            <hr class="my-4">
            <?= form_open(admin_url('team/'.$user['id'].'/activate'), array('class' => 'd-inline')); ?>
                <button class="btn btn-outline-success">
                    <span class="mi mi-sm mi-leading">person</span>Reactivate account
                </button>
            <?= form_close(); ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
