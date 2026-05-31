<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <a href="<?= admin_url('contacts/export'); ?>" class="btn btn-outline-secondary btn-sm">Export CSV</a>
        <?php if ($can_edit): ?>
            <a href="<?= admin_url('contacts/import'); ?>" class="btn btn-outline-primary btn-sm ms-1">Import CSV</a>
        <?php endif; ?>
    </div>
    <?php if ($can_edit): ?>
        <a href="<?= admin_url('contacts/new'); ?>" class="btn btn-primary btn-sm"><span class="mi mi-sm mi-leading">add</span>New contact</a>
    <?php endif; ?>
</div>
<form class="ymo-card mb-3" method="get">
    <div class="row g-2">
        <div class="col-md-4"><input class="form-control" name="q" placeholder="Search…" value="<?= html_escape((string) $filters['q']); ?>"></div>
        <div class="col-md-3">
            <select name="tag_id" class="form-select">
                <option value="">All tags</option>
                <?php foreach ($tags as $t): ?>
                    <option value="<?= (int) $t['id']; ?>" <?= (int)($filters['tag_id'] ?? 0) === (int)$t['id'] ? 'selected' : ''; ?>><?= html_escape($t['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </div>
</form>

<?php if ($can_edit): ?>
<?= form_open(admin_url('contacts/bulk-edit'), array('id' => 'contacts-bulk-form')); ?>
<div class="ymo-card mb-3 d-none" id="contacts-bulk-bar">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <strong><span id="contacts-bulk-count">0</span> selected</strong>
        <button type="button" class="btn btn-sm btn-link" data-contacts-clear-selection>Clear selection</button>
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-check small mb-2">
                <input type="checkbox" name="apply_workshop" value="1" id="bulk_apply_workshop">
                Update workshop
            </label>
            <input class="form-control form-control-sm" name="workshop" placeholder="e.g. G1 Pune, G2 Pune, Wakad">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Tags</label>
            <select name="tag_mode" class="form-select form-select-sm">
                <option value="none">Do not change tags</option>
                <option value="add">Add tags (keep existing)</option>
                <option value="replace">Replace all tags</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Tag names</label>
            <div class="border rounded p-2 mb-2" style="max-height:120px;overflow-y:auto">
                <?php foreach ($tags as $t): ?>
                    <label class="form-check form-check-inline small me-2 mb-1">
                        <input type="checkbox" name="tag_ids[]" value="<?= (int) $t['id']; ?>">
                        <?= html_escape($t['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <input class="form-control form-control-sm" name="new_tag" placeholder="Or new tag name">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary btn-sm" data-confirm="Apply bulk changes to the selected contacts?">Apply to selected</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0" id="contacts-table">
        <thead>
            <tr>
                <?php if ($can_edit): ?>
                    <th style="width:2.5rem"><input type="checkbox" id="contacts-select-all" aria-label="Select all on this page"></th>
                <?php endif; ?>
                <th>Name</th>
                <th>Mobile</th>
                <th>Email</th>
                <th>Workshop</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr>
                <?php if ($can_edit): ?>
                    <td><input type="checkbox" class="contact-row-check" name="contact_ids[]" value="<?= (int) $c['id']; ?>"></td>
                <?php endif; ?>
                <td><?= html_escape($c['name']); ?></td>
                <td class="small"><?= html_escape($c['mobile']); ?></td>
                <td class="small"><?= html_escape($c['email']); ?></td>
                <td class="small"><?= html_escape($c['company']); ?></td>
                <td class="text-end"><a href="<?= admin_url('contacts/'.$c['id']); ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($can_edit): ?>
<?= form_close(); ?>
<?php endif; ?>

<?php if ($pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
        <?php foreach (crm_pagination_items($page, $pages, 10) as $item):
            if ($item === 'ellipsis'): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php continue; endif;
            $qs = $_GET;
            $qs['page'] = $item;
            ?>
            <li class="page-item <?= (int) $item === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?= admin_url('contacts?'.http_build_query($qs)); ?>"><?= (int) $item; ?></a>
            </li>
        <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>

<p class="ymo-muted small mt-2"><?= (int) $total; ?> total contact<?= (int) $total === 1 ? '' : 's'; ?>.</p>
