<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="md-card-outlined">
                <div class="d-flex align-items-center mb-2">
                    <span class="mi mi-xl mi-leading" style="color:var(--ymo-primary);">account_circle</span>
                    <div>
                        <h2 class="h5 mb-0"><?= html_escape($this->user['name']); ?></h2>
                        <p class="ymo-muted small mb-0">+91 <?= html_escape(substr($this->user['mobile'], -10)); ?></p>
                    </div>
                </div>
                <p class="ymo-muted small mb-3"><span class="mi mi-sm mi-leading">mail</span><?= html_escape($this->user['email']); ?></p>
                <a href="<?= site_url('account/profile'); ?>" class="btn btn-sm btn-outline-primary">
                    <span class="mi mi-sm mi-leading">edit</span>Edit profile
                </a>
            </div>
            <div class="md-card-outlined mt-3">
                <h6 class="mb-2">Quick links</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="py-1"><a href="<?= site_url('account/bookings'); ?>"><span class="mi mi-sm mi-leading">event_note</span>My bookings</a></li>
                    <li class="py-1"><a href="<?= site_url('vehicles'); ?>"><span class="mi mi-sm mi-leading">directions_car</span>My vehicles</a></li>
                    <li class="py-1"><a href="<?= site_url('packages'); ?>"><span class="mi mi-sm mi-leading">build</span>Book a service</a></li>
                </ul>
            </div>
            <?php if (!empty($referral_code)): ?>
                <div class="md-card-outlined mt-3">
                    <h6 class="mb-2"><span class="mi mi-sm mi-leading">redeem</span>Refer friends</h6>
                    <p class="small ymo-muted mb-2">Share your code — you earn &#8377;<?= number_format((float) $referrer_credit, 0); ?>, they get &#8377;<?= number_format((float) $referred_credit, 0); ?> after their first completed service.</p>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control font-monospace text-uppercase" id="ymo-ref-code" readonly value="<?= html_escape($referral_code); ?>">
                        <button class="btn btn-outline-primary" type="button" id="ymo-ref-copy">Copy</button>
                    </div>
                    <p class="small ymo-muted mb-0">
                        <?= (int) $referral_stats['completed']; ?> completed
                        <?php if ((int) $referral_stats['pending'] > 0): ?>
                            &middot; <?= (int) $referral_stats['pending']; ?> pending
                        <?php endif; ?>
                    </p>
                </div>
                <script>
                (function () {
                    var btn = document.getElementById('ymo-ref-copy');
                    var input = document.getElementById('ymo-ref-code');
                    if (!btn || !input) { return; }
                    btn.addEventListener('click', function () {
                        input.select();
                        input.setSelectionRange(0, 99999);
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(input.value);
                        } else {
                            document.execCommand('copy');
                        }
                        btn.textContent = 'Copied';
                        setTimeout(function () { btn.textContent = 'Copy'; }, 1500);
                    });
                })();
                </script>
            <?php endif; ?>
        </div>
        <div class="col-lg-8">
            <h2 class="h4 mb-3">Recent bookings</h2>
            <?php if (empty($recent)): ?>
                <div class="md-card-elevated text-center py-5">
                    <span class="mi mi-xl" style="color:var(--ymo-grey-500);">inbox</span>
                    <h5 class="mb-2 mt-2">No bookings yet.</h5>
                    <p class="ymo-muted">Pick a service package to get started.</p>
                    <a href="<?= site_url('packages'); ?>" class="btn btn-primary">
                        <span class="mi mi-leading">build</span>Browse packages
                    </a>
                </div>
            <?php else: ?>
                <?php
                $status_icons = array(
                    'pending'     => 'schedule',
                    'confirmed'   => 'event_available',
                    'in_progress' => 'autorenew',
                    'completed'   => 'check_circle',
                    'cancelled'   => 'cancel',
                );
                ?>
                <div class="ymo-booking-list d-md-none">
                    <?php foreach ($recent as $b): ?>
                        <article class="ymo-booking-card md-card-elevated">
                            <div class="ymo-booking-card-head">
                                <span class="font-monospace small text-muted"><?= html_escape($b['reference']); ?></span>
                                <span class="badge-status s-<?= html_escape($b['status']); ?>">
                                    <span class="mi"><?= isset($status_icons[$b['status']]) ? $status_icons[$b['status']] : 'help'; ?></span>
                                    <?= html_escape(str_replace('_', ' ', $b['status'])); ?>
                                </span>
                            </div>
                            <h2 class="ymo-booking-card-title h6 mb-1"><?= html_escape($b['package_name']); ?></h2>
                            <p class="small ymo-muted mb-3 font-monospace"><?= html_escape($b['vehicle_number']); ?></p>
                            <a href="<?= site_url('account/bookings/'.$b['id']); ?>" class="btn btn-sm btn-outline-primary">
                                View details<span class="mi mi-sm mi-trailing">arrow_forward</span>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="md-card-elevated p-0 d-none d-md-block">
                    <table class="ymo-table mb-0">
                        <thead><tr><th>Ref</th><th>Service</th><th>Vehicle</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $b): ?>
                            <tr>
                                <td class="font-monospace small"><?= html_escape($b['reference']); ?></td>
                                <td><?= html_escape($b['package_name']); ?></td>
                                <td class="small"><?= html_escape($b['vehicle_number']); ?></td>
                                <td>
                                    <span class="badge-status s-<?= html_escape($b['status']); ?>">
                                        <span class="mi"><?= isset($status_icons[$b['status']]) ? $status_icons[$b['status']] : 'help'; ?></span>
                                        <?= html_escape(str_replace('_', ' ', $b['status'])); ?>
                                    </span>
                                </td>
                                <td><a href="<?= site_url('account/bookings/'.$b['id']); ?>" class="small">View<span class="mi mi-sm mi-trailing">arrow_forward</span></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3"><a href="<?= site_url('account/bookings'); ?>" class="small">All bookings <span class="mi mi-sm mi-trailing">arrow_forward</span></a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
