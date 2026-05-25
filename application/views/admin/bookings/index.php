<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form class="ymo-card mb-3" method="get" action="<?= admin_url('bookings'); ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <div class="form-floating">
                <input class="form-control" id="bk_q" type="search" name="q" placeholder=" "
                       value="<?= html_escape((string) $filters['q']); ?>">
                <label for="bk_q">Search ref / name / mobile</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <select id="bk_status" name="status" class="form-select">
                    <option value="">All</option>
                    <?php foreach (array('pending','confirmed','in_progress','completed','cancelled') as $s): ?>
                        <option value="<?= $s; ?>" <?= $filters['status'] === $s ? 'selected' : ''; ?>><?= ucwords(str_replace('_',' ', $s)); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="bk_status">Status</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <select id="bk_pkg" name="package_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($packages as $p): ?>
                        <option value="<?= (int) $p['id']; ?>" <?= (int) $filters['package_id'] === (int) $p['id'] ? 'selected' : ''; ?>>
                            <?= html_escape($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="bk_pkg">Package</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <input class="form-control" id="bk_from" type="date" name="from" placeholder=" "
                       value="<?= html_escape((string) $filters['from']); ?>">
                <label for="bk_from">From</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <input class="form-control" id="bk_to" type="date" name="to" placeholder=" "
                       value="<?= html_escape((string) $filters['to']); ?>">
                <label for="bk_to">To</label>
            </div>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-primary"><span class="mi mi-sm mi-leading">filter_alt</span>Filter</button>
        </div>
    </div>
</form>

<div class="ymo-card p-0">
    <table class="ymo-table mb-0">
        <thead><tr>
            <th>Ref</th><th>Customer</th><th>Service</th><th>Vehicle</th>
            <th>Created</th><th>Status</th><th class="text-end"></th>
        </tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center py-4 ymo-muted">No bookings match those filters.</td></tr>
        <?php endif; ?>
        <?php
        $status_icons = array(
            'pending'     => 'schedule',
            'confirmed'   => 'event_available',
            'in_progress' => 'autorenew',
            'completed'   => 'check_circle',
            'cancelled'   => 'cancel',
        );
        ?>
        <?php foreach ($rows as $b): ?>
            <tr>
                <td class="font-monospace small"><?= html_escape($b['reference']); ?></td>
                <td class="small">
                    <?= html_escape($b['user_name']); ?><br>
                    <span class="ymo-muted"><?= html_escape($b['user_mobile']); ?></span>
                </td>
                <td class="small"><?= html_escape($b['package_name']); ?></td>
                <td class="small font-monospace"><?= html_escape($b['vehicle_number']); ?></td>
                <td class="small"><?= html_escape(date('d M Y', strtotime($b['created_at']))); ?></td>
                <td>
                    <span class="badge-status s-<?= html_escape($b['status']); ?>">
                        <span class="mi"><?= isset($status_icons[$b['status']]) ? $status_icons[$b['status']] : 'help'; ?></span>
                        <?= html_escape(str_replace('_',' ',$b['status'])); ?>
                    </span>
                </td>
                <td class="text-end"><a href="<?= admin_url('bookings/'.$b['id']); ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++):
            $qs = $_GET; $qs['page'] = $i;
            ?>
            <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?= admin_url('bookings?'.http_build_query($qs)); ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<p class="ymo-muted small mt-2"><?= (int) $total; ?> total result<?= $total === 1 ? '' : 's'; ?>.</p>
