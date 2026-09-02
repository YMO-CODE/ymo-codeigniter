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
        $base = function_exists('ymo_env') ? ymo_env('YMO_PUBLIC_APP_URL') : getenv('YMO_PUBLIC_APP_URL');
        if ($base === FALSE || trim((string) $base) === '') {
            $base = function_exists('ymo_env') ? ymo_env('YMO_APP_URL') : getenv('YMO_APP_URL');
        }
        if ($base === FALSE || trim((string) $base) === '' || ymo_configured_url_looks_local($base)) {
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                $base = 'https://booking.yourmechaniconline.com';
            } else {
                return site_url(ltrim($path, '/'));
            }
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

if (!function_exists('marketing_canonical_base')) {
    /** Always www marketing origin — never inbound Host (apex consolidation). */
    function marketing_canonical_base()
    {
        $base = function_exists('ymo_env') ? ymo_env('YMO_MARKETING_APP_URL') : getenv('YMO_MARKETING_APP_URL');
        if ($base === FALSE || trim((string) $base) === '') {
            $ci = &get_instance();
            $base = $ci->config->item('ymo_marketing_url');
        }
        if ($base === FALSE || trim((string) $base) === '') {
            $ci = &get_instance();
            $base = rtrim($ci->config->item('base_url'), '/');
        } else {
            $base = function_exists('ymo_sanitize_external_app_url')
                ? ymo_sanitize_external_app_url($base)
                : rtrim((string) $base, '/');
        }
        return rtrim((string) $base, '/');
    }
}

if (!function_exists('marketing_canonical_url')) {
    /** @param string $path Path without leading slash */
    function marketing_canonical_url($path = '')
    {
        $base = marketing_canonical_base();
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

        $cache_dir = APPPATH.'cache';
        $cache_file = $cache_dir.'/marketing_pages_registry.php';
        $source_files = array(
            APPPATH.'config/marketing_pages_data.php',
            APPPATH.'config/marketing_pages_city_services.php',
            APPPATH.'config/marketing_pages_option_a.php',
            APPPATH.'config/marketing_cities.php',
            APPPATH.'config/marketing_brands.php',
            APPPATH.'helpers/marketing_seo_growth_helper.php',
            APPPATH.'helpers/marketing_internal_links_helper.php',
        );
        $max_mtime = 0;
        foreach ($source_files as $src) {
            if (is_file($src)) {
                $max_mtime = max($max_mtime, (int) filemtime($src));
            }
        }
        if (is_dir($cache_dir) && is_writable($cache_dir)
            && is_file($cache_file) && filemtime($cache_file) >= $max_mtime) {
            $cached = include $cache_file;
            if (is_array($cached)) {
                $pages = $cached;
                if (function_exists('marketing_enrich_pages')) {
                    $pages = marketing_enrich_pages($pages);
                }
                return $pages;
            }
        }

        $file = APPPATH.'config/marketing_pages_data.php';
        $pages = is_file($file) ? require $file : array();
        if (function_exists('marketing_enrich_pages')) {
            $pages = marketing_enrich_pages($pages);
        }
        if (is_dir($cache_dir) && is_writable($cache_dir)) {
            @file_put_contents($cache_file, '<?php return '.var_export($pages, TRUE).';');
        }
        return is_array($pages) ? $pages : array();
    }
}

if (!function_exists('marketing_brands_config')) {
    /** @return array<string,array> */
    function marketing_brands_config()
    {
        static $brands = NULL;
        if ($brands !== NULL) {
            return $brands;
        }
        $file = APPPATH.'config/marketing_brands.php';
        $brands = is_file($file) ? require $file : array();
        return is_array($brands) ? $brands : array();
    }
}

if (!function_exists('marketing_brand_faq')) {
    /**
     * @param array  $brand
     * @param string $city_name
     * @return array<int,array{q:string,a:string}>
     */
    function marketing_brand_faq(array $brand, $city_name)
    {
        $name = isset($brand['brand_name']) ? $brand['brand_name'] : 'your car';
        return array(
            array(
                'q' => 'How much does '.$name.' car service cost in '.$city_name.'?',
                'a' => $name.' periodic service in '.$city_name.' starts from ₹1,999 at YMO. We share an upfront estimate covering oil, filters, brakes, AC check, and wash before any work begins.',
            ),
            array(
                'q' => 'Do you pick up my '.$name.' from my doorstep in '.$city_name.'?',
                'a' => 'Yes. YMO provides free pick-up and drop across '.$city_name.'. Book online, we collect your '.$name.', service it at our workshop, and return it when done.',
            ),
            array(
                'q' => 'Which '.$name.' models do you service?',
                'a' => 'We service all '.$name.' models including '.marketing_brand_models_list($brand).'. Our technicians use brand-appropriate oil grades and diagnostic checks.',
            ),
            array(
                'q' => 'Can YMO handle '.$name.' AC repair and denting in '.$city_name.'?',
                'a' => 'Yes. Beyond periodic service, YMO offers AC gas recharge, compressor repair, denting, painting, and polishing for '.$name.' cars - with workshop equipment, not just a home visit.',
            ),
            array(
                'q' => 'How often should I service my '.$name.'?',
                'a' => 'Most '.$name.' owners should service every 10,000 km or 12 months, whichever comes first. Turbo, diesel, or high-mileage cars may need shorter intervals - we advise during inspection.',
            ),
        );
    }
}

if (!function_exists('marketing_brand_models_list')) {
    /** @param array $brand */
    function marketing_brand_models_list(array $brand)
    {
        $models = isset($brand['common_models']) && is_array($brand['common_models'])
            ? $brand['common_models']
            : array();
        if ($models === array()) {
            return 'all popular models';
        }
        if (count($models) <= 3) {
            return implode(', ', $models);
        }
        $tail = array_slice($models, -2);
        $head = array_slice($models, 0, count($models) - 2);
        return implode(', ', $head).', and '.implode(' & ', $tail);
    }
}

if (!function_exists('marketing_brand_page_body')) {
    /**
     * @param array  $brand
     * @param string $city_slug
     * @param string $city_name
     * @return string
     */
    function marketing_brand_page_body(array $brand, $city_slug, $city_name)
    {
        $name = isset($brand['brand_name']) ? $brand['brand_name'] : 'Car';
        $intro = isset($brand['intro_copy']) ? $brand['intro_copy'] : '';
        $includes = isset($brand['service_includes']) && is_array($brand['service_includes'])
            ? $brand['service_includes'] : array();
        $issues = isset($brand['common_issues']) && is_array($brand['common_issues'])
            ? $brand['common_issues'] : array();
        $hub = marketing_city_by_slug($city_slug);
        $hub_path = ($hub && !empty($hub['hub_path'])) ? $hub['hub_path'] : 'locations/'.$city_slug;

        $html = '<div class="ymo-content-section mb-5">';
        $html .= '<h2 class="md-headline-md mb-3">'.$name.' car service in '.$city_name.'</h2>';
        $html .= '<p class="md-body-md mb-3">'.html_escape($intro).' In '.$city_name.', YMO combines doorstep convenience with full workshop capability - so your '.$name.' gets more than a quick oil top-up at the kerb.</p>';
        $html .= '<p class="md-body-md mb-0">Whether you drive a '.html_escape(marketing_brand_models_list($brand)).', our trained mechanics follow manufacturer-recommended schedules, use quality parts, and keep you updated on WhatsApp with photos during the job.</p>';
        $html .= '</div>';

        $html .= '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">What\'s included in a '.$name.' service</h2><ul class="md-body-md">';
        foreach ($includes as $item) {
            $html .= '<li class="mb-2">'.html_escape($item).'</li>';
        }
        $html .= '</ul><p class="md-body-md mb-0">Complete '.$name.' servicing in '.$city_name.' starts from <strong>₹1,999</strong>. <a href="/services/complete-car-servicing-in-'.$city_slug.'">View complete servicing details</a> or browse our <a href="/services">full service catalogue</a>.</p></div>';

        if ($issues !== array()) {
            $html .= '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Common '.$name.' issues we fix</h2><div class="row g-4">';
            foreach ($issues as $model => $issue) {
                $html .= '<div class="col-md-6"><div class="md-card-elevated h-100 p-4"><h3 class="md-title-md mb-2">'.html_escape($model).'</h3><p class="md-body-md mb-0">'.html_escape($issue).'</p></div></div>';
            }
            $html .= '</div></div>';
        }

        $html .= '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Pricing for '.$name.' owners in '.$city_name.'</h2>';
        $html .= '<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Service</th><th>From</th></tr></thead><tbody>';
        $html .= '<tr><td>Periodic / complete car service</td><td>₹1,999</td></tr>';
        $html .= '<tr><td>AC repair &amp; gas recharge</td><td>On inspection</td></tr>';
        $html .= '<tr><td>Interior deep cleaning</td><td>₹2,500</td></tr>';
        $html .= '<tr><td>Denting &amp; painting (per panel)</td><td>₹3,000</td></tr>';
        $html .= '<tr><td>3-stage rubbing &amp; polishing</td><td>₹6,500</td></tr>';
        $html .= '</tbody></table></div>';
        $html .= '<p class="md-body-md mb-0">All prices are indicative - we confirm the exact estimate for your '.$name.' before starting work. <a href="/locations/'.$city_slug.'">See all areas we serve in '.$city_name.'</a>.</p></div>';

        $html .= '<div class="ymo-content-section"><p class="md-body-md">Also explore <a href="/brands">all brands we service</a> · <a href="/'.html_escape($hub_path).'">Car servicing in '.$city_name.'</a> · <a href="/premium-luxury-car-service-pune">Luxury car service</a></p></div>';

        return $html;
    }
}

if (!function_exists('marketing_brand_pages')) {
    /** @return array<string,array> */
    function marketing_brand_pages()
    {
        static $pages = NULL;
        if ($pages !== NULL) {
            return $pages;
        }
        $pages = array();
        $brands = marketing_brands_config();
        $cities = array('pune', 'indore', 'nashik');
        $today = date('Y-m-d');

        foreach ($brands as $brand) {
            if (empty($brand['active']) || empty($brand['slug'])) {
                continue;
            }
            $slug = (string) $brand['slug'];
            $name = isset($brand['brand_name']) ? $brand['brand_name'] : ucfirst($slug);

            foreach ($cities as $city_slug) {
                $city = marketing_city_by_slug($city_slug);
                if (!$city) {
                    continue;
                }
                $city_name = $city['name'];
                $path = $slug.'-car-service-in-'.$city_slug;
                $pages[$path] = array(
                    'title'            => $name.' Car Service in '.$city_name.' | YMO',
                    'meta_description' => 'Book '.$name.' car service in '.$city_name.' from ₹1,999. Expert mechanics, free pick-up & drop, AC repair, denting & periodic maintenance for '.marketing_brand_models_list($brand).'.',
                    'h1'               => $name.' car service in '.$city_name,
                    'intro'            => $name.' servicing in '.$city_name.' with trained mechanics, transparent pricing, and free doorstep pick-up.',
                    'quick_answer'     => 'YMO offers '.$name.' car service in '.$city_name.' from ₹1,999 - periodic maintenance, AC repair, and body work with free pick-up and drop.',
                    'body'             => marketing_brand_page_body($brand, $city_slug, $city_name),
                    'page_type'        => 'brand',
                    'city_slug'        => $city_slug,
                    'brand_slug'       => $slug,
                    'brand_name'       => $name,
                    'faq'              => marketing_brand_faq($brand, $city_name),
                    'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
                    'updated_at'       => $today,
                    'view'             => 'marketing/page',
                );
            }
        }

        return $pages;
    }
}

if (!function_exists('marketing_brands_index_cards')) {
    /** @return array<int,array{title:string,slug:string,cities:array}> */
    function marketing_brands_index_cards()
    {
        $cards = array();
        foreach (marketing_brands_config() as $brand) {
            if (empty($brand['active']) || empty($brand['slug'])) {
                continue;
            }
            $cards[] = array(
                'title'  => isset($brand['brand_name']) ? $brand['brand_name'] : '',
                'slug'   => (string) $brand['slug'],
                'models' => marketing_brand_models_list($brand),
                'cities' => array(
                    'pune'   => $brand['slug'].'-car-service-in-pune',
                    'indore' => $brand['slug'].'-car-service-in-indore',
                    'nashik' => $brand['slug'].'-car-service-in-nashik',
                ),
            );
        }
        return $cards;
    }
}

if (!function_exists('marketing_brands_index_page')) {
    /** @return array */
    function marketing_brands_index_page()
    {
        $cards = marketing_brands_index_cards();
        $body = '<div class="ymo-content-section mb-5"><p class="md-body-lg mb-4">Your Mechanic Online services every major Indian car brand across Pune, Indore, and Nashik - with free pick-up, workshop-grade repairs, and transparent pricing from ₹1,999.</p></div>';
        $body .= '<div class="row g-4">';
        foreach ($cards as $card) {
            $body .= '<div class="col-md-6 col-lg-4"><div class="md-card-elevated h-100 p-4">';
            $body .= '<h2 class="md-title-md mb-2"><a href="/'.html_escape($card['cities']['pune']).'" class="text-decoration-none text-dark">'.html_escape($card['title']).'</a></h2>';
            $body .= '<p class="md-body-md mb-3">Models: '.html_escape($card['models']).'</p>';
            $body .= '<div class="d-flex flex-wrap gap-2">';
            $body .= '<a href="/'.html_escape($card['cities']['pune']).'" class="md-chip md-chip--outlined">Pune</a>';
            $body .= '<a href="/'.html_escape($card['cities']['indore']).'" class="md-chip md-chip--outlined">Indore</a>';
            $body .= '<a href="/'.html_escape($card['cities']['nashik']).'" class="md-chip md-chip--outlined">Nashik</a>';
            $body .= '</div></div></div>';
        }
        $body .= '</div>';

        return array(
            'title'            => 'Car Brands We Service | Your Mechanic Online',
            'meta_description' => 'Maruti, Hyundai, Tata, Honda, Mahindra, Toyota, Kia, VW, Skoda & Renault car service in Pune, Indore & Nashik. Free pick-up from ₹1,999.',
            'h1'               => 'Brands we service',
            'intro'            => 'Expert car servicing for every major brand - periodic maintenance, AC, denting, and more.',
            'quick_answer'     => 'YMO services Maruti Suzuki, Hyundai, Tata, Honda, Mahindra, Toyota, Kia, Volkswagen, Skoda, and Renault across Pune, Indore, and Nashik.',
            'body'             => $body,
            'page_type'        => 'hub',
            'updated_at'       => date('Y-m-d'),
            'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
            'view'             => 'marketing/page',
        );
    }
}

if (!function_exists('marketing_site_trust_badge')) {
    /** @return array{show:bool,text:string,rating:string,url:string} */
    function marketing_site_trust_badge()
    {
        $cfg = marketing_trust_config();
        if (empty($cfg['show_in_header'])) {
            return array('show' => FALSE);
        }
        $rating = isset($cfg['google_rating']) ? (float) $cfg['google_rating'] : 0;
        $reviews = isset($cfg['review_count']) ? (int) $cfg['review_count'] : 0;
        if ($rating <= 0 || $reviews <= 0) {
            return array('show' => FALSE);
        }
        $label = isset($cfg['review_label']) ? (string) $cfg['review_label'] : 'Google reviews';
        return array(
            'show'   => TRUE,
            'rating' => number_format($rating, 1),
            'text'   => number_format($rating, 1).'★ · '.number_format($reviews).'+ '.$label,
            'url'    => !empty($cfg['gbp_share_url']) ? (string) $cfg['gbp_share_url'] : '',
        );
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

if (!function_exists('marketing_redirect_source_paths')) {
    /**
     * Paths that 301 elsewhere — exclude from sitemap and route registration checks.
     *
     * @return array<string,bool> normalized path => true
     */
    function marketing_redirect_source_paths()
    {
        static $sources = NULL;
        if ($sources !== NULL) {
            return $sources;
        }
        $sources = array();
        $rules = marketing_redirect_rules();
        foreach ($rules['exact'] as $from => $to) {
            $from_n = marketing_normalize_path($from);
            $to_n = marketing_normalize_path($to);
            if ($from_n !== '' && $to_n !== '' && $from_n !== $to_n) {
                $sources[$from_n] = TRUE;
            }
        }
        return $sources;
    }
}

if (!function_exists('marketing_sitemap_pages')) {
    /**
     * Canonical marketing pages for XML sitemap (excludes 301 redirect sources).
     *
     * @return array<string,array>
     */
    function marketing_sitemap_pages()
    {
        $pages = marketing_pages_data();
        $redirect_sources = marketing_redirect_source_paths();
        $out = array();
        foreach ($pages as $path => $page) {
            if (!is_array($page)) {
                continue;
            }
            $norm = marketing_normalize_path($path);
            if (isset($redirect_sources[$norm])) {
                continue;
            }
            $out[$path] = $page;
        }
        return $out;
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

        $out = '<ul>';
        foreach ($lines as $line) {
            $out .= '<li>'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8').'</li>';
        }

        return $out.'</ul>';
    }
}

if (!function_exists('marketing_normalize_faq_answer_content')) {
    /**
     * Strip legacy WP wrappers and normalize FAQ answer markup for cards.
     *
     * @param string $html
     */
    function marketing_normalize_faq_answer_content($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $previous = '';
        while ($html !== $previous) {
            $previous = $html;
            $html = preg_replace('/<div class="clearfix">\s*(.*?)\s*<\/div>/is', '$1', $html);
            $html = preg_replace(
                '/<div class="wpb_text_column[^"]*"[^>]*>\s*<div class="wpb_wrapper">\s*(.*?)\s*<\/div>\s*<\/div>/is',
                '$1',
                $html
            );
        }

        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (preg_match('/<ul[^>]*>/i', $html) && preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $html, $listItems)) {
            $out = '<ul>';
            foreach ($listItems[1] as $item) {
                $line = trim(html_entity_decode(strip_tags($item), ENT_QUOTES, 'UTF-8'));
                if ($line !== '') {
                    $out .= '<li>'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8').'</li>';
                }
            }

            return $out.'</ul>';
        }

        return marketing_normalize_accordion_answer($html);
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
            '/<li>\s*(?:<div[^>]*>\s*)?<h3>(.*?)<\/h3>\s*(?:<\/div>\s*)?<div class="clearfix">([\s\S]*?)<\/div>\s*<\/li>/is',
            (string) $html,
            $matches,
            PREG_SET_ORDER
        )) {
            return $items;
        }
        foreach ($matches as $row) {
            $q = trim(html_entity_decode(strip_tags($row[1]), ENT_QUOTES, 'UTF-8'));
            $a = marketing_normalize_faq_answer_content($row[2]);
            if ($q !== '' && $a !== '') {
                $items[] = array('q' => $q, 'a' => $a);
            }
        }
        return $items;
    }
}

if (!function_exists('marketing_faq_answer_html')) {
    /**
     * Normalize FAQ answer to HTML (paragraph or bullet list).
     *
     * @param string $html Plain text or legacy markup
     */
    function marketing_faq_answer_html($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        if (preg_match('/<[a-z][^>]*>/i', $html)) {
            return marketing_normalize_faq_answer_content($html);
        }

        return '<p>'.htmlspecialchars($html, ENT_QUOTES, 'UTF-8').'</p>';
    }
}

if (!function_exists('marketing_normalize_faq_items')) {
    /** @param array<int, array{q?:string,a?:string}> $items @return array<int, array{q:string,a:string}> */
    function marketing_normalize_faq_items(array $items)
    {
        $out = array();
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            $q = isset($row['q']) ? trim((string) $row['q']) : '';
            $a = isset($row['a']) ? trim((string) $row['a']) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $out[] = array(
                'q' => $q,
                'a' => marketing_faq_answer_html($a),
            );
        }
        return $out;
    }
}

if (!function_exists('marketing_render_faq_cards_html')) {
    /** @param array<int, array{q:string,a:string}> $items */
    function marketing_render_faq_cards_html(array $items)
    {
        if ($items === array()) {
            return '';
        }

        $html = '<div class="ymo-faq-list">';
        foreach ($items as $index => $item) {
            $open = $index === 0 ? ' open' : '';
            $html .= '<details class="ymo-faq-card"'.$open.'>';
            $html .= '<summary class="ymo-faq-card__question">'.htmlspecialchars($item['q'], ENT_QUOTES, 'UTF-8').'</summary>';
            $html .= '<div class="ymo-faq-card__answer">'.$item['a'].'</div>';
            $html .= '</details>';
        }

        return $html.'</div>';
    }
}

if (!function_exists('marketing_render_faq_section_html')) {
    /**
     * Full FAQ block: grey section + title + white Q&amp;A cards.
     *
     * @param string $title
     * @param array<int, array{q:string,a:string}|array{q?:string,a?:string}> $items
     */
    function marketing_render_faq_section_html($title, array $items)
    {
        $items = marketing_normalize_faq_items($items);
        if ($items === array()) {
            return '';
        }

        $title = trim((string) $title);
        if ($title === '') {
            $title = 'Popular questions';
        }

        return '<section class="ymo-page-section ymo-faq-section">'
            .'<h2 class="ymo-page-section__title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h2>'
            .marketing_render_faq_cards_html($items)
            .'</section>';
    }
}

if (!function_exists('marketing_render_pricing_section_html')) {
    /**
     * Transparent pricing block — matches FAQ section shell.
     *
     * @param array<int, array{label?:string,price?:string}> $tiers
     */
    function marketing_render_pricing_section_html(array $tiers)
    {
        if ($tiers === array()) {
            return '';
        }

        $html = '<section class="ymo-page-section ymo-pricing-section">';
        $html .= '<h2 class="ymo-page-section__title">Transparent pricing</h2>';
        $html .= '<div class="ymo-page-section__panel ymo-pricing-table">';
        $html .= '<table><thead><tr>';
        $html .= '<th scope="col">Car type / variant</th>';
        $html .= '<th scope="col" class="ymo-pricing-table__price">Price (from)</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($tiers as $tier) {
            if (!is_array($tier) || empty($tier['label'])) {
                continue;
            }
            $html .= '<tr>';
            $html .= '<td>'.htmlspecialchars((string) $tier['label'], ENT_QUOTES, 'UTF-8').'</td>';
            $html .= '<td class="ymo-pricing-table__price">'.htmlspecialchars((string) ($tier['price'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= '<p class="ymo-page-section__note">Final price confirmed before service begins. No hidden charges.</p>';
        $html .= '</section>';

        return $html;
    }
}

if (!function_exists('marketing_render_content_section_html')) {
    /**
     * Grey section + white panel for prose content (e.g. About YMO beside FAQ).
     *
     * @param string $title
     * @param string $inner_html Safe HTML fragment
     */
    function marketing_render_content_section_html($title, $inner_html)
    {
        $title = trim((string) $title);
        $inner_html = trim((string) $inner_html);
        if ($title === '' || $inner_html === '') {
            return '';
        }

        return '<section class="ymo-page-section ymo-content-panel">'
            .'<h2 class="ymo-page-section__title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h2>'
            .'<div class="ymo-page-section__panel ymo-content-panel__body">'.$inner_html.'</div>'
            .'</section>';
    }
}

if (!function_exists('marketing_detach_about_faq_row_from_body')) {
    /**
     * Move About YMO + inline FAQ row into supplemental aside + faq[].
     *
     * @param string $body
     * @return array{body:string,aside_html:string}
     */
    function marketing_detach_about_faq_row_from_body($body)
    {
        $body = (string) $body;
        if ($body === '' || stripos($body, 'About YMO') === FALSE) {
            return array('body' => $body, 'aside_html' => '');
        }

        if (!preg_match(
            '/<div class="ymo-content-section[^"]*">\s*<div class="row g-4 g-lg-5">\s*<div class="col-lg-6">\s*'
            .'<h2 class="md-headline-md mb-3">About YMO<\/h2>([\s\S]*?)<\/div>\s*<div class="col-lg-6">[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/i',
            $body,
            $matches
        )) {
            return array('body' => $body, 'aside_html' => '');
        }

        $aside_html = marketing_render_content_section_html('About YMO', $matches[1]);
        $body = preg_replace(
            '/<div class="ymo-content-section[^"]*">\s*<div class="row g-4 g-lg-5">\s*<div class="col-lg-6">\s*'
            .'<h2 class="md-headline-md mb-3">About YMO<\/h2>[\s\S]*?<\/div>\s*<div class="col-lg-6">[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/i',
            '',
            $body,
            1
        );

        return array(
            'body'       => trim($body),
            'aside_html' => $aside_html,
        );
    }
}

if (!function_exists('marketing_detach_faq_sections_from_body')) {
    /**
     * Move inline FAQ sections out of body so they can sit beside pricing.
     *
     * @param string $body
     * @return array{body:string,faq_html:string}
     */
    function marketing_detach_faq_sections_from_body($body)
    {
        $body = (string) $body;
        $sections = array();

        if ($body !== '' && preg_match_all(
            '/<section class="ymo-page-section ymo-faq-section">[\s\S]*?<\/section>|<section class="ymo-faq-section">[\s\S]*?<\/section>/i',
            $body,
            $matches
        )) {
            $sections = $matches[0];
            $body = preg_replace(
                '/<section class="ymo-page-section ymo-faq-section">[\s\S]*?<\/section>|<section class="ymo-faq-section">[\s\S]*?<\/section>/i',
                '',
                $body
            );
        }

        return array(
            'body'     => trim($body),
            'faq_html' => implode("\n", $sections),
        );
    }
}

if (!function_exists('marketing_render_accordion_html')) {
    /** @param array<int, array{q:string,a:string}> $items @deprecated Use marketing_render_faq_cards_html */
    function marketing_render_accordion_html(array $items)
    {
        return marketing_render_faq_cards_html(marketing_normalize_faq_items($items));
    }
}

if (!function_exists('marketing_normalize_body_faq_sections')) {
    /**
     * Replace legacy WP accordion FAQ blocks in page body with card layout.
     *
     * @param string $body
     */
    function marketing_normalize_body_faq_sections($body)
    {
        $body = (string) $body;
        if ($body === '' || (stripos($body, 'accordion') === FALSE && stripos($body, 'Popular questions') === FALSE)) {
            return $body;
        }

        $patterns = array(
            '/<h([234])[^>]*>\s*Popular questions\s*<\/h\1>\s*<ul class="accordion[^"]*">([\s\S]*?)<\/ul>/i',
            '/<h([234])[^>]*>\s*Frequently asked questions\s*<\/h\1>\s*<ul class="accordion[^"]*">([\s\S]*?)<\/ul>/i',
            '/<h([234])[^>]*>\s*POPULAR QUESTIONS\s*<\/h\1>\s*<ul class="accordion[^"]*">([\s\S]*?)<\/ul>/i',
        );

        foreach ($patterns as $pattern) {
            $body = preg_replace_callback(
                $pattern,
                function ($match) {
                    $title = trim(html_entity_decode(strip_tags($match[0]), ENT_QUOTES, 'UTF-8'));
                    if (preg_match('/<h[234][^>]*>\s*(.*?)\s*<\/h/i', $match[0], $heading)) {
                        $title = trim(html_entity_decode(strip_tags($heading[1]), ENT_QUOTES, 'UTF-8'));
                    }
                    $items = marketing_extract_accordion_items('<ul class="accordion">'.$match[2].'</ul>');
                    $section = marketing_render_faq_section_html($title, $items);

                    return $section !== '' ? $section : $match[0];
                },
                $body
            );
        }

        return $body;
    }
}

if (!function_exists('marketing_strip_embedded_faq_from_body')) {
    /**
     * Remove inline FAQ blocks from body when structured faq[] is rendered separately.
     *
     * @param string $body
     */
    function marketing_strip_embedded_faq_from_body($body)
    {
        $body = (string) $body;
        if ($body === '') {
            return $body;
        }

        $body = preg_replace('/<section class="ymo-page-section ymo-faq-section">[\s\S]*?<\/section>/i', '', $body);
        $body = preg_replace('/<section class="ymo-faq-section">[\s\S]*?<\/section>/i', '', $body);
        $body = preg_replace(
            '/<div class="col-lg-6">\s*<h2 class="md-headline-md mb-3">Popular questions<\/h2>\s*<div class="ymo-faq-list">[\s\S]*?<\/div>\s*<\/div>/i',
            '',
            $body
        );

        $patterns = array(
            '/<h([234])[^>]*>\s*Popular questions\s*<\/h\1>\s*<ul class="accordion[^"]*">[\s\S]*?<\/ul>/i',
            '/<h([234])[^>]*>\s*Frequently asked questions\s*<\/h\1>\s*<ul class="accordion[^"]*">[\s\S]*?<\/ul>/i',
            '/<h([234])[^>]*>\s*POPULAR QUESTIONS\s*<\/h\1>\s*<ul class="accordion[^"]*">[\s\S]*?<\/ul>/i',
        );

        foreach ($patterns as $pattern) {
            $body = preg_replace($pattern, '', $body);
        }

        return trim($body);
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
        $html .= '</div></div>';
        $html .= marketing_render_faq_section_html('Popular questions', $faqs);
        $html .= '</div>';

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
        if (strpos($src, '//') === 0) {
            return 'https:'.$src;
        }
        $path = ltrim($src, '/');
        if (strpos($path, 'assets/img/marketing/') === 0) {
            return base_url($path);
        }
        if (strpos($path, 'assets/') === 0) {
            return base_url($path);
        }
        if (strpos($path, 'marketing/') === 0) {
            return base_url('assets/img/'.$path);
        }
        if (strpos($path, 'revslider/') === 0) {
            return base_url('assets/img/marketing/'.$path);
        }
        return base_url('assets/img/marketing/'.$path);
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
        foreach (array('title', 'h1', 'intro', 'quick_answer', 'body') as $field) {
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

if (!function_exists('marketing_default_og_image')) {
    /** Default share/search image when a page has no explicit og_image. */
    function marketing_default_og_image()
    {
        return '/assets/img/marketing/revslider/main/image_01.jpg';
    }
}

if (!function_exists('marketing_city_hero_og_image')) {
    /** @param string $city_slug @return string */
    function marketing_city_hero_og_image($city_slug)
    {
        $city = marketing_city_by_slug($city_slug);
        if (!$city || empty($city['hero_image'])) {
            return '';
        }
        $hero = (string) $city['hero_image'];
        if (strpos($hero, '/assets/') === 0) {
            return $hero;
        }
        return '/assets/img/marketing/'.ltrim($hero, '/');
    }
}

if (!function_exists('marketing_service_og_image_by_path')) {
    /**
     * Match a marketing URL path to a service-catalog og_image.
     *
     * @param string $path
     * @return string
     */
    function marketing_service_og_image_by_path($path)
    {
        $path = marketing_normalize_path($path);
        if ($path === '' || strpos($path, 'services/') !== 0) {
            return '';
        }
        $cfg = marketing_cities_config();
        if (!isset($cfg['services']) || !is_array($cfg['services'])) {
            return '';
        }
        foreach ($cfg['services'] as $svc) {
            if (empty($svc['og_image'])) {
                continue;
            }
            if (!empty($svc['pune_slug']) && $path === marketing_normalize_path($svc['pune_slug'])) {
                return (string) $svc['og_image'];
            }
            if (!empty($svc['city_slug'])) {
                foreach (array('pune', 'indore', 'nashik') as $city) {
                    $candidate = str_replace('{city}', $city, (string) $svc['city_slug']);
                    if ($path === marketing_normalize_path($candidate)) {
                        return (string) $svc['og_image'];
                    }
                }
            }
            if (!empty($svc['pune_slug'])) {
                $slug_tail = marketing_normalize_path($svc['pune_slug']);
                if ($slug_tail !== '' && strpos($path, $slug_tail) !== FALSE) {
                    return (string) $svc['og_image'];
                }
            }
        }
        return '';
    }
}

if (!function_exists('marketing_resolve_og_image')) {
    /**
     * Resolve the best share/search image path for a marketing page.
     *
     * @param string $path
     * @param array  $page
     * @return string Absolute or site-relative /assets/... path
     */
    function marketing_resolve_og_image($path, array $page = array())
    {
        if (!empty($page['og_image'])) {
            return (string) $page['og_image'];
        }
        if (!empty($page['service_key'])) {
            $svc = marketing_service_by_key($page['service_key']);
            if ($svc && !empty($svc['og_image'])) {
                return (string) $svc['og_image'];
            }
        }
        $from_path = marketing_service_og_image_by_path($path);
        if ($from_path !== '') {
            return $from_path;
        }
        $city_slug = !empty($page['city_slug']) ? (string) $page['city_slug'] : '';
        if ($city_slug === '' && strpos((string) $path, 'locations/') === 0) {
            $parts = explode('/', trim((string) $path, '/'));
            $city_slug = isset($parts[1]) ? $parts[1] : '';
        }
        if ($city_slug !== '') {
            $city_image = marketing_city_hero_og_image($city_slug);
            if ($city_image !== '') {
                return $city_image;
            }
        }
        return marketing_default_og_image();
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
        $url = marketing_hero_image_url(marketing_resolve_og_image($path, $page));
        if ($url === '') {
            return NULL;
        }
        $url = marketing_image_preferred_url($url);
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
        if (!is_array($trust)) {
            $trust = array();
        }
        $place_id = function_exists('ymo_env') ? ymo_env('YMO_GBP_PLACE_ID') : getenv('YMO_GBP_PLACE_ID');
        if ($place_id !== FALSE && trim((string) $place_id) !== '') {
            $trust['google_place_id'] = trim((string) $place_id);
        }
        return $trust;
    }
}

if (!function_exists('marketing_gbp_review_url')) {
    /** URL for post-service Google review requests. */
    function marketing_gbp_review_url()
    {
        $trust = marketing_trust_config();
        $place_id = isset($trust['google_place_id']) ? trim((string) $trust['google_place_id']) : '';
        if ($place_id !== '') {
            return 'https://search.google.com/local/writereview?placeid='.rawurlencode($place_id);
        }
        $share = isset($trust['gbp_share_url']) ? trim((string) $trust['gbp_share_url']) : '';
        return $share !== '' ? $share : 'https://www.google.com/search?q=Your+Mechanic+Online+Pune+review';
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
        } elseif ($type === 'brand') {
            $hero_type = 'brand';
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
        if ($hero_type === 'service' || $hero_type === 'hub' || $hero_type === 'brand') {
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
        } elseif ($hero_type === 'brand') {
            $brand_label = !empty($page['brand_name']) ? trim((string) $page['brand_name']) : 'Car brand';
            $city_name = ($city && !empty($city['name'])) ? $city['name'] : 'Pune';
            $eyebrow = $brand_label.' · '.$city_name;
            $eyebrow_icon = 'directions_car';
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

        $icon = ($hero_type === 'brand') ? 'directions_car' : 'build';
        if ($hero_type !== 'brand' && !empty($page['service_key'])) {
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

        $quick_book_btn_class = ($hero_type === 'home')
            ? 'md-btn md-btn--outlined md-btn--on-dark md-btn--lg'
            : 'md-btn md-btn--tonal md-btn--lg';
        $cta_quick_book = array(
            'href'  => ymo_booking_url('quick-book'),
            'label' => 'Quick book - no login',
            'icon'  => 'send',
            'class' => $quick_book_btn_class,
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
        } elseif ($hero_type === 'brand' && $city) {
            $cta_secondary = array(
                'href'  => ymo_public_nav_url($city['hub_path']),
                'label' => 'All services in '.$city['name'],
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

        $show_phone = in_array($hero_type, array('service', 'hub', 'locality', 'brand'), TRUE);
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
            'cta_quick_book'            => $cta_quick_book,
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
        if ($type === 'brand') {
            return '0.75';
        }
        if ($type === 'locality') {
            return '0.7';
        }
        return '0.6';
    }
}

if (!function_exists('marketing_page_changefreq')) {
    /** @param array $page */
    function marketing_page_changefreq(array $page)
    {
        $type = isset($page['page_type']) ? $page['page_type'] : '';
        if ($type === 'hub') {
            return 'weekly';
        }
        if ($type === 'blog') {
            return 'monthly';
        }
        return 'monthly';
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
            if (isset($page['locality_slug']) && $page['locality_slug'] !== '') {
                $loc_label = ucwords(str_replace(array('-', '_'), ' ', $page['locality_slug']));
                if (!empty($page['locality_label'])) {
                    $loc_label = $page['locality_label'];
                }
                $crumbs[] = array('label' => $loc_label, 'url' => marketing_canonical_url($path));
            } elseif (isset($page['h1'])) {
                $crumbs[] = array('label' => $page['h1'], 'url' => marketing_canonical_url($path));
            }
            return $crumbs;
        }
        if (!empty($page['brand_slug']) && !empty($page['city_slug'])) {
            $crumbs[] = array('label' => 'Brands', 'url' => marketing_canonical_url('brands'));
            $city = marketing_city_by_slug($page['city_slug']);
            if ($city) {
                $crumbs[] = array(
                    'label' => $city['name'],
                    'url'   => marketing_canonical_url($city['hub_path']),
                );
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
        $og_path = !empty($page_meta['og_image'])
            ? (string) $page_meta['og_image']
            : marketing_resolve_og_image($path, $page_meta);
        $og_url = marketing_hero_image_url($og_path);
        $image_object = ($og_url !== '')
            ? array('@type' => 'ImageObject', 'url' => $og_url)
            : NULL;
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
            'logo'        => marketing_canonical_url('assets/img/logo.png'),
            'telephone'   => $phone,
            'email'       => $email,
            'priceRange'  => '₹₹',
            'areaServed'  => $area_served,
            'openingHoursSpecification' => array(
                array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    'opens'     => '09:00',
                    'closes'    => '19:00',
                ),
            ),
        );
        if ($og_url !== '') {
            $graph[0]['image'] = $og_url;
        }

        $trust = marketing_trust_config();
        if (!empty($trust['google_rating']) && !empty($trust['review_count'])) {
            $graph[0]['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $trust['google_rating'],
                'reviewCount' => (string) (int) $trust['review_count'],
                'bestRating'  => '5',
            );
        }
        if (!empty($trust['same_as']) && is_array($trust['same_as'])) {
            $graph[0]['sameAs'] = array_values($trust['same_as']);
        } elseif (!empty($trust['gbp_share_url'])) {
            $graph[0]['sameAs'] = array($trust['gbp_share_url']);
        }

        if ($path === '') {
            $website = array(
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
            if ($og_url !== '') {
                $website['image'] = $og_url;
            }
            $graph[] = $website;
        }

        $city_slug = isset($page_meta['city_slug']) ? $page_meta['city_slug'] : '';
        $locality_slug = isset($page_meta['locality_slug']) ? $page_meta['locality_slug'] : '';
        if ($city_slug !== '') {
            $city = marketing_city_by_slug($city_slug);
            if ($city) {
                $area_name = array(array('@type' => 'City', 'name' => $city['name']));
                if ($locality_slug !== '') {
                    $loc_label = !empty($page_meta['locality_label'])
                        ? $page_meta['locality_label']
                        : ucwords(str_replace(array('-', '_'), ' ', $locality_slug));
                    $area_name[] = array('@type' => 'Place', 'name' => $loc_label.', '.$city['name']);
                }
                $local = array(
                    '@type'     => array('AutoRepair', 'LocalBusiness'),
                    '@id'       => marketing_canonical_url($city['hub_path']).'#local',
                    'name'      => $brand.' - '.$city['name'],
                    'url'       => marketing_canonical_url($city['hub_path']),
                    'telephone' => isset($city['phone']) ? $city['phone'] : $phone,
                    'email'     => isset($city['email']) ? $city['email'] : $email,
                    'address'   => isset($city['address']) ? $city['address'] : array(),
                    'areaServed'=> $area_name,
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
                if ($og_url !== '') {
                    $local['image'] = $og_url;
                }
                $graph[] = $local;
            }
        }

        if (isset($page_meta['page_type']) && $page_meta['page_type'] === 'service') {
            $area_name = 'India';
            if ($city_slug !== '' && !empty($city['name'])) {
                $area_name = $city['name'];
                if ($locality_slug !== '') {
                    $loc_label = !empty($page_meta['locality_label'])
                        ? $page_meta['locality_label']
                        : ucwords(str_replace(array('-', '_'), ' ', $locality_slug));
                    $area_name = $loc_label.', '.$city['name'];
                }
            }
            $service_node = array(
                '@type'       => 'Service',
                '@id'         => $url.'#service',
                'name'        => isset($page_meta['h1']) ? $page_meta['h1'] : '',
                'description' => isset($page_meta['meta_description']) ? $page_meta['meta_description'] : '',
                'provider'    => array('@id' => marketing_canonical_url('').'#organization'),
                'areaServed'  => $area_name,
                'url'         => $url,
            );
            if ($og_url !== '') {
                $service_node['image'] = $og_url;
            }
            $offer_price = NULL;
            if (!empty($page_meta['service_key'])) {
                foreach (marketing_service_catalog() as $cat) {
                    if ($cat['key'] === $page_meta['service_key'] && !empty($cat['price_from'])) {
                        $offer_price = (int) $cat['price_from'];
                        break;
                    }
                }
            }
            if ($offer_price === NULL && !empty($page_meta['pricing_tiers']) && is_array($page_meta['pricing_tiers'])) {
                foreach ($page_meta['pricing_tiers'] as $tier) {
                    if (empty($tier['price'])) {
                        continue;
                    }
                    if (preg_match('/₹([\d,]+)/', (string) $tier['price'], $m)) {
                        $offer_price = (int) str_replace(',', '', $m[1]);
                        break;
                    }
                }
            }
            if ($offer_price !== NULL && $offer_price > 0) {
                $service_node['offers'] = array(
                    '@type'         => 'Offer',
                    'price'         => (string) $offer_price,
                    'priceCurrency' => 'INR',
                    'priceSpecification' => array(
                        '@type'         => 'PriceSpecification',
                        'minPrice'      => (string) $offer_price,
                        'priceCurrency' => 'INR',
                    ),
                );
            }
            $graph[] = $service_node;
        }

        if (isset($page_meta['page_type']) && $page_meta['page_type'] === 'brand') {
            $area_name = 'India';
            if ($city_slug !== '' && !empty($city['name'])) {
                $area_name = $city['name'];
            }
            $brand_label = !empty($page_meta['brand_name']) ? $page_meta['brand_name'] : '';
            $brand_node = array(
                '@type'       => 'Service',
                '@id'         => $url.'#service',
                'name'        => isset($page_meta['h1']) ? $page_meta['h1'] : '',
                'description' => isset($page_meta['meta_description']) ? $page_meta['meta_description'] : '',
                'provider'    => array('@id' => marketing_canonical_url('').'#organization'),
                'areaServed'  => $area_name,
                'url'         => $url,
                'serviceType' => $brand_label !== '' ? $brand_label.' car servicing' : 'Car servicing',
            );
            if ($og_url !== '') {
                $brand_node['image'] = $og_url;
            }
            $graph[] = $brand_node;
        }

        if (isset($page_meta['page_type']) && $page_meta['page_type'] === 'blog') {
            $article = array(
                '@type'         => 'BlogPosting',
                '@id'           => $url.'#article',
                'headline'      => isset($page_meta['h1']) ? $page_meta['h1'] : '',
                'description'   => isset($page_meta['meta_description']) ? $page_meta['meta_description'] : '',
                'url'           => $url,
                'datePublished' => !empty($page_meta['updated_at']) ? $page_meta['updated_at'] : date('Y-m-d'),
                'dateModified'  => !empty($page_meta['updated_at']) ? $page_meta['updated_at'] : date('Y-m-d'),
                'author'        => array(
                    '@type' => 'Organization',
                    'name'  => $brand,
                    'url'   => marketing_canonical_url(''),
                ),
                'publisher'     => array(
                    '@type' => 'Organization',
                    'name'  => $brand,
                    'url'   => marketing_canonical_url(''),
                ),
            );
            if ($og_url !== '') {
                $article['image'] = $og_url;
            }
            $graph[] = $article;
        }

        if ($path !== '') {
            $web_page = array(
                '@type'       => 'WebPage',
                '@id'         => $url.'#webpage',
                'url'         => $url,
                'name'        => isset($page_meta['h1']) ? $page_meta['h1'] : '',
                'description' => isset($page_meta['meta_description']) ? $page_meta['meta_description'] : '',
                'isPartOf'    => array('@id' => marketing_canonical_url('').'#website'),
                'about'       => array('@id' => marketing_canonical_url('').'#organization'),
            );
            if ($image_object !== NULL) {
                $web_page['primaryImageOfPage'] = $image_object;
                $web_page['image'] = $og_url;
            }
            $graph[] = $web_page;
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

if (!function_exists('marketing_brand_logo_html')) {
    /**
     * Brand logo with WebP when available.
     *
     * @param array{class?:string,width?:int,height?:int,priority?:bool,lazy?:bool} $opts
     * @return string
     */
    function marketing_brand_logo_html(array $opts = array())
    {
        $class = isset($opts['class']) ? (string) $opts['class'] : 'ymo-brand-logo';
        $width = isset($opts['width']) ? (int) $opts['width'] : 120;
        $height = isset($opts['height']) ? (int) $opts['height'] : 44;
        $lazy = !empty($opts['lazy']);
        $priority = !empty($opts['priority']);
        $ci = &get_instance();
        $brand = $ci->config->item('ymo_brand_name');
        $png = base_url('assets/img/logo.png');
        $webp = '';
        if (is_file(FCPATH.'assets/img/logo.webp')) {
            $webp = base_url('assets/img/logo.webp');
        }
        $attrs = 'class="'.html_escape($class).'"'
            .' alt="'.html_escape($brand).'"'
            .' width="'.(int) $width.'"'
            .' height="'.(int) $height.'"'
            .' decoding="async"'
            .($lazy ? ' loading="lazy"' : '')
            .($priority ? ' fetchpriority="high"' : '');
        if ($webp !== '') {
            return '<picture><source srcset="'.html_escape($webp).'" type="image/webp">'
                .'<img src="'.html_escape($png).'" '.$attrs.'></picture>';
        }
        return '<img src="'.html_escape($png).'" '.$attrs.'>';
    }
}

if (!function_exists('marketing_image_responsive_srcset')) {
    /**
     * Build srcset for local WebP hero images with -768 / -1280 variants.
     *
     * @param string $url Absolute image URL (prefer WebP)
     * @return array{src:string,srcset:string,sizes:string,mobile:string,desktop:string}
     */
    function marketing_image_responsive_srcset($url)
    {
        $url = trim((string) $url);
        $empty = array('src' => $url, 'srcset' => '', 'sizes' => '100vw', 'mobile' => '', 'desktop' => '');
        if ($url === '') {
            return $empty;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path || !preg_match('/\.webp$/i', $path)) {
            $empty['desktop'] = $url;
            return $empty;
        }
        $ci = &get_instance();
        $base = rtrim($ci->config->item('base_url'), '/');
        $dir = dirname($path);
        $stem = pathinfo($path, PATHINFO_FILENAME);
        $local_base = FCPATH.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $parts = array();
        $mobile = '';
        foreach (array(768, 1280) as $width) {
            $variant_path = $dir.'/'.$stem.'-'.$width.'.webp';
            $local = FCPATH.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $variant_path), DIRECTORY_SEPARATOR);
            if (is_file($local)) {
                $parts[] = $base.$variant_path.' '.$width.'w';
                if ($width === 768) {
                    $mobile = $base.$variant_path;
                }
            }
        }
        if (is_file($local_base)) {
            $full_w = 1920;
            if (function_exists('getimagesize')) {
                $info = @getimagesize($local_base);
                if ($info && !empty($info[0])) {
                    $full_w = (int) $info[0];
                }
            }
            $parts[] = $base.$path.' '.$full_w.'w';
        }
        $srcset = implode(', ', $parts);
        $src = $mobile !== '' ? $mobile : ($parts ? explode(' ', $parts[count($parts) - 1])[0] : $base.$path);
        return array(
            'src'      => $src,
            'srcset'   => $srcset,
            'sizes'    => '100vw',
            'mobile'   => $mobile !== '' ? $mobile : $src,
            'desktop'  => $base.$path,
        );
    }
}

if (!function_exists('marketing_lcp_preload_hints')) {
    /**
     * @param string $og_image
     * @return array{mobile:string,desktop:string,type:string}
     */
    function marketing_lcp_preload_hints($og_image)
    {
        $url = marketing_hero_image_url($og_image);
        $webp = marketing_image_webp_url($url);
        if ($webp === '') {
            $webp = marketing_image_preferred_url($url);
        }
        $resp = marketing_image_responsive_srcset($webp);
        return array(
            'mobile'  => $resp['mobile'],
            'desktop' => $resp['desktop'],
            'type'    => 'image/webp',
        );
    }
}

if (!function_exists('marketing_image_webp_url')) {
    /**
     * WebP sibling URL when the file exists, else empty string.
     *
     * @param string $url
     * @return string
     */
    function marketing_image_webp_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path || !preg_match('/\.(jpe?g|png)$/i', $path)) {
            return '';
        }
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
        $local = FCPATH.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $webp_path), DIRECTORY_SEPARATOR);
        if (!is_file($local)) {
            return '';
        }
        $ci = &get_instance();
        return rtrim($ci->config->item('base_url'), '/').$webp_path;
    }
}

if (!function_exists('marketing_image_preferred_url')) {
    /**
     * Prefer a local WebP sibling when it exists (same path, .webp extension).
     *
     * @param string $url Absolute or site-relative image URL
     * @return string
     */
    function marketing_image_preferred_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path || !preg_match('/\.(jpe?g|png)$/i', $path)) {
            return $url;
        }
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
        $local = FCPATH.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $webp_path), DIRECTORY_SEPARATOR);
        if (is_file($local)) {
            $ci = &get_instance();
            return rtrim($ci->config->item('base_url'), '/').$webp_path;
        }
        return $url;
    }
}

if (!function_exists('marketing_optimize_content_images')) {
    /**
     * Lazy-load below-fold images and add dimensions when missing.
     *
     * @param string $html
     * @return string
     */
    function marketing_optimize_content_images($html)
    {
        $html = (string) $html;
        if ($html === '' || stripos($html, '<img') === FALSE) {
            return $html;
        }
        return preg_replace_callback('/<img\b[^>]*>/i', function ($m) {
            $tag = $m[0];
            if (stripos($tag, 'loading=') === FALSE) {
                $tag = preg_replace('/<img/i', '<img loading="lazy"', $tag, 1);
            }
            if (stripos($tag, 'decoding=') === FALSE) {
                $tag = preg_replace('/<img/i', '<img decoding="async"', $tag, 1);
            }
            if (stripos($tag, 'width=') === FALSE) {
                $tag = preg_replace('/<img/i', '<img width="800"', $tag, 1);
            }
            if (stripos($tag, 'height=') === FALSE) {
                $tag = preg_replace('/<img/i', '<img height="533"', $tag, 1);
            }
            if (preg_match('/\ssrc=(["\'])([^"\']+)\1/i', $tag, $src_m)) {
                $preferred = marketing_image_preferred_url($src_m[2]);
                if ($preferred !== $src_m[2]) {
                    $tag = str_replace($src_m[0], ' src="'.html_escape($preferred, ENT_QUOTES).'"', $tag);
                }
            }
            return $tag;
        }, $html);
    }
}

if (!function_exists('marketing_body_fix_heading_order')) {
    /**
     * Bump h3–h6 up one level in migrated body HTML (hero already provides h1).
     *
     * @param string $html
     * @return string
     */
    function marketing_body_fix_heading_order($html)
    {
        $html = (string) $html;
        if ($html === '' || !preg_match('/<h[3-6]\b/i', $html)) {
            return $html;
        }
        return preg_replace_callback('/<\/?h([3-6])\b/i', function ($m) {
            $level = (int) $m[1] - 1;
            return str_replace($m[1], (string) $level, $m[0]);
        }, $html);
    }
}

if (!function_exists('marketing_llms_txt')) {
    /** @return string llms.txt per https://llmstxt.org */
    function marketing_llms_txt()
    {
        $brand = 'Your Mechanic Online';
        $home  = marketing_canonical_url('');
        $lines = array(
            '# '.$brand,
            '> Doorstep car servicing in Pune, Indore, and Nashik — free pick-up, transparent pricing from ₹1,999, workshop-grade repairs.',
            '',
            'Use these canonical URLs when citing '.$brand.' (YMO). Booking: '.ymo_booking_url('packages'),
            '',
            '## Home',
            '- ['.$brand.' homepage]('.$home.'): Car servicing in Pune, Indore & Nashik',
            '',
            '## Cities',
        );
        foreach (array('pune', 'indore', 'nashik') as $slug) {
            $city = marketing_city_by_slug($slug);
            if (!$city) {
                continue;
            }
            $lines[] = '- [Car servicing in '.$city['name'].']('.marketing_canonical_url($city['hub_path']).'): City hub with neighbourhoods and services';
        }
        $lines[] = '';
        $lines[] = '## Key locality pages';
        if (function_exists('marketing_pune_locality_defs')) {
            foreach (marketing_pune_locality_defs() as $def) {
                $lines[] = '- ['.$def[2].', Pune]('.marketing_canonical_url($def[0]).')';
            }
        }
        $lines[] = '';
        $lines[] = '## Services';
        $lines[] = '- [Service catalogue]('.marketing_canonical_url('services').'): All car services across Pune, Indore, Nashik';
        foreach (marketing_pages_data() as $path => $page) {
            if (strpos($path, 'services/') !== 0 || !is_array($page)) {
                continue;
            }
            if (strpos($path, '-in-indore') !== FALSE || strpos($path, '-in-nashik') !== FALSE) {
                continue;
            }
            $label = !empty($page['h1']) ? $page['h1'] : $path;
            $lines[] = '- ['.$label.']('.marketing_canonical_url($path).')';
        }
        $lines[] = '';
        $lines[] = '## Optional';
        $lines[] = '- [About us]('.marketing_canonical_url('about-us').')';
        $lines[] = '- [Luxury car service Pune]('.marketing_canonical_url('premium-luxury-car-service-pune').')';
        $lines[] = '- [Why choose YMO]('.marketing_canonical_url('why-choose-ymo').')';
        $lines[] = '';
        $lines[] = '## Contact';
        $lines[] = '- [Contact us]('.marketing_canonical_url('contact-us').'): Book or enquire online';
        $lines[] = '- Phone: +91-7744-065904';
        $lines[] = '- Email: contactus@yourmechaniconline.com';
        $lines[] = '- Instagram: https://www.instagram.com/yourmechaniconline_ymo/';
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
            if (!empty($city['localities']) && is_array($city['localities'])) {
                foreach ($city['localities'] as $loc) {
                    $item['children'][] = array(
                        'label' => $loc['label'],
                        'slug'  => $loc['slug'],
                    );
                }
            }
            if ($city_slug === 'pune') {
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

if (!function_exists('marketing_home_brand_cards')) {
    /** @return array<int,array{title:string,slug:string}> */
    function marketing_home_brand_cards()
    {
        $cards = array();
        foreach (marketing_brands_config() as $brand) {
            if (empty($brand['active']) || empty($brand['slug'])) {
                continue;
            }
            $cards[] = array(
                'title' => isset($brand['brand_name']) ? $brand['brand_name'] : '',
                'slug'  => $brand['slug'].'-car-service-in-pune',
            );
        }
        return $cards;
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

if (!function_exists('marketing_strip_locality_services_section')) {
    /**
     * Remove inline service price list when pricing table is rendered separately.
     *
     * @param string $body
     */
    function marketing_strip_locality_services_section($body)
    {
        $body = (string) $body;
        if ($body === '') {
            return $body;
        }

        return trim(preg_replace(
            '/<div class="ymo-content-section[^"]*"[^>]*>\s*<h2 class="md-headline-md[^"]*"[^>]*>\s*Services in [^<]+<\/h2>[\s\S]*?<\/div>/i',
            '',
            $body,
            1
        ));
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
