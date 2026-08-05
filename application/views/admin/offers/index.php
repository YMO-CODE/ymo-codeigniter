<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-end mb-3">
    <?php if (!empty($can_edit)): ?>
    <a href="<?= admin_url('offers/new'); ?>" class="btn btn-primary">
        <span class="mi mi-leading">add</span>New offer
    </a>
    <?php endif; ?>
</div>

<div class="md-card-elevated p-0">
    <table class="ymo-table mb-0">
        <thead>
            <tr>
                <th>Order</th>
                <th>Title</th>
                <th>Schedule</th>
                <th>Active</th>
                <th class="text-end"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($offers)): ?>
            <tr><td colspan="5" class="text-center ymo-muted py-4">No offers yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($offers as $o): ?>
            <tr>
                <td class="small"><?= (int) $o['sort_order']; ?></td>
                <td>
                    <strong><?= html_escape($o['title']); ?></strong><br>
                    <span class="ymo-muted small"><?php
                        $preview = (string) $o['body'];
                        echo html_escape(strlen($preview) > 80 ? substr($preview, 0, 77).'…' : $preview);
                    ?></span>
                </td>
                <td class="small">
                    <?php if ($o['starts_at']): ?>
                        From <?= html_escape(date('d M Y H:i', strtotime($o['starts_at']))); ?><br>
                    <?php else: ?>
                        <span class="ymo-muted">No start</span><br>
                    <?php endif; ?>
                    <?php if ($o['ends_at']): ?>
                        Until <?= html_escape(date('d M Y H:i', strtotime($o['ends_at']))); ?>
                    <?php else: ?>
                        <span class="ymo-muted">No end</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($o['is_active']): ?>
                        <span class="badge bg-success-subtle text-success"><span class="mi mi-sm mi-leading">visibility</span>active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><span class="mi mi-sm mi-leading">visibility_off</span>hidden</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="<?= html_escape(rtrim(ymo_booking_url(''), '/').'?ymo_offer_preview='.(int) $o['id']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="Open booking site with this offer popup">
                        <span class="mi mi-sm mi-leading">open_in_new</span>Preview
                    </a>
                    <?php if (!empty($can_edit)): ?>
                    <a href="<?= admin_url('offers/'.$o['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">
                        <span class="mi mi-sm mi-leading">edit</span>Edit
                    </a>
                    <?= form_open(admin_url('offers/'.$o['id'].'/delete'), array('class' => 'd-inline')); ?>
                        <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this offer?">
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

<p class="small ymo-muted mt-3">
    Active offers appear as a popup on the booking site and WordPress (via mu-plugin). Lowest sort order wins when multiple are active.
</p>
