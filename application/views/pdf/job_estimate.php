<?php defined('BASEPATH') OR exit('No direct script access allowed');
$money = function ($n) { return 'Rs. '.number_format((float) $n, 2); };
$num = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); };
$brand = $biz['brand_name'] ?? 'Your Mechanic Online';
$est_ids = array_map('intval', array_column($lines, 'id'));
$others = array_values(array_filter($all, function ($l) use ($est_ids) { return !in_array((int) $l['id'], $est_ids, TRUE); }));
$sum = function (array $ls, $k) { return array_sum(array_map(function ($l) use ($k) { return (float) $l[$k]; }, $ls)); };
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1d2026; margin: 0; padding: 22px 26px; }
  .top { width: 100%; border-bottom: 2px solid #2d6a4f; padding-bottom: 10px; margin-bottom: 12px; }
  .brand { font-size: 17px; font-weight: bold; color: #2d6a4f; }
  .muted { color: #6b7280; }
  .ro { text-align: right; }
  .ro .t { font-size: 15px; font-weight: bold; }
  .grid { width: 100%; margin-bottom: 10px; }
  .grid td { padding: 2px 0; vertical-align: top; }
  .label { color: #6b7280; width: 90px; }
  h3 { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin: 14px 0 4px; }
  table.items { width: 100%; border-collapse: collapse; }
  table.items th, table.items td { border: 1px solid #e5e7eb; padding: 6px 7px; text-align: left; }
  table.items th { background: #f1f5f2; font-size: 9.5px; text-transform: uppercase; }
  .r { text-align: right; white-space: nowrap; }
  .tot { width: 300px; margin-left: auto; margin-top: 8px; border-collapse: collapse; }
  .tot td { padding: 3px 0; }
  .tot .big td { border-top: 2px solid #1d2026; font-size: 13px; font-weight: bold; padding-top: 6px; }
  .note { background: #fff8e1; padding: 8px 10px; margin: 10px 0; }
  .how { margin-top: 18px; padding: 10px; border: 1px solid #cfe3d6; background: #f3faf5; }
  .foot { margin-top: 16px; font-size: 9.5px; color: #6b7280; }
</style>
</head>
<body>
<table class="top" cellpadding="0" cellspacing="0"><tr>
  <td>
    <div class="brand"><?= html_escape($brand); ?></div>
    <div class="muted"><?= html_escape($biz['legal_entity'] ?? ''); ?></div>
    <div class="muted"><?= $workshop !== '' ? html_escape($workshop).' workshop · ' : ''; ?>Calls <?= html_escape($biz['phone_display'] ?? ''); ?> · WhatsApp <?= html_escape($biz['whatsapp_display'] ?? ''); ?></div>
  </td>
  <td class="ro">
    <div class="t">ESTIMATE #<?= (int) $est['number']; ?></div>
    <div>Job card <?= html_escape($wo['ro_number']); ?></div>
    <div class="muted"><?= html_escape(date('d M Y, h:i A', strtotime($est['sent_at']))); ?></div>
  </td>
</tr></table>

<table class="grid" cellpadding="0" cellspacing="0">
  <tr><td class="label">Customer</td><td><strong><?= html_escape($wo['customer_name']); ?></strong> · <?= html_escape($wo['customer_mobile']); ?></td>
      <td class="label">Car</td><td><strong><?= html_escape($wo['vehicle_number']); ?></strong> · <?= html_escape(trim($wo['vehicle_make'].' '.$wo['vehicle_variant'])); ?></td></tr>
  <?php if ($wo['odometer_km'] !== NULL || !empty($wo['advisor_name'])): ?>
  <tr><td class="label">Odometer</td><td><?= $wo['odometer_km'] !== NULL ? number_format((int) $wo['odometer_km']).' km' : '-'; ?></td>
      <td class="label">Advisor</td><td><?= html_escape($wo['advisor_name'] ?: '-'); ?></td></tr>
  <?php endif; ?>
</table>

<?php if (trim($note) !== ''): ?><div class="note"><?= nl2br(html_escape($note)); ?></div><?php endif; ?>

<h3><?= $est['scope'] === 'all' ? 'Work and parts' : 'Additional work for your approval'; ?></h3>
<table class="items">
  <thead><tr><th style="width:4%">#</th><th>Item</th><th class="r" style="width:8%">Qty</th><th class="r" style="width:15%">Rate</th><th class="r" style="width:12%">GST</th><th class="r" style="width:16%">Amount</th></tr></thead>
  <tbody>
  <?php $i = 0; foreach ($lines as $l): $i++; ?>
    <tr>
      <td><?= $i; ?></td>
      <td><?= html_escape($l['name']); ?> <span class="muted">(<?= $l['type'] === 'part' ? 'part' : 'labour'; ?>)</span><?php if ((float) $l['discount_pct'] > 0): ?> <span class="muted"><?= $num($l['discount_pct']); ?>% off</span><?php endif; ?></td>
      <td class="r"><?= $num($l['qty']); ?></td>
      <td class="r"><?= $money($l['rate']); ?></td>
      <td class="r"><?= (float) $l['tax_pct'] > 0 ? $num($l['tax_pct']).'% '.((int) $l['tax_inclusive'] ? 'incl.' : '') : '-'; ?></td>
      <td class="r"><?= $money($l['amount']); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<table class="tot">
  <tr><td class="muted">Taxable value</td><td class="r"><?= $money($sum($lines, 'taxable')); ?></td></tr>
  <tr><td class="muted">GST</td><td class="r"><?= $money($sum($lines, 'tax_amount')); ?></td></tr>
  <tr class="big"><td><?= $est['scope'] === 'all' ? 'Estimated total' : 'This estimate'; ?></td><td class="r"><?= $money($sum($lines, 'amount')); ?></td></tr>
  <?php if ($others): ?>
  <tr><td class="muted">Work already agreed</td><td class="r"><?= $money($sum($others, 'amount')); ?></td></tr>
  <tr><td><strong>Job total with this</strong></td><td class="r"><strong><?= $money($sum($all, 'amount')); ?></strong></td></tr>
  <?php endif; ?>
</table>

<?php if ($est['scope'] === 'all'): ?>
<div class="how"><strong>To confirm:</strong> tap <strong>Approve</strong> under this message on WhatsApp. Tap <strong>Call me</strong> and our service advisor will explain each item.</div>
<?php else: ?>
<div class="how"><strong>To go ahead:</strong> tap <strong>Approve</strong> under this message on WhatsApp. Tap <strong>Call me</strong> and our service advisor will explain each item. This additional work is done only after you approve it.</div>
<?php endif; ?>

<div class="foot">This is an estimate, not a tax invoice. Prices of parts can change slightly with availability; we will tell you before any change. Final bill is issued at delivery.</div>
</body>
</html>
