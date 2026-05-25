<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = !empty($lead);
$action  = $is_edit ? admin_url('leads/'.$lead['id'].'/edit') : admin_url('leads/new');
?>
<a href="<?= $is_edit ? admin_url('leads/'.$lead['id']) : admin_url('leads'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span><?= $is_edit ? 'Back to lead' : 'All leads'; ?>
</a>

<div class="row justify-content-center mt-2">
    <div class="col-lg-8">
        <div class="ymo-card">
            <h2 class="h4 mb-4">
                <span class="mi mi-leading"><?= $is_edit ? 'edit' : 'person_add'; ?></span>
                <?= $is_edit ? 'Edit lead' : 'New lead'; ?>
            </h2>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open($action); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_name" name="name" placeholder=" " required
                                   value="<?= html_escape(set_value('name', $lead['name'] ?? '')); ?>">
                            <label for="ld_name">Full name *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="ld_source_id" name="source_id" required>
                                <?php
                                $default_source = $lead['source_id'] ?? null;
                                if (!$default_source) {
                                    foreach ($sources as $sx) {
                                        if ($sx['slug'] === 'manual') {
                                            $default_source = (int) $sx['id'];
                                            break;
                                        }
                                    }
                                }
                                foreach ($sources as $s):
                                ?>
                                    <option value="<?= (int) $s['id']; ?>"
                                        <?= (int) set_value('source_id', $default_source) === (int) $s['id'] ? 'selected' : ''; ?>>
                                        <?= html_escape($s['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_source_id">Lead source *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_mobile" name="mobile" placeholder=" "
                                   value="<?= html_escape(set_value('mobile', $lead['mobile'] ?? '')); ?>">
                            <label for="ld_mobile">Mobile</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_email" type="email" name="email" placeholder=" "
                                   value="<?= html_escape(set_value('email', $lead['email'] ?? '')); ?>">
                            <label for="ld_email">Email</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_company" name="company" placeholder=" "
                                   value="<?= html_escape(set_value('company', $lead['company'] ?? '')); ?>">
                            <label for="ld_company">Company</label>
                        </div>
                    </div>
                    <?php if (crm_can('leads.assign')): ?>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="ld_assigned" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($admins as $a): ?>
                                    <option value="<?= (int) $a['id']; ?>"
                                        <?= (int) set_value('assigned_to', $lead['assigned_to'] ?? 0) === (int) $a['id'] ? 'selected' : ''; ?>>
                                        <?= html_escape($a['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_assigned">Assign to</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select" id="ld_stage" name="stage">
                                <?php foreach (array('new','contacted','qualified','proposal','won','lost') as $st): ?>
                                    <option value="<?= $st; ?>" <?= set_value('stage', $lead['stage'] ?? 'new') === $st ? 'selected' : ''; ?>>
                                        <?= ucfirst($st); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_stage">Stage</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select" id="ld_status" name="status">
                                <?php foreach (array('open','converted','junk') as $st): ?>
                                    <option value="<?= $st; ?>" <?= set_value('status', $lead['status'] ?? 'open') === $st ? 'selected' : ''; ?>>
                                        <?= ucfirst($st); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_status">Status</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select" id="ld_priority" name="priority">
                                <option value="0" <?= (int) set_value('priority', $lead['priority'] ?? 0) === 0 ? 'selected' : ''; ?>>Normal</option>
                                <option value="1" <?= (int) set_value('priority', $lead['priority'] ?? 0) === 1 ? 'selected' : ''; ?>>High priority</option>
                            </select>
                            <label for="ld_priority">Priority</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="ld_message" name="message" placeholder=" " style="height:120px"><?= html_escape(set_value('message', $lead['message'] ?? '')); ?></textarea>
                            <label for="ld_message">Enquiry / notes</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <span class="mi mi-sm mi-leading">save</span><?= $is_edit ? 'Save changes' : 'Create lead'; ?>
                        </button>
                    </div>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
