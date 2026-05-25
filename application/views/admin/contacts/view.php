<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('contacts'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All contacts</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <h2 class="h4"><?= html_escape($contact['name']); ?></h2>
            <p class="ymo-muted small"><?= html_escape($contact['mobile']); ?> · <?= html_escape($contact['email']); ?></p>
            <?php if ($contact['company']): ?><p><?= html_escape($contact['company']); ?></p><?php endif; ?>
            <?php if ($tags): ?>
                <p><?php foreach ($tags as $t): ?><span class="badge bg-light text-dark me-1"><?= html_escape($t['name']); ?></span><?php endforeach; ?></p>
            <?php endif; ?>
            <?php if ($contact['notes']): ?><p class="small"><?= nl2br(html_escape($contact['notes'])); ?></p><?php endif; ?>
            <?php if ($can_edit): ?>
                <a href="<?= admin_url('contacts/'.$contact['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="<?= admin_url('tasks/new?contact_id='.$contact['id']); ?>" class="btn btn-sm btn-outline-secondary">Schedule follow-up</a>
            <?php endif; ?>
        </div>
        <?php if ($bookings): ?>
        <div class="md-card-elevated mt-3">
            <h6>Service history (bookings)</h6>
            <table class="ymo-table"><thead><tr><th>Ref</th><th>Package</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td class="small font-monospace"><?= html_escape($b['reference']); ?></td>
                    <td class="small"><?= html_escape($b['package_name']); ?></td>
                    <td class="small"><?= html_escape($b['status']); ?></td>
                    <td class="small"><?= html_escape(date('d M Y', strtotime($b['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="ymo-card">
            <h6>Follow-ups</h6>
            <?php if (empty($tasks)): ?><p class="ymo-muted small mb-0">None scheduled.</p><?php endif; ?>
            <?php foreach ($tasks as $t): ?>
                <div class="small border-bottom py-2"><?= html_escape($t['title']); ?><br><span class="ymo-muted"><?= date('d M, h:i A', strtotime($t['due_at'])); ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
