<?php
/**
 * Pull WordPress content via REST API into marketing page configs.
 *
 * Run from repo root:
 *   php deploy/marketing/scripts/migrate_wp_content.php
 *   php deploy/marketing/scripts/migrate_wp_content.php --dry-run
 *   php deploy/marketing/scripts/migrate_wp_content.php --limit=5
 */
declare(strict_types=1);

$root = realpath(__DIR__.'/../../../');
if ($root === false) {
    fwrite(STDERR, "Cannot resolve repo root\n");
    exit(1);
}

$opts = getopt('', array('dry-run', 'limit:'));
$dryRun = isset($opts['dry-run']);
$limit = isset($opts['limit']) ? max(0, (int) $opts['limit']) : 0;

$wpBase = 'https://yourmechaniconline.com';
$pagesMain = $root.'/application/config/marketing_pages_data.php';
$pagesOptionA = $root.'/application/config/marketing_pages_option_a.php';
$reportFile = $root.'/deploy/marketing/generated/wp_migration_report.json';

function fetch_json(string $url): array
{
    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 60,
            'header'  => "User-Agent: YMO-Marketing-Migrator/1.0\r\n",
        ),
        'ssl' => array(
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ),
    ));
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new RuntimeException("Failed to fetch: {$url}");
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException("Invalid JSON from: {$url}");
    }
    $headers = isset($http_response_header) ? $http_response_header : array();
    return array($data, $headers);
}

function fetch_all_pages(string $wpBase): array
{
    $all = array();
    $page = 1;
    while (true) {
        $url = $wpBase.'/wp-json/wp/v2/pages?per_page=100&page='.$page.'&status=publish';
        list($batch, $headers) = fetch_json($url);
        if (!$batch) {
            break;
        }
        $all = array_merge($all, $batch);
        $totalPages = 1;
        foreach ($headers as $h) {
            if (stripos($h, 'X-WP-TotalPages:') === 0) {
                $totalPages = (int) trim(substr($h, strlen('X-WP-TotalPages:')));
            }
        }
        if ($page >= $totalPages) {
            break;
        }
        $page++;
    }
    return $all;
}

function path_from_link(string $link): string
{
    $path = parse_url($link, PHP_URL_PATH);
    return strtolower(trim((string) $path, '/'));
}

function path_from_canonical(array $item): string
{
    $aioseo = isset($item['aioseo_meta_data']) && is_array($item['aioseo_meta_data'])
        ? $item['aioseo_meta_data'] : array();
    if (!empty($aioseo['canonical_url'])) {
        return path_from_link(trim(explode(' ', (string) $aioseo['canonical_url'])[0]));
    }
    $head = isset($item['aioseo_head_json']) && is_array($item['aioseo_head_json'])
        ? $item['aioseo_head_json'] : array();
    if (!empty($head['canonical_url'])) {
        return path_from_link(trim(explode(' ', (string) $head['canonical_url'])[0]));
    }
    if (!empty($head['og:url'])) {
        return path_from_link(trim(explode(' ', (string) $head['og:url'])[0]));
    }
    return path_from_link((string) ($item['link'] ?? ''));
}

function build_parent_paths(array $pages): array
{
    $byId = array();
    foreach ($pages as $p) {
        $byId[(int) $p['id']] = $p;
    }
    $cache = array();
    $walk = function (int $id) use (&$walk, &$byId, &$cache): string {
        if (isset($cache[$id])) {
            return $cache[$id];
        }
        if (!isset($byId[$id])) {
            return '';
        }
        $page = $byId[$id];
        $slug = trim((string) ($page['slug'] ?? ''), '/');
        $parent = (int) ($page['parent'] ?? 0);
        if ($parent && isset($byId[$parent])) {
            $parentPath = $walk($parent);
            $full = $parentPath !== '' ? $parentPath.'/'.$slug : $slug;
        } else {
            $full = $slug;
        }
        $cache[$id] = strtolower($full);
        return $cache[$id];
    };
    foreach (array_keys($byId) as $id) {
        $walk((int) $id);
    }
    return $cache;
}

function strip_vc_shortcodes(string $text): string
{
    $text = preg_replace('/\[vc_raw_html\](.*?)\[\/vc_raw_html\]/is', '$1', $text);
    $text = preg_replace('/\[rev_slider_vc[^\]]*\]/i', '', $text);
    $text = preg_replace('/\[\/?vc_[^\]]*\]/i', '', $text);
    return (string) $text;
}

function clean_html(string $raw, string $wpBase): string
{
    if ($raw === '') {
        return '';
    }
    $text = strip_vc_shortcodes($raw);
    $text = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = preg_replace('/>\s+</', '><', $text);
    $text = str_replace('http://quanticalabs.com/wptest/carservice/', $wpBase.'/wp-content/uploads/', $text);
    $text = preg_replace('#href="https?://yourmechaniconline\.com/?([^"]*)"#i', 'href="/$1"', $text);
    return trim($text);
}

function plain_intro(string $raw, int $limit = 280): string
{
    $text = strip_tags(strip_vc_shortcodes($raw));
    $text = html_entity_decode(preg_replace('/\s+/u', ' ', $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim($text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    $cut = mb_substr($text, 0, $limit);
    $cut = preg_replace('/\s+\S*$/u', '', $cut);
    return rtrim($cut, '.,;:').'…';
}

function seo_title(array $item): string
{
    $aioseo = isset($item['aioseo_meta_data']) ? $item['aioseo_meta_data'] : array();
    if (!empty($aioseo['title'])) {
        return html_entity_decode(trim((string) $aioseo['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $head = isset($item['aioseo_head_json']) ? $item['aioseo_head_json'] : array();
    if (!empty($head['title'])) {
        return html_entity_decode(trim((string) $head['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $title = isset($item['title']['rendered']) ? $item['title']['rendered'] : '';
    return trim(strip_tags(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function seo_description(array $item): string
{
    $aioseo = isset($item['aioseo_meta_data']) ? $item['aioseo_meta_data'] : array();
    if (!empty($aioseo['description'])) {
        return html_entity_decode(trim((string) $aioseo['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $head = isset($item['aioseo_head_json']) ? $item['aioseo_head_json'] : array();
    if (!empty($head['description'])) {
        return html_entity_decode(trim((string) $head['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $excerpt = isset($item['excerpt']['rendered']) ? $item['excerpt']['rendered'] : '';
    return plain_intro($excerpt, 160);
}

function h1_from_title(string $title): string
{
    $title = preg_replace('/\s*[|\x{2013}-]\s*YMO.*$/iu', '', $title);
    $title = preg_replace('/\s*[|\x{2013}-]\s*Your Mechanic Online.*$/iu', '', $title);
    return trim($title);
}

function load_page_keys(string ...$files): array
{
    $keys = array();
    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }
        $text = file_get_contents($file);
        if (preg_match_all("/^\s+'([^']+)'\s*=>/m", $text, $m)) {
            foreach ($m[1] as $slug) {
                $keys[strtolower($slug)] = true;
            }
        }
    }
    return array_keys($keys);
}

function php_str(string $value): string
{
    return "'".str_replace(array('\\', "'"), array('\\\\', "\\'"), $value)."'";
}

function update_php_file(string $path, array $updates): int
{
    if (!$updates || !is_file($path)) {
        return 0;
    }
    $text = file_get_contents($path);
    $changed = 0;
    foreach ($updates as $slug => $fields) {
        foreach (array('title', 'meta_description', 'h1', 'intro', 'body') as $field) {
            if (!array_key_exists($field, $fields)) {
                continue;
            }
            $val = php_str((string) $fields[$field]);
            $pattern = "/('".preg_quote($slug, '/')."'\s*=>\s*array\s*\(.*?'".$field."'\s*=>\s*)('(?:\\\\.|[^'\\\\])*'|'')/s";
            if (preg_match($pattern, $text)) {
                $text = preg_replace($pattern, '$1'.$val, $text, 1, $count);
                $changed += $count;
            } elseif (in_array($field, array('body', 'intro'), true)) {
                $insert = "/('".preg_quote($slug, '/')."'\s*=>\s*array\s*\()(\s*\n\s*'title')/s";
                if (preg_match($insert, $text)) {
                    $text = preg_replace($insert, '$1'."\n        '{$field}'            => {$val},".'$2', $text, 1, $count);
                    $changed += $count;
                }
            }
        }
    }
    file_put_contents($path, $text);
    return $changed;
}

echo "Fetching WordPress pages…\n";
$wpPages = fetch_all_pages($wpBase);
$parentPaths = build_parent_paths($wpPages);
$targetKeys = load_page_keys($pagesMain, $pagesOptionA);

$wpByPath = array();
foreach ($wpPages as $item) {
    $paths = array(
        path_from_link((string) ($item['link'] ?? '')),
        path_from_canonical($item),
        isset($parentPaths[(int) $item['id']]) ? $parentPaths[(int) $item['id']] : '',
    );
    foreach ($paths as $p) {
        if ($p !== '') {
            $wpByPath[$p] = $item;
            $wpByPath[rtrim($p, '/')] = $item;
        }
    }
}

$matched = array();
$unmatchedTargets = array();
foreach ($targetKeys as $key) {
    if (!isset($wpByPath[$key])) {
        $unmatchedTargets[] = $key;
        continue;
    }
    $item = $wpByPath[$key];
    $raw = isset($item['content']['rendered']) ? (string) $item['content']['rendered'] : '';
    $title = seo_title($item);
    $body = clean_html($raw, $wpBase);
    if ($body === '' && $title === '') {
        continue;
    }
    $matched[$key] = array(
        'title'            => $title,
        'meta_description' => seo_description($item),
        'h1'               => h1_from_title($title),
        'intro'            => plain_intro($raw),
        'body'             => $body,
        'wp_link'          => $item['link'] ?? '',
        'wp_id'            => $item['id'] ?? null,
    );
    if ($limit && count($matched) >= $limit) {
        break;
    }
}

$report = array(
    'wp_pages_total'     => count($wpPages),
    'target_pages'       => count($targetKeys),
    'matched'            => count($matched),
    'unmatched_targets'  => $unmatchedTargets,
    'matched_slugs'      => array_keys($matched),
);
if (!is_dir(dirname($reportFile))) {
    mkdir(dirname($reportFile), 0775, true);
}
file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo 'WP pages: '.count($wpPages).' | Targets: '.count($targetKeys).' | Matched: '.count($matched)."\n";
echo 'Report: deploy/marketing/generated/wp_migration_report.json'."\n";

if ($dryRun) {
    $i = 0;
    foreach ($matched as $slug => $row) {
        echo "  [dry-run] {$slug} — ".mb_substr($row['title'], 0, 70)."\n";
        if (++$i >= 10) {
            break;
        }
    }
    exit(0);
}

$mainText = file_get_contents($pagesMain);
$mainUpdates = array();
$optionUpdates = array();
foreach ($matched as $slug => $fields) {
    if (strpos($mainText, "'{$slug}'") !== false) {
        $mainUpdates[$slug] = $fields;
    } else {
        $optionUpdates[$slug] = $fields;
    }
}

$c1 = update_php_file($pagesMain, $mainUpdates);
$c2 = update_php_file($pagesOptionA, $optionUpdates);
echo "Updated fields: marketing_pages_data.php={$c1}, marketing_pages_option_a.php={$c2}\n";

if ($unmatchedTargets) {
    echo 'Unmatched targets ('.count($unmatchedTargets)."):\n";
    foreach (array_slice($unmatchedTargets, 0, 20) as $slug) {
        echo "  - {$slug}\n";
    }
}
