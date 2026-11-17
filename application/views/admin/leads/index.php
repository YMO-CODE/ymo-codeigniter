<?php defined('BASEPATH') OR exit('No direct script access allowed');
$tab = (string) ($filters['stage_tab'] ?? 'open');
$who = (string) ($filters['who'] ?? 'me');
$qs_base = $_GET;
unset($qs_base['page']);
$url_with = function (array $changes) use ($qs_base) {
    $qs = array_merge($qs_base, $changes);
    foreach ($qs as $k => $v) {
        if ($v === NULL || $v === '') { unset($qs[$k]); }
    }
    return admin_url('leads'.($qs ? '?'.http_build_query($qs) : ''));
};
$open_count = (int) ($counts['new'] ?? 0) + (int) ($counts['contacted'] ?? 0) + (int) ($counts['quote_sent'] ?? 0);
$tabs = array('open' => array('Open', $open_count));
foreach ($stage_labels as $slug => $label) {
    $tabs[$slug] = array($label, (int) ($counts[$slug] ?? 0));
}
$tabs['all'] = array('All', NULL);
$active_filters = count(array_filter(array($filters['source_slug'] ?? '', $filters['assigned_to'] ?? '', $filters['due'] ?? '')));
$today_end = strtotime('today 23:59:59');
?>
<?php $this->load->view('admin/leads/_toolbar', array('mode' => 'list', 'sources' => $sources, 'filters' => $filters, 'can_edit' => $can_edit)); ?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
    <div class="btn-group btn-group-sm" role="group" aria-label="Whose leads">
        <a href="<?= $url_with(array('who' => NULL)); ?>" class="btn <?= $who === 'me' ? 'btn-primary' : 'btn-outline-secondary'; ?>" title="Assigned to you, plus leads nobody owns yet">My leads</a>
        <a href="<?= $url_with(array('who' => 'all')); ?>" class="btn <?= $who === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Everyone</a>
    </div>
    <form method="get" action="<?= admin_url('leads'); ?>" class="flex-grow-1" style="min-width:12rem;max-width:22rem">
        <?php foreach (array('stage', 'who', 'source', 'assigned_to', 'due') as $keep): if (!empty($_GET[$keep])): ?>
            <input type="hidden" name="<?= $keep; ?>" value="<?= html_escape($_GET[$keep]); ?>">
        <?php endif; endforeach; ?>
        <input class="form-control form-control-sm" type="search" name="q" placeholder="Search name, mobile, car, area…" value="<?= html_escape((string) $filters['q']); ?>">
    </form>
    <details class="ymo-filter-menu">
        <summary class="btn btn-sm <?= $active_filters ? 'btn-primary' : 'btn-outline-secondary'; ?>"><span class="mi mi-sm mi-leading">filter_list</span>Filter<?= $active_filters ? ' ('.$active_filters.')' : ''; ?></summary>
        <form method="get" action="<?= admin_url('leads'); ?>" class="ymo-card ymo-filter-pop">
            <?php foreach (array('stage', 'who', 'q') as $keep): if (!empty($_GET[$keep])): ?>
                <input type="hidden" name="<?= $keep; ?>" value="<?= html_escape($_GET[$keep]); ?>">
            <?php endif; endforeach; ?>
            <label class="small ymo-muted mb-1">Source</label>
            <select name="source" class="form-select form-select-sm mb-2">
                <option value="">Any source</option>
                <?php foreach ($sources as $s): ?>
                    <option value="<?= html_escape($s['slug']); ?>" <?= ($filters['source_slug'] ?? '') === $s['slug'] ? 'selected' : ''; ?>><?= html_escape($s['label']); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="small ymo-muted mb-1">Owner</label>
            <select name="assigned_to" class="form-select form-select-sm mb-2">
                <option value="">Any owner</option>
                <option value="unassigned" <?= ($filters['assigned_to'] ?? '') === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?= (int) $a['id']; ?>" <?= (string) ($filters['assigned_to'] ?? '') === (string) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="small ymo-muted mb-1">Next call</label>
            <select name="due" class="form-select form-select-sm mb-3">
                <option value="">Any</option>
                <option value="overdue" <?= ($filters['due'] ?? '') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                <option value="today" <?= ($filters['due'] ?? '') === 'today' ? 'selected' : ''; ?>>Due today</option>
                <option value="none" <?= ($filters['due'] ?? '') === 'none' ? 'selected' : ''; ?>>No next call set</option>
            </select>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1">Apply</button>
                <a class="btn btn-outline-secondary btn-sm" href="<?= $url_with(array('source' => NULL, 'assigned_to' => NULL, 'due' => NULL)); ?>">Clear</a>
            </div>
        </form>
    </details>
</div>

<div class="ymo-tabs mb-3">
    <?php foreach ($tabs as $slug => $t): ?>
        <a href="<?= $url_with(array('stage' => $slug === 'open' ? NULL : $slug)); ?>" class="<?= $tab === $slug ? 'active' : ''; ?>">
            <?= html_escape($t[0]); ?><?php if ($t[1] !== NULL): ?> <span class="count"><?= (int) $t[1]; ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Lead</th><th>Next call</th><th>Stage</th><th>Source</th><th>Owner</th><th class="text-end"></th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center py-4 ymo-muted"><?= $who === 'me' ? 'Nothing to call. Switch to Everyone to see the whole team\'s leads.' : 'No leads here.'; ?></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $l):
            $due = !empty($l['next_follow_up_at']) ? strtotime($l['next_follow_up_at']) : NULL;
            $is_open = in_array($l['stage'], crm_lead_open_stages(), TRUE);
            $tel = crm_tel_link($l['mobile']);
        ?>
            <tr class="ymo-row-link" data-href="<?= admin_url('leads/'.$l['id']); ?>">
                <td class="small">
                    <a href="<?= admin_url('leads/'.$l['id']); ?>" class="fw-semibold"><?= html_escape($l['name']); ?></a>
                    <?php if ((int) $l['priority'] > 0): ?><span class="badge badge-priority-high ms-1">!</span><?php endif; ?>
                    <br><span class="ymo-muted"><?= $l['mobile'] ? html_escape($l['mobile']) : '-'; ?><?= !empty($l['car_type']) ? ' · '.html_escape($l['car_type']) : ''; ?></span>
                </td>
                <td class="small">
                    <?php if ($is_open && $due && $due < time()): ?>
                        <span class="text-danger fw-semibold">Overdue</span><br><span class="ymo-muted"><?= html_escape(date('d M, h:i A', $due)); ?></span>
                    <?php elseif ($is_open && $l['stage'] === 'new' && !$due): ?>
                        <span class="fw-semibold">Call now</span><br><span class="ymo-muted">new <?= html_escape(crm_time_ago($l['created_at'])); ?></span>
                    <?php elseif ($is_open && $due): ?>
                        <span class="<?= $due <= $today_end ? 'fw-semibold' : ''; ?>"><?= $due <= $today_end ? 'Today' : html_escape(date('D d M', $due)); ?></span><br><span class="ymo-muted"><?= html_escape(date('h:i A', $due)); ?></span>
                    <?php elseif ($is_open): ?>
                        <span class="text-warning fw-semibold">No date set</span>
                    <?php else: ?>
                        <span class="ymo-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= crm_lead_stage_badge($l['stage']); ?>"><?= html_escape(crm_lead_stage_label($l['stage'])); ?></span></td>
                <td class="small"><?= html_escape($l['source_label']); ?></td>
                <td class="small"><?= $l['assignee_name'] ? html_escape($l['assignee_name']) : '<span class="ymo-muted">Unassigned</span>'; ?></td>
                <td class="text-end text-nowrap">
                    <?php if ($tel): ?><a href="<?= $tel; ?>" class="btn btn-sm btn-outline-secondary" title="Call"><span class="mi mi-sm">call</span></a><?php endif; ?>
                    <a href="<?= admin_url('leads/'.$l['id'].'#log'); ?>" class="btn btn-sm btn-outline-primary">Log call</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3"><ul class="pagination flex-wrap">
    <?php for ($i = 1; $i <= $pages; $i++): $qs = $_GET; $qs['page'] = $i; ?>
        <li class="page-item <?= $i === $page ? 'active' : ''; ?>"><a class="page-link" href="<?= admin_url('leads?'.http_build_query($qs)); ?>"><?= $i; ?></a></li>
    <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
<p class="ymo-muted small mt-2"><?= (int) $total; ?> lead<?= $total === 1 ? '' : 's'; ?><?= $who === 'me' ? ' (yours and unassigned)' : ''; ?>, in the order to call them.</p>
<script>
(function () {
    document.querySelectorAll('tr.ymo-row-link').forEach(function (row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, input, select, label')) { return; }
            if (window.getSelection && String(window.getSelection()).length) { return; }
            if (e.ctrlKey || e.metaKey) { window.open(row.dataset.href, '_blank'); return; }
            window.location.href = row.dataset.href;
        });
    });
})();
</script>
