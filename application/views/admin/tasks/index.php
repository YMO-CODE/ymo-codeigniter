<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between mb-3">
    <?php if ($can_edit): ?>
        <a href="<?= admin_url('tasks/new'); ?>" class="btn btn-primary btn-sm">
            <span class="mi mi-sm mi-leading">add</span>Schedule follow-up
        </a>
    <?php endif; ?>
</div>

<form class="ymo-card mb-3" method="get" action="<?= admin_url('tasks'); ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <?php foreach (array('pending','done','skipped') as $s): ?>
                    <option value="<?= $s; ?>" <?= $filters['status'] === $s ? 'selected' : ''; ?>><?= ucfirst($s); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="due" class="form-select form-select-sm">
                <option value="">All dates</option>
                <option value="today" <?= ($filters['due'] ?? '') === 'today' ? 'selected' : ''; ?>>Due today</option>
                <option value="overdue" <?= ($filters['due'] ?? '') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-check small mb-0">
                <input type="checkbox" name="mine" value="1" <?= !empty($filters['mine']) ? 'checked' : ''; ?>> My tasks
            </label>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary btn-sm">Filter</button></div>
    </div>
</form>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Task</th><th>Due</th><th>Linked</th><th>Assignee</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center py-4 ymo-muted">No tasks found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $t): ?>
            <tr class="<?= ($t['status'] === 'pending' && strtotime($t['due_at']) < time()) ? 'table-warning' : ''; ?>">
                <td class="small">
                    <?php if ((int) $t['priority'] > 0): ?><span class="badge badge-priority-high">!</span> <?php endif; ?>
                    <strong><?= html_escape($t['title']); ?></strong>
                </td>
                <td class="small"><?= html_escape(date('d M Y, h:i A', strtotime($t['due_at']))); ?></td>
                <td class="small">
                    <?php if ($t['lead_name']): ?>
                        <a href="<?= admin_url('leads/'.$t['lead_id']); ?>"><?= html_escape($t['lead_name']); ?></a>
                    <?php elseif ($t['contact_name']): ?>
                        <a href="<?= admin_url('customers/'.$t['contact_id']); ?>"><?= html_escape($t['contact_name']); ?></a>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td class="small"><?= html_escape($t['assignee_name']); ?></td>
                <td class="small"><?= html_escape($t['status']); ?></td>
                <td class="text-end">
                    <?php if ($can_edit && $t['status'] === 'pending'): ?>
                        <a href="<?= admin_url('tasks/'.$t['id'].'/done'); ?>" class="btn btn-sm btn-outline-success">Done</a>
                        <a href="<?= admin_url('tasks/'.$t['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
