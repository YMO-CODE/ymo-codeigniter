#!/usr/bin/env php
<?php
/**
 * Pairwise 5-word Jaccard similarity on marketing page intro+body (same page_type).
 * Flags pairs > 40%. Requires BASE_URL reachable (local docker or production).
 */
require __DIR__.'/_marketing_cli_bootstrap.php';

$base = getenv('BASE') ?: 'https://www.yourmechaniconline.com';
$threshold = (float) (getenv('THRESHOLD') ?: 0.4);

$pages = marketing_cli_pages_data();

function shingles5($text) {
    $text = strtolower(strip_tags($text));
    $text = preg_replace('/\s+/', ' ', $text);
    $words = preg_split('/\s+/', trim($text));
    $set = array();
    for ($i = 0; $i + 5 <= count($words); $i++) {
        $set[implode(' ', array_slice($words, $i, 5))] = true;
    }
    return $set;
}

function jaccard($a, $b) {
    if ($a === array() && $b === array()) {
        return 0.0;
    }
    $inter = count(array_intersect_key($a, $b));
    $union = count($a + $b);
    return $union ? $inter / $union : 0.0;
}

$by_type = array();
foreach ($pages as $path => $page) {
    if (!is_array($page)) {
        continue;
    }
    $type = isset($page['page_type']) ? $page['page_type'] : 'unknown';
    $text = (isset($page['intro']) ? $page['intro'] : '').' '.(isset($page['body']) ? $page['body'] : '');
    $by_type[$type][$path] = shingles5($text);
}

$fail = 0;
foreach ($by_type as $type => $map) {
    $keys = array_keys($map);
    $n = count($keys);
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $sim = jaccard($map[$keys[$i]], $map[$keys[$j]]);
            if ($sim > $threshold) {
                $fail++;
                printf("%s vs %s | type=%s | %.0f%%\n", $keys[$i], $keys[$j], $type, $sim * 100);
            }
        }
    }
}
echo $fail ? "\n{$fail} pairs above ".($threshold * 100)."% (fill locality/brand fields).\n" : "\nNo pairs above threshold.\n";
exit($fail ? 1 : 0);
