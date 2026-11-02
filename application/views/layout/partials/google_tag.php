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
</script>
