<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a href="<?= admin_url('campaigns'); ?>" class="small"><span class="mi mi-sm mi-leading">arrow_back</span>All campaigns</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="md-card-elevated">
            <h2 class="h4"><?= html_escape($camp['name']); ?></h2>
            <p class="ymo-muted small"><?= strtoupper($camp['channel']); ?> · <?= html_escape($camp['status']); ?></p>
            <?php if (!empty($retry_of)):
                $earliest = !empty($retry_first_sent) ? strtotime($retry_first_sent.' +7 days') : NULL; ?>
                <div class="ymo-alert ymo-alert-info mb-3"><span class="mi mi-sm">replay</span><span>
                    Audience: the people Meta held back in <a href="<?= admin_url('campaigns/'.$retry_of['id'].'?show=retry'); ?>"><?= html_escape($retry_of['name']); ?></a>. The list is worked out again when you send.
                    <?php if ($earliest): ?><?= $earliest > time()
                        ? 'Meta suggests waiting about a week: schedule it for <strong>'.html_escape(date('d M', $earliest)).'</strong> or later.'
                        : 'It has been over a week since the first send, so it is a good time to send.'; ?><?php endif; ?>
                </span></div>
            <?php endif; ?>
            <?php if ($camp['channel'] === 'whatsapp'): ?>
                <?php if (!empty($wa_tpl)): ?>
                    <p class="small mb-1">Template: <strong><?= html_escape($wa_tpl['name']); ?></strong> (<?= html_escape($wa_tpl['language']); ?>)
                        <?php if ($wa_tpl['status'] !== 'APPROVED'): ?><span class="badge bg-warning text-dark"><?= html_escape($wa_tpl['status']); ?></span><?php endif; ?></p>
                    <div class="border rounded p-3 bg-light small" style="white-space:pre-wrap"><?= html_escape($wa_tpl['body_text']); ?></div>
                    <?php if (!empty($wa_cfg['params'])): ?>
                        <ul class="small mt-2 mb-0">
                            <?php foreach ($wa_cfg['params'] as $i => $p): ?>
                                <li><code>{{<?= $i + 1; ?>}}</code> = <?= html_escape($p); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($wa_cfg['header_link'])): ?>
                        <p class="small mt-1 mb-0">Header media: <a href="<?= html_escape($wa_cfg['header_link']); ?>" target="_blank" rel="noopener"><?= html_escape($wa_cfg['header_link']); ?></a></p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning small mb-0">No WhatsApp template selected - edit the campaign to pick one.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="border rounded p-3 bg-light small"><?= nl2br(html_escape($camp['body'])); ?></div>
            <?php endif; ?>
            <?php if (!empty($can_edit) && in_array($camp['status'], array('draft','scheduled'), TRUE)): ?>
                <?= form_open(admin_url('campaigns/'.$camp['id'].'/test'), array('class' => 'mt-3 p-3 border rounded')); ?>
                    <div class="small fw-semibold mb-2"><span class="mi mi-sm mi-leading">science</span>Send a test to yourself first</div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?php if ($camp['channel'] !== 'email'): ?>
                            <input class="form-control form-control-sm" style="max-width:11rem" name="test_mobile" inputmode="numeric" maxlength="10" placeholder="Your mobile" value="<?= html_escape($test_mobile ?? ''); ?>" required>
                        <?php endif; ?>
                        <?php if (in_array($camp['channel'], array('email', 'both'), TRUE)): ?>
                            <input class="form-control form-control-sm" style="max-width:16rem" type="email" name="test_email" placeholder="Your email" value="<?= html_escape($test_email ?? ''); ?>" required>
                        <?php endif; ?>
                        <button class="btn btn-outline-primary btn-sm">Send test</button>
                    </div>
                    <p class="small ymo-muted mb-0 mt-1">Only you receive it. The campaign stays a draft.</p>
                <?= form_close(); ?>
            <?php endif; ?>
            <?php if (!empty($can_edit)): ?>
                <?= form_open(admin_url('campaigns/'.$camp['id'].'/duplicate'), array('class' => 'mt-2')); ?>
                    <button class="btn btn-link btn-sm px-0"><span class="mi mi-sm mi-leading">content_copy</span>Duplicate as a new draft</button>
                <?= form_close(); ?>
            <?php endif; ?>
            <?php if ($can_send && in_array($camp['status'], array('draft','scheduled'), TRUE)): ?>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <?= form_open(admin_url('campaigns/'.$camp['id'].'/send'), array('class'=>'d-inline')); ?>
                        <button class="btn btn-primary btn-sm" onclick="return confirm('Send now to all matching recipients?');">Send now</button>
                    <?= form_close(); ?>
                    <?= form_open(admin_url('campaigns/'.$camp['id'].'/schedule'), array('class'=>'d-inline-flex gap-2 align-items-center')); ?>
                        <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" required>
                        <button class="btn btn-outline-secondary btn-sm">Schedule</button>
                    <?= form_close(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ymo-card">
            <h6>Delivery stats</h6>
            <ul class="list-unstyled small mb-0">
                <li>Sent: <strong><?= (int) $stats['sent']; ?></strong></li>
                <li>Pending: <strong><?= (int) $stats['pending']; ?></strong></li>
                <li>Failed: <strong><?= (int) $stats['failed']; ?></strong></li>
                <li>Skipped: <strong><?= (int) $stats['skipped']; ?></strong></li>
            </ul>
            <?php if (!empty($delivery)): ?>
                <h6 class="mt-3">WhatsApp receipts</h6>
                <ul class="list-unstyled small mb-0">
                    <li>Delivered: <strong><?= (int) $delivery['delivered']; ?></strong></li>
                    <li>Read: <strong><?= (int) $delivery['read']; ?></strong></li>
                    <li>Failed after send: <strong><?= (int) $delivery['failed']; ?></strong></li>
                </ul>
                <p class="small ymo-muted mt-2 mb-0">Receipts arrive via the WhatsApp webhook (statuses).</p>
            <?php endif; ?>
            <?php if ($camp['status'] === 'sending'): ?>
                <p class="small ymo-muted mt-2 mb-0">Still sending - remaining recipients go out on the next cron run.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (array_sum($counts)): 
    $rq = function (array $c) use ($show) { $p = array_filter(array_merge(array('show' => $show), $c), function ($v) { return $v !== NULL && $v !== '' && $v !== 1; }); return $p ? '?'.http_build_query($p) : ''; };
    $badge = array('read' => 'bg-success', 'delivered' => 'bg-success-subtle text-success-emphasis', 'sent' => 'bg-secondary-subtle text-secondary-emphasis', 'pending' => 'bg-secondary-subtle text-secondary-emphasis',
        'retry' => 'bg-warning-subtle text-warning-emphasis', 'no_whatsapp' => 'bg-danger-subtle text-danger-emphasis', 'bad_number' => 'bg-danger-subtle text-danger-emphasis', 'failed' => 'bg-danger-subtle text-danger-emphasis', 'skipped' => 'bg-light text-dark');
?>
<div class="ymo-card mt-3" id="recipients">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h6 class="mb-0">Who got it</h6>
        <a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('campaigns/'.$camp['id'].'/export'.($show ? '?show='.$show : '')); ?>"><span class="mi mi-sm mi-leading">download</span>Download <?= $show ? '"'.html_escape($groups[$show]['label']).'"' : 'all'; ?> (CSV)</a>
    </div>
    <div class="d-flex flex-wrap gap-1 mb-3">
        <a class="btn btn-sm <?= $show === '' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= admin_url('campaigns/'.$camp['id']); ?>#recipients">All <?= array_sum($counts); ?></a>
        <?php foreach ($groups as $g => $meta): if (!$counts[$g]) { continue; } ?>
            <a class="btn btn-sm <?= $show === $g ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= admin_url('campaigns/'.$camp['id'].'?show='.$g); ?>#recipients"><?= html_escape($meta['label']); ?> <?= (int) $counts[$g]; ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($show !== ''): ?>
        <p class="small ymo-muted mb-2"><?= html_escape($groups[$show]['hint']); ?></p>
    <?php endif; ?>

    <?php if (in_array($show, array('', 'retry'), TRUE) && $counts['retry'] && !empty($can_edit) && $camp['status'] === 'completed'): 
        $earliest = $first_sent ? strtotime($first_sent.' +7 days') : NULL; ?>
        <?= form_open(admin_url('campaigns/'.$camp['id'].'/retry'), array('class' => 'p-3 border rounded mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between')); ?>
            <span class="small"><strong><?= (int) $counts['retry']; ?> people</strong> can be sent this again later<?= $earliest && $earliest > time() ? ', ideally from <strong>'.html_escape(date('d M', $earliest)).'</strong>' : ''; ?>. This makes a draft for just them; nothing is sent yet.</span>
            <button class="btn btn-sm btn-outline-primary"><span class="mi mi-sm mi-leading">replay</span>Create retry campaign</button>
        <?= form_close(); ?>
    <?php endif; ?>
    <?php if (in_array($show, array('', 'no_whatsapp'), TRUE) && $counts['no_whatsapp'] && !empty($can_edit)): ?>
        <?= form_open(admin_url('campaigns/'.$camp['id'].'/no-whatsapp'), array('class' => 'p-3 border rounded mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between')); ?>
            <span class="small"><strong><?= (int) $counts['no_whatsapp']; ?> numbers</strong> aren't on WhatsApp. Tag them "No WhatsApp" so future WhatsApp campaigns skip them (new failures are tagged automatically).</span>
            <button class="btn btn-sm btn-outline-danger"><span class="mi mi-sm mi-leading">label</span>Tag as No WhatsApp</button>
        <?= form_close(); ?>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 small">
            <thead><tr><th>Customer</th><th>Mobile</th><th>Result</th><th>Reason</th><th class="text-nowrap">Sent</th></tr></thead>
            <tbody>
            <?php foreach ($recipients as $r): ?>
                <tr>
                    <td><a href="<?= admin_url('customers?q='.rawurlencode(substr(preg_replace('/\D/', '', $r['mobile']), -10))); ?>"><?= html_escape($r['name'] !== '' ? $r['name'] : '(no name)'); ?></a></td>
                    <td class="text-nowrap"><?= html_escape($r['mobile']); ?></td>
                    <td><span class="badge <?= $badge[$r['outcome']] ?? 'bg-light text-dark'; ?>"><?= html_escape($groups[$r['outcome']]['label']); ?></span></td>
                    <td class="ymo-muted"><?= html_escape(mb_strimwidth($r['reason'], 0, 90, '…')); ?></td>
                    <td class="text-nowrap ymo-muted"><?= $r['sent_at'] ? html_escape(date('d M, h:i A', strtotime($r['sent_at']))) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($r_pages > 1): ?>
        <div class="d-flex justify-content-between small mt-2">
            <?php if ($r_page > 1): ?><a href="<?= admin_url('campaigns/'.$camp['id'].$rq(array('page' => $r_page - 1))); ?>#recipients">‹ Previous</a><?php else: ?><span></span><?php endif; ?>
            <span class="ymo-muted">Page <?= (int) $r_page; ?> of <?= (int) $r_pages; ?> · <?= (int) $r_total; ?> people</span>
            <?php if ($r_page < $r_pages): ?><a href="<?= admin_url('campaigns/'.$camp['id'].$rq(array('page' => $r_page + 1))); ?>#recipients">Next ›</a><?php else: ?><span></span><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
