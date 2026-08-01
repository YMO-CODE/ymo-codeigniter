<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEO growth landing pages — neighbourhoods, service×area, blog, comparison.
 * Loaded by marketing_pages_data.php via marketing_seo_growth_pages().
 */

if (!function_exists('marketing_seo_growth_pages')) {
    /** @return array<string,array> */
    function marketing_seo_growth_pages()
    {
        static $pages = NULL;
        if ($pages !== NULL) {
            return $pages;
        }
        $pages = array();
        $today = '2026-08-01';

        $pages = array_merge($pages, marketing_seo_growth_city_hubs($today));
        $pages = array_merge($pages, marketing_seo_growth_localities($today));
        $pages = array_merge($pages, marketing_seo_growth_service_areas($today));
        $pages = array_merge($pages, marketing_seo_growth_blog_posts($today));
        $pages['why-choose-ymo'] = marketing_seo_growth_comparison_page($today);

        return $pages;
    }
}

if (!function_exists('marketing_seo_growth_locality_entry')) {
    /**
     * @param string $slug
     * @param string $city
     * @param string $loc_slug
     * @param string $loc_label
     * @param string $landmarks
     * @param string $body_extra
     * @param string $today
     * @return array
     */
    function marketing_seo_growth_locality_entry($slug, $city, $loc_slug, $loc_label, $landmarks, $body_extra, $today)
    {
        $city_name = ucfirst($city);
        $body = '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Car service in '.$loc_label.', '.$city_name.'</h2>'
            .'<p class="md-body-md mb-3">'.html_escape($body_extra).'</p>'
            .'<p class="md-body-md mb-0">Local landmarks and neighbourhoods we cover include '.html_escape($landmarks)
            .'. YMO picks up your car, services it at our workshop, and returns it when done — with WhatsApp updates and photos throughout.</p></div>'
            .'<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Services in '.$loc_label.'</h2>'
            .'<p class="md-body-md mb-3">From periodic maintenance to specialist repairs — one booking, free pick-up, transparent pricing.</p>'
            .'<ul class="md-body-md"><li class="mb-2">Complete car servicing from ₹1,999</li>'
            .'<li class="mb-2">AC repair and gas recharge</li><li class="mb-2">Brake inspection and replacement</li>'
            .'<li class="mb-2">Denting and painting from ₹3,000 per panel</li>'
            .'<li class="mb-2">Interior deep cleaning from ₹2,500</li>'
            .'<li class="mb-0">3-stage rubbing and polishing from ₹6,500</li></ul>'
            .'<p class="md-body-md mt-3 mb-0"><a href="/locations/'.$city.'">All '.$city_name.' areas</a> · '
            .'<a href="/services">Service catalogue</a> · <a href="/brands">Brands we service</a></p></div>';

        return array(
            'title'            => 'Car Servicing in '.$loc_label.', '.$city_name.' | YMO',
            'meta_description' => 'Car service in '.$loc_label.' '.$city_name.' from ₹1,999. Free pick-up, AC repair, denting, periodic maintenance. Book YMO online.',
            'h1'               => 'Car servicing in '.$loc_label.', '.$city_name,
            'intro'            => 'Expert car servicing in '.$loc_label.' with free pick-up and transparent pricing.',
            'quick_answer'     => 'YMO offers car servicing in '.$loc_label.', '.$city_name.' from ₹1,999 with free pick-up, expert mechanics, and transparent pricing.',
            'body'             => $body,
            'page_type'        => 'locality',
            'city_slug'        => $city,
            'locality_slug'    => $loc_slug,
            'locality_label'   => $loc_label,
            'service_catalog'  => TRUE,
            'updated_at'       => $today,
            'faq'              => array(
                array('q' => 'What is the cost of car servicing in '.$loc_label.'?', 'a' => 'Complete car servicing in '.$loc_label.' starts from ₹1,999 at YMO. We share upfront estimates before work begins.'),
                array('q' => 'Do you offer free pick-up in '.$loc_label.'?', 'a' => 'Yes. We collect your car from '.$loc_label.' and surrounding '.$city_name.' areas at no extra charge.'),
                array('q' => 'Which services are available in '.$loc_label.'?', 'a' => 'Periodic service, AC repair, brake work, denting, polishing, interior cleaning, and engine diagnostics.'),
            ),
            'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
            'view'             => 'marketing/page',
        );
    }
}

if (!function_exists('marketing_seo_growth_city_hubs')) {
    /** @return array<string,array> */
    function marketing_seo_growth_city_hubs($today)
    {
        $indore_cards = marketing_seo_growth_neighbourhood_cards(array(
            array('Vijay Nagar', '/car-servicing-in-vijay-nagar-indore', 'Car servicing in Vijay Nagar with free pick-up and expert mechanics.'),
            array('Palasia', '/car-servicing-in-palasia-indore', 'Trusted car care in Palasia — periodic service, AC, and denting.'),
            array('Bhawarkua', '/car-servicing-in-bhawarkua-indore', 'Affordable car services in Bhawarkua with same-day options.'),
            array('AB Road', '/car-servicing-in-ab-road-indore', 'Complete car servicing along AB Road and connecting areas.'),
            array('Nipania', '/car-servicing-in-nipania-indore', 'Doorstep pick-up for Nipania residents and nearby townships.'),
        ));
        $indore_body = '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Indore neighbourhoods we serve</h2>'
            .'<p class="md-body-md mb-4">Your Mechanic Online is among the best car service providers in Indore — affordable denting, painting, AC repair, periodic servicing, and free pick-up/drop with transparent pricing across the city.</p>'
            .$indore_cards
            .'</div>'
            .marketing_seo_growth_why_city_block('Indore', 'Indore drivers choose YMO for workshop-grade capability — not just a quick home visit. We handle denting, painting, AC compressor work, and luxury cars alongside everyday hatchback servicing.', '/services/complete-car-servicing-in-indore');

        $nashik_cards = marketing_seo_growth_neighbourhood_cards(array(
            array('College Road', '/car-servicing-in-college-road-nashik', 'Car servicing on College Road with free pick-up and expert care.'),
            array('Nashik Road', '/car-servicing-in-nashik-road', 'Affordable car services near Nashik Road railway station area.'),
            array('Panchavati', '/car-servicing-in-panchavati-nashik', 'Trusted car care in Panchavati — periodic service and AC repair.'),
            array('Dwarka', '/car-servicing-in-dwarka-nashik', 'Doorstep car servicing in Dwarka and nearby Nashik areas.'),
            array('Uday Nagar', '/car-servicing-in-uday-nagar-nashik', 'Complete car care in Uday Nagar with transparent pricing.'),
        ));
        $nashik_body = '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Nashik neighbourhoods we serve</h2>'
            .'<p class="md-body-md mb-4">Your Mechanic Online provides expert car servicing and repairs in Nashik with trained mechanics, transparent pricing, and doorstep pick-up across College Road, Nashik Road, Panchavati, and surrounding areas.</p>'
            .$nashik_cards
            .'</div>'
            .marketing_seo_growth_why_city_block('Nashik', 'Nashik drivers get full workshop services — denting, painting, AC gas recharge, engine diagnostics — with the convenience of free pick-up and WhatsApp updates, not just a mechanic at your gate.', '/services/complete-car-servicing-in-nashik');

        return array(
            'locations/indore' => array(
                'title'            => 'Car Servicing in Indore - Your Mechanic Online',
                'meta_description' => 'Affordable car servicing in Indore — Vijay Nagar, Palasia, Bhawarkua, AB Road, Nipania. Denting, AC repair, periodic service from ₹1,999 with free pick-up.',
                'h1'               => 'Car servicing in Indore',
                'intro'            => 'Professional car care and transparent pricing for Indore drivers.',
                'quick_answer'     => 'Your Mechanic Online is among the best car service providers in Indore, offering affordable denting, painting, AC repair, periodic servicing, and free pick-up/drop with transparent pricing.',
                'body'             => $indore_body,
                'page_type'        => 'hub',
                'city_slug'        => 'indore',
                'service_catalog'  => TRUE,
                'service_catalog_heading' => 'Car services in Indore',
                'updated_at'       => $today,
                'faq'              => array(
                    array('q' => 'What is the cost of car servicing in Indore?', 'a' => 'Car servicing in Indore starts from ₹1,999 for periodic maintenance. YMO shares upfront estimates for denting, AC repair, and other jobs before starting work.'),
                    array('q' => 'Do you provide same-day car service in Indore?', 'a' => 'Yes. Most maintenance and repair jobs are completed the same day. Book online or call +91-7744-065904.'),
                    array('q' => 'Which areas in Indore do you serve?', 'a' => 'We serve Vijay Nagar, Palasia, Bhawarkua, AB Road, Nipania, Deoguradia, Rau, Scheme 54, and surrounding Indore areas with free pick-up and drop.'),
                ),
                'og_image'         => '/assets/img/marketing/2022/03/974159.jpg',
                'view'             => 'marketing/page',
            ),
            'locations/nashik' => array(
                'title'            => 'Car Servicing in Nashik - Your Mechanic Online',
                'meta_description' => 'Expert car servicing in Nashik — College Road, Nashik Road, Panchavati, Dwarka, Uday Nagar. Free pick-up, AC repair, denting from ₹1,999.',
                'h1'               => 'Car servicing in Nashik',
                'intro'            => 'Expert car care and repairs for Nashik drivers with free pick-up.',
                'quick_answer'     => 'Your Mechanic Online provides expert car servicing and repairs in Nashik with trained mechanics, transparent pricing, and doorstep pick-up across College Road, Nashik Road, Uday Nagar, and surrounding areas.',
                'body'             => $nashik_body,
                'page_type'        => 'hub',
                'city_slug'        => 'nashik',
                'service_catalog'  => TRUE,
                'service_catalog_heading' => 'Car services in Nashik',
                'updated_at'       => $today,
                'faq'              => array(
                    array('q' => 'Where can I find a reliable car mechanic in Nashik?', 'a' => 'Your Mechanic Online offers trained mechanics, modern diagnostic equipment, and upfront pricing for car repair and servicing across Nashik.'),
                    array('q' => 'Do you serve Nashik Road and College Road?', 'a' => 'Yes. We provide free pick-up and drop from Nashik Road, College Road, Uday Nagar, Panchavati, and nearby neighbourhoods.'),
                    array('q' => 'What car services are available in Nashik?', 'a' => 'Complete servicing, AC repair, brake repair, denting and painting, engine diagnostics, interior cleaning, rubbing and polishing, belts and hoses, and oil changes.'),
                ),
                'og_image'         => '/assets/img/marketing/revslider/main/image_021.jpg',
                'view'             => 'marketing/page',
            ),
        );
    }
}

if (!function_exists('marketing_seo_growth_neighbourhood_cards')) {
    /** @param array<int,array{0:string,1:string,2:string}> $items */
    function marketing_seo_growth_neighbourhood_cards(array $items)
    {
        $html = '<div class="row g-4">';
        foreach ($items as $row) {
            $html .= '<div class="col-md-6 col-lg-4"><a href="'.html_escape($row[1]).'" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
                .'<span class="mi mi-xl md-icon-primary">location_on</span>'
                .'<h3 class="md-title-md mt-3 mb-1 text-dark">'.html_escape($row[0]).'</h3>'
                .'<p class="md-body-md mb-0">'.html_escape($row[2]).'</p></a></div>';
        }
        return $html.'</div>';
    }
}

if (!function_exists('marketing_seo_growth_why_city_block')) {
    function marketing_seo_growth_why_city_block($city_name, $intro, $service_link)
    {
        return '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Why choose YMO in '.$city_name.'?</h2>'
            .'<p class="md-body-md mb-4">'.html_escape($intro).'</p>'
            .'<div class="row g-4">'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">engineering</span>'
            .'<h3 class="md-title-md mt-3 mb-2">Expert technicians</h3><p class="md-body-md mb-0">Trained mechanics for all major brands.</p></div></div>'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">payments</span>'
            .'<h3 class="md-title-md mt-3 mb-2">Upfront pricing</h3><p class="md-body-md mb-0">Clear estimates from ₹1,999.</p></div></div>'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">local_shipping</span>'
            .'<h3 class="md-title-md mt-3 mb-2">Free pick-up</h3><p class="md-body-md mb-0">We collect and return your car across '.$city_name.'.</p></div></div>'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">schedule</span>'
            .'<h3 class="md-title-md mt-3 mb-2">Same-day service</h3><p class="md-body-md mb-0">Most jobs completed the same day.</p></div></div>'
            .'</div>'
            .'<p class="md-body-md mt-4 mb-0">Browse <a href="/brands">brand-specific service pages</a> or <a href="'.html_escape($service_link).'">complete car servicing in '.$city_name.'</a>.</p></div>';
    }
}

if (!function_exists('marketing_seo_growth_localities')) {
    /** @return array<string,array> */
    function marketing_seo_growth_localities($today)
    {
        $defs = array(
            array('car-servicing-in-vijay-nagar-indore', 'indore', 'vijay_nagar', 'Vijay Nagar', 'Scheme 54, Palasia Square, and BRTS corridor', "Vijay Nagar is one of Indore's busiest commercial and residential hubs. YMO picks up your car for full workshop servicing — not just a quick check at your gate."),
            array('car-servicing-in-palasia-indore', 'indore', 'palasia', 'Palasia', 'Palasia Square, MG Road, and Old Palasia lanes', "Palasia's central location means tight parking — ideal for YMO's free pick-up model across Old and New Palasia."),
            array('car-servicing-in-bhawarkua-indore', 'indore', 'bhawarkua', 'Bhawarkua', 'Ring Road junction and educational institutes', "Bhawarkua's growing residential belt deserves reliable car care without long garage queues."),
            array('car-servicing-in-ab-road-indore', 'indore', 'ab_road', 'AB Road', 'Industry House, Satya Sai Square, and AB Road flyover', "AB Road is Indore's main commercial artery — YMO serves commuters and businesses with full workshop capability."),
            array('car-servicing-in-nipania-indore', 'indore', 'nipania', 'Nipania', 'Nipania township, Super Corridor, and adjoining projects', "Nipania's fast-growing township area is home to families who prefer booking service online with free pick-up."),
            array('car-servicing-in-college-road-nashik', 'nashik', 'college_road', 'College Road', 'College Road cafes and residential complexes', 'College Road is a popular Nashik neighbourhood — YMO provides full workshop servicing with pick-up from your building.'),
            array('car-servicing-in-nashik-road', 'nashik', 'nashik_road', 'Nashik Road', 'Nashik Road station area and Bytco Point', 'Nashik Road commuters rely on YMO for dependable car care with pick-up from the station area.'),
            array('car-servicing-in-panchavati-nashik', 'nashik', 'panchavati', 'Panchavati', 'Panchavati Karanja and Godavari ghats vicinity', "Panchavati's mix of old-city lanes and newer developments needs flexible pick-up and workshop-grade repairs."),
            array('car-servicing-in-dwarka-nashik', 'nashik', 'dwarka', 'Dwarka', 'Dwarka residential zone and Adgaon connector', "Dwarka's growing residential area benefits from YMO online booking and free pick-up."),
            array('car-servicing-in-uday-nagar-nashik', 'nashik', 'uday_nagar', 'Uday Nagar', 'Uday Nagar colony and College Road proximity', 'Uday Nagar residents choose YMO for hassle-free servicing without waiting at a garage.'),
        );
        $pages = array();
        foreach ($defs as $d) {
            $pages[$d[0]] = marketing_seo_growth_locality_entry($d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $today);
        }
        return $pages;
    }
}

if (!function_exists('marketing_seo_growth_service_areas')) {
    /** @return array<string,array> */
    function marketing_seo_growth_service_areas($today)
    {
        $defs = array(
            array('car-ac-repair-in-baner', 'Car AC Repair in Baner, Pune | YMO', 'Car AC repair in Baner Pune — gas recharge, leak test, compressor check. Free pick-up.', 'Car AC repair in Baner, Pune', 'pune', 'baner', 'Baner', 'ac', "Baner's summer heat makes working AC essential. YMO includes leak detection, compressor inspection, and gas recharge at our workshop — not a portable kit at your parking spot. See <a href=\"/the-best-car-servicing-in-baner\">car servicing in Baner</a> and <a href=\"/maruti-suzuki-car-service-in-pune\">Maruti service in Pune</a>."),
            array('car-denting-and-painting-in-wakad', 'Car Denting & Painting in Wakad, Pune | YMO', 'Car denting and painting in Wakad from ₹3,000/panel. Colour matching and free pick-up.', 'Car denting & painting in Wakad, Pune', 'pune', 'wakad', 'Wakad', 'denting', "Wakad's tight parking means dents are common. YMO picks up your car for panel repair and respray in a controlled booth — from ₹3,000 per panel. See <a href=\"/affordable-car-servicing-in-wakad-pune\">car servicing in Wakad</a>."),
            array('car-polishing-in-hinjewadi', 'Car Polishing in Hinjewadi, Pune | YMO', '3-stage car polishing in Hinjewadi from ₹6,500. Restore gloss and remove fine scratches.', 'Car polishing in Hinjewadi, Pune', 'pune', 'hinjewadi', 'Hinjewadi', 'polishing', "Hinjewadi IT corridor cars face sun exposure and fine scratches. YMO's 3-stage polish from ₹6,500 requires proper workshop equipment. Pair with <a href=\"/best-car-services-hinjewadi-pune\">car servicing in Hinjewadi</a>."),
            array('car-brake-repair-in-aundh', 'Car Brake Repair in Aundh, Pune | YMO', 'Car brake repair in Aundh — pads, rotors, fluid check. Free pick-up.', 'Car brake repair in Aundh, Pune', 'pune', 'aundh', 'Aundh', 'brakes', "Squealing brakes on Aundh hill roads need proper inspection. YMO covers pad replacement, rotor assessment, and fluid checks at our workshop. See <a href=\"/car-servicing-in-aundh\">car servicing in Aundh</a>."),
            array('car-ac-repair-in-viman-nagar', 'Car AC Repair in Viman Nagar, Pune | YMO', 'Car AC repair in Viman Nagar — gas recharge, compressor service. Free pick-up.', 'Car AC repair in Viman Nagar, Pune', 'pune', 'viman_nagar', 'Viman Nagar', 'ac', "Viman Nagar summer heat strains AC systems. YMO provides full diagnostics with pick-up from residential complexes. See <a href=\"/hyundai-car-service-in-pune\">Hyundai service in Pune</a>."),
        );
        $pages = array();
        foreach ($defs as $d) {
            $body = '<div class="ymo-content-section mb-5"><p class="md-body-md">'.$d[9].'</p></div>';
            $pages[$d[0]] = array(
                'title'            => $d[1],
                'meta_description' => $d[2],
                'h1'               => $d[3],
                'intro'            => $d[3],
                'quick_answer'     => strip_tags($d[9]),
                'body'             => $body,
                'page_type'        => 'service',
                'city_slug'        => $d[4],
                'locality_slug'    => $d[5],
                'locality_label'   => $d[6],
                'service_key'      => $d[7],
                'updated_at'       => $today,
                'faq'              => array(
                    array('q' => 'Do you pick up from '.$d[6].'?', 'a' => 'Yes. Free pick-up and drop from '.$d[6].' and nearby areas.'),
                    array('q' => 'How much does this cost in '.$d[6].'?', 'a' => 'We share an upfront estimate before starting. Pricing depends on your car model and condition.'),
                ),
                'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
                'view'             => 'marketing/page',
            );
        }
        return $pages;
    }
}

if (!function_exists('marketing_seo_growth_blog_posts')) {
    /** @return array<string,array> */
    function marketing_seo_growth_blog_posts($today)
    {
        $posts = array(
            array(
                'blog/maruti-swift-service-cost-pune-2026',
                'Maruti Swift Service Cost in Pune (2026 Guide) | YMO',
                'How much does a Maruti Swift service cost in Pune in 2026? Complete guide to periodic service prices and what is included.',
                'How much does a Maruti Swift service cost in Pune? (2026 guide)',
                'Maruti Swift periodic service in Pune starts from ₹1,999 at YMO — oil change, filters, brake cleaning, AC filter, wash, and free pick-up.',
                '<div class="ymo-content-section mb-5"><p class="md-body-md">If you own a Maruti Swift in Pune, authorised centres charge a premium while local garages may skip steps. YMO offers workshop-quality work from ₹1,999 with free pick-up.</p>'
                .'<h2 class="md-headline-md mt-4 mb-3">Typical Swift service costs (2026)</h2>'
                .'<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Service</th><th>From</th></tr></thead><tbody>'
                .'<tr><td>Periodic service</td><td>₹1,999</td></tr><tr><td>AC gas recharge</td><td>₹1,500 – ₹4,000</td></tr>'
                .'<tr><td>Brake pads</td><td>₹2,000 – ₹5,000</td></tr></tbody></table></div>'
                .'<p class="md-body-md mt-3"><a href="/maruti-suzuki-car-service-in-pune">Maruti service in Pune</a> · '
                .'<a href="/services/complete-car-servicing-in-pune">Complete servicing</a></p></div>',
            ),
            array(
                'blog/car-ac-gas-recharge-cost-pune-2026',
                'Car AC Gas Recharge Cost in Pune — 2026 Guide | YMO',
                'Car AC gas recharge cost in Pune — what affects price, when to recharge, and how YMO AC service works.',
                'Car AC gas recharge cost in Pune — complete 2026 guide',
                'Car AC gas recharge in Pune typically costs ₹1,500–₹4,000 depending on gas type and leaks — YMO includes leak test and pick-up.',
                '<div class="ymo-content-section mb-5"><p class="md-body-md">Weak AC cooling usually means low gas, a leak, or a failing compressor. Topping up without fixing a leak wastes money.</p>'
                .'<h2 class="md-headline-md mt-4 mb-3">Typical price range</h2>'
                .'<div class="table-responsive"><table class="table md-body-md"><tbody>'
                .'<tr><td>Leak test + gas recharge</td><td>₹1,500 – ₹4,000</td></tr>'
                .'<tr><td>Compressor repair</td><td>₹5,000 – ₹15,000+</td></tr></tbody></table></div>'
                .'<p class="md-body-md mt-3"><a href="/services/car-air-conditioner-servicing-in-pune">AC servicing in Pune</a> · '
                .'<a href="/car-ac-repair-in-baner">AC repair in Baner</a></p></div>',
            ),
            array(
                'blog/hyundai-i20-service-cost-pune',
                'Hyundai i20 Service Cost in Pune: What to Expect | YMO',
                'Hyundai i20 service cost in Pune — periodic maintenance prices and what YMO includes from ₹1,999.',
                'Hyundai i20 service cost in Pune: what to expect',
                'Hyundai i20 periodic service in Pune starts from ₹1,999 at YMO with oil change, filters, brake check, wash, and free pick-up.',
                '<div class="ymo-content-section mb-5"><p class="md-body-md">The i20 is one of Pune\'s most popular hatchbacks. Costs depend on petrol vs diesel and mileage interval.</p>'
                .'<p class="md-body-md"><a href="/hyundai-car-service-in-pune">Hyundai car service in Pune</a></p></div>',
            ),
            array(
                'blog/car-denting-painting-cost-pune',
                'Car Denting & Painting Cost in Pune — Price per Panel | YMO',
                'Car denting and painting cost in Pune — per panel pricing from ₹3,000 and factors that affect quotes.',
                'Car denting & painting cost in Pune — price per panel explained',
                'Car denting and painting in Pune starts from ₹3,000 per panel at YMO with colour matching and free pick-up.',
                '<div class="ymo-content-section mb-5"><p class="md-body-md">YMO denting and painting starts from ₹3,000 per panel with pick-up so you do not wait at the garage.</p>'
                .'<p class="md-body-md"><a href="/services/car-denting-and-painting-3000">Denting in Pune</a> · '
                .'<a href="/car-denting-and-painting-in-wakad">Denting in Wakad</a></p></div>',
            ),
            array(
                'blog/complete-vs-periodic-car-service',
                'Complete vs Periodic Car Service: What\'s the Difference? | YMO',
                'Complete car service vs periodic service explained — what each includes and when you need which.',
                'Complete car service vs periodic service: what\'s the difference?',
                'Periodic service covers oil, filters, and basic checks from ₹1,999; complete service adds deeper items based on mileage.',
                '<div class="ymo-content-section mb-5"><p class="md-body-md">Periodic service (every 10,000 km) covers oil, filters, brake cleaning, and wash. Complete/major service at 40,000 km+ adds spark plugs, belts, and deeper inspection.</p>'
                .'<p class="md-body-md"><a href="/services/complete-car-servicing-in-pune">Book complete car servicing in Pune</a></p></div>',
            ),
            array(
                'blog/doorstep-car-service-pune-worth-it',
                'Doorstep Car Service in Pune: Is It Worth It? | YMO',
                'Doorstep car service in Pune — compare mechanic-at-home vs pick-up/workshop model.',
                'Doorstep car service in Pune: is it worth it?',
                'Basic doorstep checks suit minor jobs; YMO pick-up + workshop model is worth it for AC, denting, brakes, and complete servicing.',
                '<div class="ymo-content-section mb-5"><p class="md-body-md">A mechanic at your gate handles oil changes; AC leaks, denting, and brake rotor work need workshop equipment. YMO gives you free pick-up plus full capability.</p>'
                .'<p class="md-body-md"><a href="/why-choose-ymo">Why choose YMO</a></p></div>',
            ),
        );
        $pages = array();
        foreach ($posts as $p) {
            $pages[$p[0]] = array(
                'title'            => $p[1],
                'meta_description' => $p[2],
                'h1'               => $p[3],
                'intro'            => $p[4],
                'quick_answer'     => $p[4],
                'body'             => $p[5],
                'page_type'        => 'blog',
                'city_slug'        => 'pune',
                'updated_at'       => $today,
                'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
                'view'             => 'marketing/page',
            );
        }
        return $pages;
    }
}

if (!function_exists('marketing_seo_growth_comparison_page')) {
    /** @return array */
    function marketing_seo_growth_comparison_page($today)
    {
        $body = '<div class="ymo-content-section mb-5"><p class="md-body-md">Searching for doorstep car service often means choosing between a mechanic at your home and a service that collects your car for proper workshop repair. Here is a factual comparison.</p></div>'
            .'<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Pick-up + workshop vs mechanic at home</h2>'
            .'<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Capability</th><th>YMO</th><th>Mechanic at home</th></tr></thead><tbody>'
            .'<tr><td>Periodic service from ₹1,999</td><td>Yes</td><td>Sometimes</td></tr>'
            .'<tr><td>Free pick-up and drop</td><td>Yes</td><td>N/A</td></tr>'
            .'<tr><td>AC gas recharge &amp; leak repair</td><td>Workshop equipment</td><td>Portable kit only</td></tr>'
            .'<tr><td>Denting &amp; painting</td><td>Yes</td><td>Not at home</td></tr>'
            .'<tr><td>Luxury cars</td><td>Yes</td><td>Rarely</td></tr>'
            .'</tbody></table></div></div>'
            .'<p class="md-body-md"><a href="/premium-luxury-car-service-pune">Luxury car service</a> · <a href="/brands">Brands we service</a></p>';

        return array(
            'title'            => 'Why Choose YMO Over Doorstep Mechanics | Your Mechanic Online',
            'meta_description' => 'Compare YMO pick-up + workshop car service vs mechanic-at-home. AC, denting, polishing, luxury cars — full capability with free pick-up.',
            'h1'               => 'Why choose YMO?',
            'intro'            => 'Workshop-grade car care with the convenience of free pick-up — an honest comparison.',
            'quick_answer'     => 'YMO combines free pick-up with full workshop services — AC repair, denting, polishing, and luxury car care.',
            'body'             => $body,
            'page_type'        => 'minimal',
            'updated_at'       => $today,
            'faq'              => array(
                array('q' => 'What is the difference between YMO and a doorstep mechanic?', 'a' => 'A doorstep mechanic performs basic checks at your location. YMO picks up your car and services it at a workshop with proper equipment for AC, denting, painting, and diagnostics.'),
                array('q' => 'Does YMO still offer doorstep convenience?', 'a' => 'Yes. We provide free pick-up and drop so you do not visit a garage or wait on-site.'),
                array('q' => 'Can YMO service luxury cars?', 'a' => 'Yes. We offer premium and luxury car servicing including Mercedes, BMW, and Audi in Pune.'),
            ),
            'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
            'view'             => 'marketing/page',
        );
    }
}
