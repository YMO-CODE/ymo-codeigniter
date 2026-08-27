<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Internal linking, crawl hygiene, and orphan-prevention for marketing pages.
 */

if (!function_exists('marketing_enforce_canonical_path')) {
    /** Redirect trailing-slash URLs to no-slash canonical (301). */
    function marketing_enforce_canonical_path()
    {
        $ci = &get_instance();
        $uri = (string) $ci->uri->uri_string();
        if ($uri === '' || substr($uri, -1) !== '/') {
            return;
        }
        marketing_redirect_to(marketing_normalize_path($uri), 301);
    }
}

if (!function_exists('marketing_seo_growth_city_hub_body')) {
    /**
     * Hub body with crawlable neighbourhood cards for any city in marketing_cities.php.
     *
     * @param string $city_slug pune|indore|nashik
     */
    function marketing_seo_growth_city_hub_body($city_slug)
    {
        $cfg = marketing_cities_config();
        if (!isset($cfg[$city_slug]) || empty($cfg[$city_slug]['localities'])) {
            return '';
        }
        $city = $cfg[$city_slug];
        $city_name = $city['name'];
        $cards = array();
        foreach ($city['localities'] as $loc) {
            if (empty($loc['slug']) || empty($loc['label'])) {
                continue;
            }
            $cards[] = array(
                $loc['label'],
                '/'.$loc['slug'],
                'Car servicing in '.$loc['label'].' — free pick-up and workshop-grade repairs.',
            );
        }
        $cards_html = marketing_seo_growth_neighbourhood_cards($cards);
        $service_link = '/services/complete-car-servicing-in-'.$city_slug;
        if ($city_slug === 'pune') {
            $service_link = '/services/complete-car-servicing';
        }

        return '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">'
            .$city_name.' neighbourhoods we serve</h2>'
            .'<p class="md-body-md mb-4">Doorstep car service, AC repair, denting, and periodic maintenance with free pick-up across '
            .$city_name.'.</p>'
            .$cards_html
            .'</div>'
            .marketing_seo_growth_why_city_block(
                $city_name,
                'Book online, get free pick-up, and receive WhatsApp updates with photos while your car is at our workshop.',
                $service_link
            );
    }
}

if (!function_exists('marketing_locality_internal_links_html')) {
    /**
     * Hub back-link + sibling localities for locality pages.
     *
     * @param string $path     canonical path
     * @param string $city_slug
     * @param string $locality_slug
     */
    function marketing_locality_internal_links_html($path, $city_slug, $locality_slug = '')
    {
        $cfg = marketing_cities_config();
        if ($city_slug === '' || !isset($cfg[$city_slug])) {
            return '';
        }
        $city = $cfg[$city_slug];
        $city_name = $city['name'];
        $hub = !empty($city['hub_path']) ? $city['hub_path'] : 'locations/'.$city_slug;
        $html = '<div class="ymo-content-section mb-5 ymo-internal-links"><h2 class="md-headline-md mb-3">More in '
            .html_escape($city_name).'</h2><ul class="md-body-md">';
        $html .= '<li class="mb-2"><a href="/'.html_escape($hub).'">Car servicing in '
            .html_escape($city_name).' — all areas</a></li>';
        $html .= '<li class="mb-2"><a href="/services">Service catalogue</a></li>';
        if ($city_slug === 'pune') {
            $html .= '<li class="mb-2"><a href="/services/complete-car-servicing">Complete car servicing in Pune</a></li>';
        } else {
            $html .= '<li class="mb-2"><a href="/services/complete-car-servicing-in-'
                .html_escape($city_slug).'">Complete car servicing in '
                .html_escape($city_name).'</a></li>';
        }

        $siblings = array();
        $current = marketing_normalize_path($path);
        if (!empty($city['localities']) && is_array($city['localities'])) {
            foreach ($city['localities'] as $loc) {
                if (empty($loc['slug']) || marketing_normalize_path($loc['slug']) === $current) {
                    continue;
                }
                $siblings[] = '<a href="/'.html_escape($loc['slug']).'">'
                    .html_escape($loc['label']).'</a>';
                if (count($siblings) >= 4) {
                    break;
                }
            }
        }
        if ($siblings !== array()) {
            $html .= '<li class="mb-0">Nearby: '.implode(' · ', $siblings).'</li>';
        } else {
            $html .= '</ul></div>';
            return $html;
        }
        $html .= '</ul></div>';
        return $html;
    }
}

if (!function_exists('marketing_service_city_variant_links_html')) {
    /**
     * Cross-links between Pune-default service pages and Indore/Nashik variants.
     *
     * @param array $page
     * @param string $path
     */
    function marketing_service_city_variant_links_html(array $page, $path)
    {
        if (empty($page['service_key'])) {
            return '';
        }
        $key = $page['service_key'];
        $cfg = marketing_cities_config();
        if (empty($cfg['services']) || !is_array($cfg['services'])) {
            return '';
        }
        $svc = NULL;
        foreach ($cfg['services'] as $row) {
            if (isset($row['key']) && $row['key'] === $key) {
                $svc = $row;
                break;
            }
        }
        if ($svc === NULL) {
            return '';
        }
        $links = array();
        $current = marketing_normalize_path($path);
        foreach (array(
            'pune'   => isset($svc['pune_slug']) ? $svc['pune_slug'] : '',
            'indore' => str_replace('{city}', 'indore', isset($svc['city_slug']) ? $svc['city_slug'] : ''),
            'nashik' => str_replace('{city}', 'nashik', isset($svc['city_slug']) ? $svc['city_slug'] : ''),
        ) as $city => $slug) {
            $slug = marketing_normalize_path($slug);
            if ($slug === '' || $slug === $current) {
                continue;
            }
            $city_cfg = marketing_city_by_slug($city);
            $name = $city_cfg ? $city_cfg['name'] : ucfirst($city);
            $links[] = '<a href="/'.html_escape($slug).'">'
                .html_escape($svc['title']).' in '.html_escape($name).'</a>';
        }
        if ($links === array()) {
            return '';
        }
        return '<div class="ymo-content-section mb-5 ymo-internal-links"><h2 class="md-headline-md mb-3">Also available in other cities</h2>'
            .'<p class="md-body-md mb-0">'.implode(' · ', $links).'.</p></div>';
    }
}

if (!function_exists('marketing_services_hub_intro_body')) {
    /** Crawlable intro block for /services hub. */
    function marketing_services_hub_intro_body()
    {
        return '<div class="ymo-content-section mb-5"><p class="md-body-md mb-3">Browse periodic servicing, AC repair, brake work, denting and painting, interior cleaning, and polishing across our three cities.</p>'
            .'<ul class="md-body-md mb-3">'
            .'<li class="mb-2"><a href="/locations/pune">Car servicing in Pune</a></li>'
            .'<li class="mb-2"><a href="/locations/indore">Car servicing in Indore</a></li>'
            .'<li class="mb-2"><a href="/locations/nashik">Car servicing in Nashik</a></li>'
            .'<li class="mb-2"><a href="/brands">Car service by brand</a></li>'
            .'<li class="mb-2"><a href="/blog/best-oil-change-service-in-pune">Oil change in Pune</a></li>'
            .'<li class="mb-2"><a href="/blog/car-denting-painting-cost-pune">Denting cost guide</a></li>'
            .'<li class="mb-0"><a href="/blog/doorstep-car-service-pune-worth-it">Doorstep service guide</a></li>'
            .'</ul></div>';
    }
}

if (!function_exists('marketing_blog_hub_body')) {
    /** Crawlable index for /blog — links every blog post in the sitemap. */
    function marketing_blog_hub_body()
    {
        $posts = array(
            array('blog/best-oil-change-service-in-pune', 'Best oil change service in Pune'),
            array('blog/car-ac-gas-recharge-cost-pune-2026', 'AC gas recharge cost guide'),
            array('blog/car-denting-painting-cost-pune', 'Denting & painting cost guide'),
            array('blog/complete-vs-periodic-car-service', 'Complete vs periodic service'),
            array('blog/doorstep-car-service-pune-worth-it', 'Doorstep car service guide'),
            array('blog/hyundai-i20-service-cost-pune', 'Hyundai i20 service cost'),
            array('blog/maruti-swift-service-cost-pune-2026', 'Maruti Swift service cost'),
            array(
                '2023/07/18/know-the-benefits-of-regular-oil-changes-for-your-car-in-summer',
                'Oil changes in summer',
            ),
        );
        $items = '';
        foreach ($posts as $i => $post) {
            $items .= '<li class="'.($i < count($posts) - 1 ? 'mb-2' : 'mb-0').'">'
                .'<a href="/'.html_escape($post[0]).'">'.html_escape($post[1]).'</a></li>';
        }

        return '<div class="ymo-content-section mb-5"><p class="md-body-md mb-3">Practical guides on servicing, costs, and booking doorstep car care in Pune.</p>'
            .'<ul class="md-body-md mb-0">'.$items.'</ul></div>';
    }
}

if (!function_exists('marketing_page_internal_links_append')) {
    /**
     * Append contextual internal links to page body (server-rendered).
     *
     * @param string $path
     * @param array  $page
     * @param string $body
     */
    function marketing_page_internal_links_append($path, array $page, $body)
    {
        $page_type = isset($page['page_type']) ? $page['page_type'] : '';
        $city_slug = isset($page['city_slug']) ? $page['city_slug'] : '';
        $locality_slug = isset($page['locality_slug']) ? $page['locality_slug'] : '';

        if ($path === 'services' && trim(strip_tags((string) $body)) === '') {
            $body = marketing_services_hub_intro_body();
        }

        if ($path === 'blog' && trim(strip_tags((string) $body)) === '') {
            $body = marketing_blog_hub_body();
        }

        if ($page_type === 'locality' && $city_slug !== '') {
            $block = marketing_locality_internal_links_html($path, $city_slug, $locality_slug);
            if ($block !== '' && strpos($body, 'ymo-internal-links') === FALSE) {
                $body .= $block;
            }
        }

        if ($page_type === 'service' && $city_slug !== '' && $locality_slug !== '') {
            $block = marketing_locality_internal_links_html($path, $city_slug, $locality_slug);
            if ($block !== '' && strpos($body, 'ymo-internal-links') === FALSE) {
                $body .= $block;
            }
        }

        if ($page_type === 'service' && !empty($page['service_key'])) {
            $block = marketing_service_city_variant_links_html($page, $path);
            if ($block !== '' && strpos($body, 'Also available in other cities') === FALSE) {
                $body .= $block;
            }
        }

        return $body;
    }
}

if (!function_exists('marketing_footer_extra_link_sections')) {
    /** @return array<int,array{title:string,links:array<int,array{label:string,slug:string}}>} */
    function marketing_footer_extra_link_sections()
    {
        $sections = array(
            array(
                'title' => 'Popular services',
                'links' => array(
                    array('label' => 'All services', 'slug' => 'services'),
                    array('label' => 'Complete car servicing', 'slug' => 'services/complete-car-servicing'),
                    array('label' => 'Denting & painting', 'slug' => 'services/car-denting-and-painting-3000'),
                    array('label' => 'AC servicing', 'slug' => 'services/car-air-conditioner-servicing-in-pune'),
                    array('label' => 'Brake repair', 'slug' => 'services/car-brake-repair'),
                ),
            ),
        );
        $cfg = marketing_cities_config();
        foreach (array('pune', 'indore', 'nashik') as $city_slug) {
            if (empty($cfg[$city_slug]['localities'])) {
                continue;
            }
            $links = array(
                array(
                    'label' => 'All '.$cfg[$city_slug]['name'].' areas',
                    'slug'  => $cfg[$city_slug]['hub_path'],
                ),
            );
            foreach ($cfg[$city_slug]['localities'] as $loc) {
                if (empty($loc['slug']) || empty($loc['label'])) {
                    continue;
                }
                $links[] = array('label' => $loc['label'], 'slug' => $loc['slug']);
            }
            $sections[] = array(
                'title' => $cfg[$city_slug]['name'].' areas',
                'links' => $links,
            );
        }
        $sections[] = array(
            'title' => 'Resources',
            'links' => array(
                array('label' => 'Blog', 'slug' => 'blog'),
                array('label' => 'All brands', 'slug' => 'brands'),
                array('label' => 'Why choose YMO', 'slug' => 'why-choose-ymo'),
                array('label' => 'Oil change in Pune', 'slug' => 'blog/best-oil-change-service-in-pune'),
                array('label' => 'Luxury cars Pune', 'slug' => 'premium-luxury-car-service-pune'),
                array('label' => 'Car spare parts', 'slug' => 'ymo-spares'),
            ),
        );
        return $sections;
    }
}

if (!function_exists('marketing_internal_link_outbound_map')) {
    /**
     * Static internal link graph from global chrome + known hub/card patterns.
     * Used by index-readiness audit (orphan detection).
     *
     * @return array<string,array<string,bool>> target_path => set of source paths
     */
    function marketing_internal_link_outbound_map()
    {
        static $map = NULL;
        if ($map !== NULL) {
            return $map;
        }
        $map = array();
        $add = function ($from, $to) use (&$map) {
            $to = marketing_normalize_path($to);
            $from = marketing_normalize_path($from);
            if ($to === '' || $from === $to) {
                return;
            }
            if (!isset($map[$to])) {
                $map[$to] = array();
            }
            $map[$to][$from] = TRUE;
        };

        foreach (array('', 'about-us', 'contact-us', 'privacy-policy', 'why-choose-ymo') as $global_from) {
            foreach (marketing_footer_extra_link_sections() as $section) {
                foreach ($section['links'] as $link) {
                    $add($global_from, $link['slug']);
                }
            }
            $add($global_from, 'about-us');
            $add($global_from, 'contact-us');
            $add($global_from, 'locations/pune');
            $add($global_from, 'locations/indore');
            $add($global_from, 'locations/nashik');
            $add($global_from, 'brands');
            $add($global_from, 'services');
        }

        $nav = marketing_public_nav_items();
        foreach ($nav['services'] as $svc) {
            foreach (array('', 'services') as $from) {
                $add($from, $svc['slug']);
            }
        }
        foreach ($nav['locations'] as $loc) {
            foreach (array('', 'locations/pune') as $from) {
                $add($from, $loc['slug']);
            }
            if (!empty($loc['children'])) {
                foreach ($loc['children'] as $child) {
                    $add($loc['slug'], $child['slug']);
                    $add('', $child['slug']);
                }
            }
        }

        foreach (array('pune', 'indore', 'nashik') as $city) {
            $hub = 'locations/'.$city;
            $body = marketing_seo_growth_city_hub_body($city);
            if (preg_match_all('/href="\/([^"]+)"/', $body, $m)) {
                foreach ($m[1] as $slug) {
                    $add($hub, $slug);
                    $add('', $slug);
                }
            }
        }

        $home_services = marketing_home_featured_services();
        foreach ($home_services as $svc) {
            $add('', $svc['slug']);
        }
        foreach (marketing_home_city_strip() as $city) {
            $add('', $city['hub_path']);
        }
        foreach (marketing_home_brand_cards() as $brand) {
            $add('', $brand['slug']);
        }
        $add('', 'services');
        $add('', 'brands');
        $add('', 'premium-luxury-car-service-pune');
        $add('', 'why-choose-ymo');

        foreach (marketing_sitemap_pages() as $path => $page) {
            if (!is_array($page)) {
                continue;
            }
            $body = marketing_page_internal_links_append($path, $page, isset($page['body']) ? $page['body'] : '');
            if (preg_match_all('/href="\/([^"]+)"/', $body, $m)) {
                foreach ($m[1] as $slug) {
                    $add($path, $slug);
                }
            }
        }

        return $map;
    }
}

if (!function_exists('marketing_internal_link_orphans')) {
    /** @return array<int,string> sitemap paths with no inbound internal links */
    function marketing_internal_link_orphans()
    {
        $map = marketing_internal_link_outbound_map();
        $orphans = array();
        foreach (array_keys(marketing_sitemap_pages()) as $path) {
            $norm = marketing_normalize_path($path);
            if ($norm === '') {
                continue;
            }
            if (empty($map[$norm])) {
                $orphans[] = $path;
            }
        }
        sort($orphans);
        return $orphans;
    }
}
