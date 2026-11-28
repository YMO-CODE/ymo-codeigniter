<?php defined('BASEPATH') OR exit('No direct script access allowed');
$label = function ($k) { return ucfirst(str_replace('_', ' ', (string) $k)); };
// Pull the parts worth reading: list fields first, then the detail record.
$parts = array();
if (is_array($data) && (isset($data['list']) || isset($data['detail']) || isset($data['payment']))) {
    foreach (array('list', 'detail', 'payment') as $k) {
        if (!empty($data[$k]) && is_array($data[$k])) {
            $parts[] = $data[$k];
        }
    }
} elseif (is_array($data)) {
    $parts[] = $data;
}
$scalars = array();
$tables = array();
foreach ($parts as $p) {
    foreach ($p as $k => $v) {
        if (is_array($v)) {
            if ($v && isset($v[0]) && is_array($v[0])) {
                $tables[$k] = $v;
            } elseif ($v && !isset($v[0])) {
                foreach ($v as $k2 => $v2) {
                    if (!is_array($v2) && trim((string) $v2) !== '' && !preg_match('#^https?://#', (string) $v2)) {
                        $scalars[$k.' · '.$k2] = $v2;
                    }
                }
            }
        } elseif (trim((string) $v) !== '' && !preg_match('#^https?://#', (string) $v) && !isset($scalars[$k])) {
            $scalars[$k] = $v;
        }
    }
}
$cols = array(
    'labour_list'       => array('labour_name' => 'Service', 'qty' => 'Qty', 'rate' => 'Rate', 'discount' => 'Disc %', 'tax' => 'GST %', 'total_price' => 'Amount'),
    'spare_part_list'   => array('item_name' => 'Part', 'qty' => 'Qty', 'sale_price' => 'Rate', 'discount' => 'Disc %', 'item_tax' => 'GST %', 'total_price' => 'Amount'),
    'item_list'         => array('item_name' => 'Item', 'qty' => 'Qty', 'purchase_price' => 'Rate', 'total_price' => 'Amount'),
    'transaction_list'  => array('created' => 'Date', 'amount' => 'Amount', 'payment_mode' => 'Mode', 'account_name' => 'Account'),
    'payment_transaction_list' => array('created' => 'Date', 'amount' => 'Amount', 'payment_mode' => 'Mode', 'account_name' => 'Account'),
);
?>
<div class="mb-3"><a href="<?= admin_url('okmechanic-archive?t='.urlencode($r['entity'])); ?>" class="small"><span class="mi mi-sm">arrow_back</span> <?= html_escape($types[$r['entity']] ?? 'Archive'); ?></a></div>

<div class="ymo-card mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-baseline">
        <h2 class="h5 mb-0"><?= html_escape($r['number'] !== '' ? $r['number'] : $r['title']); ?></h2>
        <?php if ($r['status'] !== ''): ?><span class="badge bg-secondary"><?= html_escape($r['status']); ?></span><?php endif; ?>
        <?php if ($r['amount'] !== NULL): ?><span class="ms-auto h5 mb-0">₹<?= number_format((float) $r['amount'], 2); ?></span><?php endif; ?>
    </div>
    <div class="small ymo-muted mt-1">
        <?= html_escape($r['title']); ?>
        <?= $r['doc_date'] ? ' · '.html_escape(date('d M Y', strtotime($r['doc_date']))) : ''; ?>
        <?php if ($ro): ?> · Job card <a href="<?= admin_url('job-cards/'.(int) $ro['id']); ?>"><?= html_escape($ro['ro_number']); ?></a><?php endif; ?>
        <?php if ($user): ?> · Customer <?= html_escape($user['name']); ?> (<?= html_escape($user['mobile']); ?>)<?php endif; ?>
    </div>
    <div class="small ymo-muted">From OkMechanic (read-only)</div>
</div>

<?php foreach ($tables as $k => $list): $c = $cols[$k] ?? array_slice(array_keys($list[0]), 0, 6); if (isset($cols[$k])) { $c = $cols[$k]; } else { $c = array_combine($c, array_map($label, $c)); } ?>
<div class="ymo-card p-0 mb-3">
    <div class="px-3 pt-3 fw-semibold"><?= html_escape($label($k)); ?> (<?= count($list); ?>)</div>
    <div class="table-responsive">
    <table class="ymo-table mb-0">
        <thead><tr><?php foreach ($c as $h): ?><th><?= html_escape($h); ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($list as $row): ?>
            <tr><?php foreach (array_keys($c) as $f): $v = $row[$f] ?? ''; ?><td class="small"><?= is_array($v) ? '' : nl2br(html_escape((string) $v)); ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endforeach; ?>

<div class="ymo-card mb-3">
    <div class="fw-semibold mb-2">All details</div>
    <dl class="row small mb-0">
        <?php foreach ($scalars as $k => $v): ?>
        <dt class="col-sm-4 fw-normal ymo-muted"><?= html_escape($label($k)); ?></dt>
        <dd class="col-sm-8"><?= nl2br(html_escape((string) $v)); ?></dd>
        <?php endforeach; ?>
    </dl>
</div>
