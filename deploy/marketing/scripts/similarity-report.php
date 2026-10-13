#!/usr/bin/env php
<?php
/**
 * Pairwise 5-word Jaccard on marketing page text.
 *
 * Modes (SIMILARITY_MODE):
 *   strict (default) — only pairs that can cannibalize (same-city localities, same-city blogs).
 *   all              — every same page_type pair (flags cross-city brand/service clones).
 */
require __DIR__.'/_marketing_cli_bootstrap.php';

if (!function_exists('marketing_similarity_should_compare')) {
    $helper = dirname(__DIR__, 3).'/application/helpers/marketing_similarity_helper.php';
    if (is_file($helper)) {
        require_once $helper;
    }
}

$mode = strtolower(trim((string) (getenv('SIMILARITY_MODE') ?: 'strict')));
$threshold = (float) (getenv('THRESHOLD') ?: 0.4);

$pages = marketing_cli_pages_data();

$paths = array_keys($pages);
$fail = 0;
$n = count($paths);

for ($i = 0; $i < $n; $i++) {
    $path_a = $paths[$i];
    $page_a = $pages[$path_a];
    if (!is_array($page_a)) {
        continue;
    }
    $type_a = isset($page_a['page_type']) ? $page_a['page_type'] : 'unknown';
    $text_a = function_exists('marketing_similarity_page_text')
        ? marketing_similarity_page_text($page_a)
        : ((isset($page_a['intro']) ? $page_a['intro'] : '').' '.(isset($page_a['body']) ? $page_a['body'] : ''));
    $sh_a = function_exists('marketing_similarity_shingles5')
        ? marketing_similarity_shingles5($text_a)
        : array();

    for ($j = $i + 1; $j < $n; $j++) {
        $path_b = $paths[$j];
        $page_b = $pages[$path_b];
        if (!is_array($page_b)) {
            continue;
        }
        $type_b = isset($page_b['page_type']) ? $page_b['page_type'] : 'unknown';

        if ($mode === 'strict') {
            if (!function_exists('marketing_similarity_should_compare')
                || !marketing_similarity_should_compare($path_a, $page_a, $path_b, $page_b)) {
                continue;
            }
        } elseif ($type_a !== $type_b) {
            continue;
        }

        $text_b = function_exists('marketing_similarity_page_text')
            ? marketing_similarity_page_text($page_b)
            : ((isset($page_b['intro']) ? $page_b['intro'] : '').' '.(isset($page_b['body']) ? $page_b['body'] : ''));
        $sh_b = function_exists('marketing_similarity_shingles5')
            ? marketing_similarity_shingles5($text_b)
            : array();

        $sim = function_exists('marketing_similarity_jaccard')
            ? marketing_similarity_jaccard($sh_a, $sh_b)
            : 0.0;

        if ($sim > $threshold) {
            $fail++;
            printf("%s vs %s | type=%s | %.0f%%\n", $path_a, $path_b, $type_a, $sim * 100);
        }
    }
}

if ($fail) {
    echo "\n{$fail} pairs above ".($threshold * 100)."% (mode={$mode}).\n";
    if ($mode === 'strict') {
        echo "Fix: locality facts, unique FAQ/body per area; differentiate same-city blog guides.\n";
        echo "Cross-city brand/service template overlap is ignored in strict mode (use SIMILARITY_MODE=all to audit).\n";
    } else {
        echo "Use SIMILARITY_MODE=strict for cannibalization-only checks.\n";
    }
} else {
    echo "\nNo comparable pairs above threshold (mode={$mode}).\n";
}
exit($fail ? 1 : 0);
