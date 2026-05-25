<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('bookings/'.$booking['id']); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>Booking <?= html_escape($booking['reference']); ?>
</a>

<div class="row justify-content-center mt-2">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <h2 class="h4 mb-1"><span class="mi mi-leading">edit</span>Edit invoice</h2>
            <p class="ymo-muted small mb-4">
                <?= html_escape($invoice['invoice_number']); ?> ·
                originally prepared by <?= html_escape($invoice['created_by_name']); ?> on
                <?= html_escape(date('d M Y', strtotime($invoice['created_at']))); ?>
            </p>

            <?= form_open(admin_url('bookings/'.$booking['id'].'/invoice/'.$invoice['id'].'/edit')); ?>
                <div id="ymo-invoice-lines" class="mb-2">
                    <?php foreach ($invoice['lines'] as $line): ?>
                    <div class="ymo-invoice-line row g-2 mb-2">
                        <div class="col-7">
                            <input type="text" class="form-control form-control-sm" name="line_description[]"
                                   placeholder="Work description" required
                                   value="<?= html_escape($line['description']); ?>">
                        </div>
                        <div class="col-4">
                            <input type="number" class="form-control form-control-sm ymo-line-amount" name="line_amount[]"
                                   placeholder="₹" min="0.01" step="0.01" required
                                   value="<?= html_escape(number_format((float) $line['amount'], 2, '.', '')); ?>">
                        </div>
                        <div class="col-1 d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ymo-remove-line" title="Remove">&times;</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="ymo-add-line">
                    <span class="mi mi-sm mi-leading">add</span>Add line
                </button>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">GST type</label>
                        <select class="form-select form-select-sm" name="gst_type" id="ymo-gst-type">
                            <option value="intra" <?= $invoice['gst_type'] === 'intra' ? 'selected' : ''; ?>>Intra-state (CGST + SGST)</option>
                            <option value="inter" <?= $invoice['gst_type'] === 'inter' ? 'selected' : ''; ?>>Inter-state (IGST)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">GST rate</label>
                        <select class="form-select form-select-sm" name="gst_rate" id="ymo-gst-rate">
                            <?php foreach (array(0, 5, 12, 18, 28) as $r): ?>
                                <option value="<?= $r; ?>" <?= (float) $invoice['gst_rate'] == $r ? 'selected' : ''; ?>><?= $r; ?>%</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="small mb-3 p-2 bg-light rounded" id="ymo-invoice-preview">
                    <div class="d-flex justify-content-between"><span class="ymo-muted">Subtotal</span><span id="ymo-prev-sub">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between ymo-prev-intra"><span class="ymo-muted">CGST</span><span id="ymo-prev-cgst">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between ymo-prev-intra"><span class="ymo-muted">SGST</span><span id="ymo-prev-sgst">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between ymo-prev-inter d-none"><span class="ymo-muted">IGST</span><span id="ymo-prev-igst">₹ 0.00</span></div>
                    <div class="d-flex justify-content-between fw-semibold mt-1 pt-1 border-top"><span>Grand total</span><span id="ymo-prev-grand">₹ 0.00</span></div>
                </div>

                <div class="mb-3">
                    <textarea class="form-control form-control-sm" name="invoice_notes" rows="2" placeholder="Optional notes on invoice"><?= html_escape($invoice['notes'] ?? ''); ?></textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="resend_notify" value="1" id="ymo-resend-notify" checked>
                    <label class="form-check-label small" for="ymo-resend-notify">
                        Send updated invoice to customer (SMS + email with PDF)
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary">
                        <span class="mi mi-sm mi-leading">save</span>Save changes
                    </button>
                    <a href="<?= admin_url('bookings/'.$booking['id']); ?>" class="btn btn-link">Cancel</a>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
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
    syncRemoveButtons();
    updatePreview();
})();
</script>
