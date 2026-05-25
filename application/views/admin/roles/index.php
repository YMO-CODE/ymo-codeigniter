<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-end mb-3">
    <?php if (!empty($can_manage)): ?>
        <a href="<?= admin_url('roles/new'); ?>" class="btn btn-primary">
            <span class="mi mi-sm mi-leading">add</span>New role
        </a>
    <?php endif; ?>
</div>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead>
            <tr>
                <th>Role</th>
                <th>Slug</th>
                <th>Members</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($roles as $r): ?>
            <tr>
                <td><strong><?= html_escape($r['label']); ?></strong></td>
                <td class="small font-monospace ymo-muted"><?= html_escape($r['slug']); ?></td>
                <td class="small"><?= (int) $r['user_count']; ?></td>
                <td class="text-end">
                    <?php if (!empty($can_manage)): ?>
                        <a href="<?= admin_url('roles/'.$r['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <?php if ($r['slug'] !== 'admin' && (int) $r['user_count'] === 0): ?>
                            <?= form_open(admin_url('roles/'.$r['id'].'/delete'), array('class' => 'd-inline')); ?>
                                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this role permanently?">
                                    <span class="mi mi-sm mi-leading">delete</span>Delete
                                </button>
                            <?= form_close(); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="ymo-muted small mt-3">
    Roles control which admin modules each team member can access. Changes apply after the user signs out and back in.
</p>
