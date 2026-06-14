<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('online-accounts'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>All online accounts
</a>

<?php
$status_icons = array(
    'pending'     => 'schedule',
    'confirmed'   => 'event_available',
    'in_progress' => 'autorenew',
    'completed'   => 'check_circle',
    'cancelled'   => 'cancel',
);
?>

<div class="row g-3 mt-1">
    <div class="col-lg-4">
        <div class="md-card-elevated">
            <div class="d-flex align-items-center mb-2">
                <span class="mi mi-xl mi-leading" style="color:var(--ymo-primary);">account_circle</span>
                <div>
                    <h2 class="h4 mb-0"><?= html_escape($user['name']); ?></h2>
                    <p class="ymo-muted small mb-0">Joined <?= html_escape(date('d M Y', strtotime($user['created_at']))); ?></p>
                </div>
            </div>
            <ul class="list-unstyled small mb-0">
                <li class="mb-1"><span class="mi mi-sm mi-leading">phone</span><?= html_escape($user['mobile']); ?>
                    <?php if ($user['mobile_verified_at']): ?><span class="badge bg-success-subtle text-success ms-1"><span class="mi mi-sm">verified</span></span><?php endif; ?></li>
                <li class="mb-1"><span class="mi mi-sm mi-leading">mail</span><?= html_escape($user['email']); ?>
                    <?php if ($user['email_verified_at']): ?><span class="badge bg-success-subtle text-success ms-1"><span class="mi mi-sm">verified</span></span><?php endif; ?></li>
                <li class="mb-1"><span class="mi mi-sm mi-leading">place</span><?= html_escape($user['area']); ?>, <?= html_escape($user['city']); ?></li>
            </ul>
        </div>

        <div class="md-card-elevated mt-3">
            <h6 class="mb-2"><span class="mi mi-sm mi-leading">directions_car</span>Vehicles</h6>
            <?php if (empty($vehicles)): ?>
                <p class="ymo-muted small mb-0">None saved.</p>
            <?php else: foreach ($vehicles as $v): ?>
                <div class="small mb-2 pb-2 border-bottom">
                    <strong><?= html_escape($v['make_name']); ?> <?= html_escape($v['variant']); ?></strong><br>
                    <span class="font-monospace ymo-muted"><?= html_escape($v['vehicle_number']); ?></span>
                    <?php if ($v['deleted_at']): ?> <span class="badge bg-secondary">removed</span><?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="col-lg-8">
        <h2 class="h5 mb-3"><span class="mi mi-leading">history</span>Booking history</h2>
        <div class="md-card-elevated p-0">
            <table class="ymo-table mb-0">
                <thead><tr><th>Ref</th><th>Service</th><th>Vehicle</th><th>Created</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="6" class="text-center py-4 ymo-muted">No bookings yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td class="font-monospace small"><?= html_escape($b['reference']); ?></td>
                        <td class="small"><?= html_escape($b['package_name']); ?></td>
                        <td class="small font-monospace"><?= html_escape($b['vehicle_number']); ?></td>
                        <td class="small"><?= html_escape(date('d M Y', strtotime($b['created_at']))); ?></td>
                        <td>
                            <span class="badge-status s-<?= html_escape($b['status']); ?>">
                                <span class="mi"><?= isset($status_icons[$b['status']]) ? $status_icons[$b['status']] : 'help'; ?></span>
                                <?= html_escape(str_replace('_',' ',$b['status'])); ?>
                            </span>
                        </td>
                        <td class="text-end"><a class="small" href="<?= admin_url('bookings/'.$b['id']); ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
