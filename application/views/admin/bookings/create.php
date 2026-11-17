<?php defined('BASEPATH') OR exit('No direct script access allowed');
$back_url = $lead ? admin_url('leads/'.$lead['id']) : ($contact ? admin_url('customers/'.$contact['id']) : admin_url('bookings'));
$back_label = $lead ? 'Back to lead' : ($contact ? 'Back to customer' : 'All bookings');
?>
<a href="<?= $back_url; ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span><?= $back_label; ?></a>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <?= form_open(admin_url('bookings/new'), array('class' => 'md-card-elevated', 'id' => 'ymo-admin-book')); ?>
            <?php if ($lead): ?><input type="hidden" name="lead_id" value="<?= (int) $lead['id']; ?>"><?php endif; ?>
            <?php if ($contact): ?><input type="hidden" name="contact_id" value="<?= (int) $contact['id']; ?>"><?php endif; ?>

            <h2 class="h5 mb-3"><span class="mi mi-leading">person</span>Customer</h2>
            <div class="row g-2 mb-1">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Mobile *</label>
                    <input class="form-control" name="mobile" id="bk_mobile" inputmode="numeric" maxlength="10" required
                           pattern="[6-9][0-9]{9}" value="<?= html_escape($prefill['mobile']); ?>" placeholder="10-digit mobile">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Name *</label>
                    <input class="form-control" name="name" id="bk_name" required maxlength="120" value="<?= html_escape($user['name'] ?? $prefill['name']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Email <span class="ymo-muted">(optional)</span></label>
                    <input class="form-control" type="email" name="email" id="bk_email" maxlength="180"
                           value="<?= html_escape($user && !ymo_is_placeholder_email($user['email']) ? $user['email'] : $prefill['email']); ?>">
                </div>
            </div>
            <p class="small mb-3" id="bk_account_note">
                <?php if ($user): ?>
                    <span class="badge bg-success">Existing customer</span> <span class="ymo-muted">Booking goes on their account.</span>
                <?php else: ?>
                    <span class="badge bg-info text-dark">New customer</span> <span class="ymo-muted">An account is created with this mobile; they can sign in later with OTP.</span>
                <?php endif; ?>
            </p>
            <div class="row g-2 mb-3" id="bk_new_fields" <?= $user ? 'hidden' : ''; ?>>
                <div class="col-md-8">
                    <label class="form-label small mb-1">Area / address</label>
                    <input class="form-control" name="area" maxlength="120" value="<?= html_escape($prefill['area']); ?>" placeholder="e.g. Hinjewadi Phase 1">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">City</label>
                    <select class="form-select" name="city">
                        <?php foreach (array('Pune', 'Indore', 'Nashik') as $c): ?><option><?= $c; ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h2 class="h5 mb-3"><span class="mi mi-leading">directions_car</span>Car</h2>
            <div class="mb-2" id="bk_vehicle_pick" <?= empty($vehicles) ? 'hidden' : ''; ?>>
                <select class="form-select" name="vehicle_id" id="bk_vehicle_id">
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?= (int) $v['id']; ?>"><?= html_escape(trim($v['make_name'].' '.$v['variant']).' · '.$v['vehicle_number']); ?></option>
                    <?php endforeach; ?>
                    <option value="0" <?= empty($vehicles) ? 'selected' : ''; ?>>+ Add another car</option>
                </select>
            </div>
            <div class="row g-2 mb-3" id="bk_vehicle_new" <?= !empty($vehicles) ? 'hidden' : ''; ?>>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Make</label>
                    <select class="form-select" name="make_id">
                        <option value="">Select…</option>
                        <?php foreach ($makes as $m): ?><option value="<?= (int) $m['id']; ?>"><?= html_escape($m['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Model / variant</label>
                    <input class="form-control" name="variant" maxlength="120" placeholder="e.g. Swift VXi">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Registration no.</label>
                    <input class="form-control text-uppercase" name="vehicle_number" maxlength="20" placeholder="MH12AB1234">
                </div>
            </div>

            <h2 class="h5 mb-3"><span class="mi mi-leading">build</span>Service</h2>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Package *</label>
                    <select class="form-select" name="package_id" required>
                        <option value="">Select…</option>
                        <?php foreach ($packages as $p): ?>
                            <option value="<?= (int) $p['id']; ?>"><?= html_escape($p['name']); ?><?= isset($p['price']) ? ' · Rs '.number_format((float) $p['price']) : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Pickup date</label>
                    <input class="form-control" type="date" name="preferred_date" min="<?= date('Y-m-d'); ?>" value="<?= date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select" name="status">
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Notes for the team</label>
                    <textarea class="form-control" name="remarks" rows="3" maxlength="2000"><?= html_escape($prefill['remarks']); ?></textarea>
                </div>
            </div>

            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="notify" value="1" checked>
                <span class="form-check-label small">Send the booking confirmation to the customer (WhatsApp / SMS, plus email if we have one)</span>
            </label>
            <button class="btn btn-primary"><span class="mi mi-sm mi-leading">event_available</span>Create booking</button>
        <?= form_close(); ?>
    </div>
    <div class="col-lg-4">
        <?php if ($lead): ?>
        <div class="ymo-card mb-3">
            <h6 class="mb-1">From lead</h6>
            <p class="small mb-1"><strong><?= html_escape($lead['name']); ?></strong> · <?= html_escape(crm_lead_stage_label($lead['stage'])); ?></p>
            <?php if (!empty($lead['message'])): ?><p class="small ymo-muted mb-0" style="white-space:pre-wrap"><?= html_escape(mb_strimwidth($lead['message'], 0, 300, '…')); ?></p><?php endif; ?>
            <p class="small mt-2 mb-0">Creating the booking marks this lead <span class="badge bg-success">Booked</span> and stops its follow-ups.</p>
        </div>
        <?php endif; ?>
        <div class="ymo-card">
            <h6 class="mb-1">How this works</h6>
            <ul class="small ymo-muted mb-0 ps-3">
                <li>Existing customers are matched by mobile; their saved cars appear.</li>
                <li>New customers get an account automatically and show up under Customers.</li>
                <li>The booking appears in Bookings like any website booking.</li>
            </ul>
        </div>
    </div>
</div>
<script>
(function () {
    var mobile = document.getElementById('bk_mobile');
    var pick = document.getElementById('bk_vehicle_pick');
    var sel = document.getElementById('bk_vehicle_id');
    var newCar = document.getElementById('bk_vehicle_new');
    var newFields = document.getElementById('bk_new_fields');
    var note = document.getElementById('bk_account_note');
    var nameIn = document.getElementById('bk_name');
    var emailIn = document.getElementById('bk_email');
    var lookupUrl = <?= json_encode(admin_url('bookings/lookup')); ?>;
    var last = '';

    function toggleCar() {
        newCar.hidden = !pick.hidden && sel.value !== '0';
    }
    sel.addEventListener('change', toggleCar);

    function setVehicles(list) {
        sel.innerHTML = '';
        list.forEach(function (v) {
            var o = document.createElement('option');
            o.value = v.id; o.textContent = v.label; sel.appendChild(o);
        });
        var add = document.createElement('option');
        add.value = '0'; add.textContent = '+ Add another car'; sel.appendChild(add);
        pick.hidden = list.length === 0;
        if (!list.length) { sel.value = '0'; }
        toggleCar();
    }

    function lookup() {
        var m = mobile.value.replace(/\D/g, '');
        if (m.length !== 10 || m === last) { return; }
        last = m;
        fetch(lookupUrl + '?mobile=' + m, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.found) {
                    note.innerHTML = '<span class="badge bg-success">Existing customer</span> <span class="ymo-muted">Booking goes on their account.</span>';
                    newFields.hidden = true;
                    if (!nameIn.value) { nameIn.value = d.name; }
                    if (!emailIn.value && d.email) { emailIn.value = d.email; }
                    setVehicles(d.vehicles || []);
                } else {
                    note.innerHTML = '<span class="badge bg-info text-dark">New customer</span> <span class="ymo-muted">An account is created with this mobile; they can sign in later with OTP.</span>';
                    newFields.hidden = false;
                    setVehicles([]);
                }
            })
            .catch(function () {});
    }
    mobile.addEventListener('blur', lookup);
    mobile.addEventListener('input', function () { if (mobile.value.replace(/\D/g, '').length === 10) { lookup(); } });
    toggleCar();
})();
</script>
