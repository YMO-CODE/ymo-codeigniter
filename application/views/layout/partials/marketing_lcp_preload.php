<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$lcp_hints = array('mobile' => '', 'desktop' => '', 'type' => 'image/webp');
$lcp_og = isset($og_image) ? $og_image : '';
if ($lcp_og === '' && isset($canonical_path) && function_exists('marketing_resolve_og_image')) {
    $lcp_og = marketing_resolve_og_image($canonical_path, isset($page) && is_array($page) ? $page : array());
}
if ($lcp_og !== '' && function_exists('marketing_lcp_preload_hints')) {
    $lcp_hints = marketing_lcp_preload_hints($lcp_og);
}
if ($lcp_hints['mobile'] === '' && $lcp_hints['desktop'] === '') {
    return;
}
?>
<?php if ($lcp_hints['mobile'] !== ''): ?>
<link rel="preload" as="image" href="<?= html_escape($lcp_hints['mobile']); ?>" type="<?= html_escape($lcp_hints['type']); ?>" fetchpriority="high" media="(max-width: 768px)">
<?php endif; ?>
<?php if ($lcp_hints['desktop'] !== ''): ?>
<link rel="preload" as="image" href="<?= html_escape($lcp_hints['desktop']); ?>" type="<?= html_escape($lcp_hints['type']); ?>" fetchpriority="high" media="(min-width: 769px)">
<?php endif; ?>
