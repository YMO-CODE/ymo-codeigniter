<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ymo-card">
            <h1 class="h4 mb-1"><span class="mi mi-leading">key</span>Forgot your password?</h1>
            <p class="ymo-muted mb-4">Enter the mobile number on your account and we'll send you a verification code.</p>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open(site_url('forgot-password')); ?>
                <div class="input-group mb-3">
                    <span class="input-group-text">+91</span>
                    <div class="form-floating flex-grow-1">
                        <input class="form-control" id="fp_mobile" name="mobile" placeholder=" "
                               inputmode="numeric" pattern="[6-9]\d{9}"
                               value="<?= set_value('mobile'); ?>" required>
                        <label for="fp_mobile">Mobile number</label>
                    </div>
                </div>
                <button class="btn btn-primary w-100" type="submit">
                    <span class="mi mi-leading">send</span>Send OTP
                </button>
            <?= form_close(); ?>
        </div>
    </div>
</div>
