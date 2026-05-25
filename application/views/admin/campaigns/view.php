<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('campaigns'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All campaigns</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <h2 class="h4"><?= html_escape($camp['name']); ?></h2>
            <p class="ymo-muted small"><?= strtoupper($camp['channel']); ?> · <?= html_escape($camp['status']); ?></p>
            <div class="border rounded p-3 bg-light small"><?= nl2br(html_escape($camp['body'])); ?></div>
            <?php if ($can_send && in_array($camp['status'], array('draft','scheduled'), TRUE)): ?>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <?= form_open(admin_url('campaigns/'.$camp['id'].'/send'), array('class'=>'d-inline')); ?>
                        <button class="btn btn-primary btn-sm" onclick="return confirm('Send now to all matching recipients?');">Send now</button>
                    <?= form_close(); ?>
                    <?= form_open(admin_url('campaigns/'.$camp['id'].'/schedule'), array('class'=>'d-inline-flex gap-2 align-items-center')); ?>
                        <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" required>
                        <button class="btn btn-outline-secondary btn-sm">Schedule</button>
                    <?= form_close(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ymo-card">
            <h6>Delivery stats</h6>
            <ul class="list-unstyled small mb-0">
                <li>Sent: <strong><?= (int) $stats['sent']; ?></strong></li>
                <li>Pending: <strong><?= (int) $stats['pending']; ?></strong></li>
                <li>Failed: <strong><?= (int) $stats['failed']; ?></strong></li>
                <li>Skipped: <strong><?= (int) $stats['skipped']; ?></strong></li>
            </ul>
        </div>
    </div>
</div>
