<?php defined('BASEPATH') OR exit('No direct script access allowed');
$crm   = $summary['crm'] ?? NULL;
$board = $summary['board'];
$my    = $crm ? $crm['my_day'] : array('overdue' => array(), 'today' => array());
$calls = array_merge($my['overdue'], $my['today']);
$car = function ($b) {
    return trim(($b['vehicle_make'] ?? '').' '.($b['vehicle_variant'] ?? '')).(!empty($b['vehicle_number']) ? ' · '.$b['vehicle_number'] : '');
};
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="small ymo-muted"><?= html_escape(date('l, d M Y')); ?></div>
    <div class="d-flex gap-2">
        <?php if ($crm): ?>
        <div class="btn-group btn-group-sm">
            <a href="<?= admin_url('dashboard'); ?>" class="btn <?= $who === 'me' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Mine</a>
            <a href="<?= admin_url('dashboard?who=all'); ?>" class="btn <?= $who === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Everyone</a>
        </div>
        <?php endif; ?>
        <?php if ($can_book): ?>
            <a href="<?= admin_url('bookings/new'); ?>" class="btn btn-primary btn-sm"><span class="mi mi-sm mi-leading">add</span>New booking</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($crm): $sc = $crm['stage_counts']; ?>
<div class="row g-2 mb-3">
    <?php
    $tiles = array(
        array('Calls due', count($calls), admin_url('tasks'.($who === 'all' ? '?who=all' : '')), count($my['overdue']) ? count($my['overdue']).' overdue' : 'today', count($my['overdue']) ? 'text-danger' : ''),
        array('New leads', (int) $crm['new_leads_count'], admin_url('leads?stage=new'), 'not contacted yet', ''),
        array('In progress', (int) ($sc['contacted'] ?? 0) + (int) ($sc['quote_sent'] ?? 0), admin_url('leads'), (int) ($sc['quote_sent'] ?? 0).' quotes sent', ''),
        array('Booked', (int) ($sc['booked'] ?? 0), admin_url('leads?stage=booked'), 'leads this month', 'text-success'),
    );
    foreach ($tiles as $t): ?>
    <div class="col-6 col-md-3">
        <a href="<?= $t[2]; ?>" class="md-card-elevated d-block text-decoration-none h-100">
            <p class="ymo-muted text-uppercase small mb-1"><?= $t[0]; ?></p>
            <div class="h3 mb-0 <?= $t[4]; ?>"><?= (int) $t[1]; ?></div>
            <small class="ymo-muted"><?= html_escape($t[3]); ?></small>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <?php if ($crm): ?>
        <div class="ymo-card p-0 mb-3">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <strong class="small text-uppercase ymo-muted"><span class="mi mi-sm mi-leading">call</span>Calls to make</strong>
                <a href="<?= admin_url('tasks'.($who === 'all' ? '?who=all' : '')); ?>" class="small">All follow-ups</a>
            </div>
            <?php if (!$calls): ?><p class="small ymo-muted px-3 py-3 mb-0">Nothing due. New leads below are next.</p><?php endif; ?>
            <?php foreach (array_slice($calls, 0, 12) as $t):
                $name   = $t['lead_name'] ?: ($t['contact_name'] ?: $t['title']);
                $mobile = $t['lead_mobile'] ?: ($t['contact_mobile'] ?: '');
                $link   = $t['lead_id'] ? admin_url('leads/'.$t['lead_id'].'#log') : ($t['contact_id'] ? admin_url('customers/'.$t['contact_id']) : admin_url('tasks/'.$t['id'].'/edit'));
                $overdue = $t['due_at'] < date('Y-m-d 00:00:00');
                $tel = crm_tel_link($mobile); ?>
                <div class="ymo-myday-row px-3">
                    <div class="flex-grow-1 small">
                        <a href="<?= $link; ?>" class="fw-semibold"><?= html_escape($name); ?></a>
                        <?php if (!empty($t['lead_stage'])): ?><span class="badge <?= crm_lead_stage_badge($t['lead_stage']); ?> ms-1"><?= html_escape(crm_lead_stage_label($t['lead_stage'])); ?></span><?php endif; ?>
                        <div class="<?= $overdue ? 'text-danger' : 'ymo-muted'; ?>"><?= html_escape(date($overdue ? 'd M, h:i A' : 'h:i A', strtotime($t['due_at']))); ?><?= $who === 'all' && !empty($t['assignee_name']) ? ' · '.html_escape($t['assignee_name']) : ''; ?></div>
                    </div>
                    <div class="d-flex gap-1">
                        <?php if ($tel): ?><a class="btn btn-sm btn-outline-primary" href="<?= $tel; ?>" title="Call"><span class="mi mi-sm">call</span></a><?php endif; ?>
                        <a class="btn btn-sm btn-primary" href="<?= $link; ?>"><?= $t['lead_id'] ? 'Log call' : 'Open'; ?></a>
                        <?php if ($can_task): ?><a class="btn btn-sm btn-outline-success" href="<?= admin_url('tasks/'.$t['id'].'/done?back=dashboard'); ?>" title="Done"><span class="mi mi-sm">check</span></a><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ymo-card p-0 mb-3">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <strong class="small text-uppercase ymo-muted"><span class="mi mi-sm mi-leading">fiber_new</span>New leads, not contacted</strong>
                <a href="<?= admin_url('leads?stage=new'); ?>" class="small">All new (<?= (int) $crm['new_leads_count']; ?>)</a>
            </div>
            <?php if (empty($crm['new_leads'])): ?><p class="small ymo-muted px-3 py-3 mb-0">No new leads waiting.</p><?php endif; ?>
            <?php foreach ($crm['new_leads'] as $l): $tel = crm_tel_link($l['mobile']); ?>
                <div class="ymo-myday-row px-3">
                    <div class="flex-grow-1 small">
                        <a href="<?= admin_url('leads/'.$l['id'].'#log'); ?>" class="fw-semibold"><?= html_escape($l['name']); ?></a>
                        <div class="ymo-muted"><?= html_escape($l['source_label']); ?> · <?= html_escape(crm_time_ago($l['created_at'])); ?><?= empty($l['assigned_to']) ? ' · unassigned' : ''; ?></div>
                    </div>
                    <div class="d-flex gap-1">
                        <?php if ($tel): ?><a class="btn btn-sm btn-outline-primary" href="<?= $tel; ?>" title="Call"><span class="mi mi-sm">call</span></a><?php endif; ?>
                        <a class="btn btn-sm btn-primary" href="<?= admin_url('leads/'.$l['id'].'#log'); ?>">Log call</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="ymo-card p-0 mb-3">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <strong class="small text-uppercase ymo-muted"><span class="mi mi-sm mi-leading">garage</span>Workshop today</strong>
                <a href="<?= admin_url('bookings'); ?>" class="small">All bookings</a>
            </div>
            <?php
            $sections = array(
                'due'         => 'Due today',
                'in_progress' => 'In the workshop',
                'unconfirmed' => 'Waiting for confirmation',
            );
            $any = FALSE;
            foreach ($sections as $key => $label): if (empty($board[$key])) { continue; } $any = TRUE; ?>
                <div class="px-3 pt-2 small fw-semibold"><?= $label; ?> <span class="ymo-muted">(<?= count($board[$key]); ?>)</span></div>
                <?php foreach ($board[$key] as $b): ?>
                    <div class="ymo-myday-row px-3">
                        <div class="flex-grow-1 small">
                            <a href="<?= admin_url('bookings/'.$b['id']); ?>" class="fw-semibold"><?= html_escape($b['user_name']); ?></a>
                            <span class="ymo-muted font-monospace ms-1"><?= html_escape($b['reference']); ?></span>
                            <div class="ymo-muted"><?= html_escape($car($b)); ?><?= $b['package_name'] ? ' · '.html_escape($b['package_name']) : ''; ?><?= $key === 'unconfirmed' && $b['preferred_date'] ? ' · for '.html_escape(date('d M', strtotime($b['preferred_date']))) : ''; ?></div>
                        </div>
                        <span class="badge-status s-<?= html_escape($b['status']); ?>"><?= html_escape(str_replace('_', ' ', $b['status'])); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (!$any): ?><p class="small ymo-muted px-3 py-3 mb-0">No cars due today.</p><?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if ($crm): ?>
        <div class="ymo-card mb-3">
            <p class="ymo-muted text-uppercase small mb-2">Leads by stage</p>
            <?php $total_open = 0; foreach (crm_lead_open_stages() as $s) { $total_open += (int) ($crm['stage_counts'][$s] ?? 0); } ?>
            <?php foreach (crm_lead_stages() as $slug => $label): $n = (int) ($crm['stage_counts'][$slug] ?? 0); ?>
                <a href="<?= admin_url('leads?stage='.$slug); ?>" class="d-flex justify-content-between small py-1 border-bottom text-decoration-none">
                    <span><span class="badge <?= crm_lead_stage_badge($slug); ?>"><?= html_escape($label); ?></span><?= in_array($slug, array('booked', 'lost'), TRUE) ? ' <span class="ymo-muted">this month</span>' : ''; ?></span>
                    <strong><?= $n; ?></strong>
                </a>
            <?php endforeach; ?>
            <a href="<?= admin_url('leads/pipeline'); ?>" class="small d-inline-block mt-2">Open the board</a>
        </div>

        <div class="ymo-card mb-3">
            <p class="ymo-muted text-uppercase small mb-2">Where bookings come from · <?= html_escape(date('F')); ?></p>
            <?php if (empty($crm['sources_month'])): ?><p class="small ymo-muted mb-0">No leads this month yet.</p><?php else: ?>
            <table class="ymo-table mb-0 small">
                <thead><tr><th>Source</th><th class="text-end">Leads</th><th class="text-end">Booked</th><th class="text-end">Rate</th></tr></thead>
                <tbody>
                <?php foreach ($crm['sources_month'] as $r): $leads = (int) $r['leads']; $booked = (int) $r['booked']; ?>
                    <tr>
                        <td><?= html_escape($r['label'] ?: 'Unknown'); ?></td>
                        <td class="text-end"><?= $leads; ?></td>
                        <td class="text-end"><?= $booked; ?></td>
                        <td class="text-end"><?= $leads ? round($booked * 100 / $leads).'%' : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($crm['service_due'])): ?>
        <div class="ymo-card mb-3">
            <p class="ymo-muted text-uppercase small mb-2">Service due · <?= (int) $crm['service_due_count']; ?> customers</p>
            <?php foreach ($crm['service_due'] as $c): ?>
                <a href="<?= admin_url('customers/'.$c['id']); ?>" class="d-flex justify-content-between small py-1 border-bottom text-decoration-none">
                    <span><?= html_escape($c['name']); ?></span>
                    <span class="ymo-muted"><?= !empty($c['due_at']) ? html_escape(date('d M', strtotime($c['due_at']))) : ''; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="ymo-card">
            <p class="ymo-muted text-uppercase small mb-2">Bookings</p>
            <div class="d-flex justify-content-between small py-1 border-bottom"><span>Created today</span><strong><?= (int) $summary['today']; ?></strong></div>
            <div class="d-flex justify-content-between small py-1 border-bottom"><span>Waiting for confirmation</span><strong class="text-warning"><?= (int) $summary['pending']; ?></strong></div>
            <div class="d-flex justify-content-between small py-1 border-bottom"><span>In the workshop</span><strong><?= (int) $summary['in_progress']; ?></strong></div>
            <div class="d-flex justify-content-between small py-1 border-bottom"><span>Completed, last 30 days</span><strong class="text-success"><?= (int) $summary['completed_30d']; ?></strong></div>
            <div class="d-flex justify-content-between small py-1"><span>Service reminders this week</span><strong><?= (int) $summary['reminders_week']; ?></strong></div>
        </div>
    </div>
</div>
