<?php defined('BASEPATH') OR exit('No direct script access allowed');
$open = $phone !== '';
$qs = function (array $changes) use ($show, $q, $src) {
    $p = array_filter(array_merge(array('show' => $show, 'q' => $q, 'src' => $src), $changes), function ($v) { return $v !== NULL && $v !== ''; });
    return $p ? '?'.http_build_query($p) : '';
};
$label = function ($m) {
    $body = trim((string) $m['body']);
    if (!empty($m['rendered'])) {
        return $m['rendered'];
    }
    if ($m['kind'] === 'template') {
        return 'Template: '.$m['template_name'].($body !== '' ? ' ('.$body.')' : '');
    }
    if ($body !== '') {
        return $body;
    }
    return '['.($m['kind'] ?: 'message').']';
};
// WhatsApp formatting: *bold*, _italic_, ~strike~; everything escaped first.
$wa_format = function ($text) {
    $h = html_escape((string) $text);
    $h = preg_replace('/(^|[\s(])\*([^*\n]+)\*(?=[\s).,!?:;]|$)/u', '$1<strong>$2</strong>', $h);
    $h = preg_replace('/(^|[\s(])_([^_\n]+)_(?=[\s).,!?:;]|$)/u', '$1<em>$2</em>', $h);
    $h = preg_replace('/(^|[\s(])~([^~\n]+)~(?=[\s).,!?:;]|$)/u', '$1<s>$2</s>', $h);
    return nl2br($h);
};
// Where the conversation came from (Meta ad / post, website button, direct).
$src_chip = function ($s) {
    $lbl = crm_wa_source_label($s);
    if ($lbl === '' || $s['kind'] === 'direct') {
        return '';
    }
    $cls = $s['kind'] === 'website' ? 'web' : (($s['platform'] ?? '') === 'instagram' ? 'ig' : 'fb');
    $title = trim(($s['headline'] ?? '').(!empty($s['ad_id']) ? ' · ad '.$s['ad_id'] : ''), ' ·');
    return '<span class="ymo-src ymo-src-'.$cls.'"'.($title !== '' ? ' title="'.html_escape($title).'"' : '').'>'.html_escape($lbl).'</span>';
};
$ticks = array('queued' => 'schedule', 'sent' => 'done', 'delivered' => 'done_all', 'read' => 'done_all', 'failed' => 'error');
?>
<?php if (!$ready): ?>
    <div class="ymo-alert ymo-alert-warning mb-3"><span class="mi mi-sm">info</span><span>Unread tracking needs the v7 database update (<code>database/crm_migration_v7_inbox.sql</code>). Messages still show below.</span></div>
<?php endif; ?>

<div class="ymo-inbox <?= $open ? 'is-open' : ''; ?>">
    <section class="ymo-inbox-list ymo-card p-0">
        <div class="p-2 border-bottom d-flex flex-wrap gap-2 align-items-center">
            <div class="btn-group btn-group-sm">
                <a class="btn <?= $show === 'unread' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= admin_url('inbox'.$qs(array('show' => 'unread', 'page' => NULL))); ?>">Unread<?= $unread ? ' ('.(int) $unread.')' : ''; ?></a>
                <a class="btn <?= $show === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= admin_url('inbox'.$qs(array('show' => 'all', 'page' => NULL))); ?>">All</a>
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Where the chat came from">
                <?php foreach (array('' => 'Any', 'ads' => 'From ads', 'website' => 'Website', 'direct' => 'Direct') as $k => $lbl):
                    $n = $k !== '' && isset($src_counts[$k]) ? (int) $src_counts[$k] : NULL; ?>
                    <a class="btn <?= $src === $k ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?= admin_url('inbox'.$qs(array('src' => $k, 'page' => NULL))); ?>"><?= $lbl; ?><?= $n ? ' ('.$n.')' : ''; ?></a>
                <?php endforeach; ?>
            </div>
            <form method="get" action="<?= admin_url('inbox'); ?>" class="flex-grow-1">
                <input type="hidden" name="show" value="<?= html_escape($show); ?>">
                <?php if ($src !== ''): ?><input type="hidden" name="src" value="<?= html_escape($src); ?>"><?php endif; ?>
                <input class="form-control form-control-sm" type="search" name="q" placeholder="Search number" value="<?= html_escape($q); ?>">
            </form>
            <?php if ($unread): ?>
                <?= form_open(admin_url('inbox/read-all'), array('class' => 'd-inline')); ?>
                    <button class="btn btn-link btn-sm px-1" title="Mark everything as read">Mark all read</button>
                <?= form_close(); ?>
            <?php endif; ?>
        </div>
        <?php if (!$rows): ?>
            <p class="ymo-muted small p-3 mb-0"><?= $src !== '' ? 'No conversations from this source'.($show === 'unread' ? ' with unread messages' : '').'.' : ($show === 'unread' ? 'No unread messages. You are all caught up.' : 'No WhatsApp conversations yet.'); ?></p>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $rw = $r['who'];
            $name = $rw['name'] !== '' ? $rw['name'] : '+'.$r['phone'];
            $m = $r['last'];
        ?>
            <a href="<?= admin_url('inbox/'.$r['phone'].$qs(array())); ?>" class="ymo-inbox-row <?= $r['phone'] === $phone ? 'active' : ''; ?> <?= $r['unread'] ? 'unread' : ''; ?>">
                <div class="d-flex justify-content-between gap-2">
                    <strong class="text-truncate"><?= html_escape($name); ?> <?= $src_chip($r['source']); ?></strong>
                    <span class="small ymo-muted text-nowrap"><?= $m ? html_escape(crm_time_ago($m['created_at'])) : ''; ?></span>
                </div>
                <div class="d-flex justify-content-between gap-2 align-items-center">
                    <span class="small text-truncate <?= $r['unread'] ? '' : 'ymo-muted'; ?>"><?= $m && $m['direction'] === 'out' ? 'You: ' : ''; ?><?= $m ? html_escape(mb_strimwidth($label($m), 0, 80, '…')) : ''; ?></span>
                    <?php if ($r['unread']): ?><span class="badge rounded-pill bg-success"><?= (int) $r['unread']; ?></span><?php endif; ?>
                </div>
                <div class="small ymo-muted">
                    <?php if ($rw['booking']): ?>Booking <?= html_escape($rw['booking']['reference']); ?> · <?= html_escape(str_replace('_', ' ', $rw['booking']['status'])); ?>
                    <?php elseif ($rw['lead']): ?>Lead · <?= html_escape(crm_lead_stage_label($rw['lead']['stage'])); ?><?= $rw['lead']['assignee_name'] ? ' · '.html_escape($rw['lead']['assignee_name']) : ''; ?>
                    <?php elseif ($rw['contact']): ?>Customer
                    <?php else: ?>+<?= html_escape($r['phone']); ?><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if ($pages > 1): ?>
            <div class="p-2 d-flex justify-content-between small">
                <?php if ($page > 1): ?><a href="<?= admin_url('inbox'.$qs(array('page' => $page - 1))); ?>">‹ Newer</a><?php else: ?><span></span><?php endif; ?>
                <?php if ($page < $pages): ?><a href="<?= admin_url('inbox'.$qs(array('page' => $page + 1))); ?>">Older ›</a><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="ymo-inbox-thread ymo-card p-0">
        <?php if (!$open): ?>
            <div class="h-100 d-flex align-items-center justify-content-center text-center p-4 ymo-muted">
                <div><span class="mi" style="font-size:2.5rem">forum</span><p class="mb-0 mt-2">Pick a conversation to read and reply.</p></div>
            </div>
        <?php else:
            $tel = crm_tel_link($who['mobile']);
        ?>
            <header class="p-3 border-bottom">
                <a href="<?= admin_url('inbox'.$qs(array())); ?>" class="small d-lg-none d-inline-block mb-1"><span class="mi mi-sm mi-leading">arrow_back</span>All conversations</a>
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <strong><?= html_escape($who['name'] !== '' ? $who['name'] : '+'.$phone); ?></strong>
                        <div class="small ymo-muted">+<?= html_escape($phone); ?></div>
                        <?php $first_src = $sources ? $sources[0] : NULL;
                        if ($first_src && $first_src['kind'] !== 'direct'): ?>
                            <div class="small mt-1">
                                <?= $src_chip($first_src); ?>
                                <?php if ($first_src['headline'] !== ''): ?><span class="ymo-muted">“<?= html_escape(mb_strimwidth($first_src['headline'], 0, 70, '…')); ?>”</span><?php endif; ?>
                                <?php if ($first_src['source_url'] !== '' && preg_match('#^https://#', $first_src['source_url'])): ?> <a href="<?= html_escape($first_src['source_url']); ?>" target="_blank" rel="noopener noreferrer" class="small">View ad ↗</a><?php endif; ?>
                                <span class="ymo-muted">· <?= html_escape(date('d M, H:i', strtotime($first_src['created_at']))); ?></span>
                                <?php if (count($sources) > 1): ?>
                                    <details class="d-inline-block ms-1"><summary class="small ymo-muted" style="cursor:pointer">earlier (<?= count($sources) - 1; ?>)</summary>
                                        <?php foreach (array_slice($sources, 1) as $es): ?>
                                            <div class="small ymo-muted"><?= html_escape(crm_wa_source_label($es)); ?><?= $es['headline'] !== '' ? ' · “'.html_escape(mb_strimwidth($es['headline'], 0, 50, '…')).'”' : ''; ?> · <?= html_escape(date('d M Y', strtotime($es['created_at']))); ?></div>
                                        <?php endforeach; ?>
                                    </details>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php if ($tel): ?><a class="btn btn-sm btn-outline-primary" href="<?= $tel; ?>"><span class="mi mi-sm mi-leading">call</span>Call</a><?php endif; ?>
                        <?php if ($who['lead']): ?><a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('leads/'.$who['lead']['id'].'#log'); ?>">Lead · <?= html_escape(crm_lead_stage_label($who['lead']['stage'])); ?></a><?php endif; ?>
                        <?php if ($who['contact']): ?><a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('customers/'.$who['contact']['id']); ?>">Customer</a><?php endif; ?>
                        <?php if ($who['booking']): ?><a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('bookings/'.$who['booking']['id']); ?>"><?= html_escape($who['booking']['reference']); ?></a><?php endif; ?>
                        <?php if (!$who['booking']): ?>
                            <a class="btn btn-sm btn-primary" href="<?= admin_url('bookings/new?'.($who['lead'] ? 'lead_id='.(int) $who['lead']['id'] : ($who['contact'] ? 'contact_id='.(int) $who['contact']['id'] : ''))); ?>">Book service</a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="ymo-inbox-messages p-3" id="ymo-thread">
                <?php $day = ''; foreach ($thread as $m):
                    $d = date('d M Y', strtotime($m['created_at']));
                    $auto = strpos((string) $m['kind'], 'auto_') === 0;
                ?>
                    <?php if ($d !== $day): $day = $d; ?><div class="ymo-inbox-day"><?= html_escape($d === date('d M Y') ? 'Today' : $d); ?></div><?php endif; ?>
                    <div class="ymo-bubble <?= $m['direction'] === 'in' ? 'in' : 'out'; ?> <?= $m['status'] === 'failed' ? 'failed' : ''; ?>">
                        <?php if ($m['direction'] === 'out' && ($auto || $m['kind'] === 'template')): ?>
                            <div class="ymo-bubble-tag"><?php
                                $ctx = (string) ($m['context_type'] ?? '');
                                echo $auto ? 'Auto-reply' : ($ctx === 'campaign' ? 'Campaign' : ($ctx === 'campaign_test' ? 'Campaign test' : 'Booking update'));
                                if ($m['kind'] === 'template' && $m['template_name']) { echo ' · '.html_escape($m['template_name']); }
                            ?></div>
                        <?php endif; ?>
                        <div class="ymo-bubble-text"><?= $wa_format($label($m)); ?></div>
                        <div class="ymo-bubble-meta">
                            <?= html_escape(date('h:i A', strtotime($m['created_at']))); ?>
                            <?php if ($m['direction'] === 'out'): ?>
                                <span class="mi mi-sm <?= $m['status'] === 'read' ? 'text-primary' : ''; ?>" title="<?= html_escape($m['status']); ?>"><?= $ticks[$m['status']] ?? 'done'; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($m['status'] === 'failed' && $m['error']): ?><div class="small text-danger mt-1"><?= html_escape(mb_strimwidth($m['error'], 0, 160, '…')); ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <footer class="p-3 border-top">
                <?php if (!$can_reply): ?>
                    <p class="small ymo-muted mb-0">You can read this conversation; replying needs edit access to leads.</p>
                <?php elseif ($in_window): ?>
                    <?= form_open(admin_url('inbox/'.$phone.'/reply'.$qs(array())), array('class' => 'd-flex gap-2 align-items-end')); ?>
                        <textarea name="body" class="form-control" rows="2" maxlength="4096" placeholder="Type a reply…" required></textarea>
                        <button class="btn btn-primary"><span class="mi mi-sm">send</span></button>
                    <?= form_close(); ?>
                    <p class="small ymo-muted mb-0 mt-1">Replies are free for 24 hours after their last message. Enter sends a new line; click the arrow to send.</p>
                <?php else: ?>
                    <p class="small mb-0"><span class="mi mi-sm mi-leading">schedule</span>It's been over 24 hours since they wrote, so WhatsApp won't allow a free reply. <strong>Call them</strong><?= $tel ? ' (<a href="'.$tel.'">'.html_escape($who['mobile']).'</a>)' : ''; ?>, or wait for them to message again.</p>
                <?php endif; ?>
            </footer>
            <script>(function () { var t = document.getElementById('ymo-thread'); if (t) { t.scrollTop = t.scrollHeight; } })();</script>
        <?php endif; ?>
    </section>
</div>
