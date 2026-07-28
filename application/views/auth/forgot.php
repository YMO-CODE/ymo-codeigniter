<?php defined('BASEPATH') OR exit('No direct script access allowed');
$field_err_open  = '<div class="invalid-feedback d-block">';
$field_err_close = '</div>';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ymo-card">
            <h1 class="h4 mb-1"><span class="mi mi-leading">key</span>Forgot your password?</h1>
            <p class="ymo-muted mb-4">Enter the email address on your account and we'll send you a verification code.</p>

            <?= form_open(site_url('forgot-password')); ?>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control<?= form_error('email') ? ' is-invalid' : ''; ?>" id="fp_email" name="email" placeholder=" "
                           value="<?= set_value('email'); ?>" required autofocus>
                    <label for="fp_email">Email address</label>
                </div>
                <?= form_error('email', $field_err_open, $field_err_close); ?>
                <button class="btn btn-primary w-100" type="submit">
                    <span class="mi mi-leading">send</span>Send verification code
                </button>
            <?= form_close(); ?>

            <p class="text-center ymo-muted mt-4 mb-0 small">
                Remember your password? <a href="<?= site_url('login'); ?>">Sign in</a>
            </p>
        </div>
    </div>
</div>
