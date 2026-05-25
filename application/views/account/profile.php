<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="ymo-card">
                <h1 class="h4 mb-4"><span class="mi mi-leading">manage_accounts</span>My profile</h1>

                <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

                <?= form_open(site_url('account/profile')); ?>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-floating">
                                <input class="form-control" id="pf_name" name="name" placeholder=" "
                                       value="<?= set_value('name', $this->user['name']); ?>" required>
                                <label for="pf_name">Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input class="form-control" id="pf_mobile" placeholder=" "
                                       value="+91 <?= html_escape(substr($this->user['mobile'], -10)); ?>" disabled>
                                <label for="pf_mobile">Mobile</label>
                            </div>
                            <div class="md-field-help">Contact support to change your mobile.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input class="form-control" id="pf_email" placeholder=" "
                                       value="<?= html_escape($this->user['email']); ?>" disabled>
                                <label for="pf_email">Email</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input class="form-control" id="pf_area" name="area" placeholder=" "
                                       value="<?= set_value('area', $this->user['area']); ?>" required>
                                <label for="pf_area">Area / locality</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <?php $cities = (array) $this->config->item('ymo_service_cities'); ?>
                            <div class="form-floating">
                                <select class="form-select" id="pf_city" name="city" required>
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?= html_escape($c); ?>" <?= set_select('city', $c, $this->user['city'] === $c); ?>><?= html_escape($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="pf_city">City</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">
                        <span class="mi mi-leading">save</span>Save changes
                    </button>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
