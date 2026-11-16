<?php defined('BASEPATH') OR exit('No direct script access allowed');
$seg = json_decode($camp['segment_json'] ?? '{}', TRUE) ?: array();
$wa_templates = isset($wa_templates) ? $wa_templates : array();
$wa_ready = !empty($wa_ready);
$wa_cfg = isset($wa_cfg) ? $wa_cfg : array('params' => array(), 'header_link' => '');
$cur_channel = set_value('channel', $camp['channel'] ?? 'email');
$cur_tpl = (int) set_value('wa_template_id', $camp['wa_template_id'] ?? 0);
$posted_params = $this->input->post('wa_params');
$cur_params = is_array($posted_params) ? array_values($posted_params) : $wa_cfg['params'];
$wa_selectable = $wa_ready && !empty($wa_templates);
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ymo-card">
            <h2 class="h4 mb-4"><?= $camp ? 'Edit campaign' : 'New campaign'; ?></h2>
            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?= form_open($camp ? admin_url('campaigns/'.$camp['id'].'/edit') : admin_url('campaigns/new')); ?>
            <div class="row g-3">
                <div class="col-md-8"><div class="form-floating"><input class="form-control" name="name" required value="<?= html_escape(set_value('name', $camp['name'] ?? '')); ?>"><label>Campaign name *</label></div></div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" name="channel">
                            <?php foreach (array('email','sms','both') as $ch): ?>
                                <option value="<?= $ch; ?>" <?= $cur_channel === $ch ? 'selected' : ''; ?>><?= strtoupper($ch); ?></option>
                            <?php endforeach; ?>
                            <option value="whatsapp" <?= $cur_channel === 'whatsapp' ? 'selected' : ''; ?> <?= (!$wa_selectable && $cur_channel !== 'whatsapp') ? 'disabled' : ''; ?>>WHATSAPP<?= $wa_selectable ? '' : ($wa_ready ? ' (sync templates first)' : ' (not configured)'); ?></option>
                        </select>
                        <label>Channel</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="segment_type">
                            <option value="all_contacts" <?= ($seg['type'] ?? '') === 'all_contacts' ? 'selected' : ''; ?>>All contacts</option>
                            <option value="leads_open" <?= ($seg['type'] ?? '') === 'leads_open' ? 'selected' : ''; ?>>Open leads</option>
                            <option value="tag" <?= ($seg['type'] ?? '') === 'tag' ? 'selected' : ''; ?>>Contacts with tag</option>
                        </select>
                        <label>Audience</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="tag_id">
                            <option value="">-</option>
                            <?php foreach ($tags as $t): ?>
                                <option value="<?= (int) $t['id']; ?>" <?= (int)($seg['tag_id'] ?? 0) === (int)$t['id'] ? 'selected' : ''; ?>><?= html_escape($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Tag (if segment = tag)</label>
                    </div>
                </div>
                <div class="col-12 js-wa-only">
                    <div class="border rounded p-3 bg-light">
                        <div class="form-floating mb-2">
                            <select class="form-select" name="wa_template_id" id="wa_template_id">
                                <option value="">- Pick an approved template -</option>
                                <?php foreach ($wa_templates as $t): ?>
                                    <option value="<?= (int) $t['id']; ?>"
                                        data-params="<?= (int) $t['param_count']; ?>"
                                        data-header="<?= html_escape((string) $t['header_type']); ?>"
                                        data-body="<?= html_escape((string) $t['body_text']); ?>"
                                        <?= $cur_tpl === (int) $t['id'] ? 'selected' : ''; ?>><?= html_escape($t['name']); ?> (<?= html_escape($t['language']); ?> · <?= html_escape(strtolower((string) $t['category'])); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <label>WhatsApp template *</label>
                        </div>
                        <div class="small ymo-muted mb-2" id="wa_body_preview" style="white-space:pre-wrap"></div>
                        <div id="wa_params"></div>
                        <div class="form-floating mt-2" id="wa_header_wrap" style="display:none">
                            <input class="form-control" name="wa_header_link" placeholder="https://" value="<?= html_escape(set_value('wa_header_link', $wa_cfg['header_link'])); ?>">
                            <label id="wa_header_label">Header media link (public https URL)</label>
                        </div>
                        <p class="small ymo-muted mb-0 mt-2">Variables can use <code>{first_name}</code>, <code>{name}</code> or <code>{mobile}</code>. Templates are created and approved in WhatsApp Manager, then pulled in from <a href="<?= admin_url('whatsapp-templates'); ?>">WhatsApp templates</a>. Contacts who replied STOP are skipped.</p>
                    </div>
                </div>
                <div class="col-12 js-not-wa"><div class="form-floating"><input class="form-control" name="subject" value="<?= html_escape(set_value('subject', $camp['subject'] ?? '')); ?>"><label>Email subject</label></div></div>
                <div class="col-12"><div class="form-floating"><textarea class="form-control" name="body" id="camp_body" style="height:160px"><?= html_escape(set_value('body', $camp['body'] ?? '')); ?></textarea><label id="camp_body_label">Message body *</label></div></div>
                <div class="col-12"><button class="btn btn-primary">Save draft</button></div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
<script>
(function () {
    var channel = document.querySelector('select[name="channel"]');
    var tplSel = document.getElementById('wa_template_id');
    var paramsBox = document.getElementById('wa_params');
    var preview = document.getElementById('wa_body_preview');
    var headerWrap = document.getElementById('wa_header_wrap');
    var headerLabel = document.getElementById('wa_header_label');
    var body = document.getElementById('camp_body');
    var bodyLabel = document.getElementById('camp_body_label');
    var initial = <?= json_encode(array_values($cur_params)); ?>;
    if (!channel || !tplSel) { return; }

    function renderParams() {
        var opt = tplSel.options[tplSel.selectedIndex];
        var n = opt && opt.value ? parseInt(opt.getAttribute('data-params') || '0', 10) : 0;
        var header = opt && opt.value ? (opt.getAttribute('data-header') || '') : '';
        preview.textContent = opt && opt.value ? (opt.getAttribute('data-body') || '') : '';
        var existing = Array.prototype.map.call(paramsBox.querySelectorAll('input'), function (i) { return i.value; });
        var values = existing.length ? existing : initial;
        paramsBox.innerHTML = '';
        for (var i = 0; i < n; i++) {
            var wrap = document.createElement('div');
            wrap.className = 'form-floating mb-2';
            var input = document.createElement('input');
            input.className = 'form-control';
            input.name = 'wa_params[]';
            input.required = channel.value === 'whatsapp';
            input.placeholder = '{{' + (i + 1) + '}}';
            input.value = values[i] !== undefined ? values[i] : (i === 0 ? '{first_name}' : '');
            var label = document.createElement('label');
            label.textContent = 'Variable {{' + (i + 1) + '}}';
            wrap.appendChild(input);
            wrap.appendChild(label);
            paramsBox.appendChild(wrap);
        }
        var media = ['image', 'document', 'video'].indexOf(header) !== -1;
        headerWrap.style.display = media ? '' : 'none';
        headerLabel.textContent = 'Header ' + header + ' link (public https URL) *';
    }

    function toggle() {
        var wa = channel.value === 'whatsapp';
        document.querySelectorAll('.js-wa-only').forEach(function (el) { el.style.display = wa ? '' : 'none'; });
        document.querySelectorAll('.js-not-wa').forEach(function (el) { el.style.display = wa ? 'none' : ''; });
        tplSel.required = wa;
        body.required = !wa;
        bodyLabel.textContent = wa ? 'Internal note (optional - not sent)' : 'Message body *';
        paramsBox.querySelectorAll('input').forEach(function (i) { i.required = wa; });
    }

    channel.addEventListener('change', toggle);
    tplSel.addEventListener('change', function () { initial = []; paramsBox.innerHTML = ''; renderParams(); });
    renderParams();
    toggle();
})();
</script>
