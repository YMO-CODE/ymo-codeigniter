<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Legacy WordPress URL retirement — HTTP 410 Gone for paths with no modern equivalent.
 * Sitemap/canonical pages are never marked gone (protected set).
 */

if (!function_exists('marketing_sitemap_protected_paths')) {
    /**
     * Paths that must never receive 410, redirect changes, or noindex from legacy logic.
     *
     * @return array<string,bool> normalized path => true
     */
    function marketing_sitemap_protected_paths()
    {
        static $paths = NULL;
        if ($paths !== NULL) {
            return $paths;
        }
        $paths = array(
            ''            => TRUE,
            'contact-us'  => TRUE,
            'sitemap.xml' => TRUE,
            'robots.txt'  => TRUE,
            'llms.txt'    => TRUE,
        );
        if (function_exists('marketing_sitemap_pages')) {
            foreach (array_keys(marketing_sitemap_pages()) as $path) {
                $norm = marketing_normalize_path($path);
                if ($norm !== '') {
                    $paths[$norm] = TRUE;
                }
            }
        }
        return $paths;
    }
}

if (!function_exists('marketing_legacy_gone_prefixes')) {
    /** @return array<int,string> */
    function marketing_legacy_gone_prefixes()
    {
        return array(
            'feed',
            'feeds',
            'comments/feed',
            'author/',
            'wp-admin',
            'wp-includes',
            'wp-content/',
            'wp-json/',
            'xmlrpc.php',
            'trackback',
            'trackback/',
            'embed/',
            'attachment/',
            'cgi-bin/',
        );
    }
}

if (!function_exists('marketing_legacy_query_should_gone')) {
    /** WordPress legacy query parameters (?p=, ?replytocom=, ?attachment_id=). */
    function marketing_legacy_query_should_gone()
    {
        if (!empty($_GET['p']) || isset($_GET['replytocom']) || !empty($_GET['attachment_id'])) {
            return TRUE;
        }
        return FALSE;
    }
}

if (!function_exists('marketing_should_respond_gone')) {
    /**
     * True when a non-sitemap path should return HTTP 410 (no modern equivalent).
     * Exact 301 rules must be checked by the caller before this runs.
     *
     * @param string $path normalized URI without leading slash
     */
    function marketing_should_respond_gone($path)
    {
        $path = marketing_normalize_path($path);
        if ($path === '') {
            return FALSE;
        }

        $protected = marketing_sitemap_protected_paths();
        if (isset($protected[$path])) {
            return FALSE;
        }

        foreach (marketing_legacy_gone_prefixes() as $prefix) {
            $prefix = marketing_normalize_path($prefix);
            if ($prefix === $path || ($prefix !== '' && strpos($path, $prefix) === 0)) {
                return TRUE;
            }
        }

        if (preg_match('#/feed/?$#', $path)) {
            return TRUE;
        }

        if (preg_match('#(?:^|/)page/\d+/?$#', $path)) {
            return TRUE;
        }

        // WordPress date archives (year/month/day); sitemap blog posts are protected above.
        if (preg_match('#^\d{4}(/|$)#', $path)) {
            return TRUE;
        }

        // Taxonomy / gallery / team archives without an explicit 301 in redirect config.
        foreach (array('tag/', 'category/', 'galleries/', 'team/') as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return TRUE;
            }
        }

        // Nested WordPress hierarchy paths without an explicit 301.
        foreach (array(
            'best-car-servicing-in-pune-ymo/',
            'ymo-car-servicing-locations-in-pune/',
        ) as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return TRUE;
            }
        }

        return FALSE;
    }
}

if (!function_exists('marketing_respond_gone')) {
    /** Emit HTTP 410 Gone with minimal HTML (legacy WP URLs permanently removed). */
    function marketing_respond_gone()
    {
        $ci = &get_instance();
        $body = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="robots" content="noindex">'
            .'<title>410 Gone</title></head><body>'
            .'<p>This page has been permanently removed.</p>'
            .'</body></html>';
        $ci->output
            ->set_status_header(410)
            ->set_header('X-Robots-Tag: noindex')
            ->set_content_type('text/html', 'utf-8')
            ->set_output($body)
            ->_display();
        exit;
    }
}
