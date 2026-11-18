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
    else if (/wa\.me\/|api\.whatsapp\.com|^whatsapp:/i.test(h)) { window.ymoConversion('whatsapp'); }
  }, true);
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
