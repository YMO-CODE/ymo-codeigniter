#!/usr/bin/env php
<?php
/** Count internal hrefs pointing to each sitemap path; flag < 3 inlinks. */
require __DIR__.'/_marketing_cli_bootstrap.php';

$pages = marketing_cli_pages_data();
$paths = array_keys($pages);
$inlinks = array_fill_keys($paths, 0);

foreach ($pages as $from => $page) {
    if (!is_array($page)) {
        continue;
    }
    $blob = json_encode($page);
    foreach ($paths as $target) {
        if ($from === $target) {
            continue;
        }
        if (strpos($blob, '"'.$target.'"') !== false || strpos($blob, '/'.$target) !== false) {
            $inlinks[$target]++;
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
echo "\n{$fail} URLs with fewer than {$min} inlinks (see Phase 5 hub/service/blog links).\n";
exit($fail ? 1 : 0);
