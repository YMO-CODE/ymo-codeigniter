<?php defined('BASEPATH') OR exit('No direct script access allowed');
$langs = Crm_technician_model::LANGS;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="small ymo-muted flex-grow-1" style="min-width:16rem">
        Technicians use their phone to see their jobs, tick off work and add photos.
        They open <a href="<?= html_escape($tech_url); ?>" target="_blank"><?= html_escape(preg_replace('#^https?://#', '', $tech_url)); ?></a>
        and sign in with their mobile number and 4-digit PIN. They stay signed in for 30 days on that phone.
    </div>
    <?php if ($can_edit): ?>
    <a href="<?= admin_url('technicians/new'); ?>" class="btn btn-sm btn-primary"><span class="mi mi-sm mi-leading">person_add</span>Add technician</a>
    <?php endif; ?>
</div>

<div class="ymo-card p-0">
    <?php if (!$rows): ?>
        <p class="small ymo-muted p-3 mb-0">No technicians yet. Add your mechanics so you can assign them on job cards.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="ymo-table mb-0">
        <thead><tr><th>Name</th><th>Workshop</th><th class="d-none d-md-table-cell">Language</th><th class="d-none d-md-table-cell">Last used</th><th class="text-end">Phones</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): $n = (int) ($sessions[(int) $t['id']] ?? 0); ?>
            <tr class="<?= (int) $t['is_active'] ? '' : 'opacity-50'; ?>">
                <td>
                    <?php if ($can_edit): ?><a href="<?= admin_url('technicians/'.(int) $t['id'].'/edit'); ?>" class="fw-semibold text-reset"><?= html_escape($t['name']); ?></a><?php else: ?><span class="fw-semibold"><?= html_escape($t['name']); ?></span><?php endif; ?>
                    <?php if (!(int) $t['is_active']): ?><span class="badge bg-secondary ms-1">Inactive</span><?php endif; ?>
                    <?php if (empty($t['pin_hash'])): ?><span class="badge bg-warning text-dark ms-1">No PIN</span><?php endif; ?>
                    <?php if (!empty($t['locked_until']) && strtotime($t['locked_until']) > time()): ?><span class="badge bg-danger ms-1">Locked (wrong PIN)</span><?php endif; ?>
                    <br><span class="small ymo-muted"><?= html_escape($t['mobile']); ?></span>
                </td>
                <td class="small"><?= $t['workshop'] !== '' && isset($workshops[$t['workshop']]) ? html_escape($workshops[$t['workshop']]['label']) : 'All workshops'; ?></td>
                <td class="small d-none d-md-table-cell"><?= html_escape($langs[$t['lang']] ?? $t['lang']); ?></td>
                <td class="small d-none d-md-table-cell"><?= $t['last_seen_at'] ? html_escape(date('d M, H:i', strtotime($t['last_seen_at']))) : '—'; ?></td>
                <td class="text-end small">
                    <?= $n; ?>
                    <?php if ($n && $can_edit): ?>
                    <?= form_open(admin_url('technicians/'.(int) $t['id'].'/signout'), array('class' => 'd-inline', 'onsubmit' => "return confirm('Sign ".html_escape(addslashes($t['name']))." out of all phones?');")); ?>
                        <button class="btn btn-sm btn-link px-1">Sign out</button>
                    <?= form_close(); ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
