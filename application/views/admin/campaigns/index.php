<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('admin/partials/messages_tabs', array('active' => 'campaigns')); ?>
<?php if ($can_edit): ?>
    <a href="<?= admin_url('campaigns/new'); ?>" class="btn btn-primary btn-sm mb-3"><span class="mi mi-sm mi-leading">add</span>New campaign</a>
<?php endif; ?>

<?= $can_edit ? form_open(admin_url('campaigns/delete'), array('id' => 'camp-bulk-form')) : ''; ?>
<?php if ($can_edit): ?>
<div class="ymo-card mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="camp-bulk-bar" hidden>
    <strong class="small"><span id="camp-bulk-count">0</span> selected</strong>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-link" id="camp-bulk-clear">Clear</button>
        <button type="submit" class="btn btn-sm btn-danger"><span class="mi mi-sm mi-leading">delete</span>Delete selected</button>
    </div>
</div>
<?php endif; ?>
<div class="ymo-card p-0">
    <table class="ymo-table mb-0" id="camp-table">
        <thead><tr>
            <?php if ($can_edit): ?><th style="width:2.5rem"><input type="checkbox" class="form-check-input" id="camp-select-all" aria-label="Select all campaigns"></th><?php endif; ?>
            <th>Name</th><th>Channel</th><th>Status</th><th>Scheduled</th><th>Created by</th><th></th>
        </tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center py-4 ymo-muted">No campaigns yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $c): $sending = $c['status'] === 'sending'; ?>
            <tr class="camp-row" data-href="<?= admin_url('campaigns/'.$c['id']); ?>" style="cursor:pointer">
                <?php if ($can_edit): ?>
                <td class="camp-check-cell"><input type="checkbox" class="form-check-input camp-row-check" name="ids[]" value="<?= (int) $c['id']; ?>"
                           data-name="<?= html_escape($c['name']); ?>" data-status="<?= html_escape($c['status']); ?>"
                           aria-label="Select <?= html_escape($c['name']); ?>"
                           <?= $sending ? 'disabled title="Can\'t delete while sending"' : ''; ?>></td>
                <?php endif; ?>
                <td><a href="<?= admin_url('campaigns/'.$c['id']); ?>"><?= html_escape($c['name']); ?></a></td>
                <td class="small text-uppercase"><?= html_escape($c['channel']); ?></td>
                <td class="small"><?= html_escape($c['status']); ?></td>
                <td class="small"><?= $c['scheduled_at'] ? html_escape(date('d M Y H:i', strtotime($c['scheduled_at']))) : '-'; ?></td>
                <td class="small"><?= html_escape($c['creator_name'] ?? '-'); ?></td>
                <td class="text-end"><a href="<?= admin_url('campaigns/'.$c['id']); ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $can_edit ? form_close() : ''; ?>

<?php if (!empty($pages) && $pages > 1): ?>
    <nav class="mt-3"><ul class="pagination flex-wrap">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : ''; ?>"><a class="page-link" href="<?= admin_url('campaigns?page='.$i); ?>"><?= $i; ?></a></li>
    <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<?php if ($can_edit): ?>
<script>
(function () {
    var form = document.getElementById('camp-bulk-form');
    var bar = document.getElementById('camp-bulk-bar');
    var countEl = document.getElementById('camp-bulk-count');
    var all = document.getElementById('camp-select-all');
    if (!form || !bar) { return; }
    function checks() { return Array.prototype.slice.call(form.querySelectorAll('.camp-row-check:not(:disabled)')); }
    function sync() {
        var picked = checks().filter(function (c) { return c.checked; });
        countEl.textContent = String(picked.length);
        bar.hidden = picked.length === 0;
        if (all) {
            var list = checks();
            all.checked = list.length > 0 && picked.length === list.length;
            all.indeterminate = picked.length > 0 && picked.length < list.length;
        }
    }
    form.addEventListener('change', function (e) {
        if (e.target === all) { checks().forEach(function (c) { c.checked = all.checked; }); }
        sync();
    });
    document.getElementById('camp-bulk-clear').addEventListener('click', function () {
        checks().forEach(function (c) { c.checked = false; });
        sync();
    });
    form.addEventListener('submit', function (e) {
        var picked = checks().filter(function (c) { return c.checked; });
        if (!picked.length) { e.preventDefault(); return; }
        var sent = picked.filter(function (c) { return c.dataset.status !== 'draft' && c.dataset.status !== 'scheduled'; }).length;
        var msg = 'Delete ' + picked.length + ' campaign' + (picked.length === 1 ? '' : 's') + '?\n\n'
            + picked.slice(0, 8).map(function (c) { return '• ' + c.dataset.name; }).join('\n')
            + (picked.length > 8 ? '\n…' : '')
            + (sent ? '\n\n' + sent + ' already sent: their delivery history will be removed too.' : '')
            + '\n\nThis cannot be undone.';
        if (!window.confirm(msg)) { e.preventDefault(); }
    });
    sync();
})();
</script>
<?php endif; ?>
<script>
(function () {
    var table = document.getElementById('camp-table');
    if (!table) { return; }
    table.addEventListener('click', function (e) {
        var row = e.target.closest('tr.camp-row');
        if (!row || e.target.closest('a, button, input, label, .camp-check-cell')) { return; }
        if (window.getSelection && String(window.getSelection()).length) { return; }
        if (e.ctrlKey || e.metaKey) { window.open(row.dataset.href, '_blank'); return; }
        window.location.href = row.dataset.href;
    });
})();
</script>
