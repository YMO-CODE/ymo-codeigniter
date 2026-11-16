<?php defined('BASEPATH') OR exit('No direct script access allowed');
$tab = (string) ($filters['stage_tab'] ?? 'open');
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
?>
<?php $this->load->view('admin/leads/_toolbar', array('mode' => 'list', 'sources' => $sources, 'filters' => $filters, 'can_edit' => $can_edit)); ?>

<div class="ymo-tabs mb-3">
    <?php foreach ($tabs as $slug => $t): ?>
        <a href="<?= $url_with(array('stage' => $slug === 'open' ? NULL : $slug)); ?>" class="<?= $tab === $slug ? 'active' : ''; ?>">
            <?= html_escape($t[0]); ?><?php if ($t[1] !== NULL): ?> <span class="count"><?= (int) $t[1]; ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<form class="ymo-card mb-3" method="get" action="<?= admin_url('leads'); ?>">
    <?php foreach (array('source', 'stage') as $keep): if (!empty($_GET[$keep])): ?>
        <input type="hidden" name="<?= $keep; ?>" value="<?= html_escape($_GET[$keep]); ?>">
    <?php endif; endforeach; ?>
    <div class="row g-2 align-items-center">
        <div class="col-md-4">
            <input class="form-control form-control-sm" type="search" name="q" placeholder="Search name, mobile, car, area…" value="<?= html_escape((string) $filters['q']); ?>">
        </div>
        <div class="col-md-3">
            <select name="assigned_to" class="form-select form-select-sm">
                <option value="">Any owner</option>
                <option value="unassigned" <?= $filters['assigned_to'] === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?= (int) $a['id']; ?>" <?= (string) $filters['assigned_to'] === (string) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="due" class="form-select form-select-sm">
                <option value="">Any follow-up</option>
                <option value="overdue" <?= ($filters['due'] ?? '') === 'overdue' ? 'selected' : ''; ?>>Follow-up overdue</option>
                <option value="today" <?= ($filters['due'] ?? '') === 'today' ? 'selected' : ''; ?>>Follow-up today</option>
                <option value="none" <?= ($filters['due'] ?? '') === 'none' ? 'selected' : ''; ?>>No follow-up set</option>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary btn-sm">Filter</button></div>
    </div>
    <label class="form-check form-check-inline small mt-2 mb-0">
        <input class="form-check-input" type="checkbox" name="mine" value="1" <?= !empty($filters['mine']) ? 'checked' : ''; ?> onchange="this.form.submit()"> My leads only
    </label>
</form>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Lead</th><th>Source</th><th>Stage</th><th>Next follow-up</th><th>Owner</th><th class="text-end"></th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center py-4 ymo-muted">No leads here.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $l):
            $due = !empty($l['next_follow_up_at']) ? strtotime($l['next_follow_up_at']) : NULL;
            $tel = crm_tel_link($l['mobile']);
        ?>
            <tr>
                <td class="small">
                    <a href="<?= admin_url('leads/'.$l['id']); ?>" class="fw-semibold"><?= html_escape($l['name']); ?></a>
                    <?php if ((int) $l['priority'] > 0): ?><span class="badge badge-priority-high ms-1">!</span><?php endif; ?>
                    <br><span class="ymo-muted"><?= $l['mobile'] ? html_escape($l['mobile']) : '-'; ?><?= !empty($l['car_type']) ? ' · '.html_escape($l['car_type']) : ''; ?></span>
                </td>
                <td class="small"><?= html_escape($l['source_label']); ?><br><span class="ymo-muted"><?= html_escape(date('d M', strtotime($l['created_at']))); ?></span></td>
                <td><span class="badge <?= crm_lead_stage_badge($l['stage']); ?>"><?= html_escape(crm_lead_stage_label($l['stage'])); ?></span></td>
                <td class="small">
                    <?php if ($due): ?>
                        <span class="<?= $due < strtotime('today') ? 'text-danger fw-semibold' : ($due <= strtotime('today 23:59:59') ? 'fw-semibold' : ''); ?>">
                            <?= html_escape(date('d M, h:i A', $due)); ?>
                        </span>
                    <?php elseif (in_array($l['stage'], crm_lead_open_stages(), TRUE)): ?>
                        <span class="ymo-muted">-</span>
                    <?php endif; ?>
                </td>
                <td class="small"><?= $l['assignee_name'] ? html_escape($l['assignee_name']) : '<span class="ymo-muted">-</span>'; ?></td>
                <td class="text-end text-nowrap">
                    <?php if ($tel): ?><a href="<?= $tel; ?>" class="btn btn-sm btn-outline-secondary" title="Call"><span class="mi mi-sm">call</span></a><?php endif; ?>
                    <a href="<?= admin_url('leads/'.$l['id']); ?>" class="btn btn-sm btn-outline-primary">Open</a>
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
<p class="ymo-muted small mt-2"><?= (int) $total; ?> lead<?= $total === 1 ? '' : 's'; ?>.</p>
