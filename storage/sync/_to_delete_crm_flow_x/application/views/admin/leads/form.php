<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = !empty($lead);
$action  = $is_edit ? admin_url('leads/'.$lead['id'].'/edit') : admin_url('leads/new');
?>
<a href="<?= $is_edit ? admin_url('leads/'.$lead['id']) : admin_url('leads'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span><?= $is_edit ? 'Back to lead' : 'All leads'; ?>
</a>

<div class="row justify-content-center mt-2">
    <div class="col-lg-8">
        <div class="ymo-card">
            <h2 class="h4 mb-4">
                <span class="mi mi-leading"><?= $is_edit ? 'edit' : 'person_add'; ?></span>
                <?= $is_edit ? 'Edit lead' : 'New lead'; ?>
            </h2>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open($action); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_name" name="name" placeholder=" " required
                                   value="<?= html_escape(set_value('name', $lead['name'] ?? '')); ?>">
                            <label for="ld_name">Full name *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="ld_source_id" name="source_id" required>
                                <?php
                                $default_source = $lead['source_id'] ?? null;
                                if (!$default_source) {
                                    foreach ($sources as $sx) {
                                        if ($sx['slug'] === 'manual') {
                                            $default_source = (int) $sx['id'];
                                            break;
                                        }
                                    }
                                }
                                foreach ($sources as $s):
                                ?>
                                    <option value="<?= (int) $s['id']; ?>"
                                        <?= (int) set_value('source_id', $default_source) === (int) $s['id'] ? 'selected' : ''; ?>>
                                        <?= html_escape($s['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_source_id">Lead source *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_mobile" name="mobile" placeholder=" "
                                   value="<?= html_escape(set_value('mobile', $lead['mobile'] ?? '')); ?>">
                            <label for="ld_mobile">Mobile</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_email" type="email" name="email" placeholder=" "
                                   value="<?= html_escape(set_value('email', $lead['email'] ?? '')); ?>">
                            <label for="ld_email">Email</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_company" name="company" placeholder=" "
                                   value="<?= html_escape(set_value('company', $lead['company'] ?? '')); ?>">
                            <label for="ld_company">Workshop</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_address" name="address" placeholder=" "
                                   value="<?= html_escape(set_value('address', $lead ? crm_lead_field($lead, 'address') : '')); ?>">
                            <label for="ld_address">Address</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="ld_car_type" name="car_type" placeholder=" "
                                   value="<?= html_escape(set_value('car_type', $lead ? crm_lead_field($lead, 'car_type') : '')); ?>">
                            <label for="ld_car_type">Car type</label>
                        </div>
                    </div>
                    <?php if (crm_can('leads.assign')): ?>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="ld_assigned" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($admins as $a): ?>
                                    <option value="<?= (int) $a['id']; ?>"
                                        <?= (int) set_value('assigned_to', $lead['assigned_to'] ?? 0) === (int) $a['id'] ? 'selected' : ''; ?>>
                                        <?= html_escape($a['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_assigned">Assign to</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select" id="ld_stage" name="stage">
                                <?php $cur_stage = set_value('stage', $lead['stage'] ?? 'new');
                                foreach (crm_lead_stages() as $st => $label):
                                    if ($st === 'booked' && $cur_stage !== 'booked') { continue; } ?>
                                    <option value="<?= $st; ?>" <?= $cur_stage === $st ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_stage">Stage</label>
                        </div>
                    </div>
                    <div class="col-md-4" id="ld_lost_wrap" <?= $cur_stage === 'lost' ? '' : 'hidden'; ?>>
                        <div class="form-floating">
                            <select class="form-select" id="ld_lost_reason" name="lost_reason">
                                <?php foreach (crm_lead_lost_reasons() as $k => $label): ?>
                                    <option value="<?= $k; ?>" <?= set_value('lost_reason', $lead['lost_reason'] ?? '') === $k ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ld_lost_reason">Why lost?</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="datetime-local" class="form-control" id="ld_follow" name="next_follow_up_at"
                                   value="<?= html_escape(set_value('next_follow_up_at', !empty($lead['next_follow_up_at']) ? date('Y-m-d\TH:i', strtotime($lead['next_follow_up_at'])) : '')); ?>">
                            <label for="ld_follow">Next follow-up</label>
                        </div>
                    </div>
                    <p class="col-12 small ymo-muted mb-0">To book this customer, save and use <strong>Book service</strong> on the lead page.</p>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select" id="ld_priority" name="priority">
                                <option value="0" <?= (int) set_value('priority', $lead['priority'] ?? 0) === 0 ? 'selected' : ''; ?>>Normal</option>
                                <option value="1" <?= (int) set_value('priority', $lead['priority'] ?? 0) === 1 ? 'selected' : ''; ?>>High priority</option>
                            </select>
                            <label for="ld_priority">Priority</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="ld_message" name="message" placeholder=" " style="height:120px"><?= html_escape(set_value('message', $lead['message'] ?? '')); ?></textarea>
                            <label for="ld_message">Enquiry / notes</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <span class="mi mi-sm mi-leading">save</span><?= $is_edit ? 'Save changes' : 'Create lead'; ?>
                        </button>
                    </div>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
<script>
(function () {
    var st = document.getElementById('ld_stage'), wrap = document.getElementById('ld_lost_wrap');
    if (st && wrap) { st.addEventListener('change', function () { wrap.hidden = st.value !== 'lost'; }); }
})();
</script>
