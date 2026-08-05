<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ymo-card">
            <h1 class="h3 mb-1"><span class="mi mi-leading">login</span>Welcome back</h1>
            <p class="ymo-muted mb-4">Sign in to manage your bookings and vehicles.</p>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open(site_url('login')); ?>
                <?php if (!empty($next)): ?>
                    <input type="hidden" name="next" value="<?= html_escape($next); ?>">
                <?php endif; ?>
                <div class="form-floating mb-3">
                    <input class="form-control" id="li_id" name="identifier" placeholder=" "
                           value="<?= set_value('identifier'); ?>" required autofocus>
                    <label for="li_id">Mobile or email</label>
                </div>
                <div class="form-floating mb-2">
                    <input type="password" class="form-control" id="li_pw" name="password" placeholder=" " required>
                    <label for="li_pw">Password</label>
                </div>
                <div class="d-flex justify-content-end mb-3">
                    <a href="<?= site_url('forgot-password'); ?>" class="small">Forgot password?</a>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">
                    <span class="mi mi-leading">login</span>Sign in
                </button>
            <?= form_close(); ?>

            <p class="text-center ymo-muted mt-4 mb-0 small">
                New here? <a href="<?= site_url('signup'); ?>">Create an account</a><br>
                No account? <a href="<?= site_url('quick-book'); ?>">Quick book without signing in</a>
            </p>
        </div>
    </div>
</div>
