#!/usr/bin/env php
<?php
/** List marketing titles > 60 chars and meta descriptions > 155 chars. */
require __DIR__.'/_marketing_cli_bootstrap.php';

$pages = marketing_cli_pages_data();

$long_titles = 0;
$long_desc = 0;
foreach ($pages as $path => $page) {
    if (!is_array($page)) {
        continue;
    }
    $title = !empty($page['meta_title']) ? $page['meta_title'] : (isset($page['title']) ? $page['title'] : '');
    $desc = isset($page['meta_description']) ? $page['meta_description'] : '';
    if (strlen($title) > 60) {
        $long_titles++;
        echo 'TITLE '.strlen($title)." | /{$path} | {$title}\n";
    }
    if (strlen($desc) > 155) {
        $long_desc++;
        echo 'DESC  '.strlen($desc)." | /{$path} | {$desc}\n";
    }
}
echo "\nSummary: {$long_titles} titles > 60, {$long_desc} descriptions > 155\n";
