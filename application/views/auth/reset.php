<?php defined('BASEPATH') OR exit('No direct script access allowed');
$field_err_open  = '<div class="invalid-feedback d-block">';
$field_err_close = '</div>';

$masked_email = $email;
if (strpos($email, '@') !== FALSE) {
    list($local, $domain) = explode('@', $email, 2);
    $masked_email = substr($local, 0, 1).str_repeat('*', max(1, strlen($local) - 1)).'@'.$domain;
}
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ymo-card">
            <h1 class="h4 mb-1"><span class="mi mi-leading">lock_reset</span>Set a new password</h1>
            <p class="ymo-muted mb-4">
                Enter the verification code we sent to <strong><?= html_escape($masked_email); ?></strong>
                and choose a new password.
            </p>

            <?= form_open(site_url('reset-password')); ?>
                <label class="form-label small ymo-muted" for="rs_code">Verification code</label>
                <input class="form-control form-control-lg text-center mb-1<?= form_error('code') ? ' is-invalid' : ''; ?>"
                       id="rs_code" name="code"
                       inputmode="numeric" maxlength="6" pattern="\d{6}" required
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

            <p class="text-center ymo-muted mt-4 mb-0 small">
                Didn't get the code? <a href="<?= site_url('forgot-password'); ?>">Try again</a>
            </p>
        </div>
    </div>
</div>
