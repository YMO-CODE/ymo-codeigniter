<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?><!doctype html>
<html lang="hi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#2d6a4f">
<meta name="robots" content="noindex, nofollow">
<title>YMO Workshop</title>
<link rel="manifest" href="<?= html_escape($base); ?>/manifest.webmanifest">
<link rel="icon" href="<?= html_escape($base); ?>/icon.svg" type="image/svg+xml">
<style>
:root { --g: #2d6a4f; --g2: #1b4332; --bg: #f4f6f5; --card: #fff; --mut: #5f6b66; --line: #e2e7e4; --warn: #b7791f; --red: #c0392b; --blue: #1d4ed8; }
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html, body { margin: 0; background: var(--bg); color: #14211b; font: 16px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Devanagari", sans-serif; }
button, input, textarea, select { font: inherit; }
header { position: sticky; top: 0; z-index: 10; background: var(--g); color: #fff; padding: 10px 14px; display: flex; align-items: center; gap: 10px; min-height: 54px; }
header h1 { font-size: 18px; margin: 0; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
header button { background: rgba(255,255,255,.15); color: #fff; border: 0; border-radius: 10px; padding: 8px 12px; font-size: 15px; }
main { padding: 12px 12px 96px; max-width: 640px; margin: 0 auto; }
.banner { margin: 0 0 10px; padding: 10px 12px; border-radius: 10px; font-size: 14px; }
.banner.off { background: #fff4e5; color: #7a4b00; }
.banner.sync { background: #e8f1fd; color: #1e3a8a; }
.banner.err { background: #fdecea; color: #8a1c13; }
.card { background: var(--card); border-radius: 14px; padding: 14px; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
.tabs { display: flex; gap: 8px; margin-bottom: 12px; }
.tabs button { flex: 1; border: 2px solid var(--g); background: #fff; color: var(--g); border-radius: 12px; padding: 10px; font-weight: 600; }
.tabs button.on { background: var(--g); color: #fff; }
.job { display: block; width: 100%; text-align: left; border: 0; cursor: pointer; }
.plate { font-size: 22px; font-weight: 800; letter-spacing: .5px; }
.row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.mut { color: var(--mut); font-size: 14px; }
.badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 13px; font-weight: 700; }
.s-draft { background: #e5e7eb; color: #374151; } .s-in_progress { background: #dbeafe; color: #1e40af; }
.s-on_hold { background: #fef3c7; color: #92400e; } .s-ready { background: #d1fae5; color: #065f46; }
.s-delivered, .s-returned { background: #e5e7eb; color: #374151; }
.bar { height: 8px; background: var(--line); border-radius: 99px; overflow: hidden; margin-top: 8px; }
.bar i { display: block; height: 100%; background: var(--g); }
.late { color: var(--red); font-weight: 700; }
h2 { font-size: 15px; text-transform: uppercase; letter-spacing: .4px; color: var(--mut); margin: 18px 4px 8px; }
.task { display: flex; gap: 12px; align-items: flex-start; width: 100%; text-align: left; background: var(--card); border: 2px solid var(--line); border-radius: 14px; padding: 14px; margin-bottom: 10px; }
.task .box { flex: 0 0 34px; height: 34px; border-radius: 9px; border: 3px solid #9aa5a0; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; }
.task.done { border-color: #95d5b2; background: #f0faf4; }
.task.done .box { background: var(--g); border-color: var(--g); }
.task.done .nm { text-decoration: line-through; color: var(--mut); }
.task .nm { font-weight: 700; font-size: 17px; }
.task.pending { opacity: .75; }
.task.wait { border-style: dashed; border-color: #e0b44a; background: #fffbeb; }
.task.wait .box { border-color: #e0b44a; background: #fff; color: #b7791f; font-size: 18px; }
.task .wl { color: #92400e; font-weight: 700; font-size: 14px; }
.big { display: block; width: 100%; border: 0; border-radius: 14px; padding: 16px; font-size: 18px; font-weight: 800; margin-bottom: 10px; }
.b-go { background: var(--g); color: #fff; } .b-hold { background: #fff; color: var(--warn); border: 2px solid var(--warn); }
.b-ready { background: var(--blue); color: #fff; }
.b-sec { background: #fff; color: var(--g); border: 2px solid var(--g); }
.field { width: 100%; padding: 14px; border: 2px solid var(--line); border-radius: 12px; background: #fff; font-size: 18px; }
.field:focus { outline: 0; border-color: var(--g); }
label.l { display: block; font-weight: 700; margin: 12px 2px 6px; }
.pin { letter-spacing: 12px; text-align: center; font-size: 26px; }
.langs { display: flex; gap: 8px; margin-top: 14px; }
.langs button { flex: 1; border: 2px solid var(--line); background: #fff; border-radius: 10px; padding: 10px 4px; font-weight: 700; }
.langs button.on { border-color: var(--g); color: var(--g); }
.err { color: var(--red); margin-top: 10px; font-weight: 600; }
.photos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
.photos div { position: relative; aspect-ratio: 4/3; background: #dfe5e2; border-radius: 8px; overflow: hidden; }
.photos img { width: 100%; height: 100%; object-fit: cover; display: block; }
.photos span { position: absolute; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.55); color: #fff; font-size: 11px; padding: 2px 4px; }
.photos .wait::after { content: '⏳'; position: absolute; top: 4px; right: 6px; }
.kinds { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; }
.kinds button { border: 2px solid var(--g); background: #fff; color: var(--g); border-radius: 12px; padding: 12px 6px; font-weight: 700; font-size: 15px; }
.note { padding: 8px 10px; border-radius: 10px; margin-bottom: 6px; font-size: 15px; }
.note.advisor { background: #fff8e1; } .note.tech { background: #eef5f0; }
.sheet-bg { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 30; display: flex; align-items: flex-end; }
.sheet { background: #fff; width: 100%; border-radius: 18px 18px 0 0; padding: 16px 14px calc(16px + env(safe-area-inset-bottom)); max-width: 640px; margin: 0 auto; }
.sheet h3 { margin: 0 0 12px; font-size: 19px; }
.toast { position: fixed; left: 50%; bottom: 22px; transform: translateX(-50%); background: #14211b; color: #fff; padding: 12px 18px; border-radius: 12px; z-index: 40; font-size: 15px; max-width: 92vw; }
.center { text-align: center; padding: 30px 10px; }
.foot { text-align: center; margin-top: 24px; }
.foot > button { background: none; border: 0; color: var(--mut); text-decoration: underline; padding: 8px; }
</style>
</head>
<body>
<header><button id="hBack" hidden aria-label="Back">←</button><h1 id="hTitle">YMO Workshop</h1><button id="hRefresh" hidden>⟳</button></header>
<main id="app"><div class="center mut">…</div></main>
<input type="file" id="cam" accept="image/*" capture="environment" hidden>
<script>
(function () {
'use strict';
var BASE = <?= json_encode($base); ?>;
var VERSION = <?= json_encode($version); ?>;

// ---------------------------------------------------------------- text
var T = {
  en: {
    title: 'YMO Workshop', mobile: 'Mobile number', pin: '4-digit PIN', sign_in: 'Sign in',
    e_bad: 'Wrong mobile number or PIN.', e_locked: 'Too many wrong tries. Try again in 15 minutes or ask the advisor.',
    e_inactive: 'Your account is not active. Ask the advisor.', e_no_pin: 'No PIN set yet. Ask the advisor.',
    e_net: 'No internet. Connect and try again.', hello: 'Hi {name}',
    mine: 'My jobs', workshop: 'Whole workshop', no_jobs: 'No open jobs right now.',
    st_draft: 'Waiting', st_in_progress: 'Working', st_on_hold: 'On hold', st_ready: 'Ready', st_delivered: 'Delivered', st_returned: 'Returned',
    due: 'Due', done_of: '{d} of {n} done', bay: 'Bay',
    complaint: 'Customer says', instructions: 'Instructions', work: 'Work to do', parts: 'Parts', none: 'Nothing added yet. Ask the advisor.',
    start: 'Start work', hold: 'Put on hold', resume: 'Resume work', ready: 'Work finished – Ready',
    ready_q: 'Mark this car ready for delivery?', ready_left: '{n} item(s) not ticked yet.',
    hold_q: 'Why is the work stopped?', h_parts: 'Waiting for parts', h_approval: 'Waiting for customer approval', h_other: 'Other reason',
    h_other_ph: 'Write the reason', ok: 'OK', cancel: 'Cancel', yes: 'Yes, ready',
    photos: 'Photos', take: 'Take photo', k_before: 'Before', k_during: 'During work', k_after: 'After', k_damage: 'Damage / issue', k_part: 'Old part',
    msgs: 'Messages', note_ph: 'Found something more? Tell the advisor here', send: 'Send',
    off: 'No internet. {n} change(s) saved on phone, will be sent when connected.', off0: 'No internet. Showing the last saved list.',
    syncing: 'Sending {n} change(s)…', saved: 'Saved', sent: 'Sent', closed: 'This job is closed.',
    e_gen: 'Something went wrong. Try again.', e_photo: 'Could not read that photo.', changed: 'The advisor changed this job. Updated.',
    logout: 'Sign out', logout_q: '{n} change(s) are not sent yet. Signing out deletes them. Sign out?', lang: 'Language',
    advisor: 'Advisor', km: 'km', you: 'You', updated: 'Updated {t}',
    wait_ok: 'Waiting for customer OK – do not start yet', waiting_n: '{n} waiting for customer OK'
  },
  hi: {
    title: 'YMO वर्कशॉप', mobile: 'मोबाइल नंबर', pin: '4 अंकों का PIN', sign_in: 'लॉग इन करें',
    e_bad: 'मोबाइल नंबर या PIN गलत है।', e_locked: 'बहुत बार गलत PIN डाला। 15 मिनट बाद कोशिश करें या एडवाइज़र से पूछें।',
    e_inactive: 'आपका अकाउंट चालू नहीं है। एडवाइज़र से पूछें।', e_no_pin: 'अभी PIN नहीं बना है। एडवाइज़र से पूछें।',
    e_net: 'इंटरनेट नहीं है। इंटरनेट चालू करके फिर कोशिश करें।', hello: 'नमस्ते {name}',
    mine: 'मेरे काम', workshop: 'पूरा वर्कशॉप', no_jobs: 'अभी कोई खुला काम नहीं है।',
    st_draft: 'इंतज़ार में', st_in_progress: 'काम चालू', st_on_hold: 'रुका हुआ', st_ready: 'तैयार', st_delivered: 'डिलीवर', st_returned: 'वापस गई',
    due: 'डिलीवरी', done_of: '{n} में से {d} पूरे', bay: 'बे',
    complaint: 'ग्राहक की शिकायत', instructions: 'निर्देश', work: 'करने का काम', parts: 'पार्ट्स', none: 'अभी कुछ नहीं जोड़ा गया। एडवाइज़र से पूछें।',
    start: 'काम शुरू करें', hold: 'काम रोकें', resume: 'काम फिर शुरू करें', ready: 'काम पूरा – गाड़ी तैयार',
    ready_q: 'गाड़ी को डिलीवरी के लिए तैयार मार्क करें?', ready_left: '{n} काम पर अभी टिक नहीं लगा है।',
    hold_q: 'काम क्यों रुका है?', h_parts: 'पार्ट्स का इंतज़ार', h_approval: 'ग्राहक की मंज़ूरी का इंतज़ार', h_other: 'कोई और कारण',
    h_other_ph: 'कारण लिखें', ok: 'ठीक है', cancel: 'रद्द करें', yes: 'हाँ, तैयार है',
    photos: 'फ़ोटो', take: 'फ़ोटो लें', k_before: 'काम से पहले', k_during: 'काम के दौरान', k_after: 'काम के बाद', k_damage: 'नुकसान / खराबी', k_part: 'पुराना पार्ट',
    msgs: 'संदेश', note_ph: 'कुछ और खराबी मिली? यहाँ एडवाइज़र को बताएँ', send: 'भेजें',
    off: 'इंटरनेट नहीं है। {n} बदलाव फ़ोन में सेव हैं, इंटरनेट आने पर भेजे जाएँगे।', off0: 'इंटरनेट नहीं है। आख़िरी सेव की हुई लिस्ट दिख रही है।',
    syncing: '{n} बदलाव भेजे जा रहे हैं…', saved: 'सेव हुआ', sent: 'भेज दिया', closed: 'यह काम बंद हो चुका है।',
    e_gen: 'कुछ गड़बड़ हुई। फिर से कोशिश करें।', e_photo: 'यह फ़ोटो नहीं खुली।', changed: 'एडवाइज़र ने इस काम में बदलाव किया। अपडेट हो गया।',
    logout: 'लॉग आउट', logout_q: '{n} बदलाव अभी भेजे नहीं गए। लॉग आउट करने पर वे मिट जाएँगे। लॉग आउट करें?', lang: 'भाषा',
    advisor: 'एडवाइज़र', km: 'कि.मी.', you: 'आप', updated: '{t} पर अपडेट',
    wait_ok: 'ग्राहक की मंज़ूरी का इंतज़ार – अभी शुरू न करें', waiting_n: 'ग्राहक की मंज़ूरी बाकी: {n}'
  },
  mr: {
    title: 'YMO वर्कशॉप', mobile: 'मोबाईल नंबर', pin: '4 अंकी PIN', sign_in: 'लॉग इन करा',
    e_bad: 'मोबाईल नंबर किंवा PIN चुकीचा आहे.', e_locked: 'खूप वेळा चुकीचा PIN टाकला. 15 मिनिटांनी पुन्हा प्रयत्न करा किंवा अ‍ॅडव्हायझरला विचारा.',
    e_inactive: 'तुमचे अकाउंट चालू नाही. अ‍ॅडव्हायझरला विचारा.', e_no_pin: 'अजून PIN तयार केलेला नाही. अ‍ॅडव्हायझरला विचारा.',
    e_net: 'इंटरनेट नाही. इंटरनेट चालू करून पुन्हा प्रयत्न करा.', hello: 'नमस्कार {name}',
    mine: 'माझी कामे', workshop: 'संपूर्ण वर्कशॉप', no_jobs: 'सध्या कोणतेही चालू काम नाही.',
    st_draft: 'प्रतीक्षेत', st_in_progress: 'काम चालू', st_on_hold: 'थांबवलेले', st_ready: 'तयार', st_delivered: 'डिलिव्हर', st_returned: 'परत गेली',
    due: 'डिलिव्हरी', done_of: '{n} पैकी {d} पूर्ण', bay: 'बे',
    complaint: 'ग्राहकाची तक्रार', instructions: 'सूचना', work: 'करायचे काम', parts: 'पार्ट्स', none: 'अजून काही जोडलेले नाही. अ‍ॅडव्हायझरला विचारा.',
    start: 'काम सुरू करा', hold: 'काम थांबवा', resume: 'काम पुन्हा सुरू करा', ready: 'काम पूर्ण – गाडी तयार',
    ready_q: 'गाडी डिलिव्हरीसाठी तयार म्हणून मार्क करायची?', ready_left: '{n} कामांवर अजून टिक केलेले नाही.',
    hold_q: 'काम का थांबले आहे?', h_parts: 'पार्ट्सची वाट', h_approval: 'ग्राहकाच्या मंजुरीची वाट', h_other: 'दुसरे कारण',
    h_other_ph: 'कारण लिहा', ok: 'ठीक आहे', cancel: 'रद्द करा', yes: 'हो, तयार आहे',
    photos: 'फोटो', take: 'फोटो काढा', k_before: 'कामाआधी', k_during: 'काम चालू असताना', k_after: 'कामानंतर', k_damage: 'नुकसान / बिघाड', k_part: 'जुना पार्ट',
    msgs: 'संदेश', note_ph: 'आणखी काही बिघाड सापडला? इथे अ‍ॅडव्हायझरला सांगा', send: 'पाठवा',
    off: 'इंटरनेट नाही. {n} बदल फोनमध्ये सेव्ह आहेत, इंटरनेट आल्यावर पाठवले जातील.', off0: 'इंटरनेट नाही. शेवटची सेव्ह केलेली यादी दिसत आहे.',
    syncing: '{n} बदल पाठवत आहे…', saved: 'सेव्ह झाले', sent: 'पाठवले', closed: 'हे काम बंद झाले आहे.',
    e_gen: 'काहीतरी चुकले. पुन्हा प्रयत्न करा.', e_photo: 'हा फोटो उघडला नाही.', changed: 'अ‍ॅडव्हायझरने या कामात बदल केला. अपडेट झाले.',
    logout: 'लॉग आउट', logout_q: '{n} बदल अजून पाठवलेले नाहीत. लॉग आउट केल्यास ते पुसले जातील. लॉग आउट करायचे?', lang: 'भाषा',
    advisor: 'अ‍ॅडव्हायझर', km: 'कि.मी.', you: 'तुम्ही', updated: '{t} ला अपडेट',
    wait_ok: 'ग्राहकाच्या मंजुरीची वाट – अजून सुरू करू नका', waiting_n: 'ग्राहकाची मंजुरी बाकी: {n}'
  }
};
var LANGS = { en: 'English', hi: 'हिंदी', mr: 'मराठी' };

var store = {
  get: function (k, d) { try { var v = localStorage.getItem('ymt_' + k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
  set: function (k, v) { try { localStorage.setItem('ymt_' + k, JSON.stringify(v)); } catch (e) {} },
  del: function (k) { try { localStorage.removeItem('ymt_' + k); } catch (e) {} }
};
var lang = store.get('lang', 'hi');
function t(k, vars) {
  var s = (T[lang] && T[lang][k]) || T.en[k] || k;
  if (vars) { Object.keys(vars).forEach(function (v) { s = s.split('{' + v + '}').join(vars[v]); }); }
  return s;
}
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function uid() { var a = new Uint8Array(12); (window.crypto || window.msCrypto).getRandomValues(a); return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join(''); }
function fmtTime(iso) { if (!iso) return ''; var d = new Date(iso); return d.toLocaleString((lang === 'en' ? 'en' : lang) + '-IN-u-nu-latn', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }); }

var app = document.getElementById('app'), hTitle = document.getElementById('hTitle'), hBack = document.getElementById('hBack'), hRefresh = document.getElementById('hRefresh');
var me = store.get('me', null);
var state = { scope: store.get('scope', 'mine'), jobs: store.get('jobs_' + store.get('scope', 'mine'), null), job: null, error: '' };

// ---------------------------------------------------------------- network
function api(method, path, body) {
  var opt = { method: method, credentials: 'same-origin', headers: { 'X-YMO-Tech': '1' } };
  if (body instanceof FormData) { opt.body = body; }
  else if (body !== undefined) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); }
  return fetch(BASE + '/api/' + path, opt).then(function (r) {
    return r.json().catch(function () { return {}; }).then(function (j) { j._status = r.status; return j; });
  });
}

// ---------------------------------------------------------------- offline queue (IndexedDB)
var dbp = null;
function db() {
  if (dbp) return dbp;
  dbp = new Promise(function (res, rej) {
    var rq = indexedDB.open('ymo-tech', 1);
    rq.onupgradeneeded = function () { rq.result.createObjectStore('q', { keyPath: 'uid' }); };
    rq.onsuccess = function () { res(rq.result); };
    rq.onerror = function () { rej(rq.error); };
  });
  return dbp;
}
function qAll() { return db().then(function (d) { return new Promise(function (res) { var out = []; var c = d.transaction('q').objectStore('q').openCursor(); c.onsuccess = function () { var x = c.result; if (x) { out.push(x.value); x.continue(); } else { res(out.sort(function (a, b) { return a.at - b.at; })); } }; c.onerror = function () { res([]); }; }); }).catch(function () { return []; }); }
function qPut(item) { return db().then(function (d) { return new Promise(function (res, rej) { var tx = d.transaction('q', 'readwrite'); tx.objectStore('q').put(item); tx.oncomplete = res; tx.onerror = function () { rej(tx.error); }; }); }); }
function qDel(id) { return db().then(function (d) { return new Promise(function (res) { var tx = d.transaction('q', 'readwrite'); tx.objectStore('q').delete(id); tx.oncomplete = res; tx.onerror = res; }); }); }
function qClear() { return db().then(function (d) { return new Promise(function (res) { var tx = d.transaction('q', 'readwrite'); tx.objectStore('q').clear(); tx.oncomplete = res; tx.onerror = res; }); }); }

var pending = [];   // mirror of the queue for the UI
var flushing = false;
function refreshPending() { return qAll().then(function (a) { pending = a; banner(); return a; }); }

function enqueue(item) {
  item.uid = item.uid || uid(); item.at = Date.now(); item.tech = me ? me.id : 0;
  return qPut(item).then(refreshPending).then(function () { flush(); });
}

function flush() {
  if (flushing || !navigator.onLine) { banner(); return Promise.resolve(); }
  flushing = true; banner();
  return qAll().then(function (items) {
    var chain = Promise.resolve(), stop = false, touched = {};
    items.forEach(function (it) {
      chain = chain.then(function () {
        if (stop) return;
        if (me && it.tech && it.tech !== me.id) { return qDel(it.uid); }   // another technician's leftover
        var p;
        if (it.type === 'photo') {
          var fd = new FormData(); fd.append('photo', it.blob, 'photo.jpg'); fd.append('kind', it.kind); fd.append('uid', it.uid);
          p = api('POST', 'jobs/' + it.job + '/photos', fd);
        } else {
          var body = Object.assign({}, it.body, { uid: it.uid });
          p = api('POST', it.path, body);
        }
        return p.then(function (j) {
          if (j._status === 401) { stop = true; signedOut(); return; }
          if (j._status >= 500 || j._status === 0) { stop = true; return; }   // try later
          touched[it.job] = true;
          if (!j.ok && j._status === 409 && it.type !== 'photo') { toast(j.message || t('changed')); }
          return qDel(it.uid);   // done, or refused for good (job closed / not allowed)
        }, function () { stop = true; });   // offline / network error
      });
    });
    return chain.then(function () {
      flushing = false;
      return refreshPending().then(function () {
        if (state.job && touched[state.job.id] && !pending.some(function (p) { return p.job === state.job.id; })) { loadJob(state.job.id, true); }
      });
    });
  }).catch(function () { flushing = false; banner(); });
}

window.addEventListener('online', function () { flush(); route(); });
window.addEventListener('offline', banner);
setInterval(function () { if (pending.length) flush(); }, 20000);

// ---------------------------------------------------------------- UI bits
var bannerEl = null;
function banner() {
  if (!bannerEl) return;
  var n = pending.length;
  if (!navigator.onLine) { bannerEl.className = 'banner off'; bannerEl.textContent = n ? t('off', { n: n }) : t('off0'); bannerEl.hidden = false; }
  else if (n) { bannerEl.className = 'banner sync'; bannerEl.textContent = t('syncing', { n: n }); bannerEl.hidden = false; }
  else { bannerEl.hidden = true; }
}
var toastTimer = null;
function toast(msg) {
  var el = document.querySelector('.toast'); if (el) el.remove();
  el = document.createElement('div'); el.className = 'toast'; el.textContent = msg; document.body.appendChild(el);
  clearTimeout(toastTimer); toastTimer = setTimeout(function () { el.remove(); }, 2600);
}
function sheet(html, onReady) {
  var bg = document.createElement('div'); bg.className = 'sheet-bg';
  bg.innerHTML = '<div class="sheet">' + html + '</div>';
  bg.addEventListener('click', function (e) { if (e.target === bg) bg.remove(); });
  document.body.appendChild(bg);
  onReady(bg, function () { bg.remove(); });
}
function shell(title, back, body) {
  hTitle.textContent = title; hBack.hidden = !back; hRefresh.hidden = !me;
  app.innerHTML = '<div class="banner" hidden></div>' + body;
  bannerEl = app.querySelector('.banner'); banner();
}
hBack.onclick = function () { location.hash = '#/'; };
hRefresh.onclick = function () { flush(); route(true); };

// ---------------------------------------------------------------- screens
function signedOut() { me = null; store.del('me'); renderLogin(); }

function renderLogin(err, typed) {
  document.documentElement.lang = lang;
  shell(t('title'), false,
    '<div class="card"><form id="lf" autocomplete="on">' +
    '<label class="l" for="lm">' + t('mobile') + '</label><input class="field" id="lm" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel-national" value="' + esc(typed || store.get('mobile', '')) + '" required>' +
    '<label class="l" for="lp">' + t('pin') + '</label><input class="field pin" id="lp" type="password" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="current-password" required>' +
    '<div class="err" id="le">' + (err ? esc(err) : '') + '</div>' +
    '<button class="big b-go" style="margin-top:16px">' + t('sign_in') + '</button></form>' +
    langButtons() + '</div>');
  bindLang(function () { renderLogin(); });
  var f = document.getElementById('lf');
  f.onsubmit = function (e) {
    e.preventDefault();
    var m = document.getElementById('lm').value.replace(/\D/g, ''), p = document.getElementById('lp').value;
    if (!navigator.onLine) { document.getElementById('le').textContent = t('e_net'); return; }
    if (!/^[6-9]\d{9}$/.test(m) || !/^\d{4}$/.test(p)) { document.getElementById('le').textContent = t('e_bad'); return; }
    f.querySelector('button').disabled = true;
    api('POST', 'login', { mobile: m, pin: p }).then(function (j) {
      if (!j.ok) { renderLogin(t('e_' + (j.error || 'bad')), m); document.getElementById('lp').focus(); return; }
      store.set('mobile', m); me = j.me; store.set('me', me);
      if (me.lang && !store.get('lang_chosen', false)) { lang = me.lang; store.set('lang', lang); }
      location.hash = '#/'; route(true); flush();
    }, function () { renderLogin(t('e_net'), m); });
  };
}

function langButtons() {
  return '<div class="langs">' + Object.keys(LANGS).map(function (k) { return '<button type="button" data-lang="' + k + '" class="' + (k === lang ? 'on' : '') + '">' + LANGS[k] + '</button>'; }).join('') + '</div>';
}
function bindLang(after) {
  Array.prototype.forEach.call(app.querySelectorAll('[data-lang]'), function (b) {
    b.onclick = function () {
      lang = b.getAttribute('data-lang'); store.set('lang', lang); store.set('lang_chosen', true);
      document.documentElement.lang = lang;
      if (me && navigator.onLine) { api('POST', 'lang', { lang: lang }).catch(function () {}); }
      after();
    };
  });
}

function statusBadge(s) { return '<span class="badge s-' + esc(s) + '">' + esc(t('st_' + s)) + '</span>'; }

function renderList() {
  var jobs = state.jobs;
  var body = '<div class="mut" style="margin:0 4px 10px">' + esc(t('hello', { name: me.name })) + (me.workshop_label ? ' · ' + esc(me.workshop_label) : '') + '</div>' +
    '<div class="tabs"><button data-scope="mine" class="' + (state.scope === 'mine' ? 'on' : '') + '">' + t('mine') + '</button>' +
    '<button data-scope="workshop" class="' + (state.scope === 'workshop' ? 'on' : '') + '">' + t('workshop') + '</button></div>';
  if (!jobs) { body += '<div class="center mut">…</div>'; }
  else if (!jobs.length) { body += '<div class="card center mut">' + t('no_jobs') + '</div>'; }
  else {
    body += jobs.map(function (j) {
      var late = j.due && new Date(j.due) < new Date();
      var pct = j.lines ? Math.round(100 * j.done / j.lines) : 0;
      return '<button class="card job" data-job="' + j.id + '">' +
        '<div class="row" style="justify-content:space-between"><span class="plate">' + esc(j.car) + '</span>' + statusBadge(j.status) + '</div>' +
        '<div class="mut">' + esc(j.model) + ' · ' + esc(j.ro) + (j.bay ? ' · ' + t('bay') + ' ' + esc(j.bay) : '') + '</div>' +
        (j.due ? '<div class="mut ' + (late ? 'late' : '') + '">' + t('due') + ' ' + esc(fmtTime(j.due)) + '</div>' : '') +
        (j.lines ? '<div class="bar"><i style="width:' + pct + '%"></i></div><div class="mut" style="margin-top:4px">' + t('done_of', { d: j.done, n: j.lines }) + '</div>' : '') +
        (j.waiting ? '<div class="mut" style="color:#92400e;font-weight:700">⏳ ' + t('waiting_n', { n: j.waiting }) + '</div>' : '') +
        '</button>';
    }).join('');
  }
  body += '<div class="foot">' + langButtons() + '<button id="lo">' + t('logout') + '</button></div>';
  shell(t('title'), false, body);
  Array.prototype.forEach.call(app.querySelectorAll('[data-scope]'), function (b) {
    b.onclick = function () { state.scope = b.getAttribute('data-scope'); store.set('scope', state.scope); state.jobs = store.get('jobs_' + state.scope, null); renderList(); loadJobs(); };
  });
  Array.prototype.forEach.call(app.querySelectorAll('[data-job]'), function (b) { b.onclick = function () { location.hash = '#/job/' + b.getAttribute('data-job'); }; });
  bindLang(renderList);
  document.getElementById('lo').onclick = logout;
}

function logout() {
  var go = function () { api('POST', 'logout').catch(function () {}); qClear().then(refreshPending); store.del('me'); me = null; renderLogin(); };
  if (pending.length) { if (confirm(t('logout_q', { n: pending.length }))) go(); } else { go(); }
}

function loadJobs() {
  if (!navigator.onLine) { renderList(); return; }
  api('GET', 'jobs?scope=' + state.scope).then(function (j) {
    if (j._status === 401) { return signedOut(); }
    if (!j.ok) { return; }
    state.jobs = j.jobs; store.set('jobs_' + state.scope, j.jobs);
    if (location.hash === '' || location.hash === '#/' || location.hash === '#') renderList();
  }, function () { renderList(); });
}

// --- job screen
function applyPending(job) {
  // Show what this phone has done but not yet sent.
  var p = pending.filter(function (x) { return x.job === job.id; });
  p.forEach(function (x) {
    if (x.type === 'line') { job.items.forEach(function (it) { if (it.id === x.line) { it.done = x.body.done; it.pending = true; if (x.body.done) it.by = t('you'); } }); }
    if (x.type === 'status') { job.status = x.body.to; job.moves = ({ draft: ['in_progress'], in_progress: ['on_hold', 'ready'], on_hold: ['in_progress'] })[x.body.to] || []; job.pendingStatus = true; }
    if (x.type === 'note') { job.notes.unshift({ at: new Date(x.at).toISOString(), text: x.body.text, by: me.name, from: 'tech', pending: true }); }
  });
  job.done = job.items.filter(function (i) { return i.done; }).length;
  return job;
}

function renderJob() {
  var j = applyPending(JSON.parse(JSON.stringify(state.job)));
  var services = j.items.filter(function (i) { return i.type === 'service'; }), parts = j.items.filter(function (i) { return i.type === 'part'; });
  var b = '<div class="card"><div class="row" style="justify-content:space-between"><span class="plate">' + esc(j.car) + '</span>' + statusBadge(j.status) + '</div>' +
    '<div class="mut">' + esc(j.model) + ' · ' + esc(j.ro) + (j.bay ? ' · ' + t('bay') + ' ' + esc(j.bay) : '') + (j.odometer !== null ? ' · ' + j.odometer.toLocaleString('en-IN') + ' ' + t('km') : '') + '</div>' +
    (j.due ? '<div class="mut ' + (new Date(j.due) < new Date() ? 'late' : '') + '">' + t('due') + ' ' + esc(fmtTime(j.due)) + '</div>' : '') +
    (j.advisor ? '<div class="mut">' + t('advisor') + ': ' + esc(j.advisor) + '</div>' : '') + '</div>';
  if (j.closed) { b += '<div class="banner err">' + t('closed') + '</div>'; }
  if (j.complaint) { b += '<h2>' + t('complaint') + '</h2><div class="card">' + esc(j.complaint).replace(/\n/g, '<br>') + '</div>'; }
  if (j.instructions) { b += '<h2>' + t('instructions') + '</h2><div class="card" style="background:#fff8e1">' + esc(j.instructions).replace(/\n/g, '<br>') + '</div>'; }

  // status buttons
  var mv = j.closed ? [] : j.moves;
  var btns = '';
  if (mv.indexOf('in_progress') >= 0) btns += '<button class="big b-go" data-to="in_progress">▶ ' + (j.status === 'draft' ? t('start') : t('resume')) + '</button>';
  if (mv.indexOf('ready') >= 0) btns += '<button class="big b-ready" data-to="ready">✓ ' + t('ready') + '</button>';
  if (mv.indexOf('on_hold') >= 0) btns += '<button class="big b-hold" data-to="on_hold">❚❚ ' + t('hold') + '</button>';

  var task = function (it) {
    return '<button class="task ' + (it.done ? 'done ' : '') + (it.pending ? 'pending ' : '') + (it.wait && !it.done ? 'wait' : '') + '" data-line="' + it.id + '" ' + (j.closed ? 'disabled' : '') + '>' +
      '<span class="box">' + (it.done ? '✓' : (it.wait ? '⏳' : '')) + '</span><span><span class="nm">' + esc(it.name) + (it.qty !== '1' ? ' × ' + esc(it.qty) : '') + '</span>' +
      (it.wait && !it.done ? '<br><span class="wl">' + t('wait_ok') + '</span>' : '') +
      (it.desc ? '<br><span class="mut">' + esc(it.desc) + '</span>' : '') +
      (it.done && it.by ? '<br><span class="mut">✓ ' + esc(it.by) + '</span>' : (it.tech ? '<br><span class="mut">' + esc(it.tech) + '</span>' : '')) + '</span></button>';
  };
  if (j.status === 'draft' && !j.closed) { b += '<div style="margin-top:14px">' + btns + '</div>'; btns = ''; }
  b += '<h2>' + t('work') + ' · ' + t('done_of', { d: j.done, n: j.items.length }) + '</h2>';
  b += services.length ? services.map(task).join('') : '<div class="card mut">' + t('none') + '</div>';
  if (parts.length) { b += '<h2>' + t('parts') + '</h2>' + parts.map(task).join(''); }
  if (btns) { b += '<div style="margin-top:14px">' + btns + '</div>'; }

  // photos
  var mine = pending.filter(function (x) { return x.job === j.id && x.type === 'photo'; });
  b += '<h2>' + t('photos') + ' (' + (j.photos.length + mine.length) + ')</h2><div class="card">';
  if (j.photos.length || mine.length) {
    b += '<div class="photos">' + j.photos.map(function (p) {
      return '<div><a href="' + BASE + '/photo/' + p.id + '" target="_blank"><img loading="lazy" src="' + BASE + '/photo/' + p.id + '?t=1" alt=""></a><span>' + esc(t('k_' + p.kind)) + '</span></div>';
    }).join('') + mine.map(function (p) {
      return '<div class="wait"><img data-blob="' + p.uid + '" alt=""><span>' + esc(t('k_' + p.kind)) + '</span></div>';
    }).join('') + '</div>';
  }
  b += '<div class="kinds">' + ['before', 'during', 'after', 'damage', 'part'].map(function (k) { return '<button data-kind="' + k + '">📷 ' + esc(t('k_' + k)) + '</button>'; }).join('') + '</div></div>';

  // messages
  b += '<h2>' + t('msgs') + '</h2><div class="card">' +
    j.notes.map(function (n) { return '<div class="note ' + n.from + '">' + esc(n.text) + '<div class="mut">' + esc(n.by) + ' · ' + esc(fmtTime(n.at)) + (n.pending ? ' ⏳' : '') + '</div></div>'; }).join('') +
    '<form id="nf" class="row" style="margin-top:8px;flex-wrap:nowrap"><input class="field" id="nt" maxlength="500" placeholder="' + esc(t('note_ph')) + '" style="font-size:16px"><button class="big b-go" style="width:auto;margin:0;padding:14px 16px">' + t('send') + '</button></form></div>';

  shell(j.car, true, b);

  mine.forEach(function (p) { var img = app.querySelector('img[data-blob="' + p.uid + '"]'); if (img && p.blob) { img.src = URL.createObjectURL(p.blob); } });
  Array.prototype.forEach.call(app.querySelectorAll('[data-line]'), function (el) {
    el.onclick = function () {
      var id = +el.getAttribute('data-line'), cur = applyPending(JSON.parse(JSON.stringify(state.job))).items.filter(function (i) { return i.id === id; })[0];
      if (!cur) return;
      if (cur.wait && !cur.done) { toast(t('wait_ok')); return; }
      // Replace an unsent tick for the same line instead of stacking them.
      var old = pending.filter(function (x) { return x.type === 'line' && x.job === j.id && x.line === id; });
      Promise.all(old.map(function (o) { return qDel(o.uid); })).then(function () {
        return enqueue({ type: 'line', job: j.id, line: id, path: 'jobs/' + j.id + '/lines/' + id, body: { done: !cur.done } });
      }).then(renderJob);
      if (navigator.vibrate) navigator.vibrate(15);
    };
  });
  Array.prototype.forEach.call(app.querySelectorAll('[data-to]'), function (el) { el.onclick = function () { askStatus(j, el.getAttribute('data-to')); }; });
  Array.prototype.forEach.call(app.querySelectorAll('[data-kind]'), function (el) { el.onclick = function () { takePhoto(j.id, el.getAttribute('data-kind')); }; });
  document.getElementById('nf').onsubmit = function (e) {
    e.preventDefault();
    var v = document.getElementById('nt').value.trim(); if (!v) return;
    enqueue({ type: 'note', job: j.id, path: 'jobs/' + j.id + '/note', body: { text: v } }).then(function () { toast(navigator.onLine ? t('sent') : t('saved')); renderJob(); });
  };
}

function askStatus(j, to) {
  var send = function (note) {
    enqueue({ type: 'status', job: j.id, path: 'jobs/' + j.id + '/status', body: { to: to, note: note || '' } }).then(renderJob);
    if (navigator.vibrate) navigator.vibrate(30);
  };
  if (to === 'in_progress') { return send(''); }
  if (to === 'ready') {
    var left = j.items.length - j.done;
    return sheet('<h3>' + t('ready_q') + '</h3>' + (left > 0 ? '<p class="err">' + t('ready_left', { n: left }) + '</p>' : '') +
      '<button class="big b-ready" id="sy">' + t('yes') + '</button><button class="big b-sec" id="sn">' + t('cancel') + '</button>', function (el, close) {
      el.querySelector('#sy').onclick = function () { close(); send(''); };
      el.querySelector('#sn').onclick = close;
    });
  }
  sheet('<h3>' + t('hold_q') + '</h3>' +
    '<button class="big b-sec" data-r="' + esc(T.en.h_parts) + '">' + t('h_parts') + '</button>' +
    '<button class="big b-sec" data-r="' + esc(T.en.h_approval) + '">' + t('h_approval') + '</button>' +
    '<input class="field" id="ro" maxlength="150" placeholder="' + esc(t('h_other_ph')) + '" style="margin-bottom:10px">' +
    '<button class="big b-hold" id="rs">' + t('ok') + '</button><button class="big b-sec" id="rc">' + t('cancel') + '</button>', function (el, close) {
    Array.prototype.forEach.call(el.querySelectorAll('[data-r]'), function (b) { b.onclick = function () { close(); send(b.getAttribute('data-r')); }; });
    el.querySelector('#rs').onclick = function () { var v = el.querySelector('#ro').value.trim(); close(); send(v || T.en.h_other); };
    el.querySelector('#rc').onclick = close;
  });
}

// --- photos: shrink on the phone (max 1600 px JPEG), then queue
var cam = document.getElementById('cam'), camFor = null;
function takePhoto(jobId, kind) { camFor = { job: jobId, kind: kind }; cam.value = ''; cam.click(); }
cam.onchange = function () {
  var f = cam.files && cam.files[0]; if (!f || !camFor) return;
  var target = camFor; camFor = null;
  shrink(f).then(function (blob) {
    return enqueue({ type: 'photo', job: target.job, kind: target.kind, blob: blob });
  }).then(function () { toast(navigator.onLine ? t('saved') : t('saved')); if (state.job && state.job.id === target.job) renderJob(); },
    function () { toast(t('e_photo')); });
};
function shrink(file) {
  var draw = function (src, w, h) {
    var s = Math.min(1, 1600 / Math.max(w, h)), c = document.createElement('canvas');
    c.width = Math.round(w * s); c.height = Math.round(h * s);
    c.getContext('2d').drawImage(src, 0, 0, c.width, c.height);
    return new Promise(function (res, rej) { c.toBlob(function (b) { b ? res(b) : rej(); }, 'image/jpeg', 0.75); });
  };
  if (window.createImageBitmap) {
    return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bm) { return draw(bm, bm.width, bm.height); });
  }
  return new Promise(function (res, rej) {
    var img = new Image(); img.onload = function () { draw(img, img.naturalWidth, img.naturalHeight).then(res, rej); URL.revokeObjectURL(img.src); };
    img.onerror = rej; img.src = URL.createObjectURL(file);
  });
}

function loadJob(id, quiet) {
  var cached = store.get('job_' + id, null);
  if (cached && !quiet) { state.job = cached; renderJob(); }
  else if (!cached && !quiet) { shell('…', true, '<div class="center mut">…</div>'); }
  if (!navigator.onLine) { if (!cached) shell('…', true, '<div class="card mut">' + t('off0') + '</div>'); return; }
  api('GET', 'jobs/' + id).then(function (j) {
    if (j._status === 401) return signedOut();
    if (!j.ok) { shell('…', true, '<div class="card mut">' + t('closed') + '</div>'); return; }
    state.job = j.job; store.set('job_' + id, j.job);
    if (location.hash === '#/job/' + id) renderJob();
  }, function () {});
}

// ---------------------------------------------------------------- router
function route(force) {
  if (!me) { return renderLogin(); }
  document.documentElement.lang = lang;
  var m = location.hash.match(/^#\/job\/(\d+)/);
  if (m) { loadJob(+m[1]); }
  else { state.job = null; renderList(); loadJobs(); }
}
window.addEventListener('hashchange', function () { route(); });

// Start: check the session (works offline from the saved one).
refreshPending().then(function () {
  if (!navigator.onLine) { route(); return; }
  api('GET', 'me').then(function (j) {
    if (j.ok) { me = j.me; store.set('me', me); if (!store.get('lang_chosen', false) && me.lang) { lang = me.lang; store.set('lang', lang); } route(); flush(); }
    else if (j._status === 401) { signedOut(); }
    else { route(); }
  }, function () { route(); });
});

if ('serviceWorker' in navigator) {
  var scope = new URL(BASE).pathname;
  navigator.serviceWorker.register(BASE + '/sw.js?v=' + VERSION, { scope: scope }).catch(function () {});
}
})();
</script>
</body>
</html>
