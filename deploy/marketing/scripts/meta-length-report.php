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
    $title = function_exists('marketing_page_report_title')
        ? marketing_page_report_title($page)
        : (!empty($page['meta_title']) ? $page['meta_title'] : (isset($page['title']) ? $page['title'] : ''));
    $desc = isset($page['meta_description']) ? $page['meta_description'] : '';
    $title_len = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
    $desc_len = function_exists('mb_strlen') ? mb_strlen($desc, 'UTF-8') : strlen($desc);
    if ($title_len > 60) {
        $long_titles++;
        echo 'TITLE '.$title_len." | /{$path} | {$title}\n";
    }
    if ($desc_len > 155) {
        $long_desc++;
        echo 'DESC  '.$desc_len." | /{$path} | {$desc}\n";
    }
}
echo "\nSummary: {$long_titles} titles > 60, {$long_desc} descriptions > 155\n";
