<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><span class="mi mi-leading">event_note</span>My bookings</h1>
        <a href="<?= site_url('packages'); ?>" class="btn btn-primary d-none d-md-inline-flex">
            <span class="mi mi-leading">add</span>New booking
        </a>
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

    <?php if (empty($rows)): ?>
        <div class="md-card-elevated text-center py-5">
            <span class="mi mi-xl" style="color:var(--ymo-grey-500);">inbox</span>
            <h5 class="mb-2 mt-2">No bookings yet.</h5>
            <p class="ymo-muted">Pick a service package to get started.</p>
            <a href="<?= site_url('packages'); ?>" class="btn btn-primary">
                <span class="mi mi-leading">build</span>Browse packages
            </a>
        </div>
    <?php else: ?>
        <div class="md-card-elevated p-0">
            <table class="ymo-table mb-0">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Service</th>
                        <th>Vehicle</th>
                        <th>When</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $b): ?>
                    <tr>
                        <td class="font-monospace small"><?= html_escape($b['reference']); ?></td>
                        <td><?= html_escape($b['package_name']); ?></td>
                        <td class="small">
                            <?= html_escape($b['vehicle_make']); ?> <?= html_escape($b['vehicle_variant']); ?><br>
                            <span class="ymo-muted font-monospace"><?= html_escape($b['vehicle_number']); ?></span>
                        </td>
                        <td class="small">
                            <?= html_escape(date('d M Y', strtotime($b['created_at']))); ?>
                            <?php if (!empty($b['preferred_date'])): ?>
                                <br><span class="ymo-muted">Preferred <?= html_escape(date('d M', strtotime($b['preferred_date']))); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-status s-<?= html_escape($b['status']); ?>">
                                <span class="mi"><?= isset($status_icons[$b['status']]) ? $status_icons[$b['status']] : 'help'; ?></span>
                                <?= html_escape(str_replace('_', ' ', $b['status'])); ?>
                            </span>
                        </td>
                        <td class="text-end small">
                            <a href="<?= site_url('account/bookings/'.$b['id']); ?>">View</a>
                            <?php if (in_array($b['status'], array('completed', 'cancelled'))): ?>
                                <span class="text-muted">·</span>
                                <a href="<?= site_url('booking/rebook/'.$b['id']); ?>"><span class="mi mi-sm mi-leading">refresh</span>Re-book</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?= site_url('account/bookings?page='.$i); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<a href="<?= site_url('packages'); ?>" class="md-fab" aria-label="Book a new service">
    <span class="mi mi-fill">add</span>
    <span class="d-none d-sm-inline">Book now</span>
</a>
