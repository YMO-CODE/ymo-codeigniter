<?php defined('BASEPATH') OR exit('No direct script access allowed');
$stars = function ($n) {
    $n = (int) $n;
    $cls = $n >= 4 ? 'text-success' : ($n === 3 ? 'text-warning' : 'text-danger');
    return '<span class="'.$cls.'" title="'.$n.' stars" style="letter-spacing:1px">'.str_repeat('★', $n).'<span class="text-muted">'.str_repeat('☆', 5 - $n).'</span></span>';
};
$wlabel = function ($slug) use ($workshops) {
    return isset($workshops[$slug]) ? $workshops[$slug]['label'] : ucfirst((string) $slug);
};
$action_label = array(
    'auto_replied' => array('Auto-replied', 'bg-success'),
    'alerted' => array('Sent to team', 'bg-warning text-dark'),
    'failed' => array('Reply failed', 'bg-danger'),
    'replied_manual' => array('Replied by team', 'bg-success'),
    'had_reply' => array('Already replied', 'bg-light text-dark border'),
    'recorded' => array('Recorded', 'bg-light text-dark border'),
    'old' => array('Old, not replied', 'bg-light text-dark border'),
    'new' => array('New', 'bg-info text-dark'),
);
$ago = function ($dt) {
    if (!$dt) { return ''; }
    $d = (int) floor((time() - strtotime($dt)) / 86400);
    return $d <= 0 ? 'today' : ($d === 1 ? 'yesterday' : ($d < 60 ? $d.' days ago' : date('d M Y', strtotime($dt))));
};
?>
<style>
.rv-stat { min-width: 140px; }
.rv-stat .n { font-size: 1.6rem; font-weight: 600; line-height: 1.1; }
.rv-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: .75rem .9rem; margin-bottom: .6rem; background: #fff; }
.rv-item.focus { box-shadow: 0 0 0 3px rgba(46,125,50,.25); }
.rv-reason { background: #fff8e1; border-radius: 6px; padding: .35rem .55rem; font-size: .85rem; }
.rv-reply { background: #f1f8f1; border-radius: 6px; padding: .35rem .55rem; font-size: .85rem; }
.rv-table td { vertical-align: top; font-size: .85rem; }
.rv-filters .btn { --bs-btn-padding-y: .15rem; --bs-btn-padding-x: .55rem; --bs-btn-font-size: .8rem; }
</style>

<div class="d-flex flex-wrap gap-2 mb-3">
    <div class="ymo-card p-3 rv-stat"><div class="small ymo-muted">Need attention</div><div class="n <?= $counts['attention'] ? 'text-danger' : ''; ?>"><?= (int) $counts['attention']; ?></div></div>
    <div class="ymo-card p-3 rv-stat"><div class="small ymo-muted">New reviews · 30 days</div><div class="n"><?= (int) $counts['new_30d']; ?></div></div>
    <div class="ymo-card p-3 rv-stat"><div class="small ymo-muted">Average · 30 days</div><div class="n"><?= $counts['avg_30d'] !== NULL ? $counts['avg_30d'].'★' : '–'; ?></div></div>
    <div class="ymo-card p-3 rv-stat"><div class="small ymo-muted">Auto-replied · 30 days</div><div class="n"><?= (int) $counts['auto_30d']; ?></div></div>
    <div class="ymo-card p-3 rv-stat"><div class="small ymo-muted">Review requests sent · 30 days</div><div class="n"><?= (int) $asked_30d; ?></div></div>
</div>

<?php if (!$connected): ?>
<div class="alert alert-warning small">Google is not connected yet, so reviews are not being checked. <?= $can_setup ? '<a href="#setup">Set it up below</a>.' : 'Ask an admin to connect it.'; ?> Review requests to customers already work.</div>
<?php endif; ?>

<div class="row g-3">
<div class="col-lg-7">
    <div class="ymo-card p-3 mb-3" id="attention">
        <h2 class="h6 mb-1">Needs your attention<?= $att_pager['total'] ? ' <span class="badge bg-danger align-middle">'.(int) $att_pager['total'].'</span>' : ''; ?></h2>
        <p class="small ymo-muted mb-2">1–3★ reviews are never answered automatically: reply yourself (and call the customer). 4★ reviews are answered automatically and shown here with why a star was lost.</p>
        <?php if (!$attention): ?>
            <p class="small mb-0">Nothing waiting. 👍</p>
        <?php endif; ?>
        <?php foreach ($attention as $r): ?>
            <div class="rv-item<?= $focus === (int) $r['id'] ? ' focus' : ''; ?>" id="r<?= (int) $r['id']; ?>">
                <div class="d-flex justify-content-between flex-wrap gap-1">
                    <div><?= $stars($r['stars']); ?> <strong><?= html_escape($r['author']); ?></strong>
                        <span class="small ymo-muted">· <?= html_escape($wlabel($r['workshop'])); ?> · <?= html_escape($ago($r['review_time'])); ?></span></div>
                    <?php $al = isset($action_label[$r['action']]) ? $action_label[$r['action']] : array($r['action'], 'bg-light'); ?>
                    <span class="badge <?= $al[1]; ?>"><?= (int) $r['stars'] === 4 && $r['action'] === 'alerted' ? 'Auto-replied · why not 5★' : html_escape($al[0]); ?></span>
                </div>
                <p class="small mb-2 mt-1" style="white-space:pre-wrap"><?= $r['comment'] !== '' ? html_escape($r['comment']) : '<span class="ymo-muted">(no comment)</span>'; ?></p>
                <?php if (!empty($r['reason'])): ?>
                    <div class="rv-reason mb-2"><strong><?= (int) $r['stars'] === 4 ? 'Why not 5★:' : 'What went wrong:'; ?></strong> <?= html_escape($r['reason']); ?></div>
                <?php endif; ?>
                <?php if (!empty($r['reply_text'])): ?>
                    <div class="rv-reply mb-2"><strong>Our reply on Google:</strong> <?= html_escape($r['reply_text']); ?></div>
                <?php endif; ?>
                <?php if ($r['action'] === 'failed' && $r['error']): ?>
                    <div class="small text-danger mb-2"><?= html_escape($r['error']); ?></div>
                <?php endif; ?>
                <?php if ($can_reply): ?>
                    <?php if (empty($r['reply_text'])): ?>
                    <?= form_open(admin_url('reviews/'.(int) $r['id'].'/reply')); ?>
                        <textarea name="text" class="form-control form-control-sm mb-2" rows="3" maxlength="4000" placeholder="Your public reply on Google (e.g. apologise, say what you have done, invite them to call 75587 83868)"></textarea>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" <?= $connected ? '' : 'disabled'; ?>>Post reply on Google</button>
                    <?= form_close(); ?>
                    <?= form_open(admin_url('reviews/'.(int) $r['id'].'/done'), array('class' => 'd-inline')); ?>
                            <button class="btn btn-sm btn-outline-secondary">Mark handled</button>
                        </div>
                    <?= form_close(); ?>
                    <?php else: ?>
                    <?= form_open(admin_url('reviews/'.(int) $r['id'].'/done')); ?>
                        <button class="btn btn-sm btn-outline-secondary">Got it, mark handled</button>
                    <?= form_close(); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?= crm_list_pager($att_pager); ?>
    </div>

    <div class="ymo-card p-3 mb-3" id="latest">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="h6 mb-0">Latest Google reviews</h2>
            <?php
            $fq = function (array $set) use ($f_ws, $f_stars) {
                $q = array_filter(array('ws' => $f_ws, 'stars' => $f_stars) + array());
                foreach ($set as $k => $v) { if ($v === '') { unset($q[$k]); } else { $q[$k] = $v; } }
                $keep = array_intersect_key($_GET, array('ap' => 1, 'qp' => 1));
                $q = $keep + $q;
                return admin_url('reviews'.($q ? '?'.http_build_query($q) : '')).'#latest';
            };
            ?>
            <div class="rv-filters d-flex flex-wrap gap-1 align-items-center">
                <div class="btn-group" role="group" aria-label="Stars">
                    <?php foreach (array('' => 'All', '5' => '5★', '4' => '4★', 'low' => '1–3★') as $k => $lbl): ?>
                        <a class="btn <?= $f_stars === (string) $k ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= html_escape($fq(array('stars' => (string) $k))); ?>"><?= $lbl; ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="btn-group" role="group" aria-label="Workshop">
                    <a class="btn <?= $f_ws === '' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= html_escape($fq(array('ws' => ''))); ?>">All workshops</a>
                    <?php foreach ($workshops as $slug => $w): ?>
                        <a class="btn <?= $f_ws === $slug ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= html_escape($fq(array('ws' => $slug))); ?>"><?= html_escape($w['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php if (!$reviews): ?>
            <p class="small ymo-muted mb-0"><?= ($f_ws !== '' || $f_stars !== '') ? 'No reviews match these filters.' : 'No reviews checked yet. They appear here after the first daily check.'; ?></p>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-sm rv-table mb-0">
            <thead><tr><th>Review</th><th>Workshop</th><th>What happened</th></tr></thead>
            <tbody>
            <?php foreach ($reviews as $r): ?>
                <?php $al = isset($action_label[$r['action']]) ? $action_label[$r['action']] : array($r['action'], 'bg-light'); ?>
                <tr>
                    <td><?= $stars($r['stars']); ?> <strong><?= html_escape($r['author']); ?></strong> <span class="ymo-muted"><?= html_escape($ago($r['review_time'])); ?></span>
                        <?php if ($r['comment'] !== ''): ?><div class="ymo-muted"><?= html_escape(mb_strimwidth($r['comment'], 0, 160, '…')); ?></div><?php endif; ?>
                        <?php if (!empty($r['reply_text']) && in_array($r['action'], array('auto_replied', 'alerted', 'replied_manual'), TRUE)): ?><div class="mt-1"><span class="ymo-muted">↳</span> <?= html_escape(mb_strimwidth($r['reply_text'], 0, 160, '…')); ?></div><?php endif; ?>
                    </td>
                    <td><?= html_escape($wlabel($r['workshop'])); ?></td>
                    <td><span class="badge <?= $al[1]; ?>"><?= html_escape($al[0]); ?></span><?= $r['handled_at'] ? '<div class="ymo-muted">handled</div>' : ''; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?= crm_list_pager($rev_pager); ?>
        <?php endif; ?>
    </div>
</div>

<div class="col-lg-5">
    <div class="ymo-card p-3 mb-3" id="ask">
        <h2 class="h6 mb-1">Ask a customer for a review</h2>
        <p class="small ymo-muted mb-2">Sends a WhatsApp with a <em>Write a Google review</em> button for the chosen workshop (SMS if WhatsApp fails). Bookings marked completed are asked automatically.</p>
        <?php if ($can_reply): ?>
        <?= form_open(admin_url('reviews/ask')); ?>
            <div class="row g-2">
                <div class="col-6"><input name="name" class="form-control form-control-sm" placeholder="Customer name" maxlength="120"></div>
                <div class="col-6"><input name="mobile" class="form-control form-control-sm" placeholder="Mobile number" required inputmode="tel"></div>
                <div class="col-6">
                    <select name="workshop" class="form-select form-select-sm">
                        <?php foreach ($workshops as $slug => $w): ?><option value="<?= html_escape($slug); ?>"><?= html_escape($w['label']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6"><button class="btn btn-sm btn-primary w-100"><span class="mi mi-sm mi-leading">send</span>Send request</button></div>
            </div>
            <label class="small ymo-muted mt-2 d-flex gap-1 align-items-center"><input type="checkbox" name="force" value="1"> Send even if we asked this number in the last 30 days</label>
        <?= form_close(); ?>
        <details class="mt-3" id="ask-bulk">
            <summary class="small fw-semibold">Send to many customers at once</summary>
            <?= form_open(admin_url('reviews/ask-bulk'), array('class' => 'mt-2')); ?>
                <textarea name="lines" class="form-control form-control-sm mb-2" rows="6" required placeholder="One customer per line, e.g.&#10;Rahul Patil, 98220 00011&#10;Sneha 9876543210 warje&#10;+91 99220 12345"></textarea>
                <div class="d-flex gap-2 align-items-center">
                    <select name="workshop" class="form-select form-select-sm w-auto" title="Workshop for lines that don't name one">
                        <?php foreach ($workshops as $slug => $w): ?><option value="<?= html_escape($slug); ?>"><?= html_escape($w['label']); ?></option><?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary"><span class="mi mi-sm mi-leading">send</span>Send to all</button>
                </div>
                <p class="small ymo-muted mt-1 mb-0">Up to 50 lines. Write a workshop name (hinjewadi, warje, indore, nashik) on a line to override the dropdown. Numbers asked in the last 30 days are skipped.</p>
            <?= form_close(); ?>
        </details>
        <?php endif; ?>
        <?php if ($requests): ?>
            <h3 class="small fw-semibold mt-3 mb-1" id="asked">Recently asked</h3>
            <div class="table-responsive">
            <table class="table table-sm rv-table mb-0">
                <?php foreach ($requests as $q): ?>
                <tr>
                    <td><?= html_escape($q['name'] ?: '—'); ?><div class="ymo-muted"><?= html_escape($q['mobile']); ?></div></td>
                    <td><?= html_escape($wlabel($q['workshop'])); ?><div class="ymo-muted"><?= html_escape($q['source']); ?></div></td>
                    <td><?= $q['status'] === 'sent' ? '<span class="badge bg-success">'.html_escape($q['channel'] ?: 'sent').'</span>' : '<span class="badge bg-danger" title="'.html_escape((string) $q['error']).'">failed</span>'; ?>
                        <div class="ymo-muted"><?= html_escape(date('d M H:i', strtotime($q['created_at']))); ?></div></td>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>
            <?= crm_list_pager($req_pager); ?>
        <?php endif; ?>
    </div>

    <div class="ymo-card p-3 mb-3" id="setup">
        <h2 class="h6 mb-1">Google connection</h2>
        <p class="small ymo-muted">Reviews are checked once a day. 5★ and 4★ get an automatic public reply<?= $ai_on ? ' written by Claude' : ''; ?>; 4★ and below also alert the team (phone + email).</p>
        <?php if (!empty($last_check['at'])): ?>
            <?php $ls = isset($last_check['summary']) ? $last_check['summary'] : array(); ?>
            <p class="small mb-2">Last check: <?= html_escape(date('d M, H:i', strtotime($last_check['at']))); ?> ·
                <?= (int) ($ls['new'] ?? 0); ?> new · <?= (int) ($ls['replied'] ?? 0); ?> replied · <?= (int) ($ls['alerted'] ?? 0); ?> alerts
                <?php if (!empty($ls['errors'])): ?><br><span class="text-danger"><?= html_escape(implode(' ', $ls['errors'])); ?></span><?php endif; ?></p>
        <?php endif; ?>

        <?php if ($can_setup): ?>
            <?php if (!$client): ?>
                <ol class="small ps-3 mb-2">
                    <li>Wait for Google to approve Business Profile API access for the Cloud project.</li>
                    <li>In that project: <em>APIs &amp; Services → Credentials → Create OAuth client ID → Web application</em>.</li>
                    <li>Add this redirect URI: <code class="user-select-all"><?= html_escape($redirect_uri); ?></code></li>
                    <li>Paste the client id and secret here.</li>
                </ol>
                <?= form_open(admin_url('reviews/credentials')); ?>
                    <input name="client_id" class="form-control form-control-sm mb-2" placeholder="Client ID (…apps.googleusercontent.com)" required>
                    <input name="client_secret" type="password" class="form-control form-control-sm mb-2" placeholder="Client secret" required autocomplete="off">
                    <button class="btn btn-sm btn-primary">Save</button>
                <?= form_close(); ?>
            <?php elseif (!$connected): ?>
                <p class="small mb-2">Redirect URI in Google Cloud must be <code class="user-select-all"><?= html_escape($redirect_uri); ?></code>. Sign in with the account that manages the listings (ashish@yourmechaniconline.com).</p>
                <a class="btn btn-sm btn-primary" href="<?= admin_url('reviews/connect'); ?>"><span class="mi mi-sm mi-leading">link</span>Connect Google account</a>
                <?php if (!$client_env): ?>
                    <details class="small mt-2"><summary>Change client id / secret</summary>
                    <?= form_open(admin_url('reviews/credentials'), array('class' => 'mt-2')); ?>
                        <input name="client_id" class="form-control form-control-sm mb-2" placeholder="Client ID" required>
                        <input name="client_secret" type="password" class="form-control form-control-sm mb-2" placeholder="Client secret" required autocomplete="off">
                        <button class="btn btn-sm btn-outline-primary">Save</button>
                    <?= form_close(); ?></details>
                <?php endif; ?>
            <?php else: ?>
                <p class="small mb-2"><span class="badge bg-success">Connected</span> <?= html_escape($connected['email'] ?? ''); ?></p>
                <?php if ($available): ?>
                    <?= form_open(admin_url('reviews/locations')); ?>
                    <p class="small fw-semibold mb-1">Which listing is which workshop</p>
                    <?php $mapped = array(); foreach ($listings as $l) { $mapped[$l['path']] = $l['workshop']; } ?>
                    <?php foreach ($available as $l): ?>
                        <div class="d-flex gap-2 align-items-center mb-1 small">
                            <div class="flex-grow-1"><?= html_escape($l['title']); ?><div class="ymo-muted"><?= html_escape(mb_strimwidth($l['address'], 0, 70, '…')); ?></div></div>
                            <select name="workshop[<?= md5($l['path']); ?>]" class="form-select form-select-sm w-auto">
                                <option value="">Don't check</option>
                                <?php foreach ($workshops as $slug => $w): ?>
                                    <option value="<?= html_escape($slug); ?>" <?= isset($mapped[$l['path']]) && $mapped[$l['path']] === $slug ? 'selected' : ''; ?>><?= html_escape($w['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-primary">Save</button>
                        <button class="btn btn-sm btn-outline-secondary" name="refresh" value="1">Reload listings</button>
                    </div>
                    <?= form_close(); ?>
                <?php else: ?>
                    <?= form_open(admin_url('reviews/locations')); ?><button class="btn btn-sm btn-outline-secondary" name="refresh" value="1">Load listings</button><?= form_close(); ?>
                <?php endif; ?>
                <div class="d-flex gap-2 mt-3">
                    <?= form_open(admin_url('reviews/check')); ?><button class="btn btn-sm btn-outline-primary">Check reviews now</button><?= form_close(); ?>
                    <?= form_open(admin_url('reviews/disconnect')); ?><button class="btn btn-sm btn-link text-danger">Disconnect</button><?= form_close(); ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="small mb-0"><?= $connected ? 'Connected.' : 'Not connected.'; ?></p>
        <?php endif; ?>
    </div>
</div>
</div>
<?php if ($focus): ?>
<script>(function(){var el=document.getElementById('r<?= (int) $focus; ?>'); if(el){el.scrollIntoView({block:'center'});}})();</script>
<?php endif; ?>
