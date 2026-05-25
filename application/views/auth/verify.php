<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="ymo-card">
            <h1 class="h4 mb-1"><span class="mi mi-leading">verified</span>Verify your mobile</h1>
            <p class="ymo-muted mb-4">
                We've sent a 6-digit code to <strong>+91 <?= html_escape(substr($mobile, -10)); ?></strong>.
                Enter it below to finish creating your account.
            </p>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open(site_url('signup/verify')); ?>
                <label class="form-label small ymo-muted">Verification code</label>
                <input class="form-control form-control-lg text-center" name="code"
                       inputmode="numeric" maxlength="6" pattern="\d{6}"
                       autofocus required style="letter-spacing:8px;font-weight:700;">
                <button class="btn btn-primary btn-lg w-100 mt-3" type="submit">
                    <span class="mi mi-leading">check_circle</span>Verify &amp; continue
                </button>
            <?= form_close(); ?>

            <hr class="ymo-divider">

            <?= form_open(site_url('signup/resend')); ?>
                <button class="btn btn-link p-0" type="submit" data-otp-resend="<?= (int) $cooldown; ?>">
                    <span class="mi mi-sm mi-leading">refresh</span>Resend code
                </button>
            <?= form_close(); ?>
        </div>
    </div>
</div>
