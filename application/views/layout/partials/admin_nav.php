<?php defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Admin sidebar. Daily work first (Today → Leads → Bookings → Customers); the full
 * follow-up list opens from Today's "Next 7 days" link,
 * then outreach and reports, then setup. Source views (cold calling, Instagram…)
 * live as chips on the Leads page; online accounts are a filter on Customers.
 */
$can = function ($perm) { return !function_exists('crm_can') || crm_can($perm); };
$crm = function_exists('crm_can');
$item = function ($path, $icon, $label, $active) {
    return '<a href="'.admin_url($path).'" class="'.($active ? 'active' : '').'"><span class="mi mi-sm mi-leading">'.$icon.'</span>'.$label.'</a>';
};
?>
<?php if ($can('dashboard.view')): ?>
<?= $item('dashboard', 'today', 'Today', admin_nav_active('dashboard') || admin_nav_active('tasks')); ?>
<?php elseif ($crm && crm_can('tasks.view')): ?>
<?= $item('tasks', 'task_alt', 'Follow-ups', admin_nav_active('tasks')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('leads.view')): ?>
<a href="<?= admin_url('inbox'); ?>" class="<?= admin_nav_active('inbox') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">forum</span>Inbox<span class="ymo-nav-badge" id="ymo-inbox-badge" hidden></span></a>
<a href="<?= admin_url('ymochat'); ?>" class="<?= admin_nav_active('ymochat') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">travel_explore</span>YMOChat<span class="ymo-nav-badge ymochat-live-badge" hidden></span></a>
<?= $item('leads', 'person_search', 'Leads', admin_nav_active('leads')); ?>
<?= $item('reviews', 'reviews', 'Google reviews', admin_nav_active('reviews')); ?>
<?php if (crm_can('campaigns.view')): ?><?= $item('google-posts', 'storefront', 'Google posts', admin_nav_active('google-posts')); ?><?php endif; ?>
<?php endif; ?>
<?php if ($can('bookings.view')): ?>
<?= $item('bookings', 'event_note', 'Bookings', admin_nav_active('bookings')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('contacts.view')): ?>
<?= $item('customers', 'groups', 'Customers', admin_nav_active('customers') || admin_nav_active('online-accounts')); ?>
<?php elseif ($can('customers.view')): ?>
<?= $item('online-accounts', 'person', 'Customers', admin_nav_active('online-accounts')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('campaigns.view')): ?>
<?= $item('campaigns', 'campaign', 'Campaigns', admin_nav_active('campaigns') || admin_nav_active('whatsapp-templates')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('reports.view')): ?>
<?= $item('reports', 'analytics', 'Reports', admin_nav_active('reports')); ?>
<?php endif; ?>

<?php
$setup = array();
if ($can('packages.view'))   { $setup[] = $item('packages', 'build', 'Packages', admin_nav_active('packages')); }
if ($can('offers.view'))     { $setup[] = $item('offers', 'local_offer', 'Offers', admin_nav_active('offers')); }
if ($crm && crm_can('team.view'))  { $setup[] = $item('team', 'group', 'Team', admin_nav_active('team')); }
if ($crm && crm_can('roles.view')) { $setup[] = $item('roles', 'admin_panel_settings', 'Roles', admin_nav_active('roles')); }
if ($can('settings.manage')) { $setup[] = $item('settings', 'settings', 'Settings', admin_nav_active('settings')); }
?>
<?php if ($setup): ?>
<hr class="ymo-admin-nav-div">
<div class="ymo-admin-nav-label">Setup</div>
<?= implode("\n", $setup); ?>
<?php endif; ?>

<?php if ($crm && crm_can('recruitment.view')): ?>
<hr class="ymo-admin-nav-div">
<?= $item('recruitment', 'badge', 'Recruitment', admin_nav_active('recruitment')); ?>
<?php endif; ?>

<hr class="ymo-admin-nav-div">
<?php $ci = &get_instance(); ?>
<form action="<?= admin_url('logout'); ?>" method="post" class="ymo-admin-nav-logout">
    <?= form_hidden($ci->security->get_csrf_token_name(), $ci->security->get_csrf_hash()); ?>
    <button class="btn btn-sm btn-outline-light w-100" type="submit"><span class="mi mi-sm mi-leading">logout</span>Sign out</button>
</form>
<?php if ($crm && crm_can('leads.view')): ?>
<script>
(function () {
    // YMOChat alerts: every CRM page checks the website every 5 seconds. When a new
    // visitor arrives (or a visitor sends a chat message) the CRM plays a sound and
    // shows a pop-up, if the user turned alerts on (YMOChat → Turn on visitor alerts).
    if (window.YMOChatAlerts || !window.fetch) { return; }
    var KEY = 'ymochat_alerts', SEEN = 'ymochat_seen', PULSE = <?= json_encode(admin_url('ymochat/pulse')); ?>, OPEN = <?= json_encode(admin_url('ymochat')); ?>;
    var store = { get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
                  set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} } };
    var ctx = null;
    function beep() {
        try {
            ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
            if (ctx.state === 'suspended') { ctx.resume(); }
            [0, 0.18].forEach(function (t, i) {
                var o = ctx.createOscillator(), g = ctx.createGain();
                o.type = 'sine'; o.frequency.value = i ? 1175 : 880;
                g.gain.setValueAtTime(0.0001, ctx.currentTime + t);
                g.gain.exponentialRampToValueAtTime(0.35, ctx.currentTime + t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + t + 0.16);
                o.connect(g); g.connect(ctx.destination); o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.17);
            });
        } catch (e) {}
    }
    var api = window.YMOChatAlerts = {
        enabled: function () { return store.get(KEY) === '1'; },
        enable: function () {
            store.set(KEY, '1'); beep();
            if ('Notification' in window && Notification.permission === 'default') { Notification.requestPermission(); }
        },
        disable: function () { store.set(KEY, '0'); },
        beep: beep
    };
    // Browsers only allow sound after a click on the page: unlock audio on the first click.
    document.addEventListener('click', function unlock() { if (api.enabled()) { try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); ctx.resume(); } catch (e) {} } document.removeEventListener('click', unlock); });

    var seen = {}, first = true, lastUnread = null;
    try { seen = JSON.parse(sessionStorage.getItem(SEEN) || '{}') || {}; first = !sessionStorage.getItem(SEEN); } catch (e) {}
    var baseTitle = document.title, flashT = null;
    function flash(text) {
        if (flashT) { clearInterval(flashT); }
        var n = 0; flashT = setInterval(function () { document.title = (n++ % 2 ? baseTitle : text); if (n > 20 || !document.hidden) { clearInterval(flashT); document.title = baseTitle; } }, 1000);
    }
    function notify(title, body, id) {
        // This device already gets push alerts from the server: don't alert twice.
        if (!api.enabled() || store.get('ymochat_push_on') === '1') { return; }
        beep(); flash(title);
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                var n = new Notification(title, { body: body, tag: 'ymochat-' + id, renotify: true });
                n.onclick = function () { window.focus(); location.href = OPEN + '?v=' + id; n.close(); };
            } catch (e) {}
        }
    }
    function paint(online, unread) {
        document.querySelectorAll('.ymochat-live-badge').forEach(function (b) {
            var n = unread || online;
            b.hidden = !n; b.textContent = unread ? unread + ' new' : String(online); b.title = unread ? unread + ' unread chat messages' : online + ' on the website now';
            b.style.background = unread ? '#c62828' : '#2e7d32';
        });
    }
    function poll() {
        fetch(PULSE, {credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) { return; }
                paint(d.online || 0, d.unread || 0);
                (d.latest || []).forEach(function (v) {
                    if (seen[v.visit_key]) { return; }
                    seen[v.visit_key] = 1;
                    if (first) { return; }
                    var where = [v.city, v.region].filter(Boolean).join(', ') || 'Unknown location';
                    notify((v.source === 'google_ads' ? '🟡 Ad visitor · ' : '🟢 New visitor · ') + where,
                        v.source_label + (v.current_title ? ' · ' + v.current_title : '') + (v.visits > 1 ? ' · visit #' + v.visits : ''), v.id);
                });
                if (lastUnread !== null && d.unread > lastUnread) {
                    var who = (d.latest || []).filter(function (v) { return v.unread_agent > 0; })[0];
                    notify('💬 New chat message', who ? (who.name || [who.city, who.region].filter(Boolean).join(', ') || 'Website visitor') + ' wrote to you' : 'A visitor wrote to you', who ? who.id : '');
                }
                lastUnread = d.unread || 0;
                first = false;
                try { sessionStorage.setItem(SEEN, JSON.stringify(seen)); } catch (e) {}
            })
            .catch(function () {});
    }
    poll();
    setInterval(poll, 5000);
})();
</script>
<script>
(function () {
    // Unread WhatsApp badge: refresh every 30 seconds while the admin is open.
    var badge = document.getElementById('ymo-inbox-badge');
    if (!badge || !window.fetch) { return; }
    var baseTitle = document.title.replace(/^\(\d+\)\s*/, '');
    function paint(n) {
        badge.hidden = !n;
        badge.textContent = n > 99 ? '99+' : String(n);
        document.title = (n ? '(' + n + ') ' : '') + baseTitle;
    }
    function poll() {
        fetch('<?= admin_url('inbox/unread'); ?>', {credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d && typeof d.unread === 'number') { paint(d.unread); } })
            .catch(function () {});
    }
    poll();
    setInterval(function () { if (!document.hidden) { poll(); } }, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });
})();
</script>
<?php endif; ?>
