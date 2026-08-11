<?php defined('BASEPATH') OR exit('No direct script access allowed');
$field_err_open  = '<div class="invalid-feedback d-block">';
$field_err_close = '</div>';

$digits = preg_replace('/\D/', '', (string) $mobile);
$masked = strlen($digits) === 10
    ? '+91 '.substr($digits, 0, 2).'******'.substr($digits, -2)
    : '+91 **********';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ymo-card">
            <h1 class="h4 mb-1"><span class="mi mi-leading">lock_reset</span>Set a new password</h1>
            <p class="ymo-muted mb-4">
                Enter the OTP we sent to <strong><?= html_escape($masked); ?></strong>
                and choose a new password.
            </p>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open(site_url('reset-password')); ?>
                <label class="form-label small ymo-muted" for="rs_code">Verification code</label>
                <input class="form-control form-control-lg text-center mb-1<?= form_error('code') ? ' is-invalid' : ''; ?>"
                       id="rs_code" name="code"
                       inputmode="numeric" maxlength="6" pattern="\d{6}" required autofocus
                       style="letter-spacing:8px;font-weight:700;">
                <?= form_error('code', $field_err_open, $field_err_close); ?>

                <div class="form-floating mb-1 mt-3">
                    <input type="password" class="form-control<?= form_error('password') ? ' is-invalid' : ''; ?>" id="rs_pw" name="password" placeholder=" "
                           minlength="<?= (int) $this->config->item('auth_password_min'); ?>" required>
                    <label for="rs_pw">New password</label>
                </div>
                <?= form_error('password', $field_err_open, $field_err_close); ?>

                <div class="form-floating mb-1 mt-3">
                    <input type="password" class="form-control<?= form_error('confirm') ? ' is-invalid' : ''; ?>" id="rs_pw2" name="confirm" placeholder=" " required>
                    <label for="rs_pw2">Confirm password</label>
                </div>
                <?= form_error('confirm', $field_err_open, $field_err_close); ?>

                <button class="btn btn-primary w-100 mt-4" type="submit">
                    <span class="mi mi-leading">check</span>Update password
                </button>
            <?= form_close(); ?>

            <hr class="ymo-divider">

            <?= form_open(site_url('reset-password/resend')); ?>
                <button class="btn btn-link p-0" type="submit" data-otp-resend="<?= (int) $cooldown; ?>">
                    <span class="mi mi-sm mi-leading">refresh</span>Resend OTP
                </button>
            <?= form_close(); ?>

            <p class="text-center ymo-muted mt-3 mb-0 small">
                Wrong number? <a href="<?= site_url('forgot-password'); ?>">Start over</a>
            </p>
        </div>
    </div>
</div>
