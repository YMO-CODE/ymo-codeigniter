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
<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Name</th><th>Mobile</th><th>Email</th><th>Company</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr>
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
