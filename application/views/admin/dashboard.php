<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="md-card-elevated">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="ymo-muted text-uppercase small mb-1">Today</p>
                    <h2 class="display-6 mb-0"><?= (int) $summary['today']; ?></h2>
                    <small class="ymo-muted">new bookings</small>
                </div>
                <span class="mi mi-lg" style="color:var(--ymo-primary);">today</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="md-card-elevated">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="ymo-muted text-uppercase small mb-1">Pending</p>
                    <h2 class="display-6 mb-0 text-warning"><?= (int) $summary['pending']; ?></h2>
                    <small class="ymo-muted">awaiting confirmation</small>
                </div>
                <span class="mi mi-lg" style="color:#b58701;">schedule</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="md-card-elevated">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="ymo-muted text-uppercase small mb-1">In progress</p>
                    <h2 class="display-6 mb-0 text-primary"><?= (int) $summary['in_progress']; ?></h2>
                    <small class="ymo-muted">currently being serviced</small>
                </div>
                <span class="mi mi-lg" style="color:var(--ymo-primary);">autorenew</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="md-card-elevated">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="ymo-muted text-uppercase small mb-1">Last 30 days</p>
                    <h2 class="display-6 mb-0 text-success"><?= (int) $summary['completed_30d']; ?></h2>
                    <small class="ymo-muted">completed services</small>
                </div>
                <span class="mi mi-lg" style="color:#16a34a;">check_circle</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="md-card-elevated p-0">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <strong class="small text-uppercase ymo-muted"><span class="mi mi-sm mi-leading">history</span>Recent bookings</strong>
                <a href="<?= admin_url('bookings'); ?>" class="small">All bookings <span class="mi mi-sm mi-trailing">arrow_forward</span></a>
            </div>
            <?php
            $status_icons = array(
                'pending'     => 'schedule',
                'confirmed'   => 'event_available',
                'in_progress' => 'autorenew',
                'completed'   => 'check_circle',
                'cancelled'   => 'cancel',
            );
            ?>
            <table class="ymo-table mb-0">
                <thead><tr><th>Ref</th><th>Customer</th><th>Service</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($summary['recent_bookings'] as $b): ?>
                    <tr>
                        <td class="font-monospace small"><?= html_escape($b['reference']); ?></td>
                        <td class="small"><?= html_escape($b['user_name']); ?><br><span class="ymo-muted"><?= html_escape($b['user_mobile']); ?></span></td>
                        <td class="small"><?= html_escape($b['package_name']); ?></td>
                        <td>
                            <span class="badge-status s-<?= html_escape($b['status']); ?>">
                                <span class="mi"><?= isset($status_icons[$b['status']]) ? $status_icons[$b['status']] : 'help'; ?></span>
                                <?= html_escape(str_replace('_',' ',$b['status'])); ?>
                            </span>
                        </td>
                        <td><a href="<?= admin_url('bookings/'.$b['id']); ?>" class="small">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="md-card-elevated">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <p class="ymo-muted text-uppercase small mb-0">Reminders due this week</p>
                <span class="mi mi-lg" style="color:var(--ymo-primary);">notifications_active</span>
            </div>
            <h2 class="display-6 mb-2"><?= (int) $summary['reminders_week']; ?></h2>
            <small class="ymo-muted d-block mb-3">Service-due nudges. Cron sends them automatically.</small>
            <a href="<?= admin_url('settings'); ?>" class="btn btn-outline-primary btn-sm">
                <span class="mi mi-sm mi-leading">tune</span>Reminder settings
            </a>
        </div>
        <?php $this->load->view('admin/dashboard_crm_panel', array('summary' => $summary)); ?>
    </div>
</div>
