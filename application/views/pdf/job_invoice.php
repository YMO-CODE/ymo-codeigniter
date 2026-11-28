<?php defined('BASEPATH') OR exit('No direct script access allowed');
$money = function ($n) { return number_format((float) $n, 2); };
$num = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); };
$inter = $inv['gst_type'] === 'inter';
// Tax summary by rate
$by_rate = array();
foreach ($lines as $l) {
    $k = $num($l['tax_pct']);
    if (!isset($by_rate[$k])) { $by_rate[$k] = array('taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0); }
    foreach (array('taxable', 'cgst', 'sgst', 'igst') as $f) { $by_rate[$k][$f] += (float) $l[$f]; }
}
ksort($by_rate, SORT_NUMERIC);
$cancelled = $inv['status'] === 'cancelled';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 18px 22px; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1d2026; margin: 0; }
  .title { text-align: center; font-size: 13px; font-weight: bold; letter-spacing: .08em; margin-bottom: 6px; }
  table { border-collapse: collapse; width: 100%; }
  .box td { border: 1px solid #9ca3af; padding: 5px 7px; vertical-align: top; }
  .seller-name { font-size: 13px; font-weight: bold; color: #2d6a4f; }
  .muted { color: #555; }
  .lbl { color: #555; font-size: 8.5px; text-transform: uppercase; letter-spacing: .04em; }
  .items th, .items td { border: 1px solid #9ca3af; padding: 4px 5px; }
  .items th { background: #eef3ef; font-size: 8px; text-transform: uppercase; }
  .r { text-align: right; white-space: nowrap; }
  .c { text-align: center; }
  .tot td { padding: 3px 6px; }
  .grand td { font-size: 11.5px; font-weight: bold; border-top: 1.5px solid #1d2026; }
  .pay td { border: 1px solid #9ca3af; padding: 6px 8px; vertical-align: top; }
  .stamp { position: absolute; top: 220px; left: 120px; font-size: 60px; color: rgba(192,57,43,.18); transform: rotate(-20deg); font-weight: bold; }
  .paid-stamp { color: rgba(45,106,79,.16); }
</style>
</head>
<body>
<?php if ($cancelled): ?><div class="stamp">CANCELLED</div><?php elseif ($inv['status'] === 'paid'): ?><div class="stamp paid-stamp">PAID</div><?php endif; ?>
<div class="title">TAX INVOICE</div>
<table class="box">
  <tr>
    <td style="width:58%">
      <div class="seller-name"><?= html_escape($seller['name'] ?? ''); ?></div>
      <?php if (!empty($seller['brand']) && ($seller['brand'] ?? '') !== ($seller['name'] ?? '')): ?><div><?= html_escape($seller['brand']); ?></div><?php endif; ?>
      <?php if (!empty($seller['address'])): ?><div class="muted"><?= nl2br(html_escape($seller['address'])); ?></div><?php endif; ?>
      <div class="muted">Phone <?= html_escape($seller['phone'] ?? ''); ?><?= !empty($seller['email']) ? ' · '.html_escape($seller['email']) : ''; ?></div>
      <div><strong>GSTIN: <?= html_escape($seller['gstin'] ?? ''); ?></strong> · State: <?= html_escape(($seller['state'] ?? '').(!empty($seller['state_code']) ? ' ('.$seller['state_code'].')' : '')); ?></div>
    </td>
    <td>
      <table>
        <tr><td style="border:0;padding:1px 0" class="lbl">Invoice no.</td><td style="border:0;padding:1px 0"><strong><?= html_escape($inv['invoice_number']); ?></strong></td></tr>
        <tr><td style="border:0;padding:1px 0" class="lbl">Date</td><td style="border:0;padding:1px 0"><?= html_escape(date('d M Y', strtotime($inv['invoice_date']))); ?></td></tr>
        <?php if ($ro !== ''): ?><tr><td style="border:0;padding:1px 0" class="lbl">Job card</td><td style="border:0;padding:1px 0"><?= html_escape($ro); ?></td></tr><?php endif; ?>
        <tr><td style="border:0;padding:1px 0" class="lbl">Place of supply</td><td style="border:0;padding:1px 0"><?= html_escape(($seller['state'] ?? '').(!empty($seller['state_code']) ? ' ('.$seller['state_code'].')' : '')); ?></td></tr>
        <tr><td style="border:0;padding:1px 0" class="lbl">Reverse charge</td><td style="border:0;padding:1px 0">No</td></tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <div class="lbl">Bill to</div>
      <div><strong><?= html_escape($inv['bill_name']); ?></strong> · <?= html_escape($inv['bill_mobile']); ?></div>
      <?php if ($inv['bill_address'] !== ''): ?><div class="muted"><?= nl2br(html_escape($inv['bill_address'])); ?></div><?php endif; ?>
      <?php if ($inv['bill_gstin'] !== ''): ?><div>GSTIN: <strong><?= html_escape($inv['bill_gstin']); ?></strong></div><?php endif; ?>
    </td>
    <td>
      <div class="lbl">Vehicle</div>
      <div><strong><?= html_escape($inv['vehicle']); ?></strong></div>
      <?php if ($inv['odometer_km'] !== NULL): ?><div class="muted">Odometer <?= number_format((int) $inv['odometer_km']); ?> km</div><?php endif; ?>
    </td>
  </tr>
</table>

<table class="items" style="margin-top:8px">
  <thead><tr>
    <th style="width:3%">#</th><th>Description</th><th style="width:8%">HSN/SAC</th><th class="r" style="width:5%">Qty</th><th class="r" style="width:9%">Rate</th><th class="r" style="width:5%">Disc</th>
    <th class="r" style="width:10%">Taxable</th><th class="r" style="width:5%">GST</th><th class="r" style="width:10%"><?= $inter ? 'IGST' : 'CGST+SGST'; ?></th><th class="r" style="width:10%">Amount</th>
  </tr></thead>
  <tbody>
  <?php foreach ($lines as $i => $l): ?>
    <tr>
      <td class="c"><?= $i + 1; ?></td>
      <td><?= html_escape($l['name']); ?></td>
      <td class="c"><?= html_escape($l['hsn_sac']); ?></td>
      <td class="r"><?= $num($l['qty']); ?></td>
      <td class="r"><?= $money($l['rate']); ?></td>
      <td class="r"><?= (float) $l['discount_pct'] > 0 ? $num($l['discount_pct']).'%' : '-'; ?></td>
      <td class="r"><?= $money($l['taxable']); ?></td>
      <td class="r"><?= $num($l['tax_pct']); ?>%</td>
      <td class="r"><?= $money((float) $l['cgst'] + (float) $l['sgst'] + (float) $l['igst']); ?></td>
      <td class="r"><?= $money($l['amount']); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<table style="margin-top:8px">
  <tr>
    <td style="width:56%;vertical-align:top;padding-right:10px">
      <table class="items">
        <thead><tr><th>GST rate</th><th class="r">Taxable</th><?php if ($inter): ?><th class="r">IGST</th><?php else: ?><th class="r">CGST</th><th class="r">SGST</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($by_rate as $rate => $t): ?>
          <tr><td><?= $rate; ?>%<?= !$inter && (float) $rate > 0 ? ' <span class="muted">('.$num($rate / 2).'% + '.$num($rate / 2).'%)</span>' : ''; ?></td><td class="r"><?= $money($t['taxable']); ?></td>
            <?php if ($inter): ?><td class="r"><?= $money($t['igst']); ?></td><?php else: ?><td class="r"><?= $money($t['cgst']); ?></td><td class="r"><?= $money($t['sgst']); ?></td><?php endif; ?></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div style="margin-top:6px"><span class="lbl">Amount in words</span><br><strong><?= html_escape($words); ?></strong></div>
    </td>
    <td style="vertical-align:top">
      <table class="tot">
        <tr><td class="muted">Taxable value</td><td class="r"><?= $money($inv['subtotal']); ?></td></tr>
        <?php if ($inter): ?>
        <tr><td class="muted">IGST</td><td class="r"><?= $money($inv['igst']); ?></td></tr>
        <?php else: ?>
        <tr><td class="muted">CGST</td><td class="r"><?= $money($inv['cgst']); ?></td></tr>
        <tr><td class="muted">SGST</td><td class="r"><?= $money($inv['sgst']); ?></td></tr>
        <?php endif; ?>
        <?php if ((float) $inv['round_off'] != 0): ?><tr><td class="muted">Round off</td><td class="r"><?= $money($inv['round_off']); ?></td></tr><?php endif; ?>
        <tr class="grand"><td>Total (Rs.)</td><td class="r"><?= $money($inv['grand_total']); ?></td></tr>
        <?php if ((float) $inv['paid'] > 0): ?>
        <tr><td class="muted">Received</td><td class="r"><?= $money($inv['paid']); ?></td></tr>
        <tr><td><strong>Balance due</strong></td><td class="r"><strong><?= $money($inv['balance']); ?></strong></td></tr>
        <?php endif; ?>
      </table>
    </td>
  </tr>
</table>

<?php if (!$cancelled && (float) $inv['balance'] > 0): ?>
<table class="pay" style="margin-top:10px">
  <tr>
    <?php if ($qr_png !== ''): ?>
    <td style="width:120px;text-align:center"><img src="<?= $qr_png; ?>" style="width:110px;height:110px" alt=""><div class="muted" style="font-size:8px">Scan with any UPI app</div></td>
    <?php endif; ?>
    <td>
      <div style="font-size:11px;font-weight:bold;margin-bottom:3px">Pay Rs. <?= $money($inv['balance']); ?></div>
      <?php if ($qr_png !== ''): ?><div>UPI ID: <strong><?= html_escape($settings['bill_upi_vpa']); ?></strong> (amount and invoice number fill in when you scan)</div><?php endif; ?>
      <div>Pay online: <a href="<?= html_escape($pay_url); ?>"><?= html_escape(preg_replace('#^https?://#', '', $pay_url)); ?></a></div>
      <?php if (trim($settings['bill_bank_text']) !== ''): ?><div style="margin-top:4px" class="muted"><?= nl2br(html_escape($settings['bill_bank_text'])); ?></div><?php endif; ?>
    </td>
  </tr>
</table>
<?php endif; ?>

<?php if ($payments): ?>
<div style="margin-top:6px" class="muted">Received: <?php foreach ($payments as $i => $p): ?><?= $i ? '; ' : ''; ?>Rs. <?= $money($p['amount']); ?> by <?= html_escape(Crm_billing_model::MODES[$p['mode']] ?? $p['mode']); ?> on <?= html_escape(date('d M Y', strtotime($p['paid_at']))); ?><?= $p['reference'] !== '' ? ' ('.html_escape($p['reference']).')' : ''; ?><?php endforeach; ?></div>
<?php endif; ?>

<table style="margin-top:12px">
  <tr>
    <td style="width:62%;vertical-align:top" class="muted">
      <?php if (trim($settings['bill_terms']) !== ''): ?><div class="lbl">Terms</div><div><?= nl2br(html_escape($settings['bill_terms'])); ?></div><?php endif; ?>
      <?php if ($inv['notes'] !== ''): ?><div style="margin-top:4px"><?= nl2br(html_escape($inv['notes'])); ?></div><?php endif; ?>
    </td>
    <td style="vertical-align:bottom;text-align:right">
      <div style="margin-bottom:28px">For <?= html_escape($seller['name'] ?? ''); ?></div>
      <div>Authorised signatory</div>
    </td>
  </tr>
</table>
<div class="muted" style="margin-top:10px;text-align:center;font-size:8px">This is a computer-generated invoice.<?= $cancelled ? ' Cancelled on '.html_escape(date('d M Y', strtotime($inv['cancelled_at']))).($inv['cancel_reason'] !== '' ? ': '.html_escape($inv['cancel_reason']) : '').'.' : ''; ?></div>
</body>
</html>
