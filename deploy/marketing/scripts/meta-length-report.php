#!/usr/bin/env php
<?php
/** List marketing titles > 60 chars and meta descriptions > 155 chars. */
define('BASEPATH', __DIR__.'/../../../system/');
define('APPPATH', __DIR__.'/../../../application/');
define('ENVIRONMENT', 'development');

foreach (array('marketing_helper.php', 'marketing_seo_growth_helper.php', 'marketing_seo_enhancements_helper.php', 'marketing_seo_project_helper.php', 'marketing_internal_links_helper.php') as $h) {
    $p = APPPATH.'helpers/'.$h;
    if (is_file($p)) {
        require_once $p;
    }
}

$pages = require APPPATH.'config/marketing_pages_data.php';
if (function_exists('marketing_enrich_pages')) {
    $pages = marketing_enrich_pages($pages);
}

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
