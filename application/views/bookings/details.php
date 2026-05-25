<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <?php $this->load->view('bookings/_stepper', array('step' => $step)); ?>

            <div class="ymo-card mb-3">
                <div class="d-flex justify-content-between flex-wrap">
                    <div>
                        <strong><?= html_escape($package['name']); ?></strong>
                        <div class="ymo-muted small">
                            <span class="mi mi-sm mi-leading">directions_car</span><?= html_escape($vehicle['make_name']); ?> <?= html_escape($vehicle['variant']); ?> · <?= html_escape($vehicle['vehicle_number']); ?>
                        </div>
                    </div>
                    <div class="ymo-muted">&#8377; <?= number_format((float) $package['price']); ?></div>
                </div>
            </div>

            <div class="ymo-card">
                <h2 class="h5 mb-3"><span class="mi mi-leading">edit_note</span>Anything we should know?</h2>
                <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

                <?= form_open(site_url('booking/details')); ?>
                    <div class="form-floating mb-1">
                        <input type="date" class="form-control" id="bk_date" name="preferred_date" placeholder=" "
                               min="<?= html_escape($min_date); ?>"
                               value="<?= set_value('preferred_date', isset($draft['preferred_date']) ? $draft['preferred_date'] : ''); ?>">
                        <label for="bk_date">Preferred date (optional)</label>
                    </div>
                    <div class="md-field-help mb-3">We'll call to confirm timing.</div>

                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="bk_remarks" name="remarks" placeholder=" "
                                  rows="4" maxlength="2000"
                                  style="height:120px;"><?= set_value('remarks', isset($draft['remarks']) ? $draft['remarks'] : ''); ?></textarea>
                        <label for="bk_remarks">Notes / remarks (e.g. things to check)</label>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= site_url('booking/vehicle'); ?>" class="btn btn-link"><span class="mi mi-sm mi-leading">arrow_back</span>Back</a>
                        <button class="btn btn-primary" type="submit">
                            Continue<span class="mi mi-trailing">arrow_forward</span>
                        </button>
                    </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
