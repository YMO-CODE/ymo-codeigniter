#!/usr/bin/env php
<?php
/** Count internal hrefs pointing to each sitemap path; flag < 3 inlinks. */
require __DIR__.'/_marketing_cli_bootstrap.php';

$pages = marketing_cli_pages_data();
$paths = array_keys($pages);
$inlinks = array_fill_keys($paths, 0);

if (function_exists('marketing_internal_link_outbound_map')) {
    $map = marketing_internal_link_outbound_map();
    foreach ($map as $target => $sources) {
        $target = trim((string) $target, '/');
        if ($target === '' || !isset($inlinks[$target])) {
            continue;
        }
        $inlinks[$target] = count($sources);
    }
} else {
    foreach ($pages as $from => $page) {
        if (!is_array($page)) {
            continue;
        }
        $body = isset($page['body']) ? (string) $page['body'] : '';
        if (function_exists('marketing_page_internal_links_append')) {
            $body = marketing_page_internal_links_append($from, $page, $body);
        }
        $blob = json_encode($page, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).$body;
        foreach ($paths as $target) {
            if ($from === $target) {
                continue;
            }
            if (strpos($blob, '"'.$target.'"') !== false
                || strpos($blob, '/'.$target) !== false
                || strpos($body, 'href="/'.$target.'"') !== false) {
                $inlinks[$target]++;
            }
        }
    }
}

$min = (int) (getenv('MIN_INLINKS') ?: 3);
$fail = 0;
foreach ($inlinks as $path => $count) {
    if ($count < $min) {
        $fail++;
        echo "INLINKS {$count} | /{$path}\n";
    }
}
echo "\n{$fail} URLs with fewer than {$min} inlinks (nav, footer, hubs, and contextual body links).\n";
exit($fail ? 1 : 0);
