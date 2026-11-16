<?php defined('BASEPATH') OR exit('No direct script access allowed');
$stage      = $lead['stage'];
$is_open    = in_array($stage, crm_lead_open_stages(), TRUE);
$tel        = crm_tel_link($lead['mobile']);
$wa         = crm_wa_link($lead['mobile']);
$reasons    = crm_lead_lost_reasons();
$outcomes   = crm_lead_call_outcomes();
$lead_address  = crm_lead_field($lead, 'address');
$lead_car_type = crm_lead_field($lead, 'car_type');
?>
<a href="<?= admin_url('leads'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All leads</a>

<div class="md-card-elevated mt-2 mb-3">
    <div class="d-flex justify-content-between flex-wrap gap-2 align-items-start">
        <div>
            <h2 class="h4 mb-1">
                <?= html_escape($lead['name']); ?>
                <span class="badge <?= crm_lead_stage_badge($stage); ?> align-middle ms-1"><?= html_escape(crm_lead_stage_label($stage)); ?></span>
                <?php if ((int) $lead['priority'] > 0): ?><span class="badge badge-priority-high align-middle">Priority</span><?php endif; ?>
            </h2>
            <p class="small mb-0">
                <?php if ($lead['mobile']): ?><strong><?= html_escape($lead['mobile']); ?></strong><?php endif; ?>
                <?php if ($lead['email']): ?> · <?= html_escape($lead['email']); ?><?php endif; ?>
            </p>
            <p class="ymo-muted small mb-0">
                <?= html_escape($lead['source_label']); ?> · <?= html_escape(date('d M Y, h:i A', strtotime($lead['created_at']))); ?>
                · <?= $lead['assignee_name'] ? 'Owner: '.html_escape($lead['assignee_name']) : 'Unassigned'; ?>
            </p>
            <?php if ($stage === 'lost' && !empty($lead['lost_reason'])): ?>
                <p class="small mb-0 mt-1 text-danger">Lost: <?= html_escape($reasons[$lead['lost_reason']] ?? $lead['lost_reason']); ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($tel): ?><a class="btn btn-outline-primary btn-sm" href="<?= $tel; ?>"><span class="mi mi-sm mi-leading">call</span>Call</a><?php endif; ?>
            <?php if ($wa): ?><a class="btn btn-outline-success btn-sm" href="<?= $wa; ?>" target="_blank" rel="noopener"><span class="mi mi-sm mi-leading">chat</span>WhatsApp</a><?php endif; ?>
            <?php if (!empty($booking)): ?>
                <a class="btn btn-success btn-sm" href="<?= admin_url('bookings/'.$booking['id']); ?>"><span class="mi mi-sm mi-leading">event_available</span>Booking <?= html_escape($booking['reference']); ?></a>
            <?php elseif ($can_book && $stage !== 'booked'): ?>
                <a class="btn btn-primary btn-sm" href="<?= admin_url('bookings/new?lead_id='.$lead['id']); ?>"><span class="mi mi-sm mi-leading">event_available</span>Book service</a>
            <?php endif; ?>
            <?php if ($can_edit): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= admin_url('leads/'.$lead['id'].'/edit'); ?>"><span class="mi mi-sm">edit</span><span class="visually-hidden">Edit</span></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <?php if ($can_edit): ?>
        <div class="md-card-elevated mb-3" id="log">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">edit_note</span>What happened?</h6>
            <?= form_open(admin_url('leads/'.$lead['id'].'/log'), array('id' => 'ymo-log-form')); ?>
                <div class="d-flex flex-wrap gap-2 mb-2" role="radiogroup" aria-label="Channel">
                    <?php foreach (array('call' => 'Call', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'Email', 'note' => 'Visit / other') as $k => $label): ?>
                        <input type="radio" class="btn-check" name="channel" id="ch_<?= $k; ?>" value="<?= $k; ?>" <?= $k === 'call' ? 'checked' : ''; ?>>
                        <label class="btn btn-sm btn-outline-secondary" for="ch_<?= $k; ?>"><?= $label; ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3" role="radiogroup" aria-label="Outcome">
                    <?php foreach ($outcomes as $k => $o):
                        if ($k === 'booked' && (!$can_book || $stage === 'booked')) { continue; }
                        $cls = $k === 'booked' ? 'btn-outline-success' : ($k === 'lost' ? 'btn-outline-danger' : 'btn-outline-primary');
                    ?>
                        <input type="radio" class="btn-check" name="outcome" id="oc_<?= $k; ?>" value="<?= $k; ?>" required>
                        <label class="btn btn-sm <?= $cls; ?>" for="oc_<?= $k; ?>"><?= html_escape($o['label']); ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="mb-2" id="lost_box" hidden>
                    <select class="form-select form-select-sm" name="lost_reason">
                        <option value="">Why? *</option>
                        <?php foreach ($reasons as $k => $label): ?><option value="<?= $k; ?>"><?= html_escape($label); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <textarea class="form-control form-control-sm mb-2" name="note" rows="2" maxlength="5000" placeholder="Notes (price quoted, car issue, when to call…)"></textarea>
                <div id="follow_box">
                    <label class="form-label small mb-1">Next follow-up</label>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php
                        $chips = array('today' => 'Later today', 'tomorrow' => 'Tomorrow', '3d' => 'In 3 days', '1w' => 'Next week', '2w' => 'In 2 weeks', '1m' => 'Next month', 'custom' => 'Pick date', 'none' => 'No follow-up');
                        $default_chip = $followup ? '' : 'tomorrow';
                        foreach ($chips as $k => $label): ?>
                            <input type="radio" class="btn-check" name="follow_up" id="fu_<?= $k; ?>" value="<?= $k; ?>" <?= $k === $default_chip ? 'checked' : ''; ?>>
                            <label class="btn btn-sm btn-outline-secondary" for="fu_<?= $k; ?>"><?= $label; ?></label>
                        <?php endforeach; ?>
                    </div>
                    <input type="datetime-local" class="form-control form-control-sm mb-2" name="follow_up_date" id="fu_date" hidden
                           value="<?= html_escape(date('Y-m-d\T10:00', strtotime('+1 day'))); ?>">
                    <?php if ($followup): ?>
                        <p class="small ymo-muted mb-2">Currently due <?= html_escape(date('D d M, h:i A', strtotime($followup['due_at']))); ?> - leave unselected to keep it.</p>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary btn-sm"><span class="mi mi-sm mi-leading">save</span>Save</button>
            <?= form_close(); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($chat_channel) && $can_edit): ?>
        <div class="md-card-elevated mb-3">
            <h6 class="mb-2"><span class="mi mi-sm mi-leading">forum</span><?= $chat_channel === 'whatsapp' ? 'WhatsApp chat' : 'Instagram chat'; ?></h6>
            <div class="ymo-chat-thread mb-2">
                <?php if (empty($chat_messages)): ?>
                    <p class="ymo-muted small mb-0">No messages yet.</p>
                <?php else: foreach ($chat_messages as $msg): ?>
                    <div class="ymo-chat-bubble <?= $msg['direction'] === 'outbound' ? 'ymo-chat-out' : 'ymo-chat-in'; ?>">
                        <div class="ymo-chat-text"><?= nl2br(html_escape($msg['text'])); ?></div>
                        <div class="ymo-chat-meta small">
                            <?php if ($msg['direction'] === 'outbound' && !empty($msg['admin_name'])): ?><?= html_escape($msg['admin_name']); ?> · <?php endif; ?>
                            <?= html_escape(date('d M, h:i A', strtotime($msg['created_at']))); ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <?= form_open(admin_url('leads/'.$lead['id'].'/send-chat'), array('class' => 'd-flex gap-2')); ?>
                <textarea name="body" class="form-control form-control-sm" rows="1" placeholder="Reply… (works within 24h of their last message)" required maxlength="4096"></textarea>
                <button class="btn btn-primary btn-sm"><span class="mi mi-sm">send</span></button>
            <?= form_close(); ?>
        </div>
        <?php endif; ?>

        <div class="md-card-elevated">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">history</span>Timeline</h6>
            <?php
            $type_icons = array(
                'note' => 'sticky_note_2', 'call' => 'call', 'email' => 'mail', 'sms' => 'sms',
                'whatsapp' => 'chat', 'status_change' => 'swap_horiz', 'system' => 'info', 'webhook' => 'forum',
            );
            $chat_ids = array();
            foreach ((array) $chat_messages as $cm) { $chat_ids[] = (int) $cm['id']; }
            $shown = 0;
            ?>
            <ul class="list-unstyled mb-0">
            <?php foreach ($activities as $a):
                if (in_array((int) $a['id'], $chat_ids, TRUE)) { continue; }
                $shown++;
            ?>
                <li class="border-bottom py-2 d-flex gap-2">
                    <span class="mi mi-sm text-muted"><?= $type_icons[$a['type']] ?? 'circle'; ?></span>
                    <div class="flex-grow-1">
                        <div class="small" style="white-space:pre-wrap"><?= html_escape($a['body']); ?></div>
                        <div class="small ymo-muted">
                            <?= $a['admin_name'] ? html_escape($a['admin_name']).' · ' : ''; ?><?= html_escape(date('d M Y, h:i A', strtotime($a['created_at']))); ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php if (!$shown): ?><p class="ymo-muted small mb-0">Nothing logged yet.</p><?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="ymo-card mb-3">
            <h6 class="mb-2"><span class="mi mi-sm mi-leading">event</span>Next follow-up</h6>
            <?php if ($followup): ?>
                <p class="mb-1 <?= strtotime($followup['due_at']) < time() ? 'text-danger fw-semibold' : ''; ?>">
                    <?= html_escape(date('D d M, h:i A', strtotime($followup['due_at']))); ?>
                </p>
                <?php if (!empty($followup['notes'])): ?><p class="small ymo-muted mb-2"><?= html_escape($followup['notes']); ?></p><?php endif; ?>
            <?php elseif ($is_open): ?>
                <p class="small ymo-muted mb-0">None set - log what happened to schedule one.</p>
            <?php else: ?>
                <p class="small ymo-muted mb-0">Closed lead - no follow-up needed.</p>
            <?php endif; ?>
        </div>

        <?php if ($lead_car_type || $lead_address || $lead['company'] || !empty($lead['message'])): ?>
        <div class="ymo-card mb-3">
            <h6 class="mb-2"><span class="mi mi-sm mi-leading">description</span>Enquiry</h6>
            <?php if ($lead_car_type): ?><p class="small mb-1"><span class="ymo-muted">Car:</span> <?= html_escape($lead_car_type); ?></p><?php endif; ?>
            <?php if ($lead_address): ?><p class="small mb-1"><span class="ymo-muted">Area:</span> <?= html_escape($lead_address); ?></p><?php endif; ?>
            <?php if ($lead['company']): ?><p class="small mb-1"><span class="ymo-muted">Workshop:</span> <?= html_escape($lead['company']); ?></p><?php endif; ?>
            <?php if (!empty($lead['message'])): ?><p class="small mb-0" style="white-space:pre-wrap"><?= html_escape($lead['message']); ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($contact || $other_leads): ?>
        <div class="ymo-card mb-3">
            <h6 class="mb-2"><span class="mi mi-sm mi-leading">person</span>Customer</h6>
            <?php if ($contact): ?>
                <p class="small mb-2"><a href="<?= admin_url('customers/'.$contact['id']); ?>"><?= html_escape($contact['name']); ?></a>
                    <?= !empty($contact['user_id']) ? '<span class="badge bg-light text-dark border">has bookings account</span>' : ''; ?></p>
            <?php endif; ?>
            <?php if ($other_leads): ?>
                <p class="small ymo-muted mb-1">Other enquiries from this number</p>
                <?php foreach ($other_leads as $o): ?>
                    <div class="small"><a href="<?= admin_url('leads/'.$o['id']); ?>"><?= html_escape(date('d M Y', strtotime($o['created_at']))); ?></a>
                        · <?= html_escape($o['source_label']); ?> · <?= html_escape(crm_lead_stage_label($o['stage'])); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($can_edit || $can_assign): ?>
        <div class="ymo-card mb-3">
            <?php if ($can_assign): ?>
            <?= form_open(admin_url('leads/'.$lead['id'].'/assign'), array('class' => 'mb-3')); ?>
                <label class="form-label small mb-1">Owner</label>
                <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Unassigned</option>
                    <?php foreach ($admins as $a): ?>
                        <option value="<?= (int) $a['id']; ?>" <?= (int) $lead['assigned_to'] === (int) $a['id'] ? 'selected' : ''; ?>><?= html_escape($a['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?= form_close(); ?>
            <?php endif; ?>
            <?php if ($can_edit): ?>
            <?= form_open(admin_url('leads/'.$lead['id'].'/stage'), array('id' => 'ymo-stage-form')); ?>
                <label class="form-label small mb-1">Correct the stage</label>
                <div class="d-flex gap-2">
                    <select name="stage" class="form-select form-select-sm" id="stage_sel">
                        <?php foreach (crm_lead_stages() as $st => $label): ?>
                            <option value="<?= $st; ?>" <?= $stage === $st ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary btn-sm">Set</button>
                </div>
                <select name="lost_reason" class="form-select form-select-sm mt-2" id="stage_reason" <?= $stage === 'lost' ? '' : 'hidden'; ?>>
                    <?php foreach ($reasons as $k => $label): ?><option value="<?= $k; ?>" <?= ($lead['lost_reason'] ?? '') === $k ? 'selected' : ''; ?>><?= html_escape($label); ?></option><?php endforeach; ?>
                </select>
            <?= form_close(); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($can_delete): ?>
            <?= form_open(admin_url('leads/'.$lead['id'].'/archive')); ?>
                <button class="btn btn-link btn-sm text-danger p-0" onclick="return confirm('Archive this lead? It disappears from lists.');">
                    <span class="mi mi-sm mi-leading">archive</span>Archive lead
                </button>
            <?= form_close(); ?>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var form = document.getElementById('ymo-log-form');
    if (form) {
        var lost = document.getElementById('lost_box');
        var follow = document.getElementById('follow_box');
        var date = document.getElementById('fu_date');
        form.addEventListener('change', function () {
            var oc = (form.querySelector('input[name="outcome"]:checked') || {}).value;
            var isLost = oc === 'lost', isBooked = oc === 'booked';
            lost.hidden = !isLost;
            lost.querySelector('select').required = isLost;
            follow.hidden = isLost || isBooked;
            var fu = (form.querySelector('input[name="follow_up"]:checked') || {}).value;
            date.hidden = fu !== 'custom';
        });
    }
    var sel = document.getElementById('stage_sel');
    if (sel) {
        sel.addEventListener('change', function () {
            document.getElementById('stage_reason').hidden = sel.value !== 'lost';
        });
    }
})();
</script>
