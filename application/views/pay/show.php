<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return Work_order_customer_money::fmt($n); };
$due = (float) $inv['balance'];
$st = $inv['status'];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2d6a4f">
<title>Pay invoice <?= html_escape($inv['invoice_number']); ?> · <?= html_escape($seller['brand'] ?? 'Your Mechanic Online'); ?></title>
<style>
:root { --g: #2d6a4f; --bg: #f4f6f5; --mut: #5f6b66; }
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: #14211b; font: 16px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
.wrap { max-width: 460px; margin: 0 auto; padding: 18px 16px 40px; }
.brand { color: var(--g); font-weight: 800; font-size: 18px; }
.card { background: #fff; border-radius: 16px; padding: 18px; margin-top: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.07); }
.mut { color: var(--mut); font-size: 14px; }
.amt { font-size: 34px; font-weight: 800; margin: 4px 0; }
.row { display: flex; justify-content: space-between; gap: 10px; padding: 5px 0; font-size: 15px; }
.btn { display: block; width: 100%; text-align: center; padding: 16px; border-radius: 14px; font-weight: 800; font-size: 18px; text-decoration: none; margin-top: 12px; }
.b-pay { background: var(--g); color: #fff; }
.b-sec { background: #fff; color: var(--g); border: 2px solid var(--g); font-size: 16px; padding: 12px; }
.qr { text-align: center; margin-top: 10px; }
.qr svg { max-width: 100%; height: auto; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 99px; font-weight: 700; font-size: 13px; }
.paid { background: #d1fae5; color: #065f46; } .part { background: #fef3c7; color: #92400e; } .cx { background: #e5e7eb; color: #374151; }
.bank { white-space: pre-line; font-size: 14px; background: #f7f8f7; border-radius: 10px; padding: 10px; margin-top: 8px; }
.copy { border: 0; background: none; color: var(--g); font-weight: 700; padding: 0 4px; cursor: pointer; font-size: 14px; }
@media (min-width: 700px) { .only-phone { display: none; } }
</style>
</head>
<body>
<div class="wrap">
  <div class="brand"><?= html_escape($seller['brand'] ?? 'Your Mechanic Online'); ?></div>
  <div class="mut"><?= html_escape($seller['name'] ?? ''); ?><?= !empty($seller['gstin']) ? ' · GSTIN '.html_escape($seller['gstin']) : ''; ?></div>

  <div class="card">
    <?php if ($st === 'cancelled'): ?>
      <span class="badge cx">Cancelled</span>
      <p>This invoice was cancelled. Please use the latest invoice we sent you, or message us on WhatsApp.</p>
    <?php elseif ($st === 'paid'): ?>
      <span class="badge paid">Paid · thank you!</span>
      <div class="amt"><?= $fmt($inv['grand_total']); ?></div>
    <?php else: ?>
      <div class="mut">Amount to pay</div>
      <div class="amt"><?= $fmt($due); ?></div>
      <?php if ($st === 'partly_paid'): ?><span class="badge part"><?= $fmt($inv['paid']); ?> already received</span><?php endif; ?>
    <?php endif; ?>
    <div style="margin-top:10px">
      <div class="row"><span class="mut">Invoice</span><strong><?= html_escape($inv['invoice_number']); ?></strong></div>
      <div class="row"><span class="mut">Date</span><span><?= html_escape(date('d M Y', strtotime($inv['invoice_date']))); ?></span></div>
      <div class="row"><span class="mut">Car</span><span><?= html_escape($inv['vehicle']); ?></span></div>
      <div class="row"><span class="mut">Name</span><span><?= html_escape($inv['bill_name']); ?></span></div>
      <div class="row"><span class="mut">Invoice total</span><span><?= $fmt($inv['grand_total']); ?></span></div>
    </div>
    <a class="btn b-sec" href="<?= html_escape($pdf_url); ?>" target="_blank" rel="noopener">View / download invoice (PDF)</a>
  </div>

  <?php if ($st !== 'cancelled' && $st !== 'paid' && $due > 0): ?>
  <div class="card">
    <?php if ($upi_link !== ''): ?>
      <a class="btn b-pay only-phone" href="<?= html_escape($upi_link); ?>">Pay <?= $fmt($due); ?> with UPI app</a>
      <div class="mut only-phone" style="text-align:center;margin-top:6px">Opens GPay, PhonePe, Paytm or your bank app with the amount filled in.</div>
      <div class="qr"><?= $qr_svg; ?></div>
      <div class="mut" style="text-align:center">Or scan this QR from any UPI app</div>
      <div style="text-align:center;margin-top:6px;font-size:15px">UPI ID: <strong id="vpa"><?= html_escape($vpa); ?></strong><button class="copy" type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('vpa').textContent);this.textContent='Copied'">Copy</button></div>
    <?php endif; ?>
    <?php if (trim((string) $bank) !== ''): ?>
      <div class="mut" style="margin-top:12px">Bank transfer (NEFT / IMPS)</div>
      <div class="bank"><?= html_escape($bank); ?></div>
    <?php endif; ?>
    <?php if ($wa_link !== ''): ?>
      <a class="btn b-sec" href="<?= html_escape($wa_link); ?>" target="_blank" rel="noopener">Paid? Send us the screenshot on WhatsApp</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <p class="mut" style="text-align:center;margin-top:18px">Questions? Call <?= html_escape($seller['phone'] ?? ''); ?></p>
</div>
</body>
</html>
