<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="ymo-card">
            <h1 class="h3 mb-1"><span class="mi mi-leading">person_add</span>Create your YMO account</h1>
            <p class="ymo-muted mb-4">Takes under a minute. We'll text you a verification code.</p>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open(site_url('signup'), array('class' => 'needs-validation', 'novalidate' => '')); ?>
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input class="form-control" id="su_name" name="name" placeholder=" "
                                   value="<?= set_value('name'); ?>" required>
                            <label for="su_name">Full name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">+91</span>
                            <div class="form-floating flex-grow-1">
                                <input class="form-control" id="su_mobile" name="mobile" placeholder=" "
                                       inputmode="numeric" pattern="[6-9]\d{9}"
                                       value="<?= set_value('mobile'); ?>" required>
                                <label for="su_mobile">Mobile (10 digits)</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="su_email" name="email" placeholder=" "
                                   value="<?= set_value('email'); ?>" required>
                            <label for="su_email">Email address</label>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-floating">
                            <input class="form-control" id="su_area" name="area" placeholder=" "
                                   value="<?= set_value('area'); ?>" required>
                            <label for="su_area">Area / locality</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <?php $cities = (array) $this->config->item('ymo_service_cities'); ?>
                        <div class="form-floating">
                            <select class="form-select" id="su_city" name="city" required>
                                <option value="">Select your city</option>
                                <?php foreach ($cities as $c): ?>
                                    <option value="<?= html_escape($c); ?>" <?= set_select('city', $c); ?>><?= html_escape($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="su_city">City</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="su_pw" name="password" placeholder=" "
                                   minlength="<?= (int) $this->config->item('auth_password_min'); ?>" required>
                            <label for="su_pw">Password</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="su_pw2" name="confirm" placeholder=" " required>
                            <label for="su_pw2">Confirm password</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" value="1" required>
                            <label class="form-check-label" for="terms">
                                I agree to receive booking updates over SMS and email.
                            </label>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-100 mt-4" type="submit">
                    <span class="mi mi-leading">check_circle</span>Create account
                </button>
            <?= form_close(); ?>

            <p class="text-center ymo-muted mt-4 mb-0 small">
                Already have an account? <a href="<?= site_url('login'); ?>">Sign in</a>
            </p>
        </div>
    </div>
</div>
