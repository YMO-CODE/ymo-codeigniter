<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2 flex-grow-1" method="get" action="<?= admin_url('team'); ?>" style="max-width:28rem;">
        <input class="form-control" name="q" placeholder="Search name or email…" value="<?= html_escape((string) $q); ?>">
        <button class="btn btn-primary"><span class="mi mi-sm mi-leading">search</span>Search</button>
    </form>
    <?php if (!empty($can_manage)): ?>
        <a href="<?= admin_url('team/new'); ?>" class="btn btn-primary">
            <span class="mi mi-sm mi-leading">person_add</span>Add member
        </a>
    <?php endif; ?>
</div>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last login</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center py-4 ymo-muted">No team members found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $u): ?>
            <tr>
                <td><?= html_escape($u['name']); ?></td>
                <td class="small"><?= html_escape($u['email']); ?></td>
                <td class="small"><?= html_escape($u['crm_role_label'] ?? '—'); ?></td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span class="badge bg-success-subtle text-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="small ymo-muted">
                    <?= !empty($u['last_login_at']) ? html_escape(date('d M Y H:i', strtotime($u['last_login_at']))) : 'Never'; ?>
                </td>
                <td class="text-end">
                    <?php if (!empty($can_manage)): ?>
                        <a href="<?= admin_url('team/'.$u['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?= admin_url('team?page='.$i.'&q='.urlencode((string) $q)); ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
<p class="ymo-muted small mt-2"><?= (int) $total; ?> team member<?= $total === 1 ? '' : 's'; ?>.</p>
