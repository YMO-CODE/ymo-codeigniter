<?php
/**
 * SEO content fingerprint — registry h1/title/meta (not rendered HTML).
 * Use before/after theme rollout to confirm copy unchanged.
 *
 *   php deploy/marketing/scripts/theme-content-snapshot.php
 *   php deploy/marketing/scripts/theme-content-snapshot.php > /tmp/ymo-seo-snapshot.tsv
 */
require __DIR__.'/_marketing_cli_bootstrap.php';

$pages = marketing_cli_pages_data();
ksort($pages);

$out = fopen('php://stdout', 'w');
fputcsv($out, array('path', 'h1_len', 'h1', 'meta_title_len', 'meta_description_len'), "\t");

foreach ($pages as $path => $page) {
    if (!is_array($page)) {
        continue;
    }
    $h1 = isset($page['h1']) ? trim((string) $page['h1']) : '';
    $mt = !empty($page['meta_title']) ? trim((string) $page['meta_title']) : trim((string) ($page['title'] ?? ''));
    $md = isset($page['meta_description']) ? trim((string) $page['meta_description']) : '';
    fputcsv($out, array(
        $path,
        strlen($h1),
        $h1,
        strlen($mt),
        strlen($md),
    ), "\t");
}

fclose($out);
fwrite(STDERR, count($pages)." pages exported.\n");
