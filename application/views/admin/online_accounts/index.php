<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form class="ymo-card mb-3" method="get" action="<?= admin_url('online-accounts'); ?>">
    <div class="d-flex gap-2 align-items-stretch">
        <div class="form-floating flex-grow-1">
            <input class="form-control" id="cu_q" name="q" placeholder=" " value="<?= html_escape((string) $q); ?>">
            <label for="cu_q">Search by name, mobile, email or city</label>
        </div>
        <button class="btn btn-primary"><span class="mi mi-sm mi-leading">search</span>Search</button>
    </div>
</form>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Name</th><th>Mobile</th><th>Email</th><th>City</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center py-4 ymo-muted">No online accounts found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $u): ?>
            <tr>
                <td><?= html_escape($u['name']); ?></td>
                <td class="small"><?= html_escape($u['mobile']); ?></td>
                <td class="small"><?= html_escape($u['email']); ?></td>
                <td class="small"><?= html_escape($u['city']); ?></td>
                <td class="small"><?= html_escape(date('d M Y', strtotime($u['created_at']))); ?></td>
                <td class="text-end"><a class="small" href="<?= admin_url('online-accounts/'.$u['id']); ?>">Open<span class="mi mi-sm mi-trailing">arrow_forward</span></a></td>
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
                <a class="page-link" href="<?= admin_url('online-accounts?page='.$i.'&q='.urlencode((string) $q)); ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
<p class="ymo-muted small mt-2"><?= (int) $total; ?> registered online account<?= $total === 1 ? '' : 's'; ?>.</p>
