<?php defined('BASEPATH') OR exit('No direct script access allowed');
$S = Crm_work_order_model::STATUSES;
$badge = array('draft' => 'bg-secondary', 'in_progress' => 'bg-primary', 'on_hold' => 'bg-warning text-dark', 'ready' => 'bg-info text-dark', 'delivered' => 'bg-success', 'returned' => 'bg-dark');
$moves = Crm_work_order_model::MOVES[$wo['status']] ?? array();
$move_label = array(
    'in_progress' => in_array($wo['status'], array('delivered', 'returned'), TRUE) ? 'Reopen' : ($wo['status'] === 'draft' ? 'Start work' : ($wo['status'] === 'ready' ? 'Back to work' : 'Resume work')),
    'on_hold'     => 'Put on hold',
    'ready'       => 'Ready for delivery',
    'delivered'   => 'Delivered',
    'returned'    => 'Returned',
);
$move_icon = array('in_progress' => 'play_arrow', 'on_hold' => 'pause', 'ready' => 'task_alt', 'delivered' => 'directions_car', 'returned' => 'undo');
$closed = in_array($wo['status'], array('delivered', 'returned'), TRUE);
$edit = !empty($can_edit) && !$closed;
$tech_ids = array_map('intval', array_column($techs, 'id'));
$mobile = preg_replace('/\D/', '', (string) $wo['customer_mobile']);
$money = function ($v) { return '&#8377; '.number_format((float) $v, 2); };
$num = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); };
$by_type = array('service' => array(), 'part' => array());
foreach ($lines as $l) { $by_type[$l['type']][] = $l; }
$fuel = array('' => '—', 0 => 'Empty', 25 => '1/4', 50 => '1/2', 75 => '3/4', 100 => 'Full');
$wsname = ($wo['workshop'] !== '' && isset($workshops[$wo['workshop']])) ? $workshops[$wo['workshop']]['label'] : '';
?>
<style>
.wo-line-done .wo-line-name { text-decoration: line-through; opacity: .6; }
.wo-pick { position: relative; }
.wo-pick-list { position: absolute; z-index: 20; left: 0; right: 0; top: 100%; max-height: 18rem; overflow: auto; background: #fff; border: 1px solid #dee2e6; border-radius: .5rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.12); }
.wo-pick-list button { display: block; width: 100%; text-align: left; border: 0; background: none; padding: .45rem .75rem; font-size: .9rem; }
.wo-pick-list button:hover, .wo-pick-list button.active { background: #eef5f0; }
.wo-sum td { padding: .2rem .5rem; }
.wo-photo { position: relative; width: 112px; }
.wo-photo img { width: 112px; height: 84px; object-fit: cover; border-radius: .4rem; background: #eef1ef; display: block; }
.wo-photo form { position: absolute; top: 2px; right: 2px; }
.wo-photo-x { border: 0; background: rgba(0,0,0,.55); color: #fff; border-radius: 50%; width: 22px; height: 22px; font-size: 11px; line-height: 22px; padding: 0; }
.wo-photo-by { font-size: .7rem; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wo-note { background: #fff8e1; border-radius: .3rem; padding: .15rem .35rem; }
</style>

<a href="<?= admin_url('job-cards'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All job cards</a>

<!-- Header -->
<div class="ymo-card mt-2 mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h2 class="h4 mb-0"><?= html_escape($wo['ro_number']); ?></h2>
                <span class="badge <?= $badge[$wo['status']]; ?>"><?= $S[$wo['status']]; ?></span>
                <?php if ($wsname !== ''): ?><span class="badge bg-light text-dark border"><?= html_escape($wsname); ?></span><?php endif; ?>
                <?php if (!empty($wo['booking_id'])): ?><a class="badge bg-light text-dark border text-decoration-none" href="<?= admin_url('bookings/'.(int) $wo['booking_id']); ?>">Booking <?= html_escape($wo['booking_reference']); ?></a><?php endif; ?>
            </div>
            <div class="mt-2">
                <strong><?= html_escape($wo['customer_name']); ?></strong>
                <?php if ($mobile !== ''): ?>
                    · <a href="tel:+91<?= substr($mobile, -10); ?>"><?= html_escape($wo['customer_mobile']); ?></a>
                    · <a href="https://wa.me/91<?= substr($mobile, -10); ?>" target="_blank" rel="noopener">WhatsApp</a>
                <?php endif; ?>
                <br><span class="fw-semibold"><?= html_escape($wo['vehicle_number']); ?></span> · <?= html_escape(trim($wo['vehicle_make'].' '.$wo['vehicle_variant'])); ?>
                <?php if ($wo['odometer_km'] !== NULL): ?> · <?= number_format((int) $wo['odometer_km']); ?> km<?php endif; ?>
                <?php if ($wo['fuel_pct'] !== NULL): ?> · fuel <?= html_escape($fuel[(int) $wo['fuel_pct']] ?? ((int) $wo['fuel_pct'].'%')); ?><?php endif; ?>
            </div>
            <div class="small ymo-muted mt-1">
                Opened <?= html_escape(date('d M Y, H:i', strtotime($wo['created_at']))); ?>
                <?php if (!empty($wo['advisor_name'])): ?> · Advisor <?= html_escape($wo['advisor_name']); ?><?php endif; ?>
                <?php if ($techs): ?> · Technicians <?= html_escape(implode(', ', array_column($techs, 'name'))); ?><?php endif; ?>
                <?php if (!empty($wo['expected_delivery_at']) && !$closed): ?> · <span class="<?= strtotime($wo['expected_delivery_at']) < time() ? 'text-danger' : ''; ?>">Due <?= html_escape(date('d M, H:i', strtotime($wo['expected_delivery_at']))); ?></span><?php endif; ?>
                <?php if ($closed && !empty($wo['delivered_at']) && $wo['status'] === 'delivered'): ?> · Delivered <?= html_escape(date('d M Y, H:i', strtotime($wo['delivered_at']))); ?><?php endif; ?>
            </div>
        </div>
        <div class="text-end">
            <div class="h4 mb-1"><?= $money($wo['grand_total']); ?></div>
            <a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('job-cards/'.(int) $wo['id'].'/print'); ?>" target="_blank"><span class="mi mi-sm mi-leading">print</span>Job card</a>
        </div>
    </div>

    <?php if (!empty($can_edit) && $moves): ?>
    <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
        <?php foreach ($moves as $to): ?>
            <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/status'), array('class' => 'd-inline wo-status-form', 'data-to' => $to)); ?>
                <input type="hidden" name="to" value="<?= $to; ?>">
                <input type="hidden" name="note" value="">
                <button class="btn btn-sm <?= in_array($to, array('ready', 'delivered'), TRUE) || ($to === 'in_progress' && $wo['status'] === 'draft') ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    <span class="mi mi-sm mi-leading"><?= $move_icon[$to]; ?></span><?= $move_label[$to]; ?>
                </button>
            <?= form_close(); ?>
        <?php endforeach; ?>
        <?php if ($wo['status'] === 'draft'): ?>
            <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/delete'), array('class' => 'd-inline ms-auto', 'onsubmit' => "return confirm('Remove this draft job card?');")); ?>
                <button class="btn btn-sm btn-link text-danger">Remove draft</button>
            <?= form_close(); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="row g-3">
<div class="col-lg-8">
    <!-- Lines -->
    <div class="ymo-card p-0 mb-3" id="lines">
        <?php foreach (array('service' => 'Services', 'part' => 'Spare parts') as $type => $title): ?>
        <div class="px-3 pt-3 d-flex justify-content-between align-items-center">
            <h3 class="h6 mb-0"><?= $title; ?> <span class="ymo-muted fw-normal">(<?= count($by_type[$type]); ?>)</span></h3>
        </div>
        <?php if (!$by_type[$type]): ?>
            <p class="small ymo-muted px-3 py-2 mb-0">None yet.</p>
        <?php else: ?>
        <div class="table-responsive">
        <table class="ymo-table mb-0">
            <tbody>
            <?php foreach ($by_type[$type] as $l): $done = !empty($l['done_at']); ?>
                <tr class="<?= $done ? 'wo-line-done' : ''; ?>">
                    <td style="width:2.2rem">
                        <?php if (!empty($can_edit)): ?>
                        <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/lines/'.(int) $l['id'].'/done'), array('class' => 'd-inline')); ?>
                            <button class="btn btn-sm p-0 border-0 bg-transparent" title="<?= $done ? 'Mark not done' : 'Mark done'; ?>"><span class="mi"><?= $done ? 'check_box' : 'check_box_outline_blank'; ?></span></button>
                        <?= form_close(); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="wo-line-name fw-semibold"><?= html_escape($l['name']); ?></div>
                        <?php if (!empty($l['description'])): ?><div class="small ymo-muted" style="max-width:34rem"><?= html_escape(mb_strimwidth($l['description'], 0, 160, '…')); ?></div><?php endif; ?>
                        <div class="small ymo-muted">
                            <?= $num($l['qty']); ?> × <?= $money($l['rate']); ?>
                            <?php if ((float) $l['discount_pct'] > 0): ?> · <?= $num($l['discount_pct']); ?>% off<?php endif; ?>
                            <?php if ((float) $l['tax_pct'] > 0): ?> · <?= $num($l['tax_pct']); ?>% GST <?= (int) $l['tax_inclusive'] ? 'incl.' : 'extra'; ?><?php endif; ?>
                            <?php if (!empty($l['technician_name'])): ?> · <?= html_escape($l['technician_name']); ?><?php endif; ?>
                            <?php if ($done && !empty($l['done_by_tech_name'])): ?> · <span class="text-success">done by <?= html_escape($l['done_by_tech_name']); ?></span><?php endif; ?>
                        </div>
                    </td>
                    <td class="text-end text-nowrap"><?= $money($l['amount']); ?></td>
                    <td class="text-end text-nowrap" style="width:1%">
                        <?php if ($edit): ?>
                        <button type="button" class="btn btn-sm btn-link px-1" data-bs-toggle="collapse" data-bs-target="#line-edit-<?= (int) $l['id']; ?>" title="Change">Edit</button>
                        <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/lines/'.(int) $l['id'].'/delete'), array('class' => 'd-inline', 'onsubmit' => "return confirm('Remove this line?');")); ?>
                            <button class="btn btn-sm btn-link text-danger px-1" title="Remove">✕</button>
                        <?= form_close(); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($edit): ?>
                <tr class="collapse" id="line-edit-<?= (int) $l['id']; ?>"><td></td><td colspan="3">
                    <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/lines/'.(int) $l['id'].'/update'), array('class' => 'row g-2 align-items-end py-1')); ?>
                        <div class="col-12"><input class="form-control form-control-sm" name="name" value="<?= html_escape($l['name']); ?>" maxlength="255"></div>
                        <div class="col-3 col-md-2"><label class="small ymo-muted">Qty</label><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="qty" value="<?= $num($l['qty']); ?>"></div>
                        <div class="col-5 col-md-2"><label class="small ymo-muted">Rate &#8377;</label><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="rate" value="<?= $num($l['rate']); ?>"></div>
                        <div class="col-4 col-md-2"><label class="small ymo-muted">Disc %</label><input class="form-control form-control-sm" type="number" step="0.01" min="0" max="100" name="discount_pct" value="<?= $num($l['discount_pct']); ?>"></div>
                        <div class="col-4 col-md-2"><label class="small ymo-muted">GST %</label><input class="form-control form-control-sm" type="number" step="0.01" min="0" max="28" name="tax_pct" value="<?= $num($l['tax_pct']); ?>"></div>
                        <div class="col-8 col-md-4"><label class="small ymo-muted">GST</label>
                            <select class="form-select form-select-sm" name="tax_inclusive"><option value="1" <?= (int) $l['tax_inclusive'] ? 'selected' : ''; ?>>Included in rate</option><option value="0" <?= (int) $l['tax_inclusive'] ? '' : 'selected'; ?>>Added on top</option></select></div>
                        <div class="col-8 col-md-6"><label class="small ymo-muted">Technician</label>
                            <select class="form-select form-select-sm" name="technician_id"><option value="">—</option>
                                <?php foreach ($tech_list as $a): ?><option value="<?= (int) $a['id']; ?>" <?= (int) $l['technician_id'] === (int) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-4 col-md-6 text-end"><button class="btn btn-sm btn-primary">Save</button></div>
                    <?= form_close(); ?>
                </td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <div class="px-3 py-2 border-top">
            <table class="ms-auto wo-sum small">
                <tr><td class="ymo-muted">Taxable value</td><td class="text-end"><?= $money($wo['subtotal']); ?></td></tr>
                <tr><td class="ymo-muted">GST</td><td class="text-end"><?= $money($wo['tax_total']); ?></td></tr>
                <tr class="fw-semibold" style="font-size:1rem"><td>Total</td><td class="text-end"><?= $money($wo['grand_total']); ?></td></tr>
            </table>
        </div>

        <?php if ($edit): ?>
        <div class="px-3 pb-3 pt-2 border-top bg-light" style="border-radius:0 0 12px 12px">
            <h3 class="h6 mt-2">Add a line</h3>
            <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/lines/add'), array('class' => 'row g-2 align-items-end', 'id' => 'wo-add', 'autocomplete' => 'off')); ?>
                <input type="hidden" name="catalog_item_id" id="wo_item_id" value="">
                <div class="col-12 col-md-3">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <input type="radio" class="btn-check" name="type" id="wo_t_s" value="service" checked><label class="btn btn-outline-secondary" for="wo_t_s">Service</label>
                        <input type="radio" class="btn-check" name="type" id="wo_t_p" value="part"><label class="btn btn-outline-secondary" for="wo_t_p">Part</label>
                    </div>
                </div>
                <div class="col-12 col-md-9 wo-pick">
                    <input class="form-control form-control-sm" name="name" id="wo_item_name" maxlength="255" placeholder="Search the catalogue, or type a new item…" required>
                    <div class="wo-pick-list" id="wo_pick" hidden></div>
                </div>
                <div class="col-3 col-md-2"><label class="small ymo-muted">Qty</label><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="qty" value="1"></div>
                <div class="col-5 col-md-2"><label class="small ymo-muted">Rate &#8377;</label><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="rate" id="wo_rate" required></div>
                <div class="col-4 col-md-2"><label class="small ymo-muted">Disc %</label><input class="form-control form-control-sm" type="number" step="0.01" min="0" max="100" name="discount_pct" value="0"></div>
                <div class="col-4 col-md-2"><label class="small ymo-muted">GST %</label><input class="form-control form-control-sm" type="number" step="0.01" min="0" max="28" name="tax_pct" id="wo_tax" value="18"></div>
                <div class="col-8 col-md-4"><label class="small ymo-muted">GST</label>
                    <select class="form-select form-select-sm" name="tax_inclusive" id="wo_incl"><option value="1">Included in rate</option><option value="0">Added on top</option></select></div>
                <div class="col-8 col-md-6"><label class="small ymo-muted">Technician</label>
                    <select class="form-select form-select-sm" name="technician_id"><option value="">—</option>
                        <?php foreach ($tech_list as $a): ?><option value="<?= (int) $a['id']; ?>" <?= count($tech_ids) === 1 && $tech_ids[0] === (int) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-4 col-md-6 text-end"><button class="btn btn-sm btn-primary"><span class="mi mi-sm mi-leading">add</span>Add</button></div>
            <?= form_close(); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Photos -->
    <div class="ymo-card mb-3" id="photos">
        <div class="d-flex align-items-center mb-2">
            <h3 class="h6 mb-0">Photos <span class="ymo-muted">(<?= count($photos); ?>)</span></h3>
        </div>
        <?php if (!$photos): ?><p class="small ymo-muted">No photos yet. Technicians add them from their phone; you can add them here too.</p><?php endif; ?>
        <?php
        $by_kind = array();
        foreach ($photos as $p) { $by_kind[$p['kind']][] = $p; }
        foreach (Crm_work_order_model::PHOTO_KINDS as $k => $klabel): if (empty($by_kind[$k])) { continue; } ?>
            <div class="small fw-semibold mt-2 mb-1"><?= $klabel; ?></div>
            <div class="d-flex flex-wrap gap-2">
            <?php foreach ($by_kind[$k] as $p): ?>
                <div class="wo-photo">
                    <a href="<?= admin_url('job-cards/photo/'.(int) $p['id']); ?>" target="_blank" title="<?= html_escape(date('d M, H:i', strtotime($p['created_at'])).' · '.($p['tech_name'] ?: $p['admin_name'] ?: '')); ?>">
                        <img src="<?= admin_url('job-cards/photo/'.(int) $p['id']).'?t=1'; ?>" alt="<?= html_escape($klabel); ?>" loading="lazy" width="112" height="84">
                    </a>
                    <?php if ($edit): ?>
                    <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/photos/'.(int) $p['id'].'/delete'), array('onsubmit' => "return confirm('Remove this photo?');")); ?>
                        <button class="wo-photo-x" title="Remove">✕</button>
                    <?= form_close(); ?>
                    <?php endif; ?>
                    <div class="wo-photo-by"><?= html_escape($p['tech_name'] ?: $p['admin_name'] ?: ''); ?></div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!empty($can_edit)): ?>
        <?= form_open_multipart(admin_url('job-cards/'.(int) $wo['id'].'/photos'), array('class' => 'row g-2 align-items-end mt-2')); ?>
            <div class="col-sm-4"><label class="small ymo-muted">Kind</label>
                <select class="form-select form-select-sm" name="kind"><?php foreach (Crm_work_order_model::PHOTO_KINDS as $k => $klabel): ?><option value="<?= $k; ?>"><?= $klabel; ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-6"><label class="small ymo-muted">Photos</label><input class="form-control form-control-sm" type="file" name="photos[]" accept="image/*" multiple required></div>
            <div class="col-sm-2"><button class="btn btn-sm btn-outline-primary w-100">Upload</button></div>
        <?= form_close(); ?>
        <?php endif; ?>
    </div>

    <!-- Activity -->
    <div class="ymo-card mb-3" id="activity">
        <h3 class="h6">Activity</h3>
        <?php if (!empty($can_edit)): ?>
        <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/note'), array('class' => 'd-flex gap-2 mb-2')); ?>
            <input class="form-control form-control-sm" name="note" maxlength="500" placeholder="Add a note for the team (technicians see it on their phone)" required>
            <button class="btn btn-sm btn-outline-secondary">Add</button>
        <?= form_close(); ?>
        <?php endif; ?>
        <?php if (!$events): ?><p class="small ymo-muted mb-0">Nothing yet.</p><?php endif; ?>
        <ul class="list-unstyled small mb-0">
            <?php foreach ($events as $e): $is_note = in_array($e['type'], array('note', 'tech_note'), TRUE); ?>
                <li class="mb-1 <?= $is_note ? 'wo-note' : ''; ?>"><span class="ymo-muted"><?= html_escape(date('d M, H:i', strtotime($e['created_at']))); ?></span> · <?php if ($e['type'] === 'tech_note'): ?><span class="badge bg-warning text-dark">From workshop</span> <?php endif; ?><?= html_escape($e['detail']); ?><?php if (!empty($e['tech_name'])): ?> <span class="ymo-muted">— <?= html_escape($e['tech_name']); ?> (technician)</span><?php elseif (!empty($e['admin_name'])): ?> <span class="ymo-muted">— <?= html_escape($e['admin_name']); ?></span><?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="col-lg-4">
    <!-- Details -->
    <div class="ymo-card mb-3" id="details">
        <h3 class="h6">Job details</h3>
        <?= form_open(admin_url('job-cards/'.(int) $wo['id'].'/update')); ?>
        <fieldset <?= empty($can_edit) ? 'disabled' : ''; ?>>
            <div class="row g-2">
                <div class="col-6"><label class="small ymo-muted">Workshop</label>
                    <select class="form-select form-select-sm" name="workshop"><?php foreach ($workshops as $slug => $w): ?><option value="<?= html_escape($slug); ?>" <?= $wo['workshop'] === $slug ? 'selected' : ''; ?>><?= html_escape($w['label']); ?></option><?php endforeach; ?></select></div>
                <div class="col-6"><label class="small ymo-muted">Car came by</label>
                    <select class="form-select form-select-sm" name="booking_type">
                        <?php foreach (array('workshop' => 'Drive-in', 'pickup' => 'Our pick-up', 'doorstep' => 'Doorstep') as $k => $lbl): ?><option value="<?= $k; ?>" <?= $wo['booking_type'] === $k ? 'selected' : ''; ?>><?= $lbl; ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-6"><label class="small ymo-muted">Odometer (km)</label><input class="form-control form-control-sm" type="number" min="0" name="odometer_km" value="<?= html_escape((string) $wo['odometer_km']); ?>"></div>
                <div class="col-6"><label class="small ymo-muted">Fuel</label>
                    <select class="form-select form-select-sm" name="fuel_pct"><?php foreach ($fuel as $v => $lbl): ?><option value="<?= $v; ?>" <?= (string) $wo['fuel_pct'] === (string) $v ? 'selected' : ''; ?>><?= $lbl; ?></option><?php endforeach; ?></select></div>
                <div class="col-6"><label class="small ymo-muted">Service advisor</label>
                    <select class="form-select form-select-sm" name="advisor_id"><option value="">—</option><?php foreach ($admins as $a): ?><option value="<?= (int) $a['id']; ?>" <?= (int) $wo['advisor_id'] === (int) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option><?php endforeach; ?></select></div>
                <div class="col-6"><label class="small ymo-muted">Bay</label><input class="form-control form-control-sm" name="bay" maxlength="20" value="<?= html_escape($wo['bay']); ?>"></div>
                <div class="col-12"><label class="small ymo-muted">Technicians</label>
                    <div class="border rounded p-2" style="max-height:9rem;overflow:auto">
                        <?php foreach ($tech_list as $a): ?>
                            <label class="form-check small mb-0"><input class="form-check-input" type="checkbox" name="technicians[]" value="<?= (int) $a['id']; ?>" <?= in_array((int) $a['id'], $tech_ids, TRUE) ? 'checked' : ''; ?>> <span class="form-check-label"><?= html_escape($a['name']); ?><?php if ($a['workshop'] !== '' && isset($workshops[$a['workshop']])): ?> <span class="ymo-muted">· <?= html_escape($workshops[$a['workshop']]['label']); ?></span><?php endif; ?></span></label>
                        <?php endforeach; ?>
                        <?php if (!$tech_list): ?><span class="small ymo-muted">No technicians yet. <a href="<?= admin_url('technicians'); ?>">Add them</a> so they can use the phone screen.</span><?php endif; ?>
                    </div></div>
                <div class="col-12"><label class="small ymo-muted">Expected delivery</label><input class="form-control form-control-sm" type="datetime-local" name="expected_delivery_at" value="<?= $wo['expected_delivery_at'] ? date('Y-m-d\TH:i', strtotime($wo['expected_delivery_at'])) : ''; ?>"></div>
                <div class="col-12"><label class="small ymo-muted">Customer complaint</label><textarea class="form-control form-control-sm" name="complaint" rows="2" maxlength="3000"><?= html_escape((string) $wo['complaint']); ?></textarea></div>
                <div class="col-12"><label class="small ymo-muted">Workshop instructions</label><textarea class="form-control form-control-sm" name="instructions" rows="2" maxlength="3000" placeholder="For the technicians, e.g. keep old parts in the boot"><?= html_escape((string) $wo['instructions']); ?></textarea></div>
                <div class="col-12">
                    <a class="small" data-bs-toggle="collapse" href="#wo-lpo" role="button">Company / insurance LPO<?= $wo['lpo_number'] !== '' ? ': '.html_escape($wo['lpo_number']) : ''; ?></a>
                    <div class="collapse <?= $wo['lpo_number'] !== '' ? 'show' : ''; ?>" id="wo-lpo"><div class="row g-2 mt-1">
                        <div class="col-12"><input class="form-control form-control-sm" name="lpo_number" maxlength="40" placeholder="LPO number" value="<?= html_escape($wo['lpo_number']); ?>"></div>
                        <div class="col-6"><input class="form-control form-control-sm" type="date" name="lpo_date" value="<?= html_escape((string) $wo['lpo_date']); ?>"></div>
                        <div class="col-6"><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="lpo_amount" placeholder="Amount" value="<?= html_escape((string) $wo['lpo_amount']); ?>"></div>
                    </div></div>
                </div>
            </div>
            <?php if (!empty($can_edit)): ?><button class="btn btn-sm btn-primary mt-3">Save details</button><?php endif; ?>
        </fieldset>
        <?= form_close(); ?>
    </div>

    <!-- Car history -->
    <div class="ymo-card mb-3">
        <h3 class="h6">This car's earlier job cards</h3>
        <?php if (!$history): ?><p class="small ymo-muted mb-0">None in the CRM yet.</p><?php endif; ?>
        <ul class="list-unstyled small mb-0">
            <?php foreach ($history as $h): ?>
                <li class="mb-1"><a href="<?= admin_url('job-cards/'.(int) $h['id']); ?>"><?= html_escape($h['ro_number']); ?></a> · <?= html_escape(date('d M Y', strtotime($h['created_at']))); ?> · <?= $S[$h['status']] ?? $h['status']; ?> · <?= $money($h['grand_total']); ?><?= $h['odometer_km'] !== NULL ? ' · '.number_format((int) $h['odometer_km']).' km' : ''; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
</div>

<script>
(function () {
    // Status buttons: a reason for Returned / On hold; a confirm for Delivered.
    document.querySelectorAll('.wo-status-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            var to = f.getAttribute('data-to');
            if (to === 'returned' || to === 'on_hold') {
                var why = window.prompt(to === 'returned' ? 'Why is the car going back without work?' : 'Why is the job on hold? (e.g. waiting for parts, customer approval)');
                if (why === null) { e.preventDefault(); return; }
                f.querySelector('[name=note]').value = why;
            } else if (to === 'delivered') {
                if (!window.confirm(<?= json_encode(!empty($wo['booking_id']) ? 'Mark as delivered? The booking is marked Completed and the customer gets the usual message.' : 'Mark as delivered?'); ?>)) { e.preventDefault(); }
            }
        });
    });

    // Catalogue picker for "Add a line".
    var form = document.getElementById('wo-add');
    if (!form) { return; }
    var input = document.getElementById('wo_item_name'), list = document.getElementById('wo_pick'), itemId = document.getElementById('wo_item_id');
    var rate = document.getElementById('wo_rate'), tax = document.getElementById('wo_tax'), incl = document.getElementById('wo_incl');
    var url = <?= json_encode(admin_url('job-cards/catalog-search')); ?>, timer = null, items = [], active = -1;
    function type() { return form.querySelector('[name=type]:checked').value; }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function render() {
        if (!items.length) { list.hidden = true; return; }
        list.innerHTML = items.map(function (it, i) {
            var price = it.rate === null ? '<span class="text-muted">price on job</span>' : '&#8377; ' + it.rate.toLocaleString('en-IN');
            return '<button type="button" data-i="' + i + '" class="' + (i === active ? 'active' : '') + '"><strong>' + esc(it.name) + '</strong> <span class="float-end small">' + price + '</span>' + (it.code ? '<br><span class="small text-muted">' + esc(it.code) + '</span>' : '') + '</button>';
        }).join('');
        list.hidden = false;
    }
    function choose(i) {
        var it = items[i]; if (!it) { return; }
        input.value = it.name; itemId.value = it.id;
        if (it.rate !== null) { rate.value = it.rate; } else { rate.value = ''; rate.focus(); }
        if (it.tax_pct !== null) { tax.value = it.tax_pct; }
        incl.value = String(it.tax_inclusive);
        items = []; render();
        if (it.rate !== null) { form.querySelector('[name=qty]').focus(); }
    }
    function search() {
        var q = input.value.trim(); itemId.value = '';
        if (q.length < 2) { items = []; render(); return; }
        fetch(url + '?type=' + type() + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function (d) { items = d.items || []; active = -1; render(); }).catch(function () {});
    }
    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(search, 200); });
    input.addEventListener('keydown', function (e) {
        if (list.hidden) { return; }
        if (e.key === 'ArrowDown') { active = Math.min(items.length - 1, active + 1); render(); e.preventDefault(); }
        else if (e.key === 'ArrowUp') { active = Math.max(0, active - 1); render(); e.preventDefault(); }
        else if (e.key === 'Enter' && active >= 0) { choose(active); e.preventDefault(); }
        else if (e.key === 'Escape') { items = []; render(); }
    });
    list.addEventListener('mousedown', function (e) { var b = e.target.closest('button'); if (b) { e.preventDefault(); choose(+b.getAttribute('data-i')); } });
    input.addEventListener('blur', function () { setTimeout(function () { items = []; render(); }, 150); });
    form.querySelectorAll('[name=type]').forEach(function (r) { r.addEventListener('change', function () { itemId.value = ''; search(); }); });
})();
</script>
