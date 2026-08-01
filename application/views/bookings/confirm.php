<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <?php $this->load->view('bookings/_stepper', array('step' => $step)); ?>

            <div class="md-card-elevated">
                <h1 class="h4 mb-4"><span class="mi mi-leading">fact_check</span>Review your booking</h1>

                <dl class="row mb-2">
                    <dt class="col-sm-4 text-muted"><span class="mi mi-sm mi-leading">build</span>Package</dt>
                    <dd class="col-sm-8"><?= html_escape($package['name']); ?> &middot; &#8377; <?= number_format((float) $package['price']); ?></dd>

                    <dt class="col-sm-4 text-muted"><span class="mi mi-sm mi-leading">directions_car</span>Vehicle</dt>
                    <dd class="col-sm-8">
                        <?= html_escape($vehicle['make_name']); ?> <?= html_escape($vehicle['variant']); ?><br>
                        <span class="font-monospace small"><?= html_escape($vehicle['vehicle_number']); ?></span>
                    </dd>

                    <dt class="col-sm-4 text-muted"><span class="mi mi-sm mi-leading">event</span>Preferred date</dt>
                    <dd class="col-sm-8"><?= !empty($draft['preferred_date']) ? html_escape(date('d M Y', strtotime($draft['preferred_date']))) : '<span class="ymo-muted">Any day — we\'ll call to schedule</span>'; ?></dd>

                    <?php if (!empty($draft['remarks'])): ?>
                        <dt class="col-sm-4 text-muted"><span class="mi mi-sm mi-leading">edit_note</span>Remarks</dt>
                        <dd class="col-sm-8"><?= nl2br(html_escape($draft['remarks'])); ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($draft['referral_code'])): ?>
                        <dt class="col-sm-4 text-muted"><span class="mi mi-sm mi-leading">redeem</span>Referral code</dt>
                        <dd class="col-sm-8">
                            <span class="font-monospace"><?= html_escape($draft['referral_code']); ?></span>
                            <div class="ymo-muted small">Up to &#8377;<?= number_format((float) $referred_credit, 0); ?> credit applies after service completion.</div>
                        </dd>
                    <?php endif; ?>
                </dl>

                <p class="ymo-muted small">
                    <span class="mi mi-sm mi-leading">info</span>
                    No payment is required online. Our team will call to confirm pick-up and pricing.
                </p>

                <?= form_open(site_url('booking/place')); ?>
                    <div class="d-flex justify-content-between">
                        <a href="<?= site_url('booking/details'); ?>" class="btn btn-link"><span class="mi mi-sm mi-leading">arrow_back</span>Back</a>
                        <button class="btn btn-primary btn-lg" type="submit">
                            <span class="mi mi-leading">event_available</span>Book now
                        </button>
                    </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
