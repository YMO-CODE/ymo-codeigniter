<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2">
        <a href="<?= admin_url('leads/pipeline'); ?>" class="btn btn-outline-secondary btn-sm">
            <span class="mi mi-sm mi-leading">view_kanban</span>Pipeline
        </a>
    </div>
    <?php if ($can_edit): ?>
        <a href="<?= admin_url('leads/new'); ?>" class="btn btn-primary btn-sm">
            <span class="mi mi-sm mi-leading">add</span>New lead
        </a>
    <?php endif; ?>
</div>

<form class="ymo-card mb-3" method="get" action="<?= admin_url('leads'); ?>">
    <?php if (!empty($filters['source_slug'])): ?>
        <input type="hidden" name="source" value="<?= html_escape($filters['source_slug']); ?>">
    <?php endif; ?>
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <div class="form-floating">
                <input class="form-control" id="ld_q" type="search" name="q" placeholder=" "
                       value="<?= html_escape((string) $filters['q']); ?>">
                <label for="ld_q">Search name / mobile / email</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <select id="ld_source" name="source_id" class="form-select">
                    <option value="">All sources</option>
                    <?php foreach ($sources as $s): ?>
                        <option value="<?= (int) $s['id']; ?>" <?= (int) $filters['source_id'] === (int) $s['id'] ? 'selected' : ''; ?>>
                            <?= html_escape($s['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="ld_source">Source</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <select id="ld_stage" name="stage" class="form-select">
                    <option value="">All stages</option>
                    <?php foreach ($stage_labels as $st => $label): ?>
                        <option value="<?= html_escape($st); ?>" <?= $filters['stage'] === $st ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="ld_stage">Stage</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <select id="ld_status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach (array('open','converted','junk') as $st): ?>
                        <option value="<?= $st; ?>" <?= $filters['status'] === $st ? 'selected' : ''; ?>><?= ucfirst($st); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="ld_status">Status</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <select id="ld_assign" name="assigned_to" class="form-select">
                    <option value="">Anyone</option>
                    <option value="unassigned" <?= $filters['assigned_to'] === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                    <?php foreach ($admins as $a): ?>
                        <option value="<?= (int) $a['id']; ?>" <?= (string) $filters['assigned_to'] === (string) $a['id'] ? 'selected' : ''; ?>>
                            <?= html_escape($a['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="ld_assign">Assigned to</label>
            </div>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-primary"><span class="mi mi-sm mi-leading">filter_alt</span>Filter</button>
        </div>
    </div>
    <div class="mt-2">
        <label class="form-check form-check-inline small">
            <input class="form-check-input" type="checkbox" name="mine" value="1" <?= !empty($filters['mine']) ? 'checked' : ''; ?>>
            My leads only
        </label>
        <label class="form-check form-check-inline small">
            <input class="form-check-input" type="checkbox" name="priority" value="1" <?= (string) $filters['priority'] === '1' ? 'checked' : ''; ?>>
            High priority only
        </label>
    </div>
</form>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr>
            <th>Lead</th><th>Source</th><th>Stage</th><th>Assigned</th><th>Created</th><th class="text-end"></th>
        </tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center py-4 ymo-muted">No leads match those filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $l): ?>
            <tr>
                <td class="small">
                    <?php if ((int) $l['priority'] > 0): ?>
                        <span class="badge badge-priority-high me-1">Priority</span>
                    <?php endif; ?>
                    <strong><?= html_escape($l['name']); ?></strong><br>
                    <span class="ymo-muted">
                        <?= $l['mobile'] ? html_escape($l['mobile']) : '—'; ?>
                        <?= $l['email'] ? ' · '.html_escape($l['email']) : ''; ?>
                    </span>
                </td>
                <td class="small"><?= html_escape($l['source_label']); ?></td>
                <td>
                    <span class="badge bg-light text-dark badge-stage"><?= html_escape(crm_lead_stage_label($l['stage'])); ?></span>
                    <span class="ymo-muted small"><?= html_escape($l['status']); ?></span>
                    <?php if (!empty($l['next_follow_up_at'])): ?>
                        <br><span class="small ymo-muted">Follow-up <?= html_escape(date('d M Y', strtotime($l['next_follow_up_at']))); ?></span>
                    <?php endif; ?>
                </td>
                <td class="small"><?= $l['assignee_name'] ? html_escape($l['assignee_name']) : '<span class="ymo-muted">—</span>'; ?></td>
                <td class="small"><?= html_escape(date('d M Y', strtotime($l['created_at']))); ?></td>
                <td class="text-end">
                    <a href="<?= admin_url('leads/'.$l['id']); ?>" class="btn btn-sm btn-outline-primary">Open</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++):
            $qs = $_GET; $qs['page'] = $i;
            ?>
            <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?= admin_url('leads?'.http_build_query($qs)); ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<p class="ymo-muted small mt-2"><?= (int) $total; ?> total result<?= $total === 1 ? '' : 's'; ?>.</p>
