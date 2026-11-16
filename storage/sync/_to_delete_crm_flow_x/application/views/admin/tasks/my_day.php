<?php defined('BASEPATH') OR exit('No direct script access allowed');
$groups = array(
    'overdue'  => array('Overdue', 'text-danger'),
    'today'    => array('Today', ''),
    'upcoming' => array('Next 7 days', 'ymo-muted'),
);
$total = count($day['overdue']) + count($day['today']);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group btn-group-sm">
        <a href="<?= admin_url('tasks'); ?>" class="btn <?= $who === 'me' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Mine</a>
        <a href="<?= admin_url('tasks?who=all'); ?>" class="btn <?= $who === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Everyone</a>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= admin_url('tasks?view=list&status=done'); ?>" class="btn btn-outline-secondary btn-sm">History</a>
        <?php if ($can_edit): ?>
            <a href="<?= admin_url('tasks/new'); ?>" class="btn btn-primary btn-sm"><span class="mi mi-sm mi-leading">add</span>Add follow-up</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$total && empty($day['upcoming'])): ?>
    <div class="ymo-card text-center py-5">
        <span class="mi mi-lg" style="color:#16a34a">task_alt</span>
        <p class="mb-1 mt-2"><strong>All caught up.</strong></p>
        <p class="small ymo-muted mb-0">Follow-ups appear here when you log a call on a lead and pick a next date.</p>
    </div>
<?php endif; ?>

<?php foreach ($groups as $key => $g): if (empty($day[$key])) { continue; } ?>
<h2 class="h6 mt-3 mb-2 <?= $g[1]; ?>"><?= $g[0]; ?> <span class="badge bg-light text-dark border"><?= count($day[$key]); ?></span></h2>
<div class="ymo-card p-0 mb-3">
    <?php foreach ($day[$key] as $t):
        $name   = $t['lead_name'] ?: ($t['contact_name'] ?: '');
        $mobile = $t['lead_mobile'] ?: ($t['contact_mobile'] ?: '');
        $link   = $t['lead_id'] ? admin_url('leads/'.$t['lead_id'].'#log') : ($t['contact_id'] ? admin_url('customers/'.$t['contact_id']) : admin_url('tasks/'.$t['id'].'/edit'));
        $tel = crm_tel_link($mobile);
        $wa  = crm_wa_link($mobile);
    ?>
        <div class="ymo-myday-row">
            <div class="flex-grow-1">
                <a href="<?= $link; ?>" class="fw-semibold"><?= html_escape($name !== '' ? $name : $t['title']); ?></a>
                <?php if (!empty($t['lead_stage'])): ?><span class="badge <?= crm_lead_stage_badge($t['lead_stage']); ?> ms-1"><?= html_escape(crm_lead_stage_label($t['lead_stage'])); ?></span><?php endif; ?>
                <?php if ((int) $t['priority'] > 0): ?><span class="badge badge-priority-high ms-1">!</span><?php endif; ?>
                <div class="small ymo-muted">
                    <?= html_escape(date($key === 'upcoming' ? 'D d M, h:i A' : 'h:i A', strtotime($t['due_at']))); ?>
                    <?= $key === 'overdue' ? ' · '.html_escape(date('d M', strtotime($t['due_at']))) : ''; ?>
                    <?= $who === 'all' && $t['assignee_name'] ? ' · '.html_escape($t['assignee_name']) : ''; ?>
                    <?= $name !== '' && $t['title'] !== 'Follow up: '.$name ? ' · '.html_escape($t['title']) : ''; ?>
                </div>
                <?php if (!empty($t['notes'])): ?><div class="small"><?= html_escape(mb_strimwidth($t['notes'], 0, 160, '…')); ?></div><?php endif; ?>
            </div>
            <div class="d-flex flex-wrap gap-1 justify-content-end">
                <?php if ($tel): ?><a class="btn btn-sm btn-outline-primary" href="<?= $tel; ?>" title="Call"><span class="mi mi-sm">call</span></a><?php endif; ?>
                <?php if ($wa): ?><a class="btn btn-sm btn-outline-success" href="<?= $wa; ?>" target="_blank" rel="noopener" title="WhatsApp"><span class="mi mi-sm">chat</span></a><?php endif; ?>
                <?php if ($t['lead_id']): ?>
                    <a class="btn btn-sm btn-primary" href="<?= $link; ?>">Log call</a>
                <?php endif; ?>
                <?php if ($can_edit): ?>
                    <?= form_open(admin_url('tasks/'.$t['id'].'/snooze'), array('class' => 'd-inline')); ?>
                        <input type="hidden" name="days" value="1">
                        <button class="btn btn-sm btn-outline-secondary" title="Move to tomorrow">+1 day</button>
                    <?= form_close(); ?>
                    <a class="btn btn-sm btn-outline-success" href="<?= admin_url('tasks/'.$t['id'].'/done'); ?>" title="Mark done"><span class="mi mi-sm">check</span></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
