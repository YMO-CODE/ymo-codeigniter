<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends Marketing_Controller
{
    public function show()
    {
        $request_path = marketing_normalize_path($this->uri->uri_string());
        $consolidated = marketing_lookup_redirect($request_path);
        if ($consolidated !== NULL && $consolidated !== '') {
            marketing_redirect_to($consolidated, 301);
        }

        $resolved = marketing_resolve_page_path($this->uri->uri_string());
        if ($resolved['redirect'] && $resolved['canonical'] !== ''
            && $resolved['canonical'] !== $request_path) {
            marketing_redirect_to($resolved['canonical'], 301);
        }

        $path = $resolved['key'];
        $pages = marketing_pages_data();

        if ($path === '' || !isset($pages[$path])) {
            $target = marketing_lookup_redirect(marketing_normalize_path($this->uri->uri_string()));
            if ($target !== NULL && $target !== '') {
                marketing_redirect_to($target, 301);
            }
            show_404();
        }

        $page = $pages[$path];
        $this->page_meta = array(
            'title'            => $page['title'],
            'meta_title'       => isset($page['meta_title']) ? $page['meta_title'] : '',
            'meta_description' => $page['meta_description'],
            'h1'               => $page['h1'],
            'intro'            => isset($page['intro']) ? $page['intro'] : '',
            'body'             => isset($page['body']) ? $page['body'] : '',
            'page_type'        => isset($page['page_type']) ? $page['page_type'] : '',
            'canonical_path'   => $path,
            'city_slug'        => isset($page['city_slug']) ? $page['city_slug'] : '',
            'locality_slug'    => isset($page['locality_slug']) ? $page['locality_slug'] : '',
            'locality_label'   => isset($page['locality_label']) ? $page['locality_label'] : '',
            'service_key'      => isset($page['service_key']) ? $page['service_key'] : '',
            'brand_slug'       => isset($page['brand_slug']) ? $page['brand_slug'] : '',
            'brand_name'       => isset($page['brand_name']) ? $page['brand_name'] : '',
            'quick_answer'     => isset($page['quick_answer']) ? $page['quick_answer'] : '',
            'faq'              => isset($page['faq']) ? $page['faq'] : array(),
            'pricing_tiers'    => isset($page['pricing_tiers']) ? $page['pricing_tiers'] : array(),
            'og_image'         => isset($page['og_image']) ? $page['og_image'] : '',
            'updated_at'       => isset($page['updated_at']) ? $page['updated_at'] : '',
        );
        $view = isset($page['view']) ? $page['view'] : 'marketing/page';
        $body = isset($page['body']) ? $page['body'] : '';
        $hero_split = ymo_marketing_split_body_hero($body);
        if ($hero_split['hero'] !== NULL) {
            $body = $hero_split['body'];
        }

        $page_type = isset($page['page_type']) ? $page['page_type'] : '';
        $city_slug = isset($page['city_slug']) ? $page['city_slug'] : '';
        $locality_slug = isset($page['locality_slug']) ? $page['locality_slug'] : '';
        $service_catalog = !empty($page['service_catalog']) || $path === 'services';
        $service_catalog_heading = isset($page['service_catalog_heading']) ? $page['service_catalog_heading'] : '';

        if ($page_type === 'service') {
            $body = marketing_normalize_service_body($body);
        }

        if ($page_type === 'locality') {
            $resolved_locality = marketing_resolve_locality_page($path, $page);
            if ($city_slug === '') {
                $city_slug = $resolved_locality['city_slug'];
            }
            if ($locality_slug === '') {
                $locality_slug = $resolved_locality['locality_slug'];
            }
            $page['locality_slug'] = $locality_slug;
            $page['city_slug'] = $city_slug;

            $body = marketing_strip_locality_services_list($body);
            $body = marketing_normalize_locality_body($body);

            if ($locality_slug !== '') {
                $service_catalog = TRUE;
                if ($service_catalog_heading === '') {
                    $service_catalog_heading = marketing_locality_service_catalog_heading($page);
                }
            }
        }

        $this->render_marketing($view, array(
            'page'                    => $page,
            'booking_url'             => ymo_booking_url('packages'),
            'body'                    => $body,
            'pricing_tiers'           => isset($page['pricing_tiers']) ? $page['pricing_tiers'] : array(),
            'service_catalog'         => $service_catalog,
            'service_catalog_heading' => $service_catalog_heading,
            'service_catalog_city'    => $city_slug !== '' ? $city_slug : 'pune',
        ));
    }
}
