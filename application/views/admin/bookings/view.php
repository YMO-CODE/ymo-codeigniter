<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('bookings'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>All bookings
</a>

<?php
$status_icons = array(
    'pending'     => 'schedule',
    'confirmed'   => 'event_available',
    'in_progress' => 'autorenew',
    'completed'   => 'check_circle',
    'cancelled'   => 'cancel',
);
?>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <div class="d-flex justify-content-between flex-wrap align-items-start mb-3">
                <div>
                    <h2 class="h4 mb-1"><span class="mi mi-leading">receipt_long</span><?= html_escape($booking['reference']); ?></h2>
                    <p class="ymo-muted small mb-0">Created <?= html_escape(date('d M Y, h:i A', strtotime($booking['created_at']))); ?></p>
                </div>
                <span class="badge-status s-<?= html_escape($booking['status']); ?>">
                    <span class="mi"><?= isset($status_icons[$booking['status']]) ? $status_icons[$booking['status']] : 'help'; ?></span>
                    <?= html_escape(str_replace('_',' ',$booking['status'])); ?>
                </span>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">person</span>Customer</h6>
                    <p class="mb-0"><strong><?= html_escape($booking['user_name']); ?></strong></p>
                    <p class="ymo-muted small mb-0"><?= html_escape($booking['user_mobile']); ?> · <?= html_escape($booking['user_email']); ?></p>
                    <a href="<?= admin_url('customers/'.$booking['user_id']); ?>" class="small">Customer profile <span class="mi mi-sm mi-trailing">arrow_forward</span></a>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">directions_car</span>Vehicle</h6>
                    <p class="mb-0"><?= html_escape($booking['vehicle_make']); ?> <?= html_escape($booking['vehicle_variant']); ?></p>
                    <p class="ymo-muted small font-monospace mb-0"><?= html_escape($booking['vehicle_number']); ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">build</span>Package</h6>
                    <p class="mb-0"><?= html_escape($booking['package_name']); ?> · &#8377; <?= number_format((float) $booking['package_price']); ?></p>
                </div>
                <?php if (!empty($booking['preferred_date'])): ?>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">event</span>Preferred date</h6>
                        <p class="mb-0"><?= html_escape(date('d M Y', strtotime($booking['preferred_date']))); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($booking['remarks'])): ?>
                    <div class="col-12">
                        <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">edit_note</span>Customer remarks</h6>
                        <p class="mb-0"><?= nl2br(html_escape($booking['remarks'])); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($booking['cancelled_reason'])): ?>
                    <div class="col-12">
                        <h6 class="text-muted small text-uppercase"><span class="mi mi-sm mi-leading">cancel</span>Cancellation reason</h6>
                        <p class="mb-0"><?= nl2br(html_escape($booking['cancelled_reason'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($reminders)): ?>
        <div class="md-card-elevated mt-3">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">notifications</span>Reminders</h6>
            <table class="ymo-table">
                <thead><tr><th>Type</th><th>Channel</th><th>Scheduled</th><th>Status</th><th>Sent</th></tr></thead>
                <tbody>
                <?php foreach ($reminders as $r): ?>
                    <tr>
                        <td class="small text-capitalize"><?= str_replace('_',' ',$r['type']); ?></td>
                        <td class="small text-uppercase"><?= html_escape($r['channel']); ?></td>
                        <td class="small"><?= html_escape(date('d M Y', strtotime($r['scheduled_at']))); ?></td>
                        <td class="small"><?= html_escape($r['status']); ?></td>
                        <td class="small"><?= $r['sent_at'] ? html_escape(date('d M', strtotime($r['sent_at']))) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($invoices)): ?>
        <div class="md-card-elevated mt-3">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">receipt</span>Service invoices</h6>
            <table class="ymo-table mb-0">
                <thead>
                    <tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Prepared by</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td class="small font-monospace"><?= html_escape($inv['invoice_number']); ?></td>
                        <td class="small"><?= html_escape(date('d M Y', strtotime($inv['created_at']))); ?></td>
                        <td class="small">&#8377; <?= number_format((float) $inv['grand_total'], 2); ?></td>
                        <td class="small"><?= html_escape($inv['created_by_name']); ?></td>
                        <td class="text-end">
                            <?php if (!empty($can_edit)): ?>
                                <a href="<?= admin_url('bookings/'.$booking['id'].'/invoice/'.$inv['id'].'/edit'); ?>" class="btn btn-sm btn-outline-secondary me-1">
                                    <span class="mi mi-sm mi-leading">edit</span>Edit
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($inv['pdf_path'])): ?>
                                <a href="<?= admin_url('bookings/'.$booking['id'].'/invoice/'.$inv['id'].'/pdf'); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <span class="mi mi-sm mi-leading">picture_as_pdf</span>PDF
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <?php if (!empty($can_edit)): ?>
        <div class="md-card-elevated mb-3">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">flag</span>Update status</h6>
            <?= form_open(admin_url('bookings/'.$booking['id'].'/status')); ?>
                <div class="form-floating mb-2">
                    <select id="bk_set_status" name="status" class="form-select">
                        <?php foreach (array('pending','confirmed','in_progress','completed','cancelled') as $s): ?>
                            <option value="<?= $s; ?>" <?= $booking['status'] === $s ? 'selected' : ''; ?>>
                                <?= ucwords(str_replace('_', ' ', $s)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="bk_set_status">Status</label>
                </div>
                <div class="form-floating mb-2">
                    <input type="text" class="form-control" id="bk_set_reason" name="cancel_reason" placeholder=" ">
                    <label for="bk_set_reason">Reason (only if cancelling)</label>
                </div>
                <button class="btn btn-primary w-100"><span class="mi mi-leading">save</span>Save</button>
            <?= form_close(); ?>
            <p class="ymo-muted small mt-2 mb-0">
                Marking complete auto-schedules a service-due reminder
                in <?= (int) $this->config->item('reminder_months'); ?> months.
            </p>
        </div>
        <?php endif; ?>

        <?php if (!empty($can_edit) && $booking['status'] !== 'cancelled'): ?>
        <div class="md-card-elevated mb-3" id="ymo-invoice-form">
            <h6 class="mb-2"><span class="mi mi-sm mi-leading">receipt_long</span>Service invoice</h6>
            <p class="ymo-muted small mb-3">Add work done and amounts. Customer receives SMS + email with PDF.</p>
            <?= form_open(admin_url('bookings/'.$booking['id'].'/invoice')); ?>
                <div id="ymo-invoice-lines" class="mb-2">
                    <div class="ymo-invoice-line row g-2 mb-2">
                        <div class="col-7">
                            <input type="text" class="form-control form-control-sm" name="line_description[]" placeholder="Work description" required>
                        </div>
                        <div class="col-4">
                            <input type="number" class="form-control form-control-sm ymo-line-amount" name="line_amount[]" placeholder="₹" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-1 d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ymo-remove-line" title="Remove" disabled>&times;</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="ymo-add-line">
                    <span class="mi mi-sm mi-leading">add</span>Add line
                </button>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">GST type</label>
                        <select class="form-select form-select-sm" name="gst_type" id="ymo-gst-type">
                            <option value="intra">Intra-state (CGST + SGST)</option>
                            <option value="inter">Inter-state (IGST)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">GST rate</label>
                        <select class="form-select form-select-sm" name="gst_rate" id="ymo-gst-rate">
                            <?php foreach (array(0, 5, 12, 18, 28) as $r): ?>
                                <option value="<?= $r; ?>" <?= $r === 18 ? 'selected' : ''; ?>><?= $r; ?>%</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="small mb-2 p-2 bg-light rounded" id="ymo-invoice-preview">
                    <div class="d-flex justify-content-between"><span class="ymo-muted">Subtotal</span><span id="ymo-prev-sub">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between ymo-prev-intra"><span class="ymo-muted">CGST</span><span id="ymo-prev-cgst">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between ymo-prev-intra"><span class="ymo-muted">SGST</span><span id="ymo-prev-sgst">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between ymo-prev-inter d-none"><span class="ymo-muted">IGST</span><span id="ymo-prev-igst">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between fw-semibold mt-1 pt-1 border-top"><span>Grand total</span><span id="ymo-prev-grand">₹ 0.00</span></div>
                </div>
                <div class="mb-2">
                    <textarea class="form-control form-control-sm" name="invoice_notes" rows="2" placeholder="Optional notes on invoice"></textarea>
                </div>
                <button class="btn btn-success w-100" data-confirm="Issue invoice and send to customer?">
                    <span class="mi mi-leading">send</span>Issue &amp; send invoice
                </button>
            <?= form_close(); ?>
        </div>
        <script>
        (function () {
            var container = document.getElementById('ymo-invoice-lines');
            if (!container) return;
            var tpl = container.querySelector('.ymo-invoice-line').cloneNode(true);

            function fmt(n) { return '₹ ' + n.toFixed(2); }

            function updatePreview() {
                var sub = 0;
                container.querySelectorAll('.ymo-line-amount').forEach(function (el) {
                    sub += parseFloat(el.value) || 0;
                });
                var type = document.getElementById('ymo-gst-type').value;
                var rate = parseFloat(document.getElementById('ymo-gst-rate').value) || 0;
                var tax = sub * rate / 100;
                var cgst = 0, sgst = 0, igst = 0;
                if (type === 'inter') {
                    igst = tax;
                } else {
                    cgst = tax / 2;
                    sgst = tax - cgst;
                }
                document.getElementById('ymo-prev-sub').textContent = fmt(sub);
                document.getElementById('ymo-prev-cgst').textContent = fmt(cgst);
                document.getElementById('ymo-prev-sgst').textContent = fmt(sgst);
                document.getElementById('ymo-prev-igst').textContent = fmt(igst);
                document.getElementById('ymo-prev-grand').textContent = fmt(sub + tax);
                document.querySelectorAll('.ymo-prev-intra').forEach(function (el) {
                    el.classList.toggle('d-none', type === 'inter');
                });
                document.querySelectorAll('.ymo-prev-inter').forEach(function (el) {
                    el.classList.toggle('d-none', type !== 'inter');
                });
            }

            function syncRemoveButtons() {
                var lines = container.querySelectorAll('.ymo-invoice-line');
                lines.forEach(function (line) {
                    var btn = line.querySelector('.ymo-remove-line');
                    if (btn) btn.disabled = lines.length <= 1;
                });
            }

            document.getElementById('ymo-add-line').addEventListener('click', function () {
                var row = tpl.cloneNode(true);
                row.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
                container.appendChild(row);
                syncRemoveButtons();
                updatePreview();
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('ymo-remove-line') && !e.target.disabled) {
                    e.target.closest('.ymo-invoice-line').remove();
                    syncRemoveButtons();
                    updatePreview();
                }
            });
            container.addEventListener('input', updatePreview);
            document.getElementById('ymo-gst-type').addEventListener('change', updatePreview);
            document.getElementById('ymo-gst-rate').addEventListener('change', updatePreview);
            updatePreview();
        })();
        </script>
        <?php elseif (!empty($can_edit) && $booking['status'] === 'cancelled'): ?>
        <div class="md-card-elevated mb-3">
            <p class="ymo-muted small mb-0">Invoices cannot be issued for cancelled bookings.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($can_edit)): ?>
        <div class="md-card-elevated">
            <h6 class="mb-2"><span class="mi mi-sm mi-leading">reviews</span>Manual review request</h6>
            <p class="ymo-muted small">
                Send a one-shot SMS + email asking the customer for a review.
                Available <?= (int) $this->config->item('reminder_review_days'); ?>+ days after completion.
            </p>
            <?= form_open(admin_url('bookings/'.$booking['id'].'/send-review')); ?>
                <button class="btn btn-outline-primary w-100" <?= $review_eligible ? '' : 'disabled'; ?>>
                    <span class="mi mi-leading">send</span>Send review request
                </button>
            <?= form_close(); ?>
            <?php if (!$review_eligible && $booking['status'] === 'completed'): ?>
                <p class="ymo-muted small mt-2 mb-0">Available shortly.</p>
            <?php elseif ($booking['status'] !== 'completed'): ?>
                <p class="ymo-muted small mt-2 mb-0">Booking must be completed first.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
