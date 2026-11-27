/*
 * YMOChat website widget.
 *  - Tells the CRM who is on the site and which page they are on (heartbeat).
 *  - Chat window: visitors can write to us, and the team can start a chat
 *    from the CRM (the message pops up here).
 *  - Asks for name + mobile so every chat becomes a CRM lead.
 * Config: window.YMOCHAT = { api, wa, phone, agent }.
 */
(function () {
    'use strict';
    var CFG = window.YMOCHAT || {};
    if (!CFG.api || !window.fetch || !window.JSON) { return; }

    var store = {
        get: function (k, s) { try { return (s ? sessionStorage : localStorage).getItem(k); } catch (e) { return null; } },
        set: function (k, v, s) { try { (s ? sessionStorage : localStorage).setItem(k, v); } catch (e) {} }
    };
    function newId() {
        var a = new Uint8Array(16);
        (window.crypto || window.msCrypto).getRandomValues(a);
        return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
    }
    var vid = store.get('ymochat_vid');
    if (!/^[a-f0-9]{32}$/.test(vid || '')) { vid = newId(); store.set('ymochat_vid', vid); }

    var lastId = 0, msgs = [], open = false, known = false, agentOnline = false, openHours = true;
    var seenAgent = parseInt(store.get('ymochat_seen_agent') || '0', 10) || 0;
    var askedContact = store.get('ymochat_asked', true) === '1', waitTimer = null, beatTimer = null;

    function attr() {
        var q = new URLSearchParams(location.search), out = {}, any = false;
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid'].forEach(function (k) {
            var v = q.get(k); if (v) { out[k] = v; any = true; }
        });
        return any ? out : null;
    }

    function post(path, data) {
        data.vid = vid; data.after = lastId;
        return fetch(CFG.api + path, {
            method: 'POST', credentials: 'same-origin', keepalive: path === 'beat',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (d) { if (d && d.ok) { apply(d); } return d; });
    }

    function apply(d) {
        known = !!d.known; agentOnline = !!d.agent_online; openHours = d.open_hours !== false;
        var fresh = [];
        (d.msgs || []).forEach(function (m) { if (m.id > lastId) { lastId = m.id; msgs.push(m); fresh.push(m); } });
        if (!ui) {
            if (!msgs.some(function (m) { return m.sender === 'agent' && m.id > seenAgent; })) { return; }
            build(); // renders every message, including the fresh ones
        } else {
            fresh.forEach(function (m) { addBubble(m); });
        }
        status();
        var unseen = msgs.filter(function (m) { return m.sender === 'agent' && m.id > seenAgent; });
        if (fresh.some(function (m) { return m.sender === 'agent'; })) { clearTimeout(waitTimer); hideNudge(); }
        if (open) { markSeen(); } else if (unseen.length) { preview(unseen[unseen.length - 1]); }
        schedule();
    }

    var page = { pv: 1 }, a0 = attr();
    function beat(extra) {
        var data = { url: location.href, title: document.title, ref: document.referrer };
        if (page.pv) { data.pv = 1; page.pv = 0; if (a0) { data.attr = a0; } }
        if (extra) { for (var k in extra) { data[k] = extra[k]; } }
        return post('beat', data).catch(function () {});
    }
    function schedule() {
        clearTimeout(beatTimer);
        var fast = open || msgs.length > 0;
        beatTimer = setTimeout(function () { beat().then(schedule); }, document.hidden ? 30000 : (fast ? 3000 : 15000));
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { beat(); } });

    // Taps on Call / WhatsApp anywhere on the page: the team sees them in YMOChat.
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a) { return; }
        var h = a.getAttribute('href') || '';
        var evt = /^tel:/i.test(h) ? 'call' : (/wa\.me|api\.whatsapp|whatsapp:/i.test(h) ? 'whatsapp' : (/book|packages/i.test(h) && /yourmechaniconline|^\//.test(h) ? 'booking' : ''));
        if (evt) { beat({ evt: evt }); }
    }, true);

    // ---- UI ------------------------------------------------------------------
    var ui = null;
    var CSS = '' +
        '.ymc-w{position:fixed;right:20px;bottom:90px;z-index:1080;width:360px;max-width:calc(100vw - 24px);height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.25);display:none;flex-direction:column;overflow:hidden;font:15px/1.4 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#1d1d1d}' +
        '.ymc-w.on{display:flex}' +
        '.ymc-h{background:#1b5e20;color:#fff;padding:12px 14px;display:flex;align-items:center;gap:10px}' +
        '.ymc-h b{display:block;font-size:15px}.ymc-h small{opacity:.85;font-size:12px}' +
        '.ymc-x{margin-left:auto;background:none;border:0;color:#fff;font-size:26px;line-height:1;cursor:pointer;padding:0 4px}' +
        '.ymc-av{width:34px;height:34px;border-radius:50%;background:#fff;color:#1b5e20;display:flex;align-items:center;justify-content:center;font-weight:700}' +
        '.ymc-b{flex:1;overflow-y:auto;padding:12px;background:#f4f1ea;display:flex;flex-direction:column;gap:6px}' +
        '.ymc-m{max-width:82%;padding:8px 11px;border-radius:12px;background:#fff;box-shadow:0 1px 1px rgba(0,0,0,.06);white-space:pre-wrap;word-wrap:break-word}' +
        '.ymc-m.me{align-self:flex-end;background:#dcf8c6}.ymc-m.them{align-self:flex-start}' +
        '.ymc-m small{display:block;font-size:11px;color:#667;margin-top:2px}' +
        '.ymc-s{align-self:center;font-size:12px;color:#445;background:#fff;border-radius:999px;padding:3px 10px;text-align:center}' +
        '.ymc-card{align-self:stretch;background:#fff;border-radius:12px;padding:10px;box-shadow:0 1px 2px rgba(0,0,0,.08)}' +
        '.ymc-card p{margin:0 0 6px;font-size:13px}' +
        '.ymc-card input{width:100%;box-sizing:border-box;border:1px solid #ccd;border-radius:8px;padding:8px;margin-bottom:6px;font:inherit}' +
        '.ymc-card button{width:100%;border:0;border-radius:8px;padding:9px;background:#1b5e20;color:#fff;font-weight:600;cursor:pointer}' +
        '.ymc-wa{display:inline-block;margin-top:6px;background:#25d366;color:#0b3d1f;font-weight:600;border-radius:999px;padding:6px 12px;text-decoration:none}' +
        '.ymc-f{display:flex;gap:8px;padding:8px;border-top:1px solid #e5e5e5;background:#fff}' +
        '.ymc-f textarea{flex:1;resize:none;border:1px solid #ccd;border-radius:10px;padding:8px 10px;font:inherit;max-height:90px}' +
        '.ymc-f button{border:0;border-radius:10px;background:#1b5e20;color:#fff;padding:0 14px;font-weight:600;cursor:pointer}' +
        '.ymc-p{position:fixed;right:20px;bottom:150px;z-index:1080;max-width:300px;max-height:60vh;overflow-y:auto;background:#fff;border-radius:14px;padding:10px 26px 10px 12px;box-shadow:0 8px 28px rgba(0,0,0,.22);font:14px/1.4 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#1d1d1d;cursor:pointer;display:none}' +
        '.ymc-p.on{display:block;animation:ymcin .3s ease-out}.ymc-p b{display:block;font-size:12px;color:#1b5e20;margin-bottom:2px}' +
        '.ymc-p .ymc-px{position:absolute;top:2px;right:8px;border:0;background:none;font-size:18px;color:#889;cursor:pointer}' +
        '@keyframes ymcin{from{transform:translateY(10px);opacity:0}to{transform:none;opacity:1}}' +
        '.ymc-dotn{position:absolute;top:-2px;right:-2px;min-width:18px;height:18px;border-radius:9px;background:#d32f2f;color:#fff;font:600 11px/18px system-ui;text-align:center;padding:0 4px}' +
        '@media (max-width:767.98px){.ymc-w{right:0;left:0;bottom:0;width:100%;max-width:100%;height:88vh;max-height:88vh;border-radius:16px 16px 0 0}.ymc-p{right:12px;left:12px;max-width:none;bottom:150px}}';

    function el(tag, cls, html) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (html != null) { e.innerHTML = html; } return e; }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

    function build() {
        if (ui) { return; }
        var st = el('style'); st.textContent = CSS; document.head.appendChild(st);
        var w = el('div', 'ymc-w');
        w.setAttribute('role', 'dialog'); w.setAttribute('aria-label', 'Chat with YMO');
        w.innerHTML = '<div class="ymc-h"><div class="ymc-av">Y</div><div><b>YMO Car Care</b><small class="ymc-st"></small></div><button class="ymc-x" aria-label="Close chat">&times;</button></div>' +
            '<div class="ymc-b"></div>' +
            '<form class="ymc-f"><textarea rows="1" maxlength="1000" placeholder="Type your message…" aria-label="Message"></textarea><button type="submit">Send</button></form>';
        document.body.appendChild(w);
        var p = el('div', 'ymc-p'); p.innerHTML = '<button class="ymc-px" aria-label="Dismiss">&times;</button><b></b><span></span>';
        document.body.appendChild(p);
        ui = { w: w, b: w.querySelector('.ymc-b'), st: w.querySelector('.ymc-st'), ta: w.querySelector('textarea'), p: p };
        w.querySelector('.ymc-x').onclick = function () { toggle(false); };
        p.onclick = function (e) { if (e.target.classList.contains('ymc-px')) { e.stopPropagation(); p.classList.remove('on'); markSeen(); return; } toggle(true); };
        w.querySelector('form').onsubmit = function (e) { e.preventDefault(); send(); };
        ui.ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });
        ui.b.appendChild(el('div', 'ymc-m them', 'Hi! 👋 Need a car service or repair? Ask us anything – price, pick-up or timing.'));
        msgs.forEach(addBubble);
        status();
    }
    function status() {
        if (!ui) { return; }
        ui.st.textContent = agentOnline ? 'Online now · we reply in a few minutes' : (openHours ? 'We usually reply within minutes' : 'We reply 9 AM–8 PM · leave your number');
    }
    function addBubble(m) {
        if (!ui) { return; }
        var mine = m.sender === 'visitor';
        if (m.sender === 'system') { ui.b.appendChild(el('div', 'ymc-s', esc(m.body))); }
        else { ui.b.appendChild(el('div', 'ymc-m ' + (mine ? 'me' : 'them'), esc(m.body) + '<small>' + (m.sender === 'agent' && agentName(m) ? esc(agentName(m)) + ' · ' : '') + esc((m.created_at || '').slice(11, 16)) + '</small>')); }
        ui.b.scrollTop = ui.b.scrollHeight;
    }
    function markSeen() {
        msgs.forEach(function (m) { if (m.sender === 'agent' && m.id > seenAgent) { seenAgent = m.id; } });
        store.set('ymochat_seen_agent', String(seenAgent));
        if (ui) { ui.p.classList.remove('on'); }
        badge(0);
    }
    // One friendly name for every team reply (CFG.agent, e.g. "Shreya"), else the agent's first name.
    function agentName(m) { return CFG.agent || m.agent_name || ''; }
    function preview(m) {
        ui.p.querySelector('b').textContent = (agentName(m) || 'YMO') + ' from YMO';
        ui.p.querySelector('span').textContent = m.body.length > 400 ? m.body.slice(0, 400) + '…' : m.body;
        ui.p.classList.add('on');
        badge(msgs.filter(function (x) { return x.sender === 'agent' && x.id > seenAgent; }).length);
    }
    function badge(n) {
        var btn = document.getElementById('ymochat-open'); if (!btn) { return; }
        var d = btn.querySelector('.ymc-dotn');
        if (!n) { if (d) { d.remove(); } return; }
        if (!d) { d = el('span', 'ymc-dotn'); btn.style.position = 'relative'; btn.appendChild(d); }
        d.textContent = n;
    }
    function toggle(on) {
        build();
        open = on;
        ui.w.classList.toggle('on', on);
        if (on) { markSeen(); setTimeout(function () { ui.ta.focus(); }, 50); beat(); } else { schedule(); }
    }
    window.YMOChat = { open: function () { toggle(true); } };

    function contactCard(text) {
        if (known || ui.b.querySelector('.ymc-card')) { return; }
        var c = el('div', 'ymc-card');
        c.innerHTML = '<p>' + esc(text) + '</p><input name="n" placeholder="Your name" maxlength="120" autocomplete="name">' +
            '<input name="m" placeholder="Mobile number" inputmode="tel" maxlength="16" autocomplete="tel"><button type="button">Call me back</button>';
        c.querySelector('button').onclick = function () {
            var n = c.querySelector('[name=n]').value.trim(), mob = c.querySelector('[name=m]').value.trim();
            if (!/^(\+?91[\s-]*)?0?[6-9](\s*-?\d){9}$/.test(mob)) { c.querySelector('[name=m]').focus(); c.querySelector('p').textContent = 'Please enter a valid 10-digit mobile number.'; return; }
            c.querySelector('button').disabled = true;
            post('contact', { name: n, mobile: mob }).then(function (d) { if (d && d.ok) { c.remove(); } else { c.querySelector('button').disabled = false; } });
        };
        ui.b.appendChild(c); ui.b.scrollTop = ui.b.scrollHeight;
    }
    function hideNudge() { var n = ui && ui.b.querySelector('.ymc-nudge'); if (n) { n.remove(); } }
    function send() {
        var t = ui.ta.value.trim(); if (!t) { return; }
        ui.ta.value = '';
        post('send', { text: t }).then(function (d) {
            if (!d || !d.ok) { ui.ta.value = t; ui.b.appendChild(el('div', 'ymc-s', 'Message not sent. Please try again.')); return; }
            if (!known && !askedContact) {
                askedContact = true; store.set('ymochat_asked', '1', true);
                contactCard('So we can call you back if you get disconnected:');
            }
            clearTimeout(waitTimer);
            var lastAgent = msgs.filter(function (m) { return m.sender === 'agent'; }).pop();
            waitTimer = setTimeout(function () {
                var after = msgs.filter(function (m) { return m.sender === 'agent'; }).pop();
                if (after !== lastAgent || ui.b.querySelector('.ymc-nudge')) { return; }
                var n = el('div', 'ymc-s ymc-nudge', 'Our team is with another customer. ' + (known ? 'We will call you shortly.' : 'Leave your number and we will call you,') + ' or chat on WhatsApp:<br><a class="ymc-wa" target="_blank" rel="noopener" href="' + esc(CFG.wa) + '">WhatsApp us</a>');
                ui.b.appendChild(n); ui.b.scrollTop = ui.b.scrollHeight;
                if (!known) { contactCard('Get a call back:'); }
            }, 120000);
        });
    }

    // Chat button (rendered by the site in the floating actions).
    function wire() {
        var btn = document.getElementById('ymochat-open');
        if (btn) { btn.addEventListener('click', function (e) { e.preventDefault(); toggle(!open); }); }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', wire); } else { wire(); }

    beat().then(schedule);
})();
