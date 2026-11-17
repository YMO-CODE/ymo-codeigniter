<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('campaigns'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All campaigns</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <h2 class="h4"><?= html_escape($camp['name']); ?></h2>
            <p class="ymo-muted small"><?= strtoupper($camp['channel']); ?> · <?= html_escape($camp['status']); ?></p>
            <?php if ($camp['channel'] === 'whatsapp'): ?>
                <?php if (!empty($wa_tpl)): ?>
                    <p class="small mb-1">Template: <strong><?= html_escape($wa_tpl['name']); ?></strong> (<?= html_escape($wa_tpl['language']); ?>)
                        <?php if ($wa_tpl['status'] !== 'APPROVED'): ?><span class="badge bg-warning text-dark"><?= html_escape($wa_tpl['status']); ?></span><?php endif; ?></p>
                    <div class="border rounded p-3 bg-light small" style="white-space:pre-wrap"><?= html_escape($wa_tpl['body_text']); ?></div>
                    <?php if (!empty($wa_cfg['params'])): ?>
                        <ul class="small mt-2 mb-0">
                            <?php foreach ($wa_cfg['params'] as $i => $p): ?>
                                <li><code>{{<?= $i + 1; ?>}}</code> = <?= html_escape($p); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($wa_cfg['header_link'])): ?>
                        <p class="small mt-1 mb-0">Header media: <a href="<?= html_escape($wa_cfg['header_link']); ?>" target="_blank" rel="noopener"><?= html_escape($wa_cfg['header_link']); ?></a></p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning small mb-0">No WhatsApp template selected - edit the campaign to pick one.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="border rounded p-3 bg-light small"><?= nl2br(html_escape($camp['body'])); ?></div>
            <?php endif; ?>
            <?php if (!empty($can_edit) && in_array($camp['status'], array('draft','scheduled'), TRUE)): ?>
                <?= form_open(admin_url('campaigns/'.$camp['id'].'/test'), array('class' => 'mt-3 p-3 border rounded')); ?>
                    <div class="small fw-semibold mb-2"><span class="mi mi-sm mi-leading">science</span>Send a test to yourself first</div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?php if ($camp['channel'] !== 'email'): ?>
                            <input class="form-control form-control-sm" style="max-width:11rem" name="test_mobile" inputmode="numeric" maxlength="10" placeholder="Your mobile" value="<?= html_escape($test_mobile ?? ''); ?>" required>
                        <?php endif; ?>
                        <?php if (in_array($camp['channel'], array('email', 'both'), TRUE)): ?>
                            <input class="form-control form-control-sm" style="max-width:16rem" type="email" name="test_email" placeholder="Your email" value="<?= html_escape($test_email ?? ''); ?>" required>
                        <?php endif; ?>
                        <button class="btn btn-outline-primary btn-sm">Send test</button>
                    </div>
                    <p class="small ymo-muted mb-0 mt-1">Only you receive it. The campaign stays a draft.</p>
                <?= form_close(); ?>
            <?php endif; ?>
            <?php if (!empty($can_edit)): ?>
                <?= form_open(admin_url('campaigns/'.$camp['id'].'/duplicate'), array('class' => 'mt-2')); ?>
                    <button class="btn btn-link btn-sm px-0"><span class="mi mi-sm mi-leading">content_copy</span>Duplicate as a new draft</button>
                <?= form_close(); ?>
            <?php endif; ?>
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
            <?php if (!empty($delivery)): ?>
                <h6 class="mt-3">WhatsApp receipts</h6>
                <ul class="list-unstyled small mb-0">
                    <li>Delivered: <strong><?= (int) $delivery['delivered']; ?></strong></li>
                    <li>Read: <strong><?= (int) $delivery['read']; ?></strong></li>
                    <li>Failed after send: <strong><?= (int) $delivery['failed']; ?></strong></li>
                </ul>
                <p class="small ymo-muted mt-2 mb-0">Receipts arrive via the WhatsApp webhook (statuses).</p>
            <?php endif; ?>
            <?php if ($camp['status'] === 'sending'): ?>
                <p class="small ymo-muted mt-2 mb-0">Still sending - remaining recipients go out on the next cron run.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
