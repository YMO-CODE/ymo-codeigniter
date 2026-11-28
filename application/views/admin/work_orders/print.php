<?php defined('BASEPATH') OR exit('No direct script access allowed');
$biz_file = APPPATH.'config/marketing_business.php';
$biz = is_file($biz_file) ? (require $biz_file) : array();
$biz = is_array($biz) ? $biz : array();
$brand = $biz['brand_name'] ?? 'Your Mechanic Online';
$S = Crm_work_order_model::STATUSES;
$ws = ($wo['workshop'] !== '' && isset($workshops[$wo['workshop']])) ? $workshops[$wo['workshop']]['label'] : '';
$money = function ($v) { return number_format((float) $v, 2); };
$num = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); };
$fuel = array(0 => 'Empty', 25 => '1/4', 50 => '1/2', 75 => '3/4', 100 => 'Full');
$by_type = array('service' => array(), 'part' => array());
foreach ($lines as $l) { if ($l['approval'] !== 'declined') { $by_type[$l['type']][] = $l; } }
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Job card <?= html_escape($wo['ro_number']); ?> · <?= html_escape($wo['vehicle_number']); ?></title>
<style>
  @page { size: A4; margin: 12mm; }
  * { box-sizing: border-box; }
  body { font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #111; font-size: 12px; margin: 0; background: #f2f2f2; }
  .sheet { background: #fff; max-width: 210mm; margin: 12px auto; padding: 14mm; }
  h1 { font-size: 18px; margin: 0; }
  .muted { color: #555; }
  .top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2d6a4f; padding-bottom: 8px; margin-bottom: 10px; }
  .ro { text-align: right; }
  .ro .n { font-size: 20px; font-weight: 700; color: #2d6a4f; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 18px; margin-bottom: 10px; }
  .grid div span { color: #555; display: inline-block; min-width: 92px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  th, td { border: 1px solid #ccc; padding: 5px 6px; vertical-align: top; }
  th { background: #f1f5f2; text-align: left; font-size: 11px; }
  td.r, th.r { text-align: right; white-space: nowrap; }
  .box { border: 1px solid #ccc; padding: 6px 8px; min-height: 38px; margin-bottom: 8px; white-space: pre-wrap; }
  .tot { width: 45%; margin-left: auto; }
  .tot td { border: 0; padding: 2px 6px; }
  .sign { display: flex; justify-content: space-between; gap: 24px; margin-top: 28px; }
  .sign div { flex: 1; border-top: 1px solid #333; padding-top: 4px; text-align: center; }
  .bar { max-width: 210mm; margin: 12px auto 0; text-align: right; }
  .bar button { padding: 8px 16px; font-size: 14px; }
  @media print { body { background: #fff; } .sheet { margin: 0; padding: 0; max-width: none; } .bar { display: none; } }
</style>
</head>
<body>
<div class="bar"><button onclick="window.print()">Print</button></div>
<div class="sheet">
  <div class="top">
    <div>
      <h1><?= html_escape($brand); ?></h1>
      <div class="muted"><?= html_escape($biz['legal_entity'] ?? ''); ?></div>
      <div class="muted"><?= $ws !== '' ? html_escape($ws).' workshop · ' : ''; ?>Calls <?= html_escape($biz['phone_display'] ?? ''); ?> · WhatsApp <?= html_escape($biz['whatsapp_display'] ?? ''); ?></div>
    </div>
    <div class="ro">
      <div class="muted">JOB CARD</div>
      <div class="n"><?= html_escape($wo['ro_number']); ?></div>
      <div class="muted"><?= html_escape(date('d M Y, H:i', strtotime($wo['created_at']))); ?></div>
      <div class="muted">Status: <?= $S[$wo['status']] ?? $wo['status']; ?></div>
    </div>
  </div>

  <div class="grid">
    <div><span>Customer</span><strong><?= html_escape($wo['customer_name']); ?></strong></div>
    <div><span>Mobile</span><?= html_escape($wo['customer_mobile']); ?></div>
    <div><span>Car</span><strong><?= html_escape($wo['vehicle_number']); ?></strong> · <?= html_escape(trim($wo['vehicle_make'].' '.$wo['vehicle_variant'])); ?></div>
    <div><span>Odometer</span><?= $wo['odometer_km'] !== NULL ? number_format((int) $wo['odometer_km']).' km' : '—'; ?> &nbsp; <span style="min-width:0">Fuel</span> <?= $wo['fuel_pct'] !== NULL ? html_escape($fuel[(int) $wo['fuel_pct']] ?? ((int) $wo['fuel_pct'].'%')) : '—'; ?></div>
    <div><span>Advisor</span><?= html_escape($wo['advisor_name'] ?: '—'); ?></div>
    <div><span>Technicians</span><?= html_escape($techs ? implode(', ', array_column($techs, 'name')) : '—'); ?></div>
    <div><span>Expected by</span><?= $wo['expected_delivery_at'] ? html_escape(date('d M Y, H:i', strtotime($wo['expected_delivery_at']))) : '—'; ?></div>
    <div><span>Booking</span><?= html_escape($wo['booking_reference'] ?: '—'); ?><?= $wo['bay'] !== '' ? ' &nbsp; <span style="min-width:0">Bay</span> '.html_escape($wo['bay']) : ''; ?></div>
    <?php if ($wo['lpo_number'] !== ''): ?><div><span>LPO</span><?= html_escape($wo['lpo_number']); ?><?= $wo['lpo_date'] ? ' · '.html_escape(date('d M Y', strtotime($wo['lpo_date']))) : ''; ?></div><?php endif; ?>
  </div>

  <div class="muted">Customer complaint</div>
  <div class="box"><?= html_escape((string) $wo['complaint']); ?></div>

  <?php foreach (array('service' => 'Services / labour', 'part' => 'Spare parts') as $type => $title): if (!$by_type[$type]) { continue; } ?>
  <table>
    <thead><tr><th style="width:4%">#</th><th><?= $title; ?></th><th class="r" style="width:8%">Qty</th><th class="r" style="width:13%">Rate</th><th class="r" style="width:12%">GST</th><th class="r" style="width:14%">Amount</th><th style="width:7%">Done</th></tr></thead>
    <tbody>
    <?php $i = 0; foreach ($by_type[$type] as $l): $i++; ?>
      <tr>
        <td><?= $i; ?></td>
        <td><?= html_escape($l['name']); ?><?php if ($l['approval'] === 'pending'): ?> <em class="muted">(waiting for customer OK)</em><?php endif; ?><?php if ((float) $l['discount_pct'] > 0): ?> <span class="muted">(<?= $num($l['discount_pct']); ?>% off)</span><?php endif; ?></td>
        <td class="r"><?= $num($l['qty']); ?></td>
        <td class="r"><?= $money($l['rate']); ?></td>
        <td class="r"><?= (float) $l['tax_pct'] > 0 ? $num($l['tax_pct']).'% '.((int) $l['tax_inclusive'] ? 'incl' : 'extra') : '—'; ?></td>
        <td class="r"><?= $money($l['amount']); ?></td>
        <td><?= !empty($l['done_at']) ? '✓' : ''; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endforeach; ?>

  <table class="tot">
    <tr><td class="muted">Taxable value</td><td class="r">&#8377; <?= $money($wo['subtotal']); ?></td></tr>
    <tr><td class="muted">GST</td><td class="r">&#8377; <?= $money($wo['tax_total']); ?></td></tr>
    <tr><td><strong>Estimated total</strong></td><td class="r"><strong>&#8377; <?= $money($wo['grand_total']); ?></strong></td></tr>
  </table>

  <?php if (!empty($wo['instructions'])): ?>
  <div class="muted">Workshop instructions</div>
  <div class="box"><?= html_escape((string) $wo['instructions']); ?></div>
  <?php endif; ?>

  <p class="muted" style="margin-top:10px">This job card is not a tax invoice. Any extra work found during service is done only after your approval.</p>

  <div class="sign">
    <div>Customer signature</div>
    <div>Service advisor</div>
  </div>
</div>
</body>
</html>
