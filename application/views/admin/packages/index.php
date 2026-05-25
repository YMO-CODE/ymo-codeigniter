<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-end mb-3">
    <?php if (!empty($can_edit)): ?>
    <a href="<?= admin_url('packages/new'); ?>" class="btn btn-primary">
        <span class="mi mi-leading">add</span>New package
    </a>
    <?php endif; ?>
</div>

<div class="md-card-elevated p-0">
    <table class="ymo-table mb-0">
        <thead><tr><th>Order</th><th>Name</th><th>Price</th><th>Active</th><th class="text-end"></th></tr></thead>
        <tbody>
        <?php foreach ($packages as $p): ?>
            <tr>
                <td class="small"><?= (int) $p['sort_order']; ?></td>
                <td>
                    <strong><?= html_escape($p['name']); ?></strong><br>
                    <span class="ymo-muted small"><?= html_escape($p['summary']); ?></span>
                </td>
                <td>&#8377; <?= number_format((float) $p['price']); ?></td>
                <td>
                    <?php if ($p['is_active']): ?>
                        <span class="badge bg-success-subtle text-success"><span class="mi mi-sm mi-leading">visibility</span>active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><span class="mi mi-sm mi-leading">visibility_off</span>hidden</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if (!empty($can_edit)): ?>
                    <a href="<?= admin_url('packages/'.$p['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">
                        <span class="mi mi-sm mi-leading">edit</span>Edit
                    </a>
                    <?= form_open(admin_url('packages/'.$p['id'].'/delete'), array('class' => 'd-inline')); ?>
                        <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this package? Existing bookings keep their snapshot.">
                            <span class="mi mi-sm mi-leading">delete</span>Delete
                        </button>
                    <?= form_close(); ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
