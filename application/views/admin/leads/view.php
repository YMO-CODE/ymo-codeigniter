<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('leads'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>All leads
</a>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <div class="d-flex justify-content-between flex-wrap align-items-start mb-3">
                <div>
                    <h2 class="h4 mb-1">
                        <?php if ((int) $lead['priority'] > 0): ?>
                            <span class="badge badge-priority-high me-1">Priority</span>
                        <?php endif; ?>
                        <?= html_escape($lead['name']); ?>
                    </h2>
                    <p class="ymo-muted small mb-0">
                        <?= html_escape($lead['source_label']); ?> ·
                        Created <?= html_escape(date('d M Y, h:i A', strtotime($lead['created_at']))); ?>
                    </p>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark badge-stage"><?= html_escape(crm_lead_stage_label($lead['stage'])); ?></span>
                    <span class="badge bg-secondary"><?= html_escape($lead['status']); ?></span>
                    <?php if (!empty($lead['next_follow_up_at'])): ?>
                        <p class="small ymo-muted mt-1 mb-0">Next follow-up: <?= html_escape(date('d M Y, h:i A', strtotime($lead['next_follow_up_at']))); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-muted small text-uppercase">Contact</h6>
                    <p class="mb-0"><?= $lead['mobile'] ? html_escape($lead['mobile']) : '-'; ?></p>
                    <p class="ymo-muted small mb-0"><?= $lead['email'] ? html_escape($lead['email']) : '-'; ?></p>
                    <?php if ($lead['company']): ?>
                        <p class="small mb-0 mt-1"><?= html_escape($lead['company']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted small text-uppercase">Assigned to</h6>
                    <p class="mb-0"><?= $lead['assignee_name'] ? html_escape($lead['assignee_name']) : '<span class="ymo-muted">Unassigned</span>'; ?></p>
                </div>
                <?php if (!empty($lead['message'])): ?>
                <div class="col-12">
                    <h6 class="text-muted small text-uppercase">Enquiry</h6>
                    <p class="mb-0"><?= nl2br(html_escape($lead['message'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                <?php if ($can_edit): ?>
                    <a href="<?= admin_url('leads/'.$lead['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">
                        <span class="mi mi-sm mi-leading">edit</span>Edit
                    </a>
                    <a href="<?= admin_url('tasks/new?lead_id='.$lead['id']); ?>" class="btn btn-sm btn-outline-secondary">
                        <span class="mi mi-sm mi-leading">task_alt</span>Schedule follow-up
                    </a>
                <?php endif; ?>
                <?php if ($can_convert && !$contact && $lead['status'] !== 'converted'): ?>
                    <?= form_open(admin_url('leads/'.$lead['id'].'/convert'), array('class' => 'd-inline')); ?>
                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Convert this lead to a customer?');">
                            <span class="mi mi-sm mi-leading">person_add</span>Convert to customer
                        </button>
                    <?= form_close(); ?>
                <?php endif; ?>
                <?php if ($contact): ?>
                    <span class="badge bg-success align-self-center">Customer #<?= (int) $contact['id']; ?></span>
                    <a href="<?= admin_url('customers/'.$contact['id']); ?>" class="btn btn-sm btn-outline-success">View customer</a>
                <?php endif; ?>
                <?php if ($can_delete): ?>
                    <?= form_open(admin_url('leads/'.$lead['id'].'/archive'), array('class' => 'd-inline ms-auto')); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this lead?');">
                            <span class="mi mi-sm mi-leading">archive</span>Archive
                        </button>
                    <?= form_close(); ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($chat_channel) && $can_edit): ?>
        <div class="md-card-elevated mt-3">
            <h6 class="mb-2">
                <span class="mi mi-sm mi-leading">chat</span>
                <?= $chat_channel === 'whatsapp' ? 'WhatsApp chat' : 'Instagram chat'; ?>
            </h6>
            <p class="small ymo-muted mb-3">Replies work within Meta’s 24-hour messaging window after the customer’s last message.</p>
            <div class="ymo-chat-thread mb-3">
                <?php if (empty($chat_messages)): ?>
                    <p class="ymo-muted small mb-0">No messages yet. When the customer messages you, the thread appears here.</p>
                <?php else: ?>
                    <?php foreach ($chat_messages as $msg): ?>
                        <div class="ymo-chat-bubble <?= $msg['direction'] === 'outbound' ? 'ymo-chat-out' : 'ymo-chat-in'; ?>">
                            <div class="ymo-chat-text"><?= nl2br(html_escape($msg['text'])); ?></div>
                            <div class="ymo-chat-meta small">
                                <?php if ($msg['direction'] === 'outbound' && !empty($msg['admin_name'])): ?>
                                    <?= html_escape($msg['admin_name']); ?> ·
                                <?php endif; ?>
                                <?= html_escape(date('d M, h:i A', strtotime($msg['created_at']))); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?= form_open(admin_url('leads/'.$lead['id'].'/send-chat')); ?>
                <div class="mb-2">
                    <textarea name="body" class="form-control form-control-sm" rows="3" placeholder="Type your reply…" required maxlength="4096"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <span class="mi mi-sm mi-leading">send</span>Send
                </button>
            <?= form_close(); ?>
        </div>
        <?php endif; ?>

        <div class="md-card-elevated mt-3">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">history</span>Activity timeline</h6>
            <?php if (empty($activities)): ?>
                <p class="ymo-muted small mb-0">No activity yet.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                <?php
                $type_icons = array(
                    'note' => 'sticky_note_2', 'call' => 'call', 'email' => 'mail',
                    'sms' => 'sms', 'whatsapp' => 'chat', 'status_change' => 'swap_horiz',
                    'system' => 'info', 'webhook' => 'webhook',
                );
                $chat_ids = array();
                if (!empty($chat_messages)) {
                    foreach ($chat_messages as $cm) {
                        $chat_ids[] = (int) $cm['id'];
                    }
                }
                foreach ($activities as $a):
                    if (in_array((int) $a['id'], $chat_ids, TRUE)) {
                        continue;
                    }
                ?>
                    <li class="border-bottom py-2">
                        <div class="d-flex gap-2">
                            <span class="mi mi-sm text-muted"><?= isset($type_icons[$a['type']]) ? $type_icons[$a['type']] : 'circle'; ?></span>
                            <div class="flex-grow-1">
                                <div class="small">
                                    <strong class="text-capitalize"><?= html_escape(str_replace('_', ' ', $a['type'])); ?></strong>
                                    <?php if ($a['admin_name']): ?>
                                        · <?= html_escape($a['admin_name']); ?>
                                    <?php endif; ?>
                                    <span class="ymo-muted"> · <?= html_escape(date('d M Y, h:i A', strtotime($a['created_at']))); ?></span>
                                </div>
                                <div class="small"><?= nl2br(html_escape($a['body'])); ?></div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($can_edit): ?>
        <div class="ymo-card mb-3">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">swap_horiz</span>Update stage</h6>
            <?= form_open(admin_url('leads/'.$lead['id'].'/stage')); ?>
                <div class="mb-2">
                    <label class="form-label small mb-1">Next follow-up</label>
                    <input type="datetime-local" name="next_follow_up_at" class="form-control form-control-sm"
                           value="<?= !empty($lead['next_follow_up_at']) ? html_escape(date('Y-m-d\TH:i', strtotime($lead['next_follow_up_at']))) : ''; ?>">
                </div>
                <div class="mb-2">
                    <select name="stage" class="form-select form-select-sm">
                        <?php foreach (crm_lead_stages() as $st => $label): ?>
                            <option value="<?= $st; ?>" <?= $lead['stage'] === $st ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach (array('open','converted','junk') as $st): ?>
                            <option value="<?= $st; ?>" <?= $lead['status'] === $st ? 'selected' : ''; ?>><?= ucfirst($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <select name="priority" class="form-select form-select-sm">
                        <option value="0" <?= (int) $lead['priority'] === 0 ? 'selected' : ''; ?>>Normal priority</option>
                        <option value="1" <?= (int) $lead['priority'] === 1 ? 'selected' : ''; ?>>High priority</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Update</button>
            <?= form_close(); ?>
        </div>

        <div class="ymo-card mb-3">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">add_comment</span>Log activity</h6>
            <?= form_open(admin_url('leads/'.$lead['id'].'/activity')); ?>
                <div class="mb-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="note">Note</option>
                        <option value="call">Call</option>
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>
                <div class="mb-2">
                    <textarea name="body" class="form-control form-control-sm" rows="3" placeholder="What happened?" required></textarea>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">Add</button>
            <?= form_close(); ?>
        </div>
        <?php endif; ?>

        <?php if ($can_assign): ?>
        <div class="ymo-card">
            <h6 class="mb-3"><span class="mi mi-sm mi-leading">person</span>Assign lead</h6>
            <?= form_open(admin_url('leads/'.$lead['id'].'/assign')); ?>
                <div class="mb-2">
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Unassigned</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?= (int) $a['id']; ?>" <?= (int) $lead['assigned_to'] === (int) $a['id'] ? 'selected' : ''; ?>>
                                <?= html_escape($a['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Assign</button>
            <?= form_close(); ?>
        </div>
        <?php endif; ?>
    </div>
</div>
