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

<?php if ($crm && !empty($crm['alerts'])): ?>
<div class="ymo-alerts mb-3" role="status">
    <?php foreach ($crm['alerts'] as $al): ?>
        <div class="ymo-alert ymo-alert-<?= html_escape($al['level']); ?>">
            <span class="mi mi-sm"><?= $al['level'] === 'critical' ? 'error' : ($al['level'] === 'info' ? 'forum' : 'warning'); ?></span>
            <span class="flex-grow-1"><?= html_escape($al['text']); ?></span>
            <?php if (!empty($al['link'])): ?>
                <a href="<?= html_escape($al['link']); ?>" class="btn btn-sm btn-light" <?= strpos($al['link'], 'http') === 0 && strpos($al['link'], admin_url('')) !== 0 ? 'target="_blank" rel="noopener"' : ''; ?>><?= html_escape($al['link_text']); ?></a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

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
                <span class="small d-flex gap-3">
                    <a href="<?= admin_url('tasks'.($who === 'all' ? '?who=all' : '')); ?>">Next 7 days</a>
                    <a href="<?= admin_url('tasks?view=list&status=done'); ?>">History</a>
                    <?php if ($can_task): ?><a href="<?= admin_url('tasks/new'); ?>">+ Add</a><?php endif; ?>
                </span>
            </div>
            <?php if (!$calls): ?><p class="small ymo-muted px-3 py-3 mb-0">Nothing due. New leads below are next.</p><?php endif; ?>
            <?php foreach (array_slice($calls, 0, 30) as $t):
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
                        <?php if ($can_task): ?>
                            <?= form_open(admin_url('tasks/'.$t['id'].'/snooze?back=dashboard'), array('class' => 'd-inline')); ?>
                                <input type="hidden" name="days" value="1">
                                <button class="btn btn-sm btn-outline-secondary" title="Move to tomorrow">+1 day</button>
                            <?= form_close(); ?>
                            <a class="btn btn-sm btn-outline-success" href="<?= admin_url('tasks/'.$t['id'].'/done?back=dashboard'); ?>" title="Done"><span class="mi mi-sm">check</span></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($calls) > 30): ?><p class="small px-3 py-2 mb-0"><a href="<?= admin_url('tasks'.($who === 'all' ? '?who=all' : '')); ?>">All <?= count($calls); ?> calls due</a></p><?php endif; ?>
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
        <?php if (!empty($wa_balance)): $wb = $wa_balance; $inr = function ($v) { return '₹'.number_format((float) $v, 2); }; ?>
        <div class="ymo-card mb-3 ymo-wa-balance <?= $wb['low'] ? 'is-low' : ''; ?>">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <p class="ymo-muted text-uppercase small mb-0">WhatsApp balance</p>
                <a class="small" href="https://business.facebook.com/billing_hub/accounts" target="_blank" rel="noopener">Meta Billing</a>
            </div>
            <?php if (!$wb['tracked']): ?>
                <p class="small mb-2">Not tracked yet. Meta doesn't let the CRM read the balance, so enter the <strong>Available funds</strong> figure shown in Meta Billing once; spend is then subtracted automatically.</p>
            <?php elseif ($wb['remaining'] === NULL): ?>
                <p class="small mb-2 text-danger">Couldn't read spend from Meta just now<?= $wb['error'] ? ': '.html_escape(mb_strimwidth($wb['error'], 0, 120, '…')) : ''; ?>. Last entered: <?= $inr($wb['base']); ?> on <?= html_escape(date('d M, h:i A', (int) $wb['base_at'])); ?>.</p>
            <?php else: ?>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="h3 mb-0 <?= $wb['low'] ? 'text-danger' : ''; ?>">~<?= $inr(max(0, $wb['remaining'])); ?></span>
                    <span class="small ymo-muted">left<?= $wb['msgs_left'] !== NULL ? ' · about '.number_format($wb['msgs_left']).' marketing messages' : ''; ?></span>
                </div>
                <?php if ($wb['low']): ?><p class="small text-danger fw-semibold mb-1"><?= $wb['remaining'] <= 0 ? 'Probably empty: WhatsApp messages will fail.' : 'Running low: top up soon.'; ?></p><?php endif; ?>
                <p class="small ymo-muted mb-2"><?= $inr($wb['base']); ?> entered <?= html_escape(date('d M, h:i A', (int) $wb['base_at'])); ?> − <?= $inr($wb['spent_since']); ?> spent since. An estimate; Meta Billing has the exact figure.</p>
            <?php endif; ?>
            <?php if ($wb['today'] !== NULL || $wb['month'] !== NULL): ?>
                <div class="d-flex justify-content-between small py-1 border-top"><span>Spent today</span><strong><?= $wb['today'] !== NULL ? $inr($wb['today']['cost']).' · '.(int) $wb['today']['volume'].' msgs' : '-'; ?></strong></div>
                <div class="d-flex justify-content-between small py-1 border-top"><span>Spent this month</span><strong><?= $wb['month'] !== NULL ? $inr($wb['month']['cost']).' · '.(int) $wb['month']['volume'].' msgs' : '-'; ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($can_wa_balance)): ?>
                <details class="mt-2" <?= $wb['tracked'] ? '' : 'open'; ?>>
                    <summary class="small">Update balance</summary>
                    <?= form_open(admin_url('dashboard/wa-balance'), array('class' => 'd-flex gap-2 mt-2')); ?>
                        <input type="hidden" name="mode" value="set">
                        <input class="form-control form-control-sm" name="amount" inputmode="decimal" style="min-width:0" placeholder="Available funds, ₹" required>
                        <button class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0">Set balance</button>
                    <?= form_close(); ?>
                    <?php if ($wb['tracked']): ?>
                        <?= form_open(admin_url('dashboard/wa-balance'), array('class' => 'd-flex gap-2 mt-2')); ?>
                            <input type="hidden" name="mode" value="topup">
                            <input class="form-control form-control-sm" name="amount" inputmode="decimal" style="min-width:0" placeholder="Amount added, ₹" required>
                            <button class="btn btn-sm btn-outline-success text-nowrap flex-shrink-0">Add top-up</button>
                        <?= form_close(); ?>
                    <?php endif; ?>
                </details>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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
