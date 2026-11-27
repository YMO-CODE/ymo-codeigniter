<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$gtag_id = (string) $ci->config->item('ymo_google_ads_tag_id');
if ($gtag_id === '') {
    return;
}
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= html_escape($gtag_id); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?= html_escape($gtag_id); ?>');
<?php $conv = (array) $ci->config->item('ymo_google_ads_conversions'); ?>
  // Conversions: ymoConversion('quick_book' | 'booking' | 'phone' | 'whatsapp', {transaction_id: ...})
  window.ymoConversions = <?= json_encode($conv, JSON_UNESCAPED_SLASHES); ?>;
  window.ymoConversion = function (key, extra) {
    var to = window.ymoConversions[key];
    if (!to) { return; }
    var p = {'send_to': to};
    for (var k in (extra || {})) { p[k] = extra[k]; }
    gtag('event', 'conversion', p);
  };
  // Phone and WhatsApp taps anywhere on the site.
  document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) { return; }
    var h = a.getAttribute('href') || '';
    if (/^tel:/i.test(h)) { window.ymoConversion('phone'); }
    else if (/wa\.me\/|api\.whatsapp\.com|^whatsapp:/i.test(h)) {
      window.ymoConversion('whatsapp');
      try { ymoWaRef(a); } catch (err) {}
    }
  }, true);
  // Google Ads visitors: hide a reference in the WhatsApp text (zero-width
  // characters, nothing visible) so the CRM can tie the chat to the ad click.
  function ymoWaRef(a) {
    var m = document.cookie.match(/(?:^|;\s*)ymo_attr=([^;]+)/), attr;
    if (!m) { return; }
    attr = JSON.parse(decodeURIComponent(m[1]));
    if (!attr || !(attr.gclid || attr.gbraid || attr.wbraid)) { return; }
    var u = new URL(a.href, location.href);
    if (!/^https?:$/.test(u.protocol)) { return; }
    var buf = new Uint32Array(1), zw = ['\u200B', '\u200C', '\u200D', '\u2060'], n, inv = '', i;
    (window.crypto || window.msCrypto).getRandomValues(buf);
    n = buf[0];
    for (i = 0; i < 16; i++) { inv = zw[n & 3] + inv; n = n >>> 2; }
    var ref = ('0000000' + buf[0].toString(16)).slice(-8);
    var text = (u.searchParams.get('text') || 'Hi YMO, I would like to book a car service').replace(/[\u200B\u200C\u200D\u2060]/g, '');
    var q = [];
    u.searchParams.forEach(function (v, k) { if (k !== 'text') { q.push(encodeURIComponent(k) + '=' + encodeURIComponent(v)); } });
    q.push('text=' + encodeURIComponent(text + inv));
    a.href = u.origin + u.pathname + '?' + q.join('&') + u.hash;
    var body = JSON.stringify({ref: ref, page: location.pathname.slice(0, 200)});
    if (navigator.sendBeacon) { navigator.sendBeacon('/api/ads/wa-click', new Blob([body], {type: 'application/json'})); }
    else { fetch('/api/ads/wa-click', {method: 'POST', body: body, headers: {'Content-Type': 'application/json'}, keepalive: true, credentials: 'same-origin'}); }
  }
  // Remember where the visitor came from (ad click / UTM) for 30 days, across
  // www. and booking. subdomains, so the CRM can tag the lead's source.
  (function () {
    try {
      var q = new URLSearchParams(location.search), keys = ['utm_source','utm_medium','utm_campaign','utm_content','utm_term','gclid','gbraid','wbraid'], v = {}, any = false;
      keys.forEach(function (k) { var x = q.get(k); if (x) { v[k] = x.slice(0, 200); any = true; } });
      if (!any) { return; }
      v.landing = location.pathname.slice(0, 200);
      v.at = Math.floor(Date.now() / 1000);
      var dom = /(^|\.)yourmechaniconline\.com$/.test(location.hostname) ? '; domain=.yourmechaniconline.com' : '';
      document.cookie = 'ymo_attr=' + encodeURIComponent(JSON.stringify(v)) + '; max-age=2592000; path=/; SameSite=Lax' + dom + (location.protocol === 'https:' ? '; Secure' : '');
    } catch (err) {}
  })();
</script>
