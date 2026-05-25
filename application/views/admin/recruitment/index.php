<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($can_edit): ?>
    <a href="<?= admin_url('recruitment/new'); ?>" class="btn btn-primary btn-sm mb-3">Add candidate</a>
<?php endif; ?>
<form class="ymo-card mb-3" method="get">
    <div class="row g-2">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= html_escape((string)($filters['q'] ?? '')); ?>" placeholder="Search…"></div>
        <div class="col-md-3">
            <select name="stage" class="form-select">
                <option value="">All stages</option>
                <?php foreach (array('applied','screening','interview','offer','hired','rejected') as $st): ?>
                    <option value="<?= $st; ?>" <?= ($filters['stage'] ?? '') === $st ? 'selected' : ''; ?>><?= ucfirst($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </div>
</form>
<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Name</th><th>Position</th><th>Stage</th><th>Assigned</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr>
                <td><?= html_escape($c['name']); ?><br><span class="ymo-muted small"><?= html_escape($c['mobile']); ?></span></td>
                <td class="small"><?= html_escape($c['position']); ?></td>
                <td class="small"><?= html_escape($c['stage']); ?></td>
                <td class="small"><?= html_escape($c['assignee_name'] ?? '—'); ?></td>
                <td class="text-end"><a href="<?= admin_url('recruitment/'.$c['id']); ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
