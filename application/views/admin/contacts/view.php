<?php defined('BASEPATH') OR exit('No direct script access allowed');
$tel = crm_tel_link($contact['mobile']);
$wa_link = crm_wa_link($contact['mobile']);
$email = (string) $contact['email'];
$show_email = $email !== '' && !ymo_is_placeholder_email($email);
?>
<a href="<?= admin_url('customers'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All customers</a>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mt-2 mb-3">
    <div>
        <h2 class="h4 mb-1"><?= html_escape($contact['name']); ?></h2>
        <div class="small ymo-muted">
            <?= html_escape($contact['mobile']); ?><?= $show_email ? ' · '.html_escape($email) : ''; ?>
            <?php if (!empty($contact['user_id'])): ?>
                · <span class="badge bg-success-subtle text-success-emphasis border">Website account</span>
            <?php endif; ?>
        </div>
        <?php if ($tags): ?>
            <div class="mt-1"><?php foreach ($tags as $t): ?><span class="badge bg-light text-dark border me-1"><?= html_escape($t['name']); ?></span><?php endforeach; ?></div>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($tel): ?><a class="btn btn-sm btn-outline-primary" href="<?= $tel; ?>"><span class="mi mi-sm mi-leading">call</span>Call</a><?php endif; ?>
        <?php if ($wa_link): ?><a class="btn btn-sm btn-outline-success" href="<?= $wa_link; ?>" target="_blank" rel="noopener"><span class="mi mi-sm mi-leading">chat</span>WhatsApp</a><?php endif; ?>
        <?php if (!empty($can_book)): ?>
            <a class="btn btn-sm btn-primary" href="<?= admin_url('bookings/new?contact_id='.(int) $contact['id']); ?>"><span class="mi mi-sm mi-leading">event_available</span>Book service</a>
        <?php endif; ?>
        <?php if ($can_edit): ?>
            <a href="<?= admin_url('customers/'.$contact['id'].'/edit'); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="ymo-card mb-3">
            <h6 class="mb-2">Bookings</h6>
            <?php if (empty($bookings)): ?>
                <p class="ymo-muted small mb-0">No bookings yet.<?php if (empty($contact['user_id'])): ?> Booking a service creates their account automatically.<?php endif; ?></p>
            <?php else: ?>
            <table class="ymo-table mb-0"><thead><tr><th>Ref</th><th>Car</th><th>Package</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td class="small font-monospace"><a href="<?= admin_url('bookings/'.(int) $b['id']); ?>"><?= html_escape($b['reference']); ?></a></td>
                    <td class="small"><?= html_escape(trim(($b['vehicle_make'] ?? '').' '.($b['vehicle_variant'] ?? '')).(!empty($b['vehicle_number']) ? ' · '.$b['vehicle_number'] : '')); ?></td>
                    <td class="small"><?= html_escape($b['package_name'] ?? ''); ?></td>
                    <td class="small"><?= html_escape(ucwords(str_replace('_', ' ', $b['status']))); ?></td>
                    <td class="small"><?= html_escape(date('d M Y', strtotime($b['preferred_date'] ?? $b['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>

        <div class="ymo-card mb-3">
            <h6 class="mb-2">Enquiries</h6>
            <?php if (empty($leads)): ?><p class="ymo-muted small mb-0">No enquiries from this number.</p><?php endif; ?>
            <?php foreach ($leads as $l): ?>
                <div class="ymo-myday-row">
                    <div class="flex-grow-1 small">
                        <a href="<?= admin_url('leads/'.(int) $l['id']); ?>" class="fw-semibold"><?= html_escape($l['source_label'] ?? 'Lead'); ?></a>
                        <span class="badge <?= crm_lead_stage_badge($l['stage']); ?> ms-1"><?= html_escape(crm_lead_stage_label($l['stage'])); ?></span>
                        <span class="ymo-muted"> · <?= html_escape(date('d M Y', strtotime($l['created_at']))); ?></span>
                        <?php if (!empty($l['message'])): ?><div class="ymo-muted"><?= html_escape(mb_strimwidth($l['message'], 0, 140, '…')); ?></div><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ymo-card mb-3">
            <h6 class="mb-2">WhatsApp</h6>
            <?php if (empty($wa)): ?><p class="ymo-muted small mb-0">No WhatsApp messages yet.</p><?php endif; ?>
            <?php foreach ($wa as $m): ?>
                <div class="small py-1 border-bottom">
                    <span class="badge <?= $m['direction'] === 'in' ? 'bg-success-subtle text-success-emphasis' : 'bg-light text-dark'; ?> border"><?= $m['direction'] === 'in' ? 'Customer' : 'Us'; ?></span>
                    <?= html_escape(mb_strimwidth((string) ($m['body'] ?: ($m['template_name'] ? 'Template: '.$m['template_name'] : '['.$m['kind'].']')), 0, 160, '…')); ?>
                    <span class="ymo-muted"> · <?= html_escape(date('d M, h:i A', strtotime($m['created_at']))); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="ymo-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Follow-ups</h6>
                <?php if ($can_edit): ?><a class="small" href="<?= admin_url('tasks/new?contact_id='.(int) $contact['id']); ?>">+ Add</a><?php endif; ?>
            </div>
            <?php if (empty($tasks)): ?><p class="ymo-muted small mb-0">None scheduled.</p><?php endif; ?>
            <?php foreach ($tasks as $t): $due = strtotime($t['due_at']); ?>
                <div class="small border-bottom py-2">
                    <?= html_escape($t['title']); ?><br>
                    <span class="<?= $due < time() ? 'text-danger' : 'ymo-muted'; ?>"><?= date('d M, h:i A', $due); ?></span>
                    <?= !empty($t['assignee_name']) ? '<span class="ymo-muted"> · '.html_escape($t['assignee_name']).'</span>' : ''; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ymo-card mb-3">
            <h6 class="mb-2">Cars</h6>
            <?php if (empty($vehicles)): ?><p class="ymo-muted small mb-0">No cars saved.</p><?php endif; ?>
            <?php foreach ($vehicles as $v): ?>
                <div class="small py-1 border-bottom"><?= html_escape(trim(($v['make_name'] ?? '').' '.($v['variant'] ?? ''))); ?> <span class="ymo-muted font-monospace"><?= html_escape($v['vehicle_number'] ?? ''); ?></span></div>
            <?php endforeach; ?>
        </div>

        <div class="ymo-card mb-3 small">
            <h6 class="mb-2">Details</h6>
            <?php if ($contact['company']): ?><div><strong>Workshop:</strong> <?= html_escape($contact['company']); ?></div><?php endif; ?>
            <?php if (!empty($user['area']) || !empty($user['city'])): ?><div><strong>Area:</strong> <?= html_escape(trim(($user['area'] ?? '').', '.($user['city'] ?? ''), ', ')); ?></div><?php endif; ?>
            <?php if ($contact['notes']): ?><div class="mt-1"><?= nl2br(html_escape($contact['notes'])); ?></div><?php endif; ?>
            <div class="ymo-muted mt-1">Customer since <?= html_escape(date('d M Y', strtotime($contact['created_at']))); ?></div>
        </div>

        <?php if ($can_edit && !empty($link_users) && empty($contact['user_id'])): ?>
        <details class="ymo-card">
            <summary class="small">Link a website account manually</summary>
            <?= form_open(admin_url('customers/'.$contact['id'].'/link-user'), array('class' => 'mt-2')); ?>
                <select name="user_id" class="form-select form-select-sm mb-2">
                    <option value="">Not linked</option>
                    <?php foreach ($link_users as $u): ?>
                        <option value="<?= (int) $u['id']; ?>"><?= html_escape($u['name'].' · '.$u['mobile']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Link</button>
            <?= form_close(); ?>
            <p class="small ymo-muted mt-2 mb-0">Usually automatic: accounts link by mobile number.</p>
        </details>
        <?php endif; ?>
    </div>
</div>
