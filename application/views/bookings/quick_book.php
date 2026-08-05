<?php defined('BASEPATH') OR exit('No direct script access allowed');
$field_err_open  = '<div class="invalid-feedback d-block">';
$field_err_close = '</div>';
$selected_package = set_value('package_id', $prefill_package_id ?: '');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ymo-card">
                <h1 class="h3 mb-1"><span class="mi mi-leading">event_available</span>Quick book</h1>
                <p class="ymo-muted mb-4">No account needed — share your details and our team will call you to confirm your service.</p>

                <?php if (validation_errors()): ?>
                    <div class="alert alert-danger mb-3" role="alert"><?= validation_errors('<div>', '</div>'); ?></div>
                <?php endif; ?>

                <?= form_open(site_url('quick-book'), array('class' => 'needs-validation', 'novalidate' => '')); ?>
                    <?php foreach ($utm as $utm_key => $utm_val): ?>
                        <input type="hidden" name="<?= html_escape($utm_key); ?>" value="<?= html_escape($utm_val); ?>">
                    <?php endforeach; ?>

                    <h2 class="h6 text-uppercase text-muted mb-3">Your details</h2>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-floating">
                                <input class="form-control<?= form_error('name') ? ' is-invalid' : ''; ?>" id="qb_name" name="name" placeholder=" "
                                       value="<?= set_value('name'); ?>" required>
                                <label for="qb_name">Full name</label>
                            </div>
                            <?= form_error('name', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">+91</span>
                                <div class="form-floating flex-grow-1">
                                    <input class="form-control<?= form_error('mobile') ? ' is-invalid' : ''; ?>" id="qb_mobile" name="mobile" placeholder=" "
                                           inputmode="numeric" pattern="[6-9]\d{9}"
                                           value="<?= set_value('mobile'); ?>" required>
                                    <label for="qb_mobile">Mobile (10 digits)</label>
                                </div>
                            </div>
                            <?= form_error('mobile', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control<?= form_error('email') ? ' is-invalid' : ''; ?>" id="qb_email" name="email" placeholder=" "
                                       value="<?= set_value('email'); ?>">
                                <label for="qb_email">Email <span class="ymo-muted fw-normal">(optional)</span></label>
                            </div>
                            <?= form_error('email', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input class="form-control<?= form_error('area') ? ' is-invalid' : ''; ?>" id="qb_area" name="area" placeholder=" "
                                       value="<?= set_value('area'); ?>" required>
                                <label for="qb_area">Area / locality</label>
                            </div>
                            <?= form_error('area', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select<?= form_error('city') ? ' is-invalid' : ''; ?>" id="qb_city" name="city" required>
                                    <option value="">Select city</option>
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?= html_escape($c); ?>" <?= set_select('city', $c); ?>><?= html_escape($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="qb_city">City</label>
                            </div>
                            <?= form_error('city', $field_err_open, $field_err_close); ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h2 class="h6 text-uppercase text-muted mb-3">Service &amp; vehicle</h2>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-floating">
                                <select class="form-select<?= form_error('package_id') ? ' is-invalid' : ''; ?>" id="qb_package" name="package_id" required>
                                    <option value="">Select service package</option>
                                    <?php foreach ($packages as $p): ?>
                                        <option value="<?= (int) $p['id']; ?>"
                                            <?= set_select('package_id', $p['id'], (string) $selected_package === (string) $p['id']); ?>>
                                            <?= html_escape($p['name']); ?> — &#8377;<?= number_format((float) $p['price']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="custom" <?= set_select('package_id', 'custom', (string) $selected_package === 'custom'); ?>>
                                        Customised package — tell us what you need
                                    </option>
                                </select>
                                <label for="qb_package">Service package</label>
                            </div>
                            <?= form_error('package_id', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-12" id="qb_custom_wrap" style="display:none;">
                            <div class="form-floating">
                                <textarea class="form-control<?= form_error('custom_package') ? ' is-invalid' : ''; ?>" id="qb_custom_package" name="custom_package" placeholder=" "
                                          style="min-height:88px"><?= set_value('custom_package'); ?></textarea>
                                <label for="qb_custom_package">Describe the service you need</label>
                            </div>
                            <div class="md-field-help mb-0">e.g. AC gas refill, clutch repair, full body paint — we will quote over the phone.</div>
                            <?= form_error('custom_package', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select<?= form_error('make_id') ? ' is-invalid' : ''; ?>" id="qb_make" name="make_id" required>
                                    <option value="">Select car brand</option>
                                    <?php foreach ($makes as $m): ?>
                                        <option value="<?= (int) $m['id']; ?>" <?= set_select('make_id', $m['id']); ?>>
                                            <?= html_escape($m['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="qb_make">Car make</label>
                            </div>
                            <?= form_error('make_id', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input class="form-control<?= form_error('variant') ? ' is-invalid' : ''; ?>" id="qb_variant" name="variant" placeholder=" "
                                       value="<?= set_value('variant'); ?>" required>
                                <label for="qb_variant">Car model / variant</label>
                            </div>
                            <?= form_error('variant', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input class="form-control font-monospace<?= form_error('vehicle_number') ? ' is-invalid' : ''; ?>" id="qb_number" name="vehicle_number" placeholder=" "
                                       value="<?= set_value('vehicle_number'); ?>" data-uppercase>
                                <label for="qb_number">Vehicle number <span class="ymo-muted fw-normal">(optional)</span></label>
                            </div>
                            <?= form_error('vehicle_number', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="date" class="form-control<?= form_error('preferred_date') ? ' is-invalid' : ''; ?>" id="qb_date" name="preferred_date"
                                       min="<?= html_escape($min_date); ?>" value="<?= set_value('preferred_date'); ?>">
                                <label for="qb_date">Preferred date <span class="ymo-muted fw-normal">(optional)</span></label>
                            </div>
                            <?= form_error('preferred_date', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control<?= form_error('remarks') ? ' is-invalid' : ''; ?>" id="qb_remarks" name="remarks" placeholder=" "
                                          style="min-height:100px"><?= set_value('remarks'); ?></textarea>
                                <label for="qb_remarks">Remarks <span class="ymo-muted fw-normal">(optional)</span></label>
                            </div>
                            <?= form_error('remarks', $field_err_open, $field_err_close); ?>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input<?= form_error('terms') ? ' is-invalid' : ''; ?>" id="qb_terms" name="terms" value="1" required>
                                <label class="form-check-label" for="qb_terms">
                                    I agree to receive booking updates over SMS and email.
                                    See our <a href="<?= html_escape(ymo_marketing_url('privacy-policy')); ?>" target="_blank" rel="noopener">Privacy policy</a>.
                                </label>
                            </div>
                            <?= form_error('terms', $field_err_open, $field_err_close); ?>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-lg w-100 mt-4" type="submit">
                        <span class="mi mi-leading">send</span>Submit request
                    </button>
                <?= form_close(); ?>

                <p class="text-center ymo-muted mt-4 mb-0 small">
                    Prefer to book online yourself? <a href="<?= site_url('packages'); ?>">Browse packages &amp; sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var sel = document.getElementById('qb_package');
    var wrap = document.getElementById('qb_custom_wrap');
    var input = document.getElementById('qb_custom_package');
    if (!sel || !wrap || !input) { return; }
    function sync() {
        var custom = sel.value === 'custom';
        wrap.style.display = custom ? '' : 'none';
        input.required = custom;
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>
