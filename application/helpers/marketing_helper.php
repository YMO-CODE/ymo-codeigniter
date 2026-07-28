<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('ymo_booking_url')) {
    /**
     * Absolute URL on the booking subdomain (never the marketing host).
     *
     * @param string $path e.g. packages, signup
     * @return string
     */
    function ymo_booking_url($path = '')
    {
        $base = getenv('YMO_PUBLIC_APP_URL');
        if ($base === FALSE || trim((string) $base) === '') {
            $base = getenv('YMO_APP_URL');
        }
        if ($base === FALSE || trim((string) $base) === '') {
            return site_url(ltrim($path, '/'));
        }
        $base = ymo_sanitize_external_app_url($base);
        $path = ltrim((string) $path, '/');
        return $path === '' ? $base.'/' : $base.'/'.$path;
    }
}

if (!function_exists('ymo_marketing_url')) {
    /**
     * Absolute URL on the marketing host (www).
     *
     * @param string $path e.g. services, about-us
     * @return string
     */
    function ymo_marketing_url($path = '')
    {
        $base = getenv('YMO_MARKETING_APP_URL');
        if ($base === FALSE || trim((string) $base) === '') {
            $ci = &get_instance();
            $base = $ci->config->item('ymo_marketing_url');
        }
        if ($base === FALSE || trim((string) $base) === '') {
            return site_url(ltrim($path, '/'));
        }
        $base = ymo_sanitize_external_app_url($base);
        $path = ltrim((string) $path, '/');
        return $path === '' ? $base.'/' : $base.'/'.$path;
    }
}

if (!function_exists('ymo_public_nav_url')) {
    /**
     * Marketing-site nav link - site_url on www host, absolute on booking host.
     *
     * @param string $path
     * @return string
     */
    function ymo_public_nav_url($path = '')
    {
        if (function_exists('ymo_is_marketing_host_request') && ymo_is_marketing_host_request()) {
            return site_url(ltrim($path, '/'));
        }
        return ymo_marketing_url($path);
    }
}

if (!function_exists('ymo_show_booking_nav')) {
    /**
     * Booking-app nav (Packages, My Bookings) only when signed in on the booking host.
     *
     * @return bool
     */
    function ymo_show_booking_nav()
    {
        $ci = &get_instance();
        if (empty($ci->session->userdata('user'))) {
            return FALSE;
        }
        if (function_exists('ymo_is_marketing_host_request') && ymo_is_marketing_host_request()) {
            return FALSE;
        }
        if (function_exists('ymo_is_admin_host_request') && ymo_is_admin_host_request()) {
            return FALSE;
        }
        return TRUE;
    }
}

if (!function_exists('marketing_canonical_url')) {
    /** @param string $path Path without leading slash */
    function marketing_canonical_url($path = '')
    {
        $ci = &get_instance();
        $base = rtrim($ci->config->item('base_url'), '/');
        $path = ltrim((string) $path, '/');
        return $path === '' ? $base.'/' : $base.'/'.$path;
    }
}

if (!function_exists('marketing_pages_data')) {
    /** @return array<string,array> */
    function marketing_pages_data()
    {
        static $pages = NULL;
        if ($pages !== NULL) {
            return $pages;
        }
        $file = APPPATH.'config/marketing_pages_data.php';
        $pages = is_file($file) ? require $file : array();
        return is_array($pages) ? $pages : array();
    }
}

if (!function_exists('marketing_redirect_rules')) {
    /** @return array{exact:array,prefix:array} */
    function marketing_redirect_rules()
    {
        static $rules = NULL;
        if ($rules !== NULL) {
            return $rules;
        }
        $file = APPPATH.'config/marketing_redirects.php';
        if (!is_file($file)) {
            $rules = array('exact' => array(), 'prefix' => array());
            return $rules;
        }
        include $file;
        $rules = array(
            'exact'  => isset($config['marketing_redirects_exact']) ? $config['marketing_redirects_exact'] : array(),
            'prefix' => isset($config['marketing_redirect_prefixes']) ? $config['marketing_redirect_prefixes'] : array(),
        );
        return $rules;
    }
}

if (!function_exists('marketing_normalize_path')) {
    /** @param string $path */
    function marketing_normalize_path($path)
    {
        $path = strtolower(trim((string) $path, '/'));
        $path = rawurldecode($path);
        return $path;
    }
}

if (!function_exists('marketing_ascii_slug')) {
    /**
     * Strip ₹ and other non-ASCII URI noise so CI3 can route the slug.
     *
     * @param string $path
     * @return string
     */
    function marketing_ascii_slug($path)
    {
        $path = marketing_normalize_path($path);
        $path = preg_replace('/\x{20B9}/u', '', $path);
        $path = str_replace(array('&#8377;', '&#x20b9;'), '', $path);
        return $path;
    }
}

if (!function_exists('marketing_resolve_page_path')) {
    /**
     * Resolve a request path to a marketing_pages_data config key.
     *
     * @param string $path
     * @return array{key:string, canonical:string, redirect:bool}
     */
    function marketing_resolve_page_path($path)
    {
        $path = marketing_normalize_path($path);
        $ascii = marketing_ascii_slug($path);
        $pages = marketing_pages_data();

        if (isset($pages[$path])) {
            return array(
                'key'       => $path,
                'canonical' => ($ascii !== $path && isset($pages[$ascii])) ? $ascii : $path,
                'redirect'  => ($ascii !== $path && isset($pages[$ascii])),
            );
        }
        if (isset($pages[$ascii])) {
            return array(
                'key'       => $ascii,
                'canonical' => $ascii,
                'redirect'  => ($path !== $ascii),
            );
        }
        return array(
            'key'       => '',
            'canonical' => $ascii,
            'redirect'  => FALSE,
        );
    }
}

if (!function_exists('marketing_lookup_redirect')) {
    /**
     * Find a 301 target for a legacy WordPress path.
     *
     * @param string $path URI without leading slash
     * @return string|null Absolute or site-relative target path (no domain)
     */
    function marketing_lookup_redirect($path)
    {
        $path = marketing_normalize_path($path);
        if ($path === '') {
            return NULL;
        }
        $rules = marketing_redirect_rules();
        if (isset($rules['exact'][$path])) {
            $target = marketing_normalize_path($rules['exact'][$path]);
            if ($target !== '' && $target !== $path) {
                return $rules['exact'][$path];
            }
            return NULL;
        }
        $with_slash = $path.'/';
        if (isset($rules['exact'][$with_slash])) {
            $target = marketing_normalize_path($rules['exact'][$with_slash]);
            if ($target !== '' && $target !== $path) {
                return $rules['exact'][$with_slash];
            }
            return NULL;
        }
        foreach ($rules['prefix'] as $prefix => $target) {
            $prefix = marketing_normalize_path($prefix);
            if ($prefix !== '' && strpos($path, $prefix) === 0) {
                return $target;
            }
        }
        return NULL;
    }
}

if (!function_exists('marketing_redirect_to')) {
    /** @param string $target Path or absolute URL */
    function marketing_redirect_to($target, $code = 301)
    {
        if (strpos($target, 'http://') === 0 || strpos($target, 'https://') === 0) {
            redirect($target, 'location', $code);
        }
        redirect(site_url(ltrim($target, '/')), 'location', $code);
    }
}

if (!function_exists('ymo_marketing_sliders_config')) {
    /** @return array<string, array{interval_ms:int, slides:array}> */
    function ymo_marketing_sliders_config()
    {
        static $sliders = NULL;
        if ($sliders !== NULL) {
            return $sliders;
        }
        $file = APPPATH.'config/marketing_sliders.php';
        $sliders = is_file($file) ? require $file : array();
        return is_array($sliders) ? $sliders : array();
    }
}

if (!function_exists('ymo_marketing_slider')) {
    /**
     * Resolve a RevSlider alias to slide data with local asset URLs.
     *
     * @param string $alias
     * @return array{interval_ms:int, slides:array}|null
     */
    function ymo_marketing_slider($alias)
    {
        $alias = trim((string) $alias);
        if ($alias === '') {
            return NULL;
        }
        $all = ymo_marketing_sliders_config();
        if (!isset($all[$alias]) || !is_array($all[$alias])) {
            return NULL;
        }
        $cfg = $all[$alias];
        $slides = isset($cfg['slides']) && is_array($cfg['slides']) ? $cfg['slides'] : array();
        $resolved = array();
        foreach ($slides as $slide) {
            if (!is_array($slide) || empty($slide['src'])) {
                continue;
            }
            $src = (string) $slide['src'];
            if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
                // absolute URL - keep as-is
            } elseif (strpos($src, 'assets/img/marketing/') === 0) {
                // already resolved
            } else {
                $src = 'assets/img/marketing/'.ltrim($src, '/');
            }
            $resolved[] = array(
                'src' => $src,
                'alt' => isset($slide['alt']) ? (string) $slide['alt'] : '',
            );
        }
        if ($resolved === array()) {
            return NULL;
        }
        return array(
            'interval_ms' => isset($cfg['interval_ms']) ? (int) $cfg['interval_ms'] : 6000,
            'slides'      => $resolved,
        );
    }
}

if (!function_exists('ymo_marketing_render_slider')) {
    /**
     * Render hero carousel partial.
     *
     * @param string $alias RevSlider alias or empty for raw slides array
     * @param array $options variant, slider_id, overlay, slides, interval_ms
     * @return string HTML
     */
    function ymo_marketing_render_slider($alias, array $options = array())
    {
        $ci = &get_instance();
        $cfg = NULL;
        if ($alias !== '') {
            $cfg = ymo_marketing_slider($alias);
        }
        if ($cfg === NULL && !empty($options['slides'])) {
            $cfg = array(
                'interval_ms' => isset($options['interval_ms']) ? (int) $options['interval_ms'] : 6000,
                'slides'      => $options['slides'],
            );
        }
        if ($cfg === NULL) {
            return '';
        }
        $data = array(
            'slides'      => $cfg['slides'],
            'interval_ms' => isset($options['interval_ms']) ? (int) $options['interval_ms'] : $cfg['interval_ms'],
            'slider_id'   => isset($options['slider_id']) ? (string) $options['slider_id'] : 'ymo-slider-'.preg_replace('/[^a-z0-9_-]/i', '-', $alias),
            'variant'     => isset($options['variant']) ? (string) $options['variant'] : 'page',
            'overlay'     => isset($options['overlay']) ? $options['overlay'] : NULL,
        );
        return $ci->load->view('marketing/partials/hero_slider', $data, TRUE);
    }
}

if (!function_exists('ymo_marketing_split_body_hero')) {
    /**
     * Pull the first WP full-width hero image row out of migrated body HTML.
     *
     * @return array{hero:array{src:string,alt:string}|null, body:string}
     */
    function ymo_marketing_split_body_hero($body)
    {
        $body = (string) $body;
        if ($body === '') {
            return array('hero' => NULL, 'body' => '');
        }
        $pattern = '/<div data-vc-full-width="true"[^>]*class="[^"]*full-width[^"]*"[^>]*>.*?<img\b[^>]*\bsrc="([^"]+)"[^>]*(?:\balt="([^"]*)")?[^>]*>.*?<\/div>\s*<div class="vc_row-full-width vc_clearfix"><\/div>/is';
        if (!preg_match($pattern, $body, $m, PREG_OFFSET_CAPTURE)) {
            return array('hero' => NULL, 'body' => $body);
        }
        $full = $m[0][0];
        $src  = html_entity_decode($m[1][0], ENT_QUOTES, 'UTF-8');
        $alt  = isset($m[2]) ? html_entity_decode($m[2][0], ENT_QUOTES, 'UTF-8') : '';
        if (preg_match('#/assets/img/marketing/([^?\"]+)#', $src, $rel)) {
            $src = $rel[1];
        } elseif (strpos($src, '/assets/img/marketing/') === FALSE) {
            return array('hero' => NULL, 'body' => $body);
        }
        $rest = substr($body, 0, $m[0][1]).substr($body, $m[0][1] + strlen($full));
        return array(
            'hero' => array('src' => $src, 'alt' => $alt),
            'body' => ltrim($rest),
        );
    }
}

if (!function_exists('marketing_normalize_service_body')) {
    /**
     * Flatten legacy WP service page markup (nested Bootstrap cols, duplicate overview).
     *
     * @param string $body
     * @return string
     */
    function marketing_normalize_service_body($body)
    {
        $body = trim((string) $body);
        if ($body === '') {
            return $body;
        }

        $body = preg_replace('/<h3[^>]*>\s*SERVICE OVERVIEW\s*<\/h3>/iu', '', $body, 1);
        $body = preg_replace(
            '/<header class="section-heading[^"]*"[^>]*>\s*<p[^>]*>.*?<\/p>\s*<\/header>/is',
            '',
            $body,
            1
        );

        if (preg_match(
            '/<div class="service-blocks[^"]*"[^>]*>[\s\S]*?(<ul>[\s\S]*?<\/ul>)[\s\S]*?(?=<div class="wpb_text_column)/i',
            $body,
            $matches
        )) {
            $list = preg_replace('/<ul>/', '<ul class="list iconList">', $matches[1], 1);
            $body = preg_replace(
                '/<div class="service-blocks[^"]*"[^>]*>[\s\S]*?(?=<div class="wpb_text_column)/i',
                $list,
                $body,
                1
            );
        }

        $body = marketing_normalize_feature_item_body($body);
        $body = marketing_normalize_service_why_faq($body);
        $body = marketing_simplify_accordion_markup($body);
        $body = marketing_strip_wp_service_chrome($body);
        $body = marketing_unwrap_wpb_text_columns($body);
        $body = marketing_remove_orphan_divs_before_section($body);
        $body = marketing_wrap_leading_overview($body);

        return trim($body);
    }
}

if (!function_exists('marketing_normalize_accordion_answer')) {
    /** @return string HTML fragment for accordion answer body */
    function marketing_normalize_accordion_answer($html)
    {
        $html = html_entity_decode((string) $html, ENT_QUOTES, 'UTF-8');
        $html = preg_replace('/<br\s*\/?>/i', '<br />', $html);

        $paragraphs = array();
        if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $matches)) {
            foreach ($matches[1] as $chunk) {
                $paragraphs[] = $chunk;
            }
        } else {
            $paragraphs[] = $html;
        }

        $lines = array();
        foreach ($paragraphs as $chunk) {
            $chunk = strip_tags($chunk, '<br>');
            foreach (preg_split('/<br\s*\/?>/i', $chunk) as $line) {
                $line = trim(strip_tags($line));
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        if ($lines === array()) {
            return '';
        }

        if (count($lines) === 1) {
            return '<p>'.htmlspecialchars($lines[0], ENT_QUOTES, 'UTF-8').'</p>';
        }

        $out = '<ul class="list iconList mb-0">';
        foreach ($lines as $line) {
            $out .= '<li>'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8').'</li>';
        }

        return $out.'</ul>';
    }
}

if (!function_exists('marketing_extract_template_bullets')) {
    /** @return array<int, string> */
    function marketing_extract_template_bullets($html)
    {
        $items = array();
        if (!preg_match_all('/<li class="template-bullet[^"]*"[^>]*>\s*<span>(.*?)<\/span>\s*<\/li>/is', (string) $html, $matches)) {
            return $items;
        }
        foreach ($matches[1] as $text) {
            $line = trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
            if ($line !== '') {
                $items[] = $line;
            }
        }
        return $items;
    }
}

if (!function_exists('marketing_extract_accordion_items')) {
    /** @return array<int, array{q:string,a:string}> */
    function marketing_extract_accordion_items($html)
    {
        $items = array();
        if (!preg_match_all(
            '/<li>\s*(?:<div[^>]*>\s*)?<h3>(.*?)<\/h3>\s*(?:<\/div>\s*)?<div class="clearfix">[\s\S]*?<p[^>]*>(.*?)<\/p>/is',
            (string) $html,
            $matches,
            PREG_SET_ORDER
        )) {
            return $items;
        }
        foreach ($matches as $row) {
            $q = trim(html_entity_decode(strip_tags($row[1]), ENT_QUOTES, 'UTF-8'));
            $a = marketing_normalize_accordion_answer($row[2]);
            if ($q !== '' && $a !== '') {
                $items[] = array('q' => $q, 'a' => $a);
            }
        }
        return $items;
    }
}

if (!function_exists('marketing_render_accordion_html')) {
    /** @param array<int, array{q:string,a:string}> $items */
    function marketing_render_accordion_html(array $items)
    {
        if ($items === array()) {
            return '';
        }

        $html = '<ul class="accordion">';
        foreach ($items as $item) {
            $html .= '<li><div><h3>'.htmlspecialchars($item['q'], ENT_QUOTES, 'UTF-8').'</h3></div>';
            $html .= '<div class="clearfix">'.$item['a'].'</div></li>';
        }
        return $html.'</ul>';
    }
}

if (!function_exists('marketing_render_template_bullet_list')) {
    /** @param array<int, string> $items */
    function marketing_render_template_bullet_list(array $items)
    {
        if ($items === array()) {
            return '';
        }

        $html = '<ul class="list margin-top-20">';
        foreach ($items as $item) {
            $html .= '<li class="template-bullet"><span>'.htmlspecialchars($item, ENT_QUOTES, 'UTF-8').'</span></li>';
        }
        return $html.'</ul>';
    }
}

if (!function_exists('marketing_build_why_faq_section')) {
    /** @param array<int, string> $bullets @param array<int, array{q:string,a:string}> $faqs */
    function marketing_build_why_faq_section($intro, array $bullets, array $faqs)
    {
        $html = '<div class="ymo-content-section"><div class="row g-4 g-lg-5">';
        $html .= '<div class="col-lg-6"><h2 class="md-headline-md mb-3">Why choose YMO</h2>';
        if ($intro !== '') {
            $html .= '<p class="md-body-md mb-3">'.htmlspecialchars($intro, ENT_QUOTES, 'UTF-8').'</p>';
        }
        $html .= marketing_render_template_bullet_list($bullets);
        $html .= '</div><div class="col-lg-6"><h2 class="md-headline-md mb-3">Popular questions</h2>';
        $html .= marketing_render_accordion_html($faqs);
        $html .= '</div></div></div>';

        return $html;
    }
}

if (!function_exists('marketing_normalize_service_why_faq')) {
    /** @return string */
    function marketing_normalize_service_why_faq($body)
    {
        $body = (string) $body;
        if (stripos($body, 'WHY CHOOSE US') === FALSE || stripos($body, 'POPULAR QUESTIONS') === FALSE) {
            return $body;
        }

        if (!preg_match(
            '/<h4[^>]*>\s*WHY CHOOSE US\s*\??\s*<\/h4>(.*?)<h4[^>]*>\s*POPULAR QUESTIONS\s*<\/h4>\s*(.*?<ul class="accordion[^>]*>[\s\S]*?<\/ul>)/is',
            $body,
            $matches
        )) {
            return $body;
        }

        $intro = '';
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $matches[1], $paragraph)) {
            $intro = trim(html_entity_decode(strip_tags($paragraph[1]), ENT_QUOTES, 'UTF-8'));
        }

        $section = marketing_build_why_faq_section(
            $intro,
            marketing_extract_template_bullets($matches[1]),
            marketing_extract_accordion_items($matches[2])
        );

        return preg_replace(
            '/(?:<div class="vc_row wpb_row vc_row-fluid page-margin-top">\s*)?(?:<div class="wpb_column vc_column_container vc_col-sm-6">\s*<div class="wpb_wrapper">\s*)?<h4[^>]*>\s*WHY CHOOSE US\s*\??\s*<\/h4>.*?<h4[^>]*>\s*POPULAR QUESTIONS\s*<\/h4>\s*.*?<ul class="accordion[^>]*>[\s\S]*?<\/ul>\s*(?:<\/div>\s*<\/div>\s*)?(?:<div class="wpb_column vc_column_container vc_col-sm-6">\s*<div class="wpb_wrapper">\s*)?(?:<\/div>\s*<\/div>\s*)?(?:<\/div>\s*)?/is',
            $section,
            $body,
            1
        );
    }
}

if (!function_exists('marketing_simplify_accordion_markup')) {
    /** @return string */
    function marketing_simplify_accordion_markup($body)
    {
        $body = preg_replace(
            '/<div class="clearfix">\s*<div class="wpb_text_column[^"]*"[^>]*>\s*<div class="wpb_wrapper">\s*(<p>.*?<\/p>)\s*<\/div>\s*<\/div>\s*<\/div>/is',
            '<div class="clearfix">$1</div>',
            (string) $body
        );

        return preg_replace(
            '/<li>\s*<div[^>]*>\s*(<h3>.*?<\/h3>)\s*<\/div>/is',
            '<li><div>$1</div>',
            (string) $body
        );
    }
}

if (!function_exists('marketing_strip_wp_service_chrome')) {
    /** @return string */
    function marketing_strip_wp_service_chrome($body)
    {
        $body = trim((string) $body);

        $body = preg_replace(
            '/<div data-vc-full-width="true"[\s\S]*?<div class="vc_row-full-width vc_clearfix"><\/div>/i',
            '',
            $body,
            1
        );

        $body = preg_replace('/<div class="wpb_text_column[^"]*"[^>]*>\s*<div class="wpb_wrapper">\s*<\/div>\s*<\/div>/is', '', $body);
        $body = preg_replace('/<div class="vc_row wpb_row vc_row-fluid page-margin-top">\s*<div class="wpb_column vc_column_container vc_col-sm-12">\s*<div class="wpb_wrapper">\s*/i', '', $body, 1);
        $body = preg_replace(
            '/<div class="vc_row wpb_row vc_row-fluid page-margin-top">\s*<div class="wpb_column vc_column_container vc_col-sm-6">\s*<div class="wpb_wrapper">\s*(<div class="ymo-content-section">[\s\S]*?<\/div>)\s*<\/div>\s*<\/div>\s*<\/div>/i',
            '$1',
            $body,
            1
        );
        $body = preg_replace('/<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*$/', '', $body, 1);

        return trim($body);
    }
}

if (!function_exists('marketing_unwrap_wpb_text_columns')) {
    /** @return string */
    function marketing_unwrap_wpb_text_columns($body)
    {
        return preg_replace(
            '/<div class="wpb_text_column[^"]*"[^>]*>\s*<div class="wpb_wrapper">\s*(.*?)\s*<\/div>\s*<\/div>/is',
            '$1',
            (string) $body
        );
    }
}

if (!function_exists('marketing_remove_orphan_divs_before_section')) {
    /** @return string */
    function marketing_remove_orphan_divs_before_section($body)
    {
        if (stripos($body, 'wpb_text_column') === FALSE
            && stripos($body, 'vc_row') === FALSE
            && stripos($body, 'wpb_column') === FALSE) {
            return (string) $body;
        }

        return preg_replace(
            '/(?:<\/div>\s*){1,8}(?=<div class="ymo-content-section")/i',
            '',
            (string) $body,
            1
        );
    }
}

if (!function_exists('marketing_wrap_leading_overview')) {
    /** @return string */
    function marketing_wrap_leading_overview($body)
    {
        $body = trim((string) $body);
        if ($body === '') {
            return $body;
        }

        if (!preg_match('/<div class="ymo-content-section/i', $body)) {
            return '<div class="ymo-content-section mb-5">'.$body.'</div>';
        }

        if (preg_match('/^<div class="ymo-content-section/i', $body)) {
            return $body;
        }

        if (!preg_match('/^(.*?)(<div class="ymo-content-section)/is', $body, $matches)) {
            return $body;
        }

        $leading = trim($matches[1]);
        if ($leading === '') {
            return $body;
        }

        return '<div class="ymo-content-section mb-5">'.$leading.'</div>'.substr($body, strlen($matches[1]));
    }
}

if (!function_exists('marketing_cities_config')) {
    /** @return array */
    function marketing_cities_config()
    {
        static $cities = NULL;
        if ($cities !== NULL) {
            return $cities;
        }
        $file = APPPATH.'config/marketing_cities.php';
        $cities = is_file($file) ? require $file : array();
        return is_array($cities) ? $cities : array();
    }
}

if (!function_exists('marketing_city_by_slug')) {
    /** @param string $slug */
    function marketing_city_by_slug($slug)
    {
        $all = marketing_cities_config();
        return isset($all[$slug]) ? $all[$slug] : NULL;
    }
}

if (!function_exists('marketing_service_by_key')) {
    /** @param string $key @return array|null */
    function marketing_service_by_key($key)
    {
        $key = trim((string) $key);
        if ($key === '') {
            return NULL;
        }
        $cfg = marketing_cities_config();
        if (!isset($cfg['services']) || !is_array($cfg['services'])) {
            return NULL;
        }
        foreach ($cfg['services'] as $svc) {
            if (isset($svc['key']) && $svc['key'] === $key) {
                return $svc;
            }
        }
        return NULL;
    }
}

if (!function_exists('marketing_hero_image_url')) {
    /** @param string $src @return string */
    function marketing_hero_image_url($src)
    {
        $src = trim((string) $src);
        if ($src === '') {
            return '';
        }
        if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
            return $src;
        }
        if (strpos($src, '/assets/') === 0) {
            return base_url(ltrim($src, '/'));
        }
        if (strpos($src, 'assets/') === 0) {
            return base_url($src);
        }
        return base_url('assets/img/marketing/'.ltrim($src, '/'));
    }
}

if (!function_exists('marketing_hero_parse_price')) {
    /** @return int|null */
    function marketing_hero_parse_price(array $page)
    {
        if (isset($page['service_key']) && $page['service_key'] !== '') {
            $svc = marketing_service_by_key($page['service_key']);
            if ($svc && isset($svc['price_from']) && $svc['price_from'] !== NULL) {
                return (int) $svc['price_from'];
            }
        }
        foreach (array('title', 'h1', 'intro', 'body') as $field) {
            if (empty($page[$field])) {
                continue;
            }
            if (preg_match('/₹\s*(\d[\d,]*)/u', (string) $page[$field], $m)) {
                return (int) str_replace(',', '', $m[1]);
            }
        }
        return NULL;
    }
}

if (!function_exists('marketing_hero_resolve_image')) {
    /**
     * @param string $path
     * @param array  $page
     * @param string $h1
     * @param string $brand
     * @return array{src:string,alt:string}|null
     */
    function marketing_hero_resolve_image($path, array $page, $h1, $brand)
    {
        $alt = trim($h1).' - '.$brand;
        $src = '';

        if (!empty($page['og_image'])) {
            $src = (string) $page['og_image'];
        } elseif (!empty($page['service_key'])) {
            $svc = marketing_service_by_key($page['service_key']);
            if ($svc && !empty($svc['og_image'])) {
                $src = (string) $svc['og_image'];
            }
        }

        if ($src === '' && !empty($page['city_slug'])) {
            $city = marketing_city_by_slug($page['city_slug']);
            if ($city && !empty($city['hero_image'])) {
                $src = (string) $city['hero_image'];
            }
        }

        if ($src === '' && strpos((string) $path, 'locations/') === 0) {
            $parts = explode('/', trim($path, '/'));
            if (isset($parts[1])) {
                $city = marketing_city_by_slug($parts[1]);
                if ($city && !empty($city['hero_image'])) {
                    $src = (string) $city['hero_image'];
                }
            }
        }

        if ($src === '') {
            $src = 'revslider/main/image_01.jpg';
        }

        $url = marketing_hero_image_url($src);
        if ($url === '') {
            return NULL;
        }
        return array('src' => $url, 'alt' => $alt);
    }
}

if (!function_exists('marketing_trust_config')) {
    /** @return array */
    function marketing_trust_config()
    {
        static $trust = NULL;
        if ($trust !== NULL) {
            return $trust;
        }
        $file = APPPATH.'config/marketing_trust.php';
        $trust = is_file($file) ? require $file : array();
        return is_array($trust) ? $trust : array();
    }
}

if (!function_exists('marketing_hero_sanitize_lead')) {
    /**
     * @param string $lead
     * @param int    $max_length Max chars before ellipsis; 0 = no truncation (curated quick_answer).
     */
    function marketing_hero_sanitize_lead($lead, $max_length = 120)
    {
        $lead = trim(strip_tags((string) $lead));
        if ($lead === '') {
            return '';
        }
        $lead = preg_replace('/^SERVICE OVERVIEW\s*/iu', '', $lead);
        $lead = preg_replace('/^MAKE AN APPOINTMENT NOW[^.]*\.?\s*/iu', '', $lead);
        $lead = preg_replace('/^Car Services Available In[^.]+\.\s*/iu', '', $lead);
        $lead = preg_replace('/^The Best Car Servicing In[^.]+\.\s*/iu', '', $lead);
        $lead = preg_replace('/^Choose from the different options for car servicing[^.]+\.\s*/iu', '', $lead);
        $lead = preg_replace('/\s+/u', ' ', $lead);
        $lead = trim($lead);
        if ($max_length > 0 && strlen($lead) > $max_length) {
            $lead = preg_replace('/\s+\S*$/u', '…', substr($lead, 0, $max_length - 3));
        }
        return $lead;
    }
}

if (!function_exists('marketing_hero_trust_proof')) {
    /**
     * @param string $variant light|dark
     * @return array
     */
    function marketing_hero_trust_proof($variant = 'light')
    {
        $cfg = marketing_trust_config();
        if (empty($cfg['show_in_hero'])) {
            return array('show' => FALSE);
        }
        $rating = isset($cfg['google_rating']) ? (float) $cfg['google_rating'] : 0;
        $reviews = isset($cfg['review_count']) ? (int) $cfg['review_count'] : 0;
        $years = isset($cfg['years']) ? (int) $cfg['years'] : 0;
        $cities = isset($cfg['cities']) ? (int) $cfg['cities'] : 0;
        $label = isset($cfg['review_label']) ? (string) $cfg['review_label'] : 'reviews';

        $proof = array(
            'show'    => TRUE,
            'variant' => $variant,
        );
        if ($rating > 0) {
            $proof['rating'] = number_format($rating, 1);
        }
        if ($reviews > 0) {
            $proof['review_count'] = number_format($reviews).'+ '.$label;
        }
        if ($years > 0) {
            $proof['years'] = $years.'+ years';
        }
        if ($cities > 0) {
            $proof['cities'] = $cities.' cities';
        }
        return $proof;
    }
}

if (!function_exists('marketing_hero_context')) {
    /**
     * Build typed hero data for marketing pages.
     *
     * @param string $path
     * @param array  $page
     * @param string $booking_url
     * @return array
     */
    function marketing_hero_context($path, array $page, $booking_url = '')
    {
        $ci    = &get_instance();
        $brand = $ci->config->item('ymo_brand_name');
        $path  = trim((string) $path, '/');
        $type  = isset($page['page_type']) ? (string) $page['page_type'] : '';

        if (in_array($path, array('about-us', 'privacy-policy'), TRUE)) {
            $hero_type = 'minimal';
        } elseif ($path === '' || $type === 'home') {
            $hero_type = 'home';
        } elseif ($type === 'service') {
            $hero_type = 'service';
        } elseif ($type === 'locality') {
            $hero_type = 'locality';
        } elseif ($type === 'hub' || $path === 'services' || strpos($path, 'locations/') === 0) {
            $hero_type = 'hub';
        } else {
            $hero_type = 'minimal';
        }

        $h1 = isset($page['h1']) ? trim((string) $page['h1']) : '';
        if ($h1 === '' && isset($page['title'])) {
            $h1 = trim((string) $page['title']);
        }

        $lead = '';
        $lead_max = 120;
        if (!empty($page['quick_answer'])) {
            $lead = trim((string) $page['quick_answer']);
            $lead_max = 0;
        } elseif (!empty($page['intro'])) {
            $lead = trim(strip_tags((string) $page['intro']));
        }
        $lead = marketing_hero_sanitize_lead($lead, $lead_max);

        $badges = array('Free pick-up');
        $price  = marketing_hero_parse_price($page);
        if ($price !== NULL) {
            $badges[] = 'From ₹'.number_format($price);
        }
        if ($hero_type === 'service' || $hero_type === 'hub') {
            $badges[] = 'Same-day service';
        }

        $city_slug = isset($page['city_slug']) ? (string) $page['city_slug'] : '';
        $city      = $city_slug !== '' ? marketing_city_by_slug($city_slug) : NULL;
        if ($city && $city_slug !== '' && $hero_type !== 'locality') {
            $badges[] = $city['name'];
        }

        $locality_label = '';
        if ($hero_type === 'locality' && $city && !empty($city['localities']) && is_array($city['localities'])) {
            foreach ($city['localities'] as $loc) {
                if (isset($loc['slug']) && $loc['slug'] === $path) {
                    $locality_label = $loc['label'].', '.$city['name'];
                    break;
                }
            }
        }
        if ($locality_label === '' && $hero_type === 'locality' && $city) {
            $locality_label = $city['name'];
        }

        $eyebrow = '';
        $eyebrow_icon = '';
        if ($hero_type === 'service') {
            if (!empty($page['hero_eyebrow'])) {
                $eyebrow = trim((string) $page['hero_eyebrow']);
                $eyebrow_icon = !empty($page['hero_eyebrow_icon']) ? (string) $page['hero_eyebrow_icon'] : 'build';
            } else {
                $service_label = 'Car servicing';
                if (!empty($page['service_key'])) {
                    $svc_meta = marketing_service_by_key($page['service_key']);
                    if ($svc_meta && !empty($svc_meta['title'])) {
                        $service_label = (string) $svc_meta['title'];
                    }
                } elseif ($h1 !== '') {
                    $service_label = preg_replace('/\s*[@|–-].*$/u', '', $h1);
                    $service_label = preg_replace('/\s+in\s+[A-Za-z]+$/u', '', $service_label);
                }
                $city_name = ($city && !empty($city['name'])) ? $city['name'] : 'Pune';
                $eyebrow = trim($service_label).' · '.$city_name;
                $eyebrow_icon = 'build';
            }
        } elseif ($hero_type === 'locality' && $locality_label !== '') {
            $eyebrow = $locality_label;
            $eyebrow_icon = 'location_on';
        } elseif ($hero_type === 'hub') {
            if ($city && !empty($city['name'])) {
                $eyebrow = 'Car services · '.$city['name'];
            } elseif ($path === 'services') {
                $eyebrow = 'Car services · Pune, Indore & Nashik';
            } else {
                $eyebrow = 'Car services';
            }
            $eyebrow_icon = 'directions_car';
        }

        $chips = array();
        $chips_label = '';
        $chips_after_cta = ($hero_type === 'hub');
        if ($hero_type === 'hub' && $path === 'services') {
            $chips_label = 'Our cities';
            foreach (array('pune', 'indore', 'nashik') as $slug) {
                $c = marketing_city_by_slug($slug);
                if (!$c) {
                    continue;
                }
                $chips[] = array(
                    'label' => $c['name'],
                    'href'  => ymo_public_nav_url($c['hub_path']),
                );
            }
        } elseif ($hero_type === 'hub' && $city) {
            $chips_label = 'Popular services';
            $cfg = marketing_cities_config();
            if (isset($cfg['services']) && is_array($cfg['services'])) {
                foreach (array_slice($cfg['services'], 0, 4) as $svc) {
                    $slug = str_replace('{city}', $city['slug'], $svc['city_slug']);
                    $chips[] = array(
                        'label' => $svc['title'],
                        'href'  => ymo_public_nav_url($slug),
                    );
                }
            }
        }

        $icon = 'build';
        if (!empty($page['service_key'])) {
            $svc = marketing_service_by_key($page['service_key']);
            if ($svc && !empty($svc['icon'])) {
                $icon = (string) $svc['icon'];
            }
        }

        $cta_primary = array(
            'href'  => $booking_url !== '' ? $booking_url : ymo_booking_url('packages'),
            'label' => 'Book now',
            'icon'  => 'event_available',
            'class' => 'md-btn md-btn--filled md-btn--lg',
        );

        $cta_secondary = NULL;
        if ($hero_type === 'service' && $city) {
            $cta_secondary = array(
                'href'  => ymo_public_nav_url($city['hub_path']),
                'label' => 'All services in '.$city['name'],
                'class' => 'md-btn md-btn--outlined md-btn--lg',
            );
        } elseif ($hero_type === 'home') {
            $cta_secondary = array(
                'href'  => ymo_public_nav_url('services'),
                'label' => 'View all services',
                'class' => 'md-btn md-btn--on-dark md-btn--lg',
            );
        } elseif ($hero_type === 'hub' && $path !== 'services') {
            $cta_secondary = array(
                'href'  => ymo_public_nav_url('services'),
                'label' => 'View all services',
                'class' => 'md-btn md-btn--outlined md-btn--lg',
            );
        } elseif ($hero_type === 'locality' && $city) {
            $cta_secondary = array(
                'href'  => ymo_public_nav_url($city['hub_path']),
                'label' => 'All services in '.$city['name'],
                'class' => 'md-btn md-btn--outlined md-btn--lg',
            );
        } elseif ($hero_type === 'minimal' && $path === 'about-us') {
            $cta_secondary = array(
                'href'  => ymo_public_nav_url('contact-us'),
                'label' => 'Contact us',
                'class' => 'md-btn md-btn--outlined md-btn--lg',
            );
        }

        $image = NULL;
        $slides = NULL;
        $slider_interval_ms = 6000;
        if ($hero_type !== 'minimal') {
            if (!empty($page['slider'])) {
                $slider_cfg = ymo_marketing_slider((string) $page['slider']);
                if ($slider_cfg && !empty($slider_cfg['slides'])) {
                    $slider_interval_ms = isset($slider_cfg['interval_ms'])
                        ? (int) $slider_cfg['interval_ms']
                        : 6000;
                    $slides = array();
                    foreach ($slider_cfg['slides'] as $slide) {
                        if (empty($slide['src'])) {
                            continue;
                        }
                        $src = (string) $slide['src'];
                        if (strpos($src, 'http://') !== 0 && strpos($src, 'https://') !== 0) {
                            $src = marketing_hero_image_url($src);
                        }
                        $alt = !empty($slide['alt']) ? (string) $slide['alt'] : trim($h1).' - '.$brand;
                        $slides[] = array('src' => $src, 'alt' => $alt);
                    }
                    if ($slides !== array()) {
                        $image = $slides[0];
                    }
                }
            }
            if ($image === NULL) {
                $image = marketing_hero_resolve_image($path, $page, $h1, $brand);
            }
        }

        $phone = (string) $ci->config->item('ymo_support_phone');
        $phone_href = 'tel:'.preg_replace('/[^+\d]/', '', $phone);

        $trust_variant = ($hero_type === 'home') ? 'dark' : 'light';
        $trust_proof = ($hero_type === 'minimal' && $path === 'privacy-policy')
            ? array('show' => FALSE)
            : marketing_hero_trust_proof($trust_variant);

        $show_phone = in_array($hero_type, array('service', 'hub', 'locality'), TRUE);
        $show_cta = ($hero_type !== 'minimal') || ($path === 'about-us');

        if ($hero_type === 'minimal' && $path === 'about-us') {
            $cta_primary = array(
                'href'  => $booking_url !== '' ? $booking_url : ymo_booking_url('packages'),
                'label' => 'Book now',
                'icon'  => 'event_available',
                'class' => 'md-btn md-btn--filled md-btn--lg',
            );
        } elseif ($hero_type === 'minimal') {
            $cta_primary = NULL;
        }

        return array(
            'type'                      => $hero_type,
            'h1'                        => $h1,
            'lead'                      => $lead,
            'eyebrow'                   => $eyebrow,
            'eyebrow_icon'              => $eyebrow_icon,
            'badges'                    => $badges,
            'chips'                     => $chips,
            'chips_label'               => $chips_label,
            'chips_after_cta'           => $chips_after_cta,
            'icon'                      => $icon,
            'image'                     => $image,
            'slides'                    => $slides,
            'slider_interval_ms'        => $slider_interval_ms,
            'slider_id'                 => !empty($page['slider'])
                ? 'ymo-slider-'.preg_replace('/[^a-z0-9_-]/i', '-', (string) $page['slider'])
                : '',
            'cta_primary'               => $cta_primary,
            'cta_secondary'             => $cta_secondary,
            'show_cta'                  => $show_cta,
            'lead_in_hero'              => ($lead !== ''),
            'locality_label'            => $locality_label,
            'phone'                     => $phone,
            'phone_href'                => $phone_href,
            'show_phone'                => $show_phone,
            'trust_proof'               => $trust_proof,
        );
    }
}

if (!function_exists('ymo_marketing_render_hero')) {
    /**
     * @param string $path
     * @param array  $page
     * @param string $booking_url
     * @return string HTML
     */
    function ymo_marketing_render_hero($path, array $page, $booking_url = '')
    {
        $ci   = &get_instance();
        $hero = marketing_hero_context($path, $page, $booking_url);
        if ($hero['h1'] === '') {
            return '';
        }
        return $ci->load->view('marketing/partials/hero', array('hero' => $hero), TRUE);
    }
}

if (!function_exists('marketing_page_priority')) {
    /** @param array $page */
    function marketing_page_priority(array $page)
    {
        $type = isset($page['page_type']) ? $page['page_type'] : '';
        if ($type === 'hub') {
            return '0.9';
        }
        if ($type === 'service') {
            return '0.8';
        }
        if ($type === 'locality') {
            return '0.7';
        }
        return '0.6';
    }
}

if (!function_exists('marketing_breadcrumbs')) {
    /**
     * @param string $path
     * @param array  $page
     * @return array<int, array{label:string,url:string}>
     */
    function marketing_breadcrumbs($path, array $page = array())
    {
        $crumbs = array(
            array('label' => 'Home', 'url' => marketing_canonical_url('')),
        );
        $parts = explode('/', trim((string) $path, '/'));
        if ($parts === array('') || $parts === array()) {
            return $crumbs;
        }
        if ($parts[0] === 'locations' && count($parts) >= 2) {
            $city = marketing_city_by_slug($parts[1]);
            $crumbs[] = array('label' => 'Locations', 'url' => marketing_canonical_url('locations/'.$parts[1]));
            if ($city) {
                $crumbs[count($crumbs) - 1]['label'] = 'Car servicing in '.$city['name'];
            }
            return $crumbs;
        }
        if ($parts[0] === 'services') {
            $crumbs[] = array('label' => 'Services', 'url' => marketing_canonical_url('services'));
            if (isset($page['city_slug'])) {
                $city = marketing_city_by_slug($page['city_slug']);
                if ($city) {
                    $crumbs[] = array(
                        'label' => $city['name'],
                        'url'   => marketing_canonical_url($city['hub_path']),
                    );
                }
            }
            if (isset($page['h1'])) {
                $crumbs[] = array('label' => $page['h1'], 'url' => marketing_canonical_url($path));
            }
            return $crumbs;
        }
        if (isset($page['h1'])) {
            $crumbs[] = array('label' => $page['h1'], 'url' => marketing_canonical_url($path));
        }
        return $crumbs;
    }
}

if (!function_exists('marketing_schema_graph')) {
    /**
     * JSON-LD @graph for the active marketing page.
     *
     * @param array $page_meta
     * @return array
     */
    function marketing_schema_graph(array $page_meta)
    {
        $ci = &get_instance();
        $brand = $ci->config->item('ymo_brand_name');
        $phone = $ci->config->item('ymo_support_phone');
        $email = $ci->config->item('ymo_support_email');
        $path  = isset($page_meta['canonical_path']) ? $page_meta['canonical_path'] : '';
        $url   = marketing_canonical_url($path);
        $graph = array();

        $area_served = array();
        foreach (array('pune', 'indore', 'nashik') as $slug) {
            $city = marketing_city_by_slug($slug);
            if ($city) {
                $area_served[] = array('@type' => 'City', 'name' => $city['name']);
            }
        }

        $graph[] = array(
            '@type'       => array('AutoRepair', 'LocalBusiness'),
            '@id'         => marketing_canonical_url('').'#organization',
            'name'        => $brand,
            'url'         => marketing_canonical_url(''),
            'telephone'   => $phone,
            'email'       => $email,
            'areaServed'  => $area_served,
        );

        if ($path === '') {
            $graph[] = array(
                '@type'       => 'WebSite',
                '@id'         => marketing_canonical_url('').'#website',
                'url'         => marketing_canonical_url(''),
                'name'        => $brand,
                'publisher'   => array('@id' => marketing_canonical_url('').'#organization'),
                'potentialAction' => array(
                    '@type'       => 'SearchAction',
                    'target'      => marketing_canonical_url('services').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ),
            );
        }

        $city_slug = isset($page_meta['city_slug']) ? $page_meta['city_slug'] : '';
        if ($city_slug !== '') {
            $city = marketing_city_by_slug($city_slug);
            if ($city) {
                $local = array(
                    '@type'     => array('AutoRepair', 'LocalBusiness'),
                    '@id'       => marketing_canonical_url($city['hub_path']).'#local',
                    'name'      => $brand.' - '.$city['name'],
                    'url'       => marketing_canonical_url($city['hub_path']),
                    'telephone' => isset($city['phone']) ? $city['phone'] : $phone,
                    'email'     => isset($city['email']) ? $city['email'] : $email,
                    'address'   => isset($city['address']) ? $city['address'] : array(),
                    'areaServed'=> array(array('@type' => 'City', 'name' => $city['name'])),
                );
                if (!empty($city['geo'])) {
                    $local['geo'] = array(
                        '@type'     => 'GeoCoordinates',
                        'latitude'  => $city['geo']['latitude'],
                        'longitude' => $city['geo']['longitude'],
                    );
                }
                if (!empty($city['gbp_url'])) {
                    $local['sameAs'] = array($city['gbp_url']);
                }
                $graph[] = $local;
            }
        }

        if (isset($page_meta['page_type']) && $page_meta['page_type'] === 'service') {
            $area_name = 'India';
            if ($city_slug !== '' && !empty($city['name'])) {
                $area_name = $city['name'];
            }
            $graph[] = array(
                '@type'       => 'Service',
                '@id'         => $url.'#service',
                'name'        => isset($page_meta['h1']) ? $page_meta['h1'] : '',
                'description' => isset($page_meta['meta_description']) ? $page_meta['meta_description'] : '',
                'provider'    => array('@id' => marketing_canonical_url('').'#organization'),
                'areaServed'  => $area_name,
                'url'         => $url,
            );
        }

        $crumbs = marketing_breadcrumbs($path, $page_meta);
        if (count($crumbs) > 1) {
            $items = array();
            foreach ($crumbs as $i => $c) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => $c['label'],
                    'item'     => $c['url'],
                );
            }
            $graph[] = array(
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $items,
            );
        }

        if (!empty($page_meta['faq']) && is_array($page_meta['faq'])) {
            $entities = array();
            foreach ($page_meta['faq'] as $row) {
                if (empty($row['q']) || empty($row['a'])) {
                    continue;
                }
                $entities[] = array(
                    '@type'          => 'Question',
                    'name'           => $row['q'],
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => $row['a'],
                    ),
                );
            }
            if ($entities !== array()) {
                $graph[] = array(
                    '@type'      => 'FAQPage',
                    'mainEntity' => $entities,
                );
            }
        }

        return array(
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        );
    }
}

if (!function_exists('marketing_llms_txt')) {
    /** @return string */
    function marketing_llms_txt()
    {
        $lines = array(
            '# Your Mechanic Online - canonical pages for AI citation',
            '# https://www.yourmechaniconline.com',
            '',
            '## Cities',
        );
        foreach (array('pune', 'indore', 'nashik') as $slug) {
            $city = marketing_city_by_slug($slug);
            if (!$city) {
                continue;
            }
            $lines[] = '- '.marketing_canonical_url($city['hub_path']).' - Car servicing in '.$city['name'];
        }
        $lines[] = '';
        $lines[] = '## Services (Pune)';
        foreach (marketing_pages_data() as $path => $page) {
            if (strpos($path, 'services/') !== 0 || strpos($path, '-in-indore') !== FALSE || strpos($path, '-in-nashik') !== FALSE) {
                continue;
            }
            if (!isset($page['page_type']) || $page['page_type'] !== 'service') {
                continue;
            }
            $lines[] = '- '.marketing_canonical_url($path).' - '.(isset($page['h1']) ? $page['h1'] : $path);
        }
        $lines[] = '';
        $lines[] = '## Contact';
        $lines[] = '- '.marketing_canonical_url('contact-us');
        $lines[] = '- Tel: +91-7744-065904';
        $lines[] = '- Email: contactus@yourmechaniconline.com';
        return implode("\n", $lines)."\n";
    }
}

if (!function_exists('marketing_public_nav_items')) {
    /**
     * Primary marketing nav - services + locations (from marketing_cities.php).
     *
     * @return array{services:array, locations:array}
     */
    function marketing_public_nav_items()
    {
        $cfg = marketing_cities_config();
        $services = array();
        if (isset($cfg['services']) && is_array($cfg['services'])) {
            $icons = array(
                'complete-car-servicing' => 'car_repair',
                'ac'                     => 'ac_unit',
                'brakes'                 => 'report',
                'denting'                => 'auto_fix_high',
                'engine'                 => 'settings',
                'interior'               => 'vacuum',
                'polishing'              => 'cleaning_services',
                'belts'                  => 'build',
                'lube'                   => 'oil_barrel',
            );
            foreach ($cfg['services'] as $svc) {
                $key = isset($svc['key']) ? $svc['key'] : '';
                $services[] = array(
                    'label' => $svc['title'],
                    'slug'  => $svc['pune_slug'],
                    'icon'  => isset($icons[$key]) ? $icons[$key] : 'build',
                );
            }
        }
        $services[] = array(
            'label'    => 'View all services',
            'slug'     => 'services',
            'icon'     => 'apps',
            'emphasis' => TRUE,
        );

        $locations = array();
        foreach (array('pune', 'nashik', 'indore') as $city_slug) {
            if (!isset($cfg[$city_slug]) || !is_array($cfg[$city_slug])) {
                continue;
            }
            $city = $cfg[$city_slug];
            $item = array(
                'label'    => $city['name'],
                'slug'     => $city['hub_path'],
                'children' => array(),
            );
            if ($city_slug === 'pune' && !empty($city['localities']) && is_array($city['localities'])) {
                foreach ($city['localities'] as $loc) {
                    $item['children'][] = array(
                        'label' => $loc['label'],
                        'slug'  => $loc['slug'],
                    );
                }
                $item['children'][] = array(
                    'label' => 'Luxury cars',
                    'slug'  => 'premium-luxury-car-service-pune',
                );
            }
            $locations[] = $item;
        }
        return array(
            'services'  => $services,
            'locations' => $locations,
            'luxury'    => array(
                'label' => 'Luxury cars',
                'slug'  => 'premium-luxury-car-service-pune',
                'icon'  => 'auto_awesome',
            ),
        );
    }
}

if (!function_exists('marketing_nav_services_active')) {
    /** @param string $current */
    function marketing_nav_services_active($current)
    {
        return $current === 'services' || strpos($current, 'services/') === 0;
    }
}

if (!function_exists('marketing_nav_luxury_active')) {
    /** @param string $current */
    function marketing_nav_luxury_active($current)
    {
        return $current === 'premium-luxury-car-service-pune';
    }
}

if (!function_exists('marketing_nav_locations_active')) {
    /** @param string $current */
    function marketing_nav_locations_active($current)
    {
        if ($current === '' || $current === 'services') {
            return FALSE;
        }
        if (strpos($current, 'locations/') === 0) {
            return TRUE;
        }
        $cfg = marketing_cities_config();
        foreach (array('pune', 'indore', 'nashik') as $city_slug) {
            if (!isset($cfg[$city_slug])) {
                continue;
            }
            $city = $cfg[$city_slug];
            if (!empty($city['legacy_hub']) && $current === $city['legacy_hub']) {
                return TRUE;
            }
            if (!empty($city['localities']) && is_array($city['localities'])) {
                foreach ($city['localities'] as $loc) {
                    if (!empty($loc['slug']) && ($current === $loc['slug'] || strpos($current, $loc['slug'].'/') === 0)) {
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }
}

if (!function_exists('marketing_city_hint_banner')) {
    /**
     * IP-based city hint for homepage only. Never redirects.
     *
     * @return array{slug:string,name:string,hub_path:string}|null
     */
    function marketing_city_hint_banner()
    {
        $CI = &get_instance();
        $CI->load->library('city_geo');
        return $CI->city_geo->homepage_hint();
    }
}

if (!function_exists('marketing_service_catalog_cards')) {
    /**
     * @param string $city_slug pune|indore|nashik
     * @return array<int, array{slug:string,title:string,teaser:string,icon:string}>
     */
    function marketing_service_catalog_cards($city_slug = 'pune')
    {
        $city_slug = strtolower(trim((string) $city_slug));
        if ($city_slug === '') {
            $city_slug = 'pune';
        }

        $cfg = marketing_cities_config();
        if (empty($cfg['services']) || !is_array($cfg['services'])) {
            return array();
        }

        $out = array();
        foreach ($cfg['services'] as $svc) {
            if (empty($svc['title'])) {
                continue;
            }
            if ($city_slug === 'pune') {
                $slug = !empty($svc['pune_slug']) ? $svc['pune_slug'] : '';
            } else {
                $slug = !empty($svc['city_slug'])
                    ? str_replace('{city}', $city_slug, $svc['city_slug'])
                    : '';
            }
            if ($slug === '') {
                continue;
            }

            $teaser = isset($svc['overview']) ? $svc['overview'] : '';
            if ($city_slug !== 'pune') {
                $city = marketing_city_by_slug($city_slug);
                $city_name = ($city && !empty($city['name'])) ? $city['name'] : ucfirst($city_slug);
                if (!empty($svc['price_from'])) {
                    $teaser .= ' Available in '.$city_name.' from ₹'.(int) $svc['price_from'].'.';
                } else {
                    $teaser .= ' Available in '.$city_name.'.';
                }
            }

            $out[] = array(
                'slug'   => $slug,
                'title'  => $svc['title'],
                'teaser' => trim($teaser),
                'icon'   => !empty($svc['icon']) ? $svc['icon'] : 'build',
            );
        }
        return $out;
    }
}

if (!function_exists('marketing_home_featured_services')) {
    /** @return array<int, array{slug:string,title:string,teaser:string,icon:string}> */
    function marketing_home_featured_services()
    {
        return marketing_service_catalog_cards('pune');
    }
}

if (!function_exists('marketing_home_city_strip')) {
    /** @return array<int, array{name:string,hub_path:string}> */
    function marketing_home_city_strip()
    {
        $cities = array();
        foreach (array('pune', 'indore', 'nashik') as $city_slug) {
            $city = marketing_city_by_slug($city_slug);
            if ($city === NULL) {
                continue;
            }
            $cities[] = array(
                'name'     => $city['name'],
                'hub_path' => $city['hub_path'],
            );
        }
        return $cities;
    }
}

if (!function_exists('marketing_resolve_locality_page')) {
    /**
     * @param string $path
     * @param array  $page
     * @return array{city_slug:string,locality_slug:string}
     */
    function marketing_resolve_locality_page($path, array $page)
    {
        $city_slug = !empty($page['city_slug']) ? $page['city_slug'] : '';
        $locality_slug = !empty($page['locality_slug']) ? $page['locality_slug'] : '';

        if ($city_slug !== '' && $locality_slug !== '') {
            return array(
                'city_slug'     => $city_slug,
                'locality_slug' => $locality_slug,
            );
        }

        $path = trim((string) $path, '/');
        $cfg = marketing_cities_config();
        foreach ($cfg as $slug => $city) {
            if ($slug === 'services' || !is_array($city) || empty($city['localities'])) {
                continue;
            }
            foreach ($city['localities'] as $loc_key => $loc) {
                if (!empty($loc['slug']) && $loc['slug'] === $path) {
                    return array(
                        'city_slug'     => $slug,
                        'locality_slug' => $loc_key,
                    );
                }
            }
        }

        return array(
            'city_slug'     => $city_slug !== '' ? $city_slug : 'pune',
            'locality_slug' => $locality_slug,
        );
    }
}

if (!function_exists('marketing_strip_locality_services_list')) {
    /** Remove legacy WP services-list grid from locality page bodies. */
    function marketing_strip_locality_services_list($body)
    {
        $body = trim((string) $body);
        if ($body === '') {
            return $body;
        }

        $body = preg_replace(
            '/<h2[^>]*>\s*Car Services Available In[^<]*<\/h2>[\s\S]*?(?=<h2[^>]*>\s*(?:Frequently Asked|YMO Benefits|Reasons To Get)|\z)/i',
            '',
            $body,
            1
        );

        $prev = NULL;
        while ($prev !== $body && preg_match('/<ul class="services-list\b/i', $body)) {
            $prev = $body;
            $body = preg_replace('/<ul class="services-list[^"]*"[^>]*>[\s\S]*?<\/ul>\s*/i', '', $body, 1);
        }

        $body = preg_replace('/^<p>\s*/i', '', $body);
        $body = preg_replace('/\s*<\/p>\s*$/i', '', $body);
        $body = preg_replace('/<p>\s*<\/p>/i', '', $body);

        return trim($body);
    }
}

if (!function_exists('marketing_feature_item_icon')) {
    /**
     * @param string $item_html
     * @param int    $index
     * @return string Material icon name
     */
    function marketing_feature_item_icon($item_html, $index = 0, $title = '')
    {
        $fallbacks = array('local_shipping', 'payments', 'engineering', 'check_circle', 'verified', 'schedule', 'support_agent');

        if ($title !== '') {
            $t = strtolower($title);
            if (strpos($t, 'leak') !== FALSE) {
                return 'water_drop';
            }
            if (strpos($t, 'pressure') !== FALSE || strpos($t, 'functional') !== FALSE) {
                return 'speed';
            }
            if (strpos($t, 'value') !== FALSE || strpos($t, 'price') !== FALSE) {
                return 'payments';
            }
            if (strpos($t, 'compressor') !== FALSE) {
                return 'settings';
            }
            if (strpos($t, 'gas') !== FALSE) {
                return 'ac_unit';
            }
            if (strpos($t, 'filter') !== FALSE || strpos($t, 'clean') !== FALSE) {
                return 'air';
            }
        }

        if (preg_match('/sl-small-signal-warning/i', $item_html)) {
            return 'local_shipping';
        }
        if (preg_match('/sl-small-person/i', $item_html)) {
            return 'engineering';
        }
        if (preg_match('/sl-small-check/i', $item_html)) {
            return 'check_circle';
        }
        return isset($fallbacks[$index]) ? $fallbacks[$index] : 'check_circle';
    }
}

if (!function_exists('marketing_parse_feature_item_inner')) {
    /**
     * @param string $inner HTML inside a legacy feature-item block
     * @return array{title:string,text:string,html:string}|null
     */
    function marketing_parse_feature_item_inner($inner)
    {
        $title = '';
        if (preg_match('/<h5[^>]*>(.*?)<\/h5>/is', $inner, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
        }

        $text_parts = array();
        if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $inner, $ps)) {
            foreach ($ps[1] as $p) {
                $t = trim(html_entity_decode(strip_tags($p), ENT_QUOTES, 'UTF-8'));
                if ($t !== '') {
                    $text_parts[] = $t;
                }
            }
        }
        $text = trim(implode(' ', $text_parts));

        if ($text === '' && $title !== '') {
            return array(
                'title' => '',
                'text'  => $title,
                'html'  => $inner,
            );
        }
        if ($text === '') {
            return NULL;
        }

        return array(
            'title' => $title,
            'text'  => $text,
            'html'  => $inner,
        );
    }
}

if (!function_exists('marketing_extract_feature_items')) {
    /** @return array<int, array{title:string,text:string,html:string}> */
    function marketing_extract_feature_items($html)
    {
        $items  = array();
        $needle = '<div class="feature-item">';
        $offset = 0;
        $len    = strlen($html);

        while (($pos = stripos($html, $needle, $offset)) !== FALSE) {
            $start = $pos + strlen($needle);
            $depth = 1;
            $i     = $start;
            $end   = NULL;

            while ($i < $len) {
                $next_open  = stripos($html, '<div', $i);
                $next_close = stripos($html, '</div>', $i);
                if ($next_close === FALSE) {
                    break;
                }
                if ($next_open !== FALSE && $next_open < $next_close) {
                    $depth++;
                    $i = $next_open + 4;
                    continue;
                }
                $depth--;
                if ($depth === 0) {
                    $end = $next_close;
                    break;
                }
                $i = $next_close + 6;
            }

            if ($end === NULL) {
                break;
            }

            $inner  = substr($html, $start, $end - $start);
            $parsed = marketing_parse_feature_item_inner($inner);
            if ($parsed !== NULL) {
                $items[] = $parsed;
            }
            $offset = $end + 6;
        }

        return $items;
    }
}

if (!function_exists('marketing_build_feature_card_grid')) {
    /**
     * @param string $heading
     * @param array  $items
     * @return string
     */
    function marketing_build_feature_card_grid($heading, array $items)
    {
        $count = count($items);
        if ($count === 0) {
            return '';
        }

        if ($count >= 6) {
            $col = 'col-md-6 col-lg-4';
        } elseif ($count >= 4) {
            $col = 'col-md-6 col-lg-3';
        } elseif ($count === 3) {
            $col = 'col-md-4';
        } else {
            $col = 'col-md-6';
        }

        $cards = '';
        foreach ($items as $i => $item) {
            $title = isset($item['title']) ? $item['title'] : '';
            $text  = isset($item['text']) ? $item['text'] : '';
            $html  = isset($item['html']) ? $item['html'] : '';

            if ($text === '') {
                continue;
            }

            $icon = marketing_feature_item_icon($html, $i, $title);
            $cards .= '<div class="'.$col.'"><div class="md-card-elevated h-100">';
            $cards .= '<span class="mi mi-xl md-icon-primary">'.htmlspecialchars($icon, ENT_QUOTES, 'UTF-8').'</span>';
            if ($title !== '') {
                $cards .= '<h3 class="md-title-md mt-3 mb-2">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h3>';
                $cards .= '<p class="md-body-md mb-0">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</p>';
            } else {
                $cards .= '<p class="md-body-md mb-0 mt-3">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</p>';
            }
            $cards .= '</div></div>';
        }

        if ($cards === '') {
            return '';
        }

        return '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">'
            .htmlspecialchars($heading, ENT_QUOTES, 'UTF-8')
            .'</h2><div class="row g-4">'.$cards.'</div></div>';
    }
}

if (!function_exists('marketing_normalize_feature_item_body')) {
    /**
     * Convert legacy WP feature-item blocks into site-standard card grids.
     *
     * @param string $body
     * @return string
     */
    function marketing_normalize_feature_item_body($body)
    {
        $body = trim((string) $body);
        if ($body === '' || stripos($body, 'feature-item') === FALSE) {
            return $body;
        }

        return preg_replace_callback(
            '/<h([234])[^>]*class="[^"]*box-header[^"]*"[^>]*>(.*?)<\/h\1>(.*?)(?=<h[234][^>]*class="[^"]*box-header|$)/is',
            function ($match) {
                $section = $match[3];
                if (stripos($section, 'feature-item') === FALSE) {
                    return $match[0];
                }

                $heading = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES, 'UTF-8'));
                $items   = marketing_extract_feature_items($section);
                $grid    = marketing_build_feature_card_grid($heading, $items);

                return $grid !== '' ? $grid : $match[0];
            },
            $body
        );
    }
}

if (!function_exists('marketing_normalize_locality_body')) {
    /**
     * Convert legacy WP feature-item blocks into site-standard card grids.
     *
     * @param string $body
     * @return string
     */
    function marketing_normalize_locality_body($body)
    {
        return marketing_normalize_feature_item_body($body);
    }
}

if (!function_exists('marketing_locality_service_catalog_heading')) {
    /** @param array $page */
    function marketing_locality_service_catalog_heading(array $page)
    {
        if (!empty($page['service_catalog_heading'])) {
            return $page['service_catalog_heading'];
        }

        $city_slug = !empty($page['city_slug']) ? $page['city_slug'] : 'pune';
        $city = marketing_city_by_slug($city_slug);
        $city_name = ($city && !empty($city['name'])) ? $city['name'] : ucfirst($city_slug);

        $locality_slug = !empty($page['locality_slug']) ? $page['locality_slug'] : '';
        if ($locality_slug !== '' && $city && !empty($city['localities'][$locality_slug]['label'])) {
            return 'Car services in '.$city['localities'][$locality_slug]['label'].', '.$city_name;
        }

        if (!empty($page['body']) && preg_match('/Car Services Available In\s+([^.<]+)/i', $page['body'], $matches)) {
            return 'Car services in '.trim($matches[1]);
        }

        return 'Car services in '.$city_name;
    }
}
