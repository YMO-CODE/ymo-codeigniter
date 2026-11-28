<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fuel = array('' => '—', 0 => 'Empty', 25 => '1/4', 50 => '1/2', 75 => '3/4', 100 => 'Full');
?>
<a href="<?= $booking ? admin_url('bookings/'.(int) $booking['id']) : admin_url('job-cards'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span><?= $booking ? 'Back to booking' : 'All job cards'; ?></a>

<div class="row g-3 mt-1">
<div class="col-lg-8">
<?= form_open(admin_url('job-cards/new'), array('class' => 'md-card-elevated', 'id' => 'wo-new')); ?>
    <?php if ($booking): ?>
        <input type="hidden" name="booking_id" value="<?= (int) $booking['id']; ?>">
        <h2 class="h5 mb-2"><span class="mi mi-leading">event_note</span>From booking <?= html_escape($booking['reference']); ?></h2>
        <p class="mb-3">
            <strong><?= html_escape($booking['user_name']); ?></strong> · <?= html_escape($booking['user_mobile']); ?><br>
            <?= html_escape($booking['vehicle_number']); ?> · <?= html_escape(trim($booking['vehicle_make'].' '.$booking['vehicle_variant'])); ?><br>
            <span class="small ymo-muted">Package <?= html_escape($booking['package_name']); ?> is added as the first service line.</span>
        </p>
    <?php else: ?>
        <h2 class="h5 mb-3"><span class="mi mi-leading">person</span>Customer</h2>
        <div class="row g-2 mb-1">
            <div class="col-md-4">
                <label class="form-label small mb-1">Mobile *</label>
                <input class="form-control" name="mobile" id="wo_mobile" inputmode="numeric" maxlength="10" required pattern="[6-9][0-9]{9}" placeholder="10-digit mobile" autofocus>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Name *</label>
                <input class="form-control" name="name" id="wo_name" required maxlength="120">
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Email <span class="ymo-muted">(optional)</span></label>
                <input class="form-control" type="email" name="email" id="wo_email" maxlength="180">
            </div>
        </div>
        <p class="small mb-3" id="wo_note"><span class="ymo-muted">Type the mobile: an existing customer and their cars are filled in.</span></p>

        <h2 class="h5 mb-3"><span class="mi mi-leading">directions_car</span>Car</h2>
        <div class="mb-2" id="wo_vehicle_pick" hidden>
            <select class="form-select" name="vehicle_id" id="wo_vehicle_id"><option value="0">+ Add another car</option></select>
        </div>
        <div class="row g-2 mb-3" id="wo_vehicle_new">
            <div class="col-md-4">
                <label class="form-label small mb-1">Make</label>
                <select class="form-select" name="make_id">
                    <option value="">Select…</option>
                    <?php foreach ($makes as $m): ?><option value="<?= (int) $m['id']; ?>"><?= html_escape($m['name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Model / variant</label>
                <input class="form-control" name="variant" maxlength="120" placeholder="e.g. Baleno Zeta">
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Registration no.</label>
                <input class="form-control text-uppercase" name="vehicle_number" maxlength="20" placeholder="MH12AB1234">
            </div>
        </div>
    <?php endif; ?>

    <h2 class="h5 mb-3"><span class="mi mi-leading">build</span>Check-in</h2>
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <label class="form-label small mb-1">Workshop</label>
            <select class="form-select" name="workshop">
                <?php foreach ($workshops as $slug => $w): ?><option value="<?= html_escape($slug); ?>" <?= $slug === 'warje' ? 'selected' : ''; ?>><?= html_escape($w['label']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Odometer (km)</label>
            <input class="form-control" type="number" name="odometer_km" min="0" inputmode="numeric">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Fuel level</label>
            <select class="form-select" name="fuel_pct">
                <?php foreach ($fuel as $v => $l): ?><option value="<?= $v; ?>"><?= $l; ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Service advisor</label>
            <select class="form-select" name="advisor_id">
                <?php foreach ($admins as $a): ?><option value="<?= (int) $a['id']; ?>" <?= (int) $a['id'] === $me ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Car came by</label>
            <select class="form-select" name="booking_type">
                <option value="workshop">Drive-in at workshop</option>
                <option value="pickup" <?= $booking ? 'selected' : ''; ?>>Our pick-up</option>
                <option value="doorstep">Doorstep service</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Expected delivery</label>
            <input class="form-control" type="datetime-local" name="expected_delivery_at" value="<?= date('Y-m-d', strtotime('+1 day')); ?>T18:00">
        </div>
        <div class="col-12">
            <label class="form-label small mb-1">Customer complaint / what they asked for</label>
            <textarea class="form-control" name="complaint" rows="3" maxlength="3000" placeholder="e.g. Brake noise, AC not cooling, general service"><?= $booking ? html_escape((string) $booking['remarks']) : ''; ?></textarea>
        </div>
    </div>
    <button class="btn btn-primary"><span class="mi mi-sm mi-leading">assignment_add</span>Open job card</button>
<?= form_close(); ?>
</div>
<div class="col-lg-4">
    <div class="ymo-card">
        <h6 class="mb-1">How this works</h6>
        <ul class="small ymo-muted mb-0 ps-3">
            <li>The job card gets the next RO number and opens as a Draft.</li>
            <li>Add services and parts from the Catalogue on the next screen.</li>
            <li>Existing customers are matched by mobile; new ones get an account, like bookings.</li>
            <li>A job card from a booking moves the booking along: In progress when work starts, Completed when delivered.</li>
        </ul>
    </div>
</div>
</div>

<?php if (!$booking): ?>
<script>
(function () {
    var mobile = document.getElementById('wo_mobile'), nameIn = document.getElementById('wo_name'), emailIn = document.getElementById('wo_email');
    var pick = document.getElementById('wo_vehicle_pick'), sel = document.getElementById('wo_vehicle_id'), newCar = document.getElementById('wo_vehicle_new');
    var note = document.getElementById('wo_note'), lookupUrl = <?= json_encode(admin_url('bookings/lookup')); ?>, last = '';
    function toggleCar() { newCar.hidden = !pick.hidden && sel.value !== '0'; }
    sel.addEventListener('change', toggleCar);
    function lookup() {
        var m = mobile.value.replace(/\D/g, '');
        if (m.length !== 10 || m === last) { return; }
        last = m;
        fetch(lookupUrl + '?mobile=' + m, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
            sel.innerHTML = '';
            if (d.found) {
                if (!nameIn.value) { nameIn.value = d.name || ''; }
                if (!emailIn.value) { emailIn.value = d.email || ''; }
                (d.vehicles || []).forEach(function (v) { var o = document.createElement('option'); o.value = v.id; o.textContent = v.label; sel.appendChild(o); });
                note.innerHTML = '<span class="badge bg-success">Existing customer</span> <span class="ymo-muted">' + (d.vehicles || []).length + ' car(s) on file.</span>';
            } else {
                note.innerHTML = '<span class="badge bg-info text-dark">New customer</span> <span class="ymo-muted">An account is created with this mobile.</span>';
            }
            var add = document.createElement('option'); add.value = '0'; add.textContent = '+ Add another car'; sel.appendChild(add);
            pick.hidden = !(d.found && (d.vehicles || []).length);
            toggleCar();
        }).catch(function () {});
    }
    mobile.addEventListener('input', lookup);
    mobile.addEventListener('blur', lookup);
})();
</script>
<?php endif; ?>
