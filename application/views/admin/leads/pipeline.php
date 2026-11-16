<?php defined('BASEPATH') OR exit('No direct script access allowed');
$stage_labels = isset($stage_labels) ? $stage_labels : crm_lead_stages();
$reasons = crm_lead_lost_reasons();
?>
<?php $this->load->view('admin/leads/_toolbar', array('mode' => 'board', 'sources' => $sources, 'filters' => $filters, 'can_edit' => $can_edit)); ?>

<form class="ymo-card mb-3" method="get" action="<?= admin_url('leads/pipeline'); ?>">
    <?php if (!empty($_GET['source'])): ?><input type="hidden" name="source" value="<?= html_escape($_GET['source']); ?>"><?php endif; ?>
    <div class="row g-2 align-items-center">
        <div class="col-md-4"><input class="form-control form-control-sm" type="search" name="q" placeholder="Search…" value="<?= html_escape((string) ($filters['q'] ?? '')); ?>"></div>
        <div class="col-md-3">
            <select name="assigned_to" class="form-select form-select-sm">
                <option value="">Any owner</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?= (int) $a['id']; ?>" <?= (string) $filters['assigned_to'] === (string) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-check small mb-0"><input class="form-check-input" type="checkbox" name="mine" value="1" <?= !empty($filters['mine']) ? 'checked' : ''; ?>> My leads only</label>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary btn-sm">Apply</button></div>
    </div>
</form>
<p class="small ymo-muted">Booked and Lost show the last 30 days. Use <strong>Move</strong> on a card to change its stage; Booked opens the booking form.</p>

<div class="ymo-pipeline">
    <?php foreach ($stage_labels as $slug => $label): ?>
        <div class="ymo-pipeline-col">
            <h6><span class="badge <?= crm_lead_stage_badge($slug); ?>"><?= html_escape($label); ?></span> <span class="ymo-muted small"><?= (int) ($counts[$slug] ?? 0); ?></span></h6>
            <?php if (empty($columns[$slug])): ?><p class="ymo-muted small mb-0">-</p><?php endif; ?>
            <?php foreach ($columns[$slug] as $l):
                $due = !empty($l['next_follow_up_at']) ? strtotime($l['next_follow_up_at']) : NULL; ?>
                <div class="ymo-pipeline-card">
                    <a href="<?= admin_url('leads/'.$l['id']); ?>" class="name d-block"><?php if ((int) $l['priority'] > 0): ?>★ <?php endif; ?><?= html_escape($l['name']); ?></a>
                    <div class="meta">
                        <?= html_escape($l['source_label']); ?><?php if ($l['assignee_name']): ?> · <?= html_escape($l['assignee_name']); ?><?php endif; ?>
                        <?php if ($due): ?><br><span class="<?= $due < strtotime('today') ? 'text-danger' : 'text-warning'; ?>">Follow up <?= html_escape(date('d M', $due)); ?></span><?php endif; ?>
                        <?php if ($slug === 'lost' && !empty($l['lost_reason'])): ?><br><?= html_escape($reasons[$l['lost_reason']] ?? $l['lost_reason']); ?><?php endif; ?>
                    </div>
                    <?php if ($can_edit && in_array($slug, crm_lead_open_stages(), TRUE)): ?>
                    <?= form_open(admin_url('leads/'.$l['id'].'/stage'), array('class' => 'mt-1 d-flex gap-1')); ?>
                        <input type="hidden" name="back" value="board">
                        <select name="stage" class="form-select form-select-sm py-0" aria-label="Move to" onchange="this.form.submit()">
                            <option value="">Move…</option>
                            <?php foreach ($stage_labels as $to => $to_label): if ($to === $slug) { continue; } ?>
                                <option value="<?= $to; ?>"><?= html_escape($to_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?= form_close(); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
