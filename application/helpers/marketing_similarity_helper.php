<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rules for which marketing pages must have distinct intro+body (SEO similarity audits).
 *
 * Full pairwise checks (same page_type only) always flag cross-city clones (brand × city, etc.).
 * Use marketing_similarity_should_compare() for a one-shot audit that matches real cannibalization risk.
 */

if (!function_exists('marketing_similarity_blog_city_slug')) {
    /**
     * @param string $path
     * @param array  $page
     */
    function marketing_similarity_blog_city_slug($path, array $page)
    {
        if (!empty($page['city_slug'])) {
            return (string) $page['city_slug'];
        }
        $path = trim((string) $path, '/');
        if (preg_match('#(?:^|/)(pune|indore|nashik)(?:/|$|-)#', $path, $m)) {
            return $m[1];
        }
        if (strpos($path, 'blog/car-service-cost-') === 0) {
            return str_replace('blog/car-service-cost-', '', $path);
        }
        if (strpos($path, 'blog/car-denting-painting-cost-') === 0) {
            return str_replace('blog/car-denting-painting-cost-', '', $path);
        }
        return 'pune';
    }
}

if (!function_exists('marketing_similarity_should_compare')) {
    /**
     * Whether two pages should read as materially different (compete for the same intent if too similar).
     *
     * @param string $path_a
     * @param array  $page_a
     * @param string $path_b
     * @param array  $page_b
     */
    function marketing_similarity_should_compare($path_a, array $page_a, $path_b, array $page_b)
    {
        $path_a = trim((string) $path_a, '/');
        $path_b = trim((string) $path_b, '/');
        if ($path_a === $path_b) {
            return FALSE;
        }

        $type_a = isset($page_a['page_type']) ? $page_a['page_type'] : '';
        $type_b = isset($page_b['page_type']) ? $page_b['page_type'] : '';
        if ($type_a === '' || $type_a !== $type_b) {
            return FALSE;
        }

        switch ($type_a) {
            case 'locality':
                $city_a = isset($page_a['city_slug']) ? $page_a['city_slug'] : '';
                $city_b = isset($page_b['city_slug']) ? $page_b['city_slug'] : '';
                return $city_a !== '' && $city_a === $city_b;

            case 'service':
                // One URL per service_key per city — no same-intent pair. Cross-service template overlap is OK.
                return FALSE;

            case 'brand':
                // Pune vs Indore Maruti pages are different queries; shared template is OK.
                return FALSE;

            case 'blog':
                // Each post targets a different keyword (Swift cost vs AC cost vs denting) — not locality-style cannibalization.
                return FALSE;

            case 'hub':
                return FALSE;

            default:
                return FALSE;
        }
    }
}

if (!function_exists('marketing_similarity_page_text')) {
    /** @return string */
    function marketing_similarity_page_text(array $page)
    {
        $parts = array();
        if (!empty($page['quick_answer'])) {
            $parts[] = $page['quick_answer'];
        }
        if (!empty($page['intro'])) {
            $parts[] = $page['intro'];
        }
        if (!empty($page['body'])) {
            $parts[] = $page['body'];
        }
        if (!empty($page['faq']) && is_array($page['faq'])) {
            foreach ($page['faq'] as $row) {
                if (is_array($row)) {
                    $parts[] = (isset($row['q']) ? $row['q'] : '').' '.(isset($row['a']) ? $row['a'] : '');
                }
            }
        }
        return trim(implode(' ', $parts));
    }
}

if (!function_exists('marketing_similarity_shingles5')) {
    /** @return array<string,bool> */
    function marketing_similarity_shingles5($text)
    {
        $text = strtolower(strip_tags((string) $text));
        $text = preg_replace('/\s+/u', ' ', $text);
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $set = array();
        $n = is_array($words) ? count($words) : 0;
        for ($i = 0; $i + 5 <= $n; $i++) {
            $set[implode(' ', array_slice($words, $i, 5))] = TRUE;
        }
        return $set;
    }
}

if (!function_exists('marketing_similarity_jaccard')) {
    function marketing_similarity_jaccard(array $a, array $b)
    {
        if ($a === array() && $b === array()) {
            return 0.0;
        }
        $inter = count(array_intersect_key($a, $b));
        $union = count($a + $b);
        return $union ? $inter / $union : 0.0;
    }
}
