<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = !empty($task);
$action = $is_edit ? admin_url('tasks/'.$task['id'].'/edit') : admin_url('tasks/new');
$due_val = $is_edit ? date('Y-m-d\TH:i', strtotime($task['due_at'])) : date('Y-m-d\TH:i', strtotime('+1 day'));
?>
<a href="<?= admin_url('tasks'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All tasks</a>
<div class="row justify-content-center mt-2">
    <div class="col-lg-7">
        <div class="ymo-card">
            <h2 class="h4 mb-4"><?= $is_edit ? 'Edit task' : 'Schedule follow-up'; ?></h2>
            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?= form_open($action); ?>
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-floating">
                        <input class="form-control" name="title" placeholder=" " required value="<?= html_escape(set_value('title', $task['title'] ?? '')); ?>">
                        <label>Title *</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input class="form-control" type="datetime-local" name="due_at" required value="<?= html_escape(set_value('due_at', $due_val)); ?>">
                        <label>Due at *</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="assignee_admin_id" required>
                            <?php foreach ($admins as $a): ?>
                                <option value="<?= (int) $a['id']; ?>" <?= (int) set_value('assignee_admin_id', $task['assignee_admin_id'] ?? $this->session->userdata('admin')['id']) === (int) $a['id'] ? 'selected' : ''; ?>>
                                    <?= html_escape($a['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Assign to *</label>
                    </div>
                </div>
                <input type="hidden" name="lead_id" value="<?= (int) set_value('lead_id', $task['lead_id'] ?? ($lead_id ?? 0)); ?>">
                <input type="hidden" name="contact_id" value="<?= (int) set_value('contact_id', $task['contact_id'] ?? ($contact_id ?? 0)); ?>">
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="priority">
                            <option value="0">Normal</option>
                            <option value="1" <?= (int) set_value('priority', $task['priority'] ?? 0) === 1 ? 'selected' : ''; ?>>High</option>
                        </select>
                        <label>Priority</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control" name="notes" style="height:80px" placeholder=" "><?= html_escape(set_value('notes', $task['notes'] ?? '')); ?></textarea>
                        <label>Notes</label>
                    </div>
                </div>
                <div class="col-12"><button type="submit" class="btn btn-primary">Save</button></div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
