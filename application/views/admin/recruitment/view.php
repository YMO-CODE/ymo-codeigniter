<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('recruitment'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All candidates</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <h2 class="h4"><?= html_escape($candidate['name']); ?></h2>
            <p class="ymo-muted"><?= html_escape($candidate['position']); ?> · <?= html_escape($candidate['stage']); ?></p>
            <p class="small"><?= html_escape($candidate['mobile']); ?> · <?= html_escape($candidate['email']); ?></p>
            <?php if ($candidate['notes']): ?><p class="small"><?= nl2br(html_escape($candidate['notes'])); ?></p><?php endif; ?>
            <?php if ($can_edit): ?><a href="<?= admin_url('recruitment/'.$candidate['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
        </div>
        <div class="md-card-elevated mt-3">
            <h6>Resume / documents</h6>
            <?php if (empty($documents)): ?><p class="ymo-muted small">No files uploaded.</p><?php endif; ?>
            <ul class="list-unstyled small">
            <?php foreach ($documents as $d): ?>
                <li class="mb-1"><a href="<?= base_url($d['file_path']); ?>" target="_blank"><?= html_escape($d['original_name']); ?></a></li>
            <?php endforeach; ?>
            </ul>
            <?php if ($can_edit): ?>
                <?= form_open_multipart(admin_url('recruitment/'.$candidate['id'].'/upload')); ?>
                    <input type="file" name="resume" class="form-control form-control-sm mb-2" accept=".pdf,.doc,.docx" required>
                    <button class="btn btn-sm btn-outline-secondary">Upload resume</button>
                <?= form_close(); ?>
            <?php endif; ?>
        </div>
        <div class="md-card-elevated mt-3">
            <h6>Interviews</h6>
            <?php foreach ($interviews as $i): ?>
                <div class="small border-bottom py-2">
                    <?= html_escape(date('d M Y, h:i A', strtotime($i['scheduled_at']))); ?>
                    · <?= html_escape($i['status']); ?>
                    <?php if ($i['location']): ?> · <?= html_escape($i['location']); ?><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($can_edit): ?>
                <?= form_open(admin_url('recruitment/'.$candidate['id'].'/schedule')); ?>
                <div class="row g-2 mt-2">
                    <div class="col-md-4"><input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><input name="location" class="form-control form-control-sm" placeholder="Location"></div>
                    <div class="col-md-4"><button class="btn btn-sm btn-primary w-100">Schedule</button></div>
                </div>
                <?= form_close(); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
