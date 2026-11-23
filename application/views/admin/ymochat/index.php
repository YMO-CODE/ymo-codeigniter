<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
.ymc-row { display:block; padding:.6rem .75rem; border-bottom:1px solid var(--ymo-grey-200, #eee); color:inherit; text-decoration:none; cursor:pointer; }
.ymc-row:hover { background: var(--ymo-grey-100, #f6f6f6); }
.ymc-row.active { background: var(--ymo-primary-soft, #e8f5e9); }
.ymc-dot { display:inline-block; width:.55rem; height:.55rem; border-radius:50%; background:#bbb; margin-right:.35rem; vertical-align:middle; }
.ymc-dot.on { background:#2e7d32; box-shadow:0 0 0 3px rgba(46,125,50,.18); }
.ymc-chip { display:inline-block; font-size:.72rem; padding:.05rem .45rem; border-radius:999px; background:#eef1f4; color:#334; margin-right:.25rem; white-space:nowrap; }
.ymc-chip.ads { background:#fff3cd; color:#7a5200; font-weight:600; }
.ymc-chip.unread { background:#2e7d32; color:#fff; font-weight:600; }
.ymc-group { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#667; padding:.5rem .75rem; background:#fafafa; border-bottom:1px solid #eee; }
.ymc-info { display:grid; grid-template-columns:auto 1fr; gap:.15rem .75rem; font-size:.85rem; }
.ymc-info dt { color:#667; font-weight:500; }
.ymc-info dd { margin:0; word-break:break-word; }
.ymc-pages { font-size:.8rem; max-height:9rem; overflow-y:auto; }
.ymc-quick button { font-size:.78rem; }
.ymc-bubble-sys { align-self:center; font-size:.78rem; color:#556; background:#fff; border-radius:999px; padding:.15rem .7rem; }
.ymc-alerts { font-size:.85rem; }
@keyframes ymcflash { 0%{background:#fff8d6} 100%{background:transparent} }
.ymc-row.fresh { animation: ymcflash 3s ease-out; }
</style>

<div class="ymo-card p-3 mb-2" id="ymc-push">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
        <div class="small" id="ymc-push-state">Checking alerts on this device…</div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary d-none" id="ymc-install"><span class="mi mi-sm mi-leading">install_mobile</span>Install app</button>
            <button type="button" class="btn btn-sm btn-primary" id="ymc-push-on"><span class="mi mi-sm mi-leading">notifications_active</span>Get alerts on this device</button>
            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="ymc-push-test">Send test alert</button>
            <button type="button" class="btn btn-sm btn-link d-none" id="ymc-push-off">Turn off on this device</button>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-3 mt-2 small align-items-center">
        <label class="d-flex align-items-center gap-1">Alert me for
            <select id="ymc-mode" class="form-select form-select-sm w-auto">
                <option value="all">every visitor</option>
                <option value="ads">Google Ads visitors only</option>
                <option value="chat">chat messages only</option>
            </select>
        </label>
        <label class="d-flex align-items-center gap-1"><input type="checkbox" id="ymc-quiet"> Quiet at night (10 PM–8 AM, chats still come)</label>
    </div>
    <div class="small ymo-muted mt-2 d-none" id="ymc-ios-tip"><span class="mi mi-sm mi-leading">ios_share</span>On iPhone: open this page in <b>Safari</b>, tap <b>Share</b> → <b>Add to Home Screen</b>, open <b>YMO CRM</b> from the home screen, then tap "Get alerts on this device".</div>
</div>

<div class="ymo-inbox" id="ymc-wrap">
    <section class="ymo-inbox-list ymo-card p-0">
        <div class="p-2 border-bottom small d-flex justify-content-between align-items-center">
            <strong><span class="ymc-dot on"></span><span id="ymc-online-count">0</span> on the website now</strong>
            <span class="ymo-muted" id="ymc-updated"></span>
        </div>
        <div id="ymc-list"><p class="ymo-muted small p-3 mb-0">Loading visitors…</p></div>
    </section>

    <section class="ymo-inbox-thread ymo-card p-0">
        <div id="ymc-empty" class="h-100 d-flex align-items-center justify-content-center text-center p-4 ymo-muted">
            <div><span class="mi" style="font-size:2.5rem">travel_explore</span><p class="mb-0 mt-2">Pick a visitor to see where they are, what they are looking at, and to start a chat.</p></div>
        </div>
        <div id="ymc-panel" class="d-none h-100 d-flex flex-column">
            <header class="p-3 border-bottom">
                <a href="#" class="small d-lg-none d-inline-block mb-1" id="ymc-back"><span class="mi mi-sm mi-leading">arrow_back</span>All visitors</a>
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div>
                        <strong id="ymc-title"></strong>
                        <div class="small ymo-muted" id="ymc-sub"></div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap" id="ymc-actions"></div>
                </div>
                <details class="mt-2">
                    <summary class="small">Visitor details and pages</summary>
                    <dl class="ymc-info mt-2 mb-2" id="ymc-info"></dl>
                    <ol class="ymc-pages ps-3 mb-0" id="ymc-pages"></ol>
                </details>
            </header>
            <div class="ymo-inbox-messages p-3" id="ymc-thread"></div>
            <?php if ($can_chat): ?>
            <form class="p-2 border-top" id="ymc-form" autocomplete="off">
                <div class="ymc-quick d-flex flex-wrap gap-1 mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-q="Hi! 👋 I'm from YMO. Looking for a car service? We can pick up your car today.">Hi + pick-up</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-q="Which car do you have and what does it need? I'll share the exact price.">Ask car</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-q="Share your mobile number and our advisor will call you in 5 minutes.">Ask number</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-q="Complete car service starts at ₹2,499 with free pick-up and drop, and WhatsApp photo updates.">Price</button>
                </div>
                <div class="d-flex gap-2">
                    <textarea class="form-control" id="ymc-text" rows="1" maxlength="1000" placeholder="Type a message… (Enter to send)"></textarea>
                    <button class="btn btn-primary" type="submit"><span class="mi mi-sm">send</span></button>
                </div>
                <div class="small ymo-muted mt-1" id="ymc-hint"></div>
            </form>
            <?php else: ?>
            <p class="small ymo-muted p-2 mb-0 border-top">You can watch visitors. Chatting needs the "edit leads" permission.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
(function () {
    var BASE = <?= json_encode(rtrim(admin_url('ymochat'), '/')); ?>;
    var LEADS = <?= json_encode(rtrim(admin_url('leads'), '/')); ?>;
    var openId = <?= (int) $open_id; ?> || null, lastMsg = 0, visitors = [], seenKeys = null, timers = {};
    var $ = function (id) { return document.getElementById(id); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };
    var hdr = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'};
    var getJSON = function (url) { return fetch(url, {credentials: 'same-origin', headers: hdr}).then(function (r) { return r.json(); }); };
    var ago = function (secs) { secs = Math.max(0, secs|0); if (secs < 60) return secs + 's'; if (secs < 3600) return Math.floor(secs/60) + 'm'; return Math.floor(secs/3600) + 'h ' + Math.floor(secs%3600/60) + 'm'; };
    var since = function (dt) { return Math.max(0, (Date.now() - new Date(dt.replace(' ', 'T')).getTime()) / 1000); };
    var path = function (u) { try { var x = new URL(u); return x.pathname + (x.search || ''); } catch (e) { return u || ''; } };
    var deviceIcon = function (d) { return d === 'mobile' ? 'smartphone' : (d === 'tablet' ? 'tablet' : 'computer'); };

    // ---- alerts on this phone / computer (web push; works with the CRM closed) ----
    var PUSH = BASE + '/push/';
    var xpost = function (url, body) {
        return fetch(url, {method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(body || {})}).then(function (r) { return r.json(); });
    };
    var b64ToBytes = function (b) { b = (b + '===='.slice((b.length % 4) || 4)).replace(/-/g, '+').replace(/_/g, '/'); var raw = atob(b), a = new Uint8Array(raw.length); for (var i = 0; i < raw.length; i++) a[i] = raw.charCodeAt(i); return a; };
    var ua = navigator.userAgent;
    var isIOS = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var standalone = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
    var pushOK = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    var deviceName = (/Android/.test(ua) ? 'Android' : (isIOS ? 'iPhone' : (/Windows/.test(ua) ? 'Windows' : (/Mac/.test(ua) ? 'Mac' : 'Computer')))) + (standalone ? ' app' : ' browser');
    var swReg = null, curSub = null;
    var store = { get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: function (k, v) { try { v === null ? localStorage.removeItem(k) : localStorage.setItem(k, v); } catch (e) {} } };
    function prefs() { return {mode: $('ymc-mode').value, quiet: $('ymc-quiet').checked}; }
    function paintPush(on, msg) {
        $('ymc-push-state').innerHTML = msg || (on
            ? '<span class="mi mi-sm mi-leading text-success">notifications_active</span><b>Alerts are on for this device.</b> You get a notification when someone visits the website, even with the CRM closed.'
            : '<span class="mi mi-sm mi-leading">notifications_off</span>Alerts are off on this device.');
        $('ymc-push-on').classList.toggle('d-none', !!on);
        $('ymc-push-test').classList.toggle('d-none', !on);
        $('ymc-push-off').classList.toggle('d-none', !on);
        store.set('ymochat_push_on', on ? '1' : null);
    }
    function pushRefresh() {
        if (!pushOK) {
            if (isIOS && !standalone) { $('ymc-ios-tip').classList.remove('d-none'); paintPush(false, '<span class="mi mi-sm mi-leading">info</span>To get alerts on this iPhone, add the CRM to your home screen first (see below).'); $('ymc-push-on').disabled = true; return; }
            paintPush(false, '<span class="mi mi-sm mi-leading">info</span>This browser cannot get alerts while the CRM is closed. You will still hear a sound while this tab is open.');
            $('ymc-push-on').textContent = (window.YMOChatAlerts && YMOChatAlerts.enabled()) ? 'Sound alerts are on' : 'Turn on sound alerts';
            return;
        }
        navigator.serviceWorker.register('/ymochat-sw.js').then(function () { return navigator.serviceWorker.ready; }).then(function (reg) {
            swReg = reg;
            return reg.pushManager.getSubscription();
        }).then(function (sub) {
            curSub = sub;
            if (!sub) { paintPush(false); return; }
            return xpost(PUSH + 'state', {endpoint: sub.endpoint}).then(function (d) {
                if (d.sub) {
                    $('ymc-mode').value = d.sub.mode; $('ymc-quiet').checked = d.sub.quiet_nights == 1;
                    paintPush(true);
                } else {
                    // The server forgot this device (e.g. new keys): register it again quietly.
                    return xpost(PUSH + 'subscribe', Object.assign({sub: sub.toJSON(), device: deviceName}, prefs())).then(function (r) { paintPush(!!r.ok); });
                }
            });
        }).catch(function () { paintPush(false, '<span class="mi mi-sm mi-leading">error</span>Could not check alerts on this device. Reload the page to try again.'); });
    }
    function pushOn() {
        if (!pushOK) { if (window.YMOChatAlerts) { YMOChatAlerts.enable(); pushRefresh(); } return; }
        var btn = $('ymc-push-on'); btn.disabled = true;
        Notification.requestPermission().then(function (perm) {
            if (perm !== 'granted') {
                throw new Error(perm === 'denied' ? 'blocked' : 'dismissed');
            }
            return Promise.all([getJSON(PUSH + 'key'), swReg || navigator.serviceWorker.ready]);
        }).then(function (r) {
            swReg = r[1];
            if (!r[0].key) throw new Error('nokey');
            return swReg.pushManager.subscribe({userVisibleOnly: true, applicationServerKey: b64ToBytes(r[0].key)});
        }).then(function (sub) {
            curSub = sub;
            return xpost(PUSH + 'subscribe', Object.assign({sub: sub.toJSON(), device: deviceName, test: 1}, prefs()));
        }).then(function (d) {
            btn.disabled = false;
            if (!d.ok) throw new Error('save');
            paintPush(true);
        }).catch(function (e) {
            btn.disabled = false;
            var m = e && e.message === 'blocked'
                ? 'Notifications are blocked for the CRM in this browser. Allow them in the browser’s site settings (lock icon next to the address), then tap the button again.'
                : 'Could not turn on alerts' + (e && e.message === 'nokey' ? ' (server keys missing – check the storage folder is writable).' : '. Please try again.');
            paintPush(false, '<span class="mi mi-sm mi-leading text-danger">error</span>' + m);
        });
    }
    $('ymc-push-on').onclick = pushOn;
    $('ymc-push-test').onclick = function () { var b = this; b.disabled = true; xpost(PUSH + 'test').then(function (d) { b.disabled = false; b.textContent = d.sent ? 'Test sent ✓' : 'Not delivered – try again'; setTimeout(function () { b.textContent = 'Send test alert'; }, 4000); }).catch(function () { b.disabled = false; }); };
    $('ymc-push-off').onclick = function () {
        var ep = curSub && curSub.endpoint;
        (curSub ? curSub.unsubscribe() : Promise.resolve()).then(function () { return ep ? xpost(PUSH + 'unsubscribe', {endpoint: ep}) : null; }).then(function () { curSub = null; paintPush(false); });
    };
    ['ymc-mode', 'ymc-quiet'].forEach(function (id) { $(id).addEventListener('change', function () {
        if (curSub) xpost(PUSH + 'subscribe', Object.assign({sub: curSub.toJSON(), device: deviceName}, prefs()));
    }); });
    var installEvt = null;
    window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); installEvt = e; $('ymc-install').classList.remove('d-none'); });
    $('ymc-install').onclick = function () { if (!installEvt) return; installEvt.prompt(); installEvt.userChoice.finally(function () { installEvt = null; $('ymc-install').classList.add('d-none'); }); };
    if (isIOS && !standalone) $('ymc-ios-tip').classList.remove('d-none');
    pushRefresh();

    // ---- visitor list ----
    function row(v, fresh) {
        var title = v.name || (v.location || 'Unknown location');
        var chips = '<span class="ymc-chip' + (v.source === 'google_ads' ? ' ads' : '') + '">' + esc(v.source_label) + '</span>';
        if (v.visits > 1) chips += '<span class="ymc-chip">Visit #' + v.visits + '</span>';
        if (v.mobile) chips += '<span class="ymc-chip">📞 ' + esc(v.mobile) + '</span>';
        if (v.unread_agent) chips += '<span class="ymc-chip unread">' + v.unread_agent + ' new</span>';
        var right = v.online ? ago(v.on_site_secs) + ' on site' : 'left ' + ago(since(v.last_seen)) + ' ago';
        return '<a class="ymc-row' + (v.id === openId ? ' active' : '') + (fresh ? ' fresh' : '') + '" data-id="' + v.id + '">'
            + '<div class="d-flex justify-content-between gap-2"><strong class="text-truncate"><span class="ymc-dot' + (v.online ? ' on' : '') + '"></span>' + esc(title) + '</strong>'
            + '<span class="small ymo-muted text-nowrap"><span class="mi mi-sm">' + deviceIcon(v.device) + '</span> ' + right + '</span></div>'
            + '<div class="small text-truncate">' + esc(v.current_title || path(v.current_url)) + '</div>'
            + '<div class="mt-1">' + chips + '</div></a>';
    }
    function renderList() {
        var online = visitors.filter(function (v) { return v.online; });
        var chats = visitors.filter(function (v) { return !v.online && (v.msg_count > 0 || v.unread_agent); });
        var rest = visitors.filter(function (v) { return !v.online && !(v.msg_count > 0 || v.unread_agent); });
        var fresh = {};
        if (seenKeys) online.forEach(function (v) { if (!seenKeys[v.visit_key]) fresh[v.id] = 1; });
        seenKeys = seenKeys || {};
        visitors.forEach(function (v) { if (v.online) seenKeys[v.visit_key] = 1; });
        var html = '';
        html += '<div class="ymc-group">Online now (' + online.length + ')</div>';
        html += online.length ? online.map(function (v) { return row(v, fresh[v.id]); }).join('') : '<p class="ymo-muted small p-3 mb-0">Nobody on the website right now. You will hear an alert when someone arrives.</p>';
        if (chats.length) html += '<div class="ymc-group">Chats (left the site)</div>' + chats.map(function (v) { return row(v); }).join('');
        if (rest.length) html += '<div class="ymc-group">Earlier today</div>' + rest.slice(0, 60).map(function (v) { return row(v); }).join('');
        $('ymc-list').innerHTML = html;
        $('ymc-online-count').textContent = online.length;
        $('ymc-updated').textContent = 'live';
    }
    $('ymc-list').addEventListener('click', function (e) {
        var a = e.target.closest('.ymc-row'); if (!a) return;
        e.preventDefault(); openVisitor(parseInt(a.getAttribute('data-id'), 10));
    });
    function loadFeed() {
        return getJSON(BASE + '/feed').then(function (d) { visitors = d.visitors || []; renderList(); if (openId) refreshHeader(); }).catch(function () { $('ymc-updated').textContent = 'offline – retrying'; });
    }

    // ---- one visitor ----
    function openVisitor(id) {
        openId = id; lastMsg = 0;
        $('ymc-thread').innerHTML = '';
        $('ymc-empty').classList.add('d-none'); $('ymc-panel').classList.remove('d-none');
        $('ymc-wrap').classList.add('is-open');
        renderList();
        if (history.replaceState) history.replaceState(null, '', BASE + '?v=' + id);
        loadThread(true);
        var t = $('ymc-text'); if (t && window.innerWidth > 991) t.focus();
    }
    function refreshHeader(v) {
        v = v || visitors.filter(function (x) { return x.id === openId; })[0];
        if (!v) return;
        $('ymc-title').innerHTML = '<span class="ymc-dot' + (v.online ? ' on' : '') + '"></span>' + esc(v.name || v.location || 'Visitor');
        $('ymc-sub').innerHTML = (v.online ? 'Online · on ' + esc(v.current_title || path(v.current_url)) : 'Left ' + ago(since(v.last_seen)) + ' ago') + ' · ' + esc(v.source_label);
    }
    function info(v, leadUrl) {
        var rows = [
            ['Location', (v.location || 'Unknown') + (v.lat ? ' · <a target="_blank" rel="noopener" href="https://www.google.com/maps?q=' + v.lat + ',' + v.lon + '">map</a>' : '')],
            ['IP address', esc(v.ip)],
            ['Device', esc((v.device || '') + ' · ' + (v.browser || ''))],
            ['Came from', esc(v.source_label)],
            ['Landed on', esc(path(v.landing))],
            ['Referrer', esc(v.referrer || '–')],
            ['This visit', ago(v.on_site_secs) + ' · ' + v.pages + ' page' + (v.pages === 1 ? '' : 's') + ' in total'],
            ['Visits', v.visits + ' (first ' + esc(v.first_seen) + ')'],
            ['Contact', v.mobile ? esc((v.name ? v.name + ' · ' : '') + v.mobile) : 'Not shared yet']
        ];
        if (leadUrl) rows.push(['CRM lead', '<a href="' + esc(leadUrl) + '">Open lead</a>']);
        $('ymc-info').innerHTML = rows.map(function (r) { return '<dt>' + r[0] + '</dt><dd>' + r[1] + '</dd>'; }).join('');
        var acts = '';
        if (v.mobile) acts += '<a class="btn btn-sm btn-outline-primary" href="tel:+91' + esc(v.mobile) + '"><span class="mi mi-sm mi-leading">call</span>Call</a>';
        if (leadUrl) acts += '<a class="btn btn-sm btn-outline-secondary" href="' + esc(leadUrl) + '">Lead</a>';
        $('ymc-actions').innerHTML = acts;
    }
    function bubble(m) {
        if (m.sender === 'system') return '<div class="ymc-bubble-sys">' + esc(m.body) + '</div>';
        if (m.sender === 'event') return '<div class="ymc-bubble-sys">⚡ ' + esc(m.body) + ' · ' + esc(m.created_at.slice(11, 16)) + '</div>';
        var mine = m.sender === 'agent';
        return '<div class="ymo-bubble ' + (mine ? 'out' : 'in') + '"><div class="ymo-bubble-text">' + esc(m.body).replace(/\n/g, '<br>') + '</div>'
            + '<div class="ymo-bubble-meta">' + (mine && m.agent_name ? esc(m.agent_name) + ' · ' : '') + esc(m.created_at.slice(11, 16)) + '</div></div>';
    }
    function addMsgs(msgs) {
        if (!msgs || !msgs.length) return;
        var box = $('ymc-thread'), atEnd = box.scrollHeight - box.scrollTop - box.clientHeight < 80;
        msgs.forEach(function (m) { if (m.id > lastMsg) { box.insertAdjacentHTML('beforeend', bubble(m)); lastMsg = m.id; } });
        if (atEnd || msgs.length) box.scrollTop = box.scrollHeight;
    }
    function loadThread(first) {
        if (!openId) return;
        var id = openId;
        getJSON(BASE + '/' + id + '?read=1&after=' + lastMsg).then(function (d) {
            if (!d.ok || id !== openId) return;
            if (first) {
                info(d.visitor, d.lead_url);
                $('ymc-pages').innerHTML = (d.pages || []).map(function (p) { return '<li><span class="ymo-muted">' + esc(p.created_at.slice(11, 16)) + '</span> ' + esc(p.title || path(p.url)) + '</li>'; }).join('');
                if (!d.msgs.length) $('ymc-thread').innerHTML = '<div class="ymc-bubble-sys">No chat yet. Say hi – it pops up on their screen.</div>';
            }
            if (!first) info(d.visitor, d.lead_url);
            refreshHeader(d.visitor);
            addMsgs(d.msgs);
            var hint = $('ymc-hint');
            if (hint) hint.textContent = d.visitor.online ? 'They are on the website now – your message pops up on their screen.' : 'They have left the website. They will see your message if they come back.';
        });
    }
    $('ymc-back') && ($('ymc-back').onclick = function (e) { e.preventDefault(); $('ymc-wrap').classList.remove('is-open'); openId = null; renderList(); });

    var form = $('ymc-form');
    if (form) {
        var ta = $('ymc-text');
        var send = function (text) {
            text = (text || '').trim(); if (!text || !openId) return;
            ta.value = '';
            fetch(BASE + '/' + openId + '/send', {method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({text: text, after: lastMsg})})
                .then(function (r) { return r.json(); })
                .then(function (d) { if (d.ok) { var s = $('ymc-thread').querySelector('.ymc-bubble-sys:only-child'); if (s && /No chat yet/.test(s.textContent)) s.remove(); addMsgs(d.msgs); } else { ta.value = text; alert('Message not sent. Please try again.'); } })
                .catch(function () { ta.value = text; alert('Message not sent. Check your connection.'); });
        };
        form.addEventListener('submit', function (e) { e.preventDefault(); send(ta.value); });
        ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(ta.value); } });
        form.querySelectorAll('[data-q]').forEach(function (b) { b.onclick = function () { ta.value = b.getAttribute('data-q'); ta.focus(); }; });
    }

    loadFeed().then(function () { if (openId) openVisitor(openId); });
    setInterval(function () { if (!document.hidden) loadFeed(); }, 4000);
    setInterval(function () { if (openId) loadThread(false); }, 2500);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { loadFeed(); if (openId) loadThread(false); } });
})();
</script>
