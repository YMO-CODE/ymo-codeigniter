<?php defined('BASEPATH') OR exit('No direct script access allowed');
$stage_labels = isset($stage_labels) ? $stage_labels : crm_lead_stages();
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a href="<?= admin_url('leads'); ?>" class="small">
        <span class="mi mi-sm mi-leading">arrow_back</span>List view
    </a>
    <?php if (crm_can('leads.edit')): ?>
        <a href="<?= admin_url('leads/new'); ?>" class="btn btn-primary btn-sm">
            <span class="mi mi-sm mi-leading">add</span>New lead
        </a>
    <?php endif; ?>
</div>

<form class="ymo-card mb-3" method="get" action="<?= admin_url('leads/pipeline'); ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <div class="form-floating">
                <select id="pl_status" name="status" class="form-select">
                    <?php foreach (array('open','converted','junk') as $st): ?>
                        <option value="<?= $st; ?>" <?= $filters['status'] === $st ? 'selected' : ''; ?>><?= ucfirst($st); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="pl_status">Status</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-floating">
                <select id="pl_assign" name="assigned_to" class="form-select">
                    <option value="">Anyone</option>
                    <?php foreach ($admins as $a): ?>
                        <option value="<?= (int) $a['id']; ?>" <?= (string) $filters['assigned_to'] === (string) $a['id'] ? 'selected' : ''; ?>>
                            <?= html_escape($a['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="pl_assign">Assigned to</label>
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-center">
            <label class="form-check small mb-0">
                <input class="form-check-input" type="checkbox" name="mine" value="1" <?= !empty($filters['mine']) ? 'checked' : ''; ?>>
                My leads only
            </label>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary btn-sm">Apply</button>
        </div>
    </div>
</form>

<div class="ymo-pipeline">
    <?php foreach ($stage_labels as $slug => $label): ?>
        <div class="ymo-pipeline-col">
            <h6><?= html_escape($label); ?> <span class="badge bg-secondary"><?= (int) ($counts[$slug] ?? 0); ?></span></h6>
            <?php if (empty($columns[$slug])): ?>
                <p class="ymo-muted small mb-0">No leads</p>
            <?php endif; ?>
            <?php foreach ($columns[$slug] as $l): ?>
                <a href="<?= admin_url('leads/'.$l['id']); ?>" class="ymo-pipeline-card">
                    <div class="name">
                        <?php if ((int) $l['priority'] > 0): ?>★ <?php endif; ?>
                        <?= html_escape($l['name']); ?>
                    </div>
                    <div class="meta">
                        <?= html_escape($l['source_label']); ?>
                        <?php if ($l['assignee_name']): ?> · <?= html_escape($l['assignee_name']); ?><?php endif; ?>
                        <?php if (!empty($l['next_follow_up_at'])): ?>
                            <br><span class="text-warning">Due <?= html_escape(date('d M', strtotime($l['next_follow_up_at']))); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
