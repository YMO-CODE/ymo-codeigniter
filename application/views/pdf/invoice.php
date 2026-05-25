<?php defined('BASEPATH') OR exit('No direct script access allowed');
$inv = $invoice;
$fmt = function ($n) { return number_format((float) $n, 2); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1d2026; margin: 0; padding: 24px; }
        .header { margin-bottom: 24px; border-bottom: 2px solid #3a6f37; padding-bottom: 16px; }
        .header table { width: 100%; }
        .logo { max-height: 48px; }
        .brand { font-size: 18px; font-weight: bold; color: #3a6f37; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; font-size: 11px; }
        .section { margin-bottom: 18px; }
        .section-title { font-size: 10px; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; letter-spacing: 0.05em; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; }
        table.items th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        table.items td.amount { text-align: right; white-space: nowrap; }
        table.totals { width: 280px; margin-left: auto; margin-top: 16px; border-collapse: collapse; }
        table.totals td { padding: 4px 0; }
        table.totals td.label { text-align: right; padding-right: 12px; color: #6b7280; }
        table.totals td.val { text-align: right; font-weight: 500; }
        table.totals tr.grand td { border-top: 2px solid #1d2026; padding-top: 8px; font-size: 14px; font-weight: bold; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; }
        .notes { background: #f9fafb; padding: 10px; border-radius: 4px; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <table cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:60%; vertical-align:middle;">
                    <?php if (!empty($logo_src)): ?>
                        <img src="<?= $logo_src; ?>" class="logo" alt="">
                    <?php endif; ?>
                    <div class="brand"><?= html_escape($brand_name); ?></div>
                </td>
                <td style="text-align:right; vertical-align:middle;">
                    <h1>TAX INVOICE</h1>
                    <div class="muted"><?= html_escape($inv['invoice_number']); ?></div>
                    <div class="muted">Date: <?= html_escape(date('d M Y', strtotime($inv['created_at']))); ?></div>
                    <div class="muted">Booking: <?= html_escape($inv['booking_reference']); ?></div>
                </td>
            </tr>
        </table>
    </div>

    <table cellpadding="0" cellspacing="0" style="width:100%; margin-bottom:20px;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div class="section-title">Bill to</div>
                <strong><?= html_escape($inv['user_name']); ?></strong><br>
                <span class="muted"><?= html_escape($inv['user_mobile']); ?></span><br>
                <?php if (!empty($inv['user_email'])): ?>
                    <span class="muted"><?= html_escape($inv['user_email']); ?></span>
                <?php endif; ?>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div class="section-title">Vehicle &amp; service</div>
                <?= html_escape(trim($inv['vehicle_make'].' '.$inv['vehicle_variant'])); ?><br>
                <span class="muted font-monospace"><?= html_escape($inv['vehicle_number']); ?></span><br>
                <span class="muted"><?= html_escape($inv['package_name']); ?></span>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Work performed</div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width:8%;">#</th>
                    <th>Description</th>
                    <th style="width:22%; text-align:right;">Amount (&#8377;)</th>
                </tr>
            </thead>
            <tbody>
            <?php $n = 1; foreach ($inv['lines'] as $line): ?>
                <tr>
                    <td><?= $n++; ?></td>
                    <td><?= html_escape($line['description']); ?></td>
                    <td class="amount"><?= $fmt($line['amount']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="label">Subtotal</td>
                <td class="val">&#8377; <?= $fmt($inv['subtotal']); ?></td>
            </tr>
            <?php if ($inv['gst_type'] === 'intra' && (float) $inv['gst_rate'] > 0): ?>
                <tr>
                    <td class="label">CGST (<?= $fmt((float) $inv['gst_rate'] / 2); ?>%)</td>
                    <td class="val">&#8377; <?= $fmt($inv['cgst_amount']); ?></td>
                </tr>
                <tr>
                    <td class="label">SGST (<?= $fmt((float) $inv['gst_rate'] / 2); ?>%)</td>
                    <td class="val">&#8377; <?= $fmt($inv['sgst_amount']); ?></td>
                </tr>
            <?php elseif ($inv['gst_type'] === 'inter' && (float) $inv['gst_rate'] > 0): ?>
                <tr>
                    <td class="label">IGST (<?= $fmt($inv['gst_rate']); ?>%)</td>
                    <td class="val">&#8377; <?= $fmt($inv['igst_amount']); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="grand">
                <td class="label">Grand total</td>
                <td class="val">&#8377; <?= $fmt($inv['grand_total']); ?></td>
            </tr>
        </table>
    </div>

    <?php if (!empty($inv['notes'])): ?>
        <div class="notes">
            <strong>Notes:</strong><br>
            <?= nl2br(html_escape($inv['notes'])); ?>
        </div>
    <?php endif; ?>

    <div class="footer">
        Prepared by: <strong><?= html_escape($inv['created_by_name']); ?></strong><br>
        This is a computer-generated invoice from <?= html_escape($brand_name); ?>.
    </div>
</body>
</html>
