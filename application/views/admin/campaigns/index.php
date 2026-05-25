<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($can_edit): ?>
    <a href="<?= admin_url('campaigns/new'); ?>" class="btn btn-primary btn-sm mb-3"><span class="mi mi-sm mi-leading">add</span>New campaign</a>
<?php endif; ?>
<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Name</th><th>Channel</th><th>Status</th><th>Scheduled</th><th>Created by</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr>
                <td><?= html_escape($c['name']); ?></td>
                <td class="small text-uppercase"><?= html_escape($c['channel']); ?></td>
                <td class="small"><?= html_escape($c['status']); ?></td>
                <td class="small"><?= $c['scheduled_at'] ? html_escape(date('d M Y H:i', strtotime($c['scheduled_at']))) : '—'; ?></td>
                <td class="small"><?= html_escape($c['creator_name'] ?? '—'); ?></td>
                <td class="text-end"><a href="<?= admin_url('campaigns/'.$c['id']); ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
