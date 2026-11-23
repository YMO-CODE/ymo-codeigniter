<?php defined('BASEPATH') OR exit('No direct script access allowed');
$by_name = array();
foreach ($rows as $r) {
    $by_name[$r['name'].'|'.$r['language']] = $r;
}
$badge = function ($status) {
    $map = array('APPROVED' => 'bg-success', 'PENDING' => 'bg-warning text-dark', 'REJECTED' => 'bg-danger',
        'PAUSED' => 'bg-secondary', 'DISABLED' => 'bg-secondary', 'DELETED' => 'bg-light text-muted border');
    return isset($map[$status]) ? $map[$status] : 'bg-secondary';
};
?>
<?php $this->load->view('admin/partials/messages_tabs', array('active' => 'templates')); ?>
<a href="<?= admin_url('campaigns'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>Campaigns</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="ymo-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="h4 mb-0">WhatsApp templates</h2>
                <?php if ($can_sync && $migrated): ?>
                    <?= form_open(admin_url('whatsapp-templates/sync'), array('class' => 'd-inline')); ?>
                        <button class="btn btn-primary btn-sm" <?= $api_ready ? '' : 'disabled'; ?>><span class="mi mi-sm mi-leading">sync</span>Sync from WhatsApp Manager</button>
                    <?= form_close(); ?>
                <?php endif; ?>
            </div>
            <?php if (!$migrated): ?>
                <div class="alert alert-warning small mb-0">Run <code>database/crm_migration_v5_whatsapp.sql</code> to enable WhatsApp templates.</div>
            <?php elseif (empty($rows)): ?>
                <p class="ymo-muted small mb-0">No templates synced yet. Create and get templates approved in WhatsApp Manager, then press Sync. Suggested texts are in CRM_SETUP.md (WhatsApp section).</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="ymo-table mb-0">
                    <thead><tr><th>Name</th><th>Lang</th><th>Category</th><th>Status</th><th>Vars</th><th>Body</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $t): ?>
                        <tr>
                            <td class="small"><strong><?= html_escape($t['name']); ?></strong><?php if ($t['header_type']): ?><br><span class="ymo-muted">header: <?= html_escape($t['header_type']); ?></span><?php endif; ?></td>
                            <td class="small"><?= html_escape($t['language']); ?></td>
                            <td class="small"><?= html_escape(strtolower((string) $t['category'])); ?></td>
                            <td class="small"><span class="badge <?= $badge($t['status']); ?>"><?= html_escape($t['status']); ?></span></td>
                            <td class="small"><?= (int) $t['param_count']; ?></td>
                            <td class="small" style="max-width:340px;white-space:pre-wrap"><?= html_escape(mb_strimwidth((string) $t['body_text'], 0, 220, '…')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <p class="small ymo-muted mt-2 mb-0">Last synced: <?= html_escape(date('d M Y H:i', strtotime($rows[0]['synced_at'] ?? 'now'))); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($library)): ?>
        <div class="ymo-card mt-3" id="ready-templates">
            <h2 class="h5 mb-1">Ready-to-use templates</h2>
            <p class="small ymo-muted">Copy each field into <strong>WhatsApp Manager → Message templates → Create template</strong> (language: English). Booking templates are picked up automatically once approved; campaign templates appear in the campaign form after Sync.</p>
            <?php foreach (array('Booking notifications' => TRUE, 'Campaigns' => FALSE) as $group => $is_event): ?>
                <h6 class="mt-3"><?= $group; ?></h6>
                <?php foreach ($library as $i => $t): ?>
                    <?php if ((bool) $t['event'] !== $is_event) { continue; } ?>
                    <?php $synced = $by_name[$t['name'].'|en'] ?? NULL; ?>
                    <details class="border rounded p-2 mb-2">
                        <summary class="small">
                            <code><?= html_escape($t['name']); ?></code>
                            <span class="ymo-muted">· <?= html_escape(strtolower($t['category'])); ?><?= $t['header'] ? ' · '.html_escape($t['header']['type']).' header' : ''; ?><?= $t['event'] ? ' · used for '.html_escape($t['event']) : ''; ?></span>
                            <?php if ($synced): ?>
                                <span class="badge <?= $badge($synced['status']); ?>"><?= html_escape(strtolower($synced['status'])); ?></span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">not created</span>
                            <?php endif; ?>
                        </summary>
                        <div class="small mt-2">
                            <?php
                            $fields = array('Name' => $t['name'], 'Category' => ucfirst(strtolower($t['category'])));
                            if ($t['header']) {
                                $fields['Header'] = $t['header']['type'] === 'text' ? $t['header']['text'] : ucfirst($t['header']['type']).' (upload any sample '.$t['header']['type'].' when submitting)';
                            }
                            $fields['Body'] = $t['body'];
                            if (!empty($t['footer'])) { $fields['Footer'] = $t['footer']; }
                            ?>
                            <?php foreach ($fields as $label => $value): ?>
                                <div class="d-flex gap-2 align-items-start mb-1">
                                    <span class="ymo-muted" style="min-width:70px"><?= $label; ?></span>
                                    <span class="flex-grow-1" style="white-space:pre-wrap"><?= html_escape($value); ?></span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 js-copy" data-copy="<?= html_escape($value); ?>">Copy</button>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!empty($t['buttons'])): ?>
                                <div class="d-flex gap-2 mb-1">
                                    <span class="ymo-muted" style="min-width:70px">Buttons</span>
                                    <span><?php foreach ($t['buttons'] as $b): ?>
                                        <span class="badge bg-light text-dark border me-1"><?= $b['type'] === 'URL' ? 'Visit website: ' : 'Quick reply: '; ?><?= html_escape($b['text']); ?><?= !empty($b['url']) ? ' → '.html_escape($b['url']) : ''; ?></span>
                                    <?php endforeach; ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2 mb-1">
                                <span class="ymo-muted" style="min-width:70px">Samples</span>
                                <span><?php foreach ($t['examples'] as $n => $ex): ?><code>{{<?= $n + 1; ?>}}</code> <?= html_escape($ex); ?><?= $n + 1 < count($t['examples']) ? ' · ' : ''; ?><?php endforeach; ?></span>
                            </div>
                            <?php if (!$synced && (empty($t['header']) || $t['header']['type'] === 'text')): ?>
                                <?= form_open(admin_url('whatsapp-templates/submit'), array('class' => 'mt-2')); ?>
                                    <input type="hidden" name="name" value="<?= html_escape($t['name']); ?>">
                                    <button class="btn btn-primary btn-sm"><span class="mi mi-sm mi-leading">send</span>Submit to Meta for approval</button>
                                <?= form_close(); ?>
                            <?php endif; ?>
                            <?php if (!empty($t['campaign_hint'])): ?>
                                <div class="ymo-muted mt-1">In the campaign form: <?= html_escape($t['campaign_hint']); ?></div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <script>
        document.querySelectorAll('#ready-templates .js-copy').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy');
                var done = function () { var o = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = o; }, 1200); };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(done);
                } else {
                    var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta);
                    ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
                }
            });
        });
        </script>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="ymo-card mb-3">
            <h6>Setup status</h6>
            <ul class="list-unstyled small mb-0">
                <li><?= $send_ready ? '✅' : '⬜'; ?> Cloud API sending (token + phone number id)</li>
                <li><?= $api_ready ? '✅' : '⬜'; ?> Template sync (WABA id)</li>
                <li><?= ($notify_on && $send_ready) ? '✅' : '⬜'; ?> Booking notifications on WhatsApp <span class="ymo-muted">(CRM_WHATSAPP_NOTIFY=1, SMS fallback)</span></li>
            </ul>
        </div>
        <div class="ymo-card">
            <h6>Customer notifications</h6>
            <p class="small ymo-muted">Event → template the app looks for. Missing or unapproved templates fall back to SMS.</p>
            <ul class="list-unstyled small mb-0">
                <?php foreach ($event_map as $event => $cfg): ?>
                    <?php $row = $by_name[($cfg['name'] ?? '').'|'.($cfg['lang'] ?? 'en')] ?? NULL; ?>
                    <li class="mb-1">
                        <span class="ymo-muted"><?= html_escape($event); ?></span> → <code><?= html_escape($cfg['name'] ?? '-'); ?></code>
                        <?php if ($row): ?>
                            <span class="badge <?= $badge($row['status']); ?>"><?= html_escape(strtolower($row['status'])); ?></span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border">not found</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
