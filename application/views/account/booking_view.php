<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$status_icons = array(
    'pending'     => 'schedule',
    'confirmed'   => 'event_available',
    'in_progress' => 'autorenew',
    'completed'   => 'check_circle',
    'cancelled'   => 'cancel',
);
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="<?= site_url('account/bookings'); ?>" class="small">
                <span class="mi mi-sm mi-leading">arrow_back</span>All bookings
            </a>

            <div class="md-card-elevated mt-3">
                <div class="d-flex justify-content-between flex-wrap align-items-start">
                    <div>
                        <h1 class="h4 mb-1"><span class="mi mi-leading">receipt_long</span>Booking #<?= html_escape($booking['reference']); ?></h1>
                        <p class="ymo-muted small mb-0">Created <?= html_escape(date('d M Y, h:i A', strtotime($booking['created_at']))); ?></p>
                    </div>
                    <span class="badge-status s-<?= html_escape($booking['status']); ?>">
                        <span class="mi"><?= isset($status_icons[$booking['status']]) ? $status_icons[$booking['status']] : 'help'; ?></span>
                        <?= html_escape(str_replace('_', ' ', $booking['status'])); ?>
                    </span>
                </div>

                <hr class="ymo-divider">

                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">build</span>Service</h6>
                        <p class="mb-1"><strong><?= html_escape($booking['package_name']); ?></strong></p>
                        <p class="ymo-muted small mb-2">&#8377; <?= number_format((float) $booking['package_price']); ?></p>
                        <?php if (!empty($features)): ?>
                            <ul class="small ymo-muted ps-3 mb-0">
                                <?php foreach (array_slice($features, 0, 8) as $f): ?>
                                    <li><?= html_escape($f); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">directions_car</span>Vehicle</h6>
                        <p class="mb-1"><?= html_escape($booking['vehicle_make']); ?> <?= html_escape($booking['vehicle_variant']); ?></p>
                        <p class="ymo-muted small font-monospace"><?= html_escape($booking['vehicle_number']); ?></p>

                        <?php if (!empty($booking['preferred_date'])): ?>
                            <h6 class="text-muted small text-uppercase mt-3"><span class="mi mi-sm mi-leading">event</span>Preferred date</h6>
                            <p class="mb-0"><?= html_escape(date('d M Y', strtotime($booking['preferred_date']))); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($booking['remarks'])): ?>
                        <div class="col-12">
                            <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">edit_note</span>Remarks</h6>
                            <p class="mb-0"><?= nl2br(html_escape($booking['remarks'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="ymo-divider">

                <?php if (!empty($invoices)): ?>
                    <h6 class="text-muted small text-uppercase mb-3"><span class="mi mi-sm mi-leading">receipt</span>Service invoices</h6>
                    <table class="table table-sm mb-4">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Total</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td class="small font-monospace"><?= html_escape($inv['invoice_number']); ?></td>
                                <td class="small"><?= html_escape(date('d M Y', strtotime($inv['created_at']))); ?></td>
                                <td class="small">&#8377; <?= number_format((float) $inv['grand_total'], 2); ?></td>
                                <td class="text-end">
                                    <?php if (!empty($inv['pdf_path'])): ?>
                                        <a href="<?= site_url('account/bookings/'.$booking['id'].'/invoice/'.$inv['id'].'/pdf'); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            Download PDF
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <hr class="ymo-divider">
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <a href="<?= site_url('booking/rebook/'.$booking['id']); ?>" class="btn btn-primary">
                        <span class="mi mi-leading">refresh</span>Re-book this service
                    </a>
                    <a href="<?= site_url('account/bookings'); ?>" class="btn btn-link">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
