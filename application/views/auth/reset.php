<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ymo-card">
            <h1 class="h4 mb-1"><span class="mi mi-leading">lock_reset</span>Set a new password</h1>
            <p class="ymo-muted mb-4">
                Enter the OTP we sent to <strong>+91 <?= html_escape(substr($mobile, -10)); ?></strong>
                and choose a new password.
            </p>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open(site_url('reset-password')); ?>
                <label class="form-label small ymo-muted">OTP</label>
                <input class="form-control form-control-lg text-center mb-3" name="code"
                       inputmode="numeric" maxlength="6" pattern="\d{6}" required
                       style="letter-spacing:8px;font-weight:700;">

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="rs_pw" name="password" placeholder=" "
                           minlength="<?= (int) $this->config->item('auth_password_min'); ?>" required>
                    <label for="rs_pw">New password</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="rs_pw2" name="confirm" placeholder=" " required>
                    <label for="rs_pw2">Confirm password</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">
                    <span class="mi mi-leading">check</span>Update password
                </button>
            <?= form_close(); ?>
        </div>
    </div>
</div>
