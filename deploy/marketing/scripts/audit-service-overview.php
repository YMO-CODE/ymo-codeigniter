<?php
/**
 * One-off audit: run marketing_normalize_service_body on config service pages.
 * Usage (in app container): php deploy/marketing/scripts/audit-service-overview.php
 */
define('BASEPATH', '1');
if (is_dir('/var/www/application')) {
    define('APPPATH', '/var/www/application/');
} else {
    define('APPPATH', dirname(__DIR__, 3) . '/application/');
}

function html_escape($str, $double_encode = true)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8', $double_encode);
}

function marketing_is_theme_v2($a, $b)
{
    return true;
}

require APPPATH . 'helpers/marketing_helper.php';

$pages = include APPPATH . 'config/marketing_pages_data.php';
if (!is_array($pages)) {
    fwrite(STDERR, "Could not load marketing_pages_data.php\n");
    exit(1);
}

$paths = array();
foreach ($pages as $path => $row) {
    if (!is_array($row) || empty($row['body'])) {
        continue;
    }
    if (strpos($path, 'services/') === 0) {
        $paths[$path] = $row;
    }
}

echo 'Service-like pages: ' . count($paths) . PHP_EOL;

foreach ($paths as $path => $row) {
    $body = marketing_normalize_service_body(
        $row['body'],
        isset($row['faqs']) ? $row['faqs'] : array()
    );
    $split = stripos($body, 'ymo-v2-service-overview--split') !== false;
    $stacked = stripos($body, 'ymo-v2-service-overview--stacked') !== false;
    $overview = stripos($body, 'ymo-v2-service-overview') !== false;
    $includes = stripos($body, 'ymo-v2-overview-layout__includes') !== false;
    $why = stripos($body, 'ymo-v2-why-faq') !== false;
    $nested = false;
    if ($overview && $why) {
        $nested = !preg_match(
            '/ymo-v2-service-overview[\s\S]*<\/div>\s*<\/div>\s*<div class="ymo-content-section ymo-v2-why-faq/i',
            $body
        );
    }
    $extraGrid = (bool) preg_match(
        '/ymo-v2-service-overview[\s\S]*<\/div>\s*<div class="ymo-content-section mb-5">[\s\S]*ymo-v2-card-grid/i',
        $body
    );
    $items = 0;
    if (preg_match('/ymo-v2-overview-layout__includes([\s\S]*?)<\/aside>/i', $body, $block)) {
        $items = preg_match_all('/ymo-v2-checklist__cell|ymo-v2-checklist__text/', $block[1], $m);
    }
    $raw = $row['body'];
    $pattern = 'plain';
    if (stripos($raw, 'feature-item') !== false) {
        $pattern = 'feature-item';
    } elseif (stripos($raw, 'service-blocks') !== false) {
        $pattern = 'service-blocks';
    } elseif (stripos($raw, 'iconList') !== false || preg_match('/<ul[\s>]/i', $raw)) {
        $pattern = 'ul/iconList';
    } elseif (preg_match('/vc_row/i', $raw)) {
        $pattern = 'vc_row';
    }

    echo implode(' | ', array(
        $path,
        'src=' . $pattern,
        'overview=' . (int) $overview,
        'split=' . (int) $split,
        'stacked=' . (int) $stacked,
        'includes=' . (int) $includes,
        'items~' . $items,
        'why=' . (int) $why,
        'nestedWhy=' . (int) $nested,
        'extraGrid=' . (int) $extraGrid,
    )) . PHP_EOL;
}
