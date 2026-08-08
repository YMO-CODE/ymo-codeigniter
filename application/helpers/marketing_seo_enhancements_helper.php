<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEO enhancements: service FAQs/pricing, title patterns, Pune locality upgrades.
 * Loaded after marketing_pages_data.php merge; see marketing_enrich_pages().
 */

if (!function_exists('marketing_service_catalog')) {
    /** @return array<int,array> */
    function marketing_service_catalog()
    {
        $cities = marketing_cities_config();
        return isset($cities['services']) && is_array($cities['services']) ? $cities['services'] : array();
    }
}

if (!function_exists('marketing_service_for_path')) {
    /** @return array|null */
    function marketing_service_for_path($path)
    {
        $path = trim((string) $path, '/');
        foreach (marketing_service_catalog() as $svc) {
            if (!empty($svc['pune_slug']) && $svc['pune_slug'] === $path) {
                return $svc;
            }
            if (!empty($svc['city_slug'])) {
                $pattern = str_replace('{city}', '[a-z]+', preg_quote($svc['city_slug'], '/'));
                if (preg_match('/^'.$pattern.'$/', $path)) {
                    return $svc;
                }
            }
        }
        return NULL;
    }
}

if (!function_exists('marketing_service_pricing_tiers')) {
    /**
     * @return array<int,array{label:string,price:string}>
     */
    function marketing_service_pricing_tiers($service_key)
    {
        $defaults = array(
            'complete-car-servicing' => array(
                array('label' => 'Hatchback (Maruti Swift, i10, etc.)', 'price' => '₹1,999'),
                array('label' => 'Sedan (Honda City, Verna, etc.)', 'price' => '₹2,499'),
                array('label' => 'SUV / MUV', 'price' => '₹2,999'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'ac' => array(
                array('label' => 'Hatchback', 'price' => '₹999'),
                array('label' => 'Sedan', 'price' => '₹1,299'),
                array('label' => 'SUV / MUV', 'price' => '₹1,599'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'brakes' => array(
                array('label' => 'Front brake pads (pair)', 'price' => '₹499'),
                array('label' => 'Full brake inspection + fluid top-up', 'price' => '₹899'),
                array('label' => 'Brake pads + rotors (sedan)', 'price' => '₹2,499'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'denting' => array(
                array('label' => 'Single panel (dent + paint)', 'price' => '₹3,000'),
                array('label' => 'Bumper (front or rear)', 'price' => '₹3,500'),
                array('label' => 'Full body (estimate)', 'price' => 'From ₹18,000'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'engine' => array(
                array('label' => 'Engine diagnostics scan', 'price' => '₹999'),
                array('label' => 'Tune-up (spark plugs, filters)', 'price' => 'From ₹2,499'),
                array('label' => 'Major engine repair', 'price' => 'On inspection'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'interior' => array(
                array('label' => 'Hatchback deep clean', 'price' => '₹2,500'),
                array('label' => 'Sedan deep clean', 'price' => '₹2,999'),
                array('label' => 'SUV / MUV deep clean', 'price' => '₹3,499'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'polishing' => array(
                array('label' => 'Hatchback / sedan', 'price' => '₹6,500'),
                array('label' => 'SUV / MUV', 'price' => '₹7,999'),
                array('label' => 'Full correction package', 'price' => 'On inspection'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'belts' => array(
                array('label' => 'Belt inspection + tension check', 'price' => '₹499'),
                array('label' => 'Single belt replacement', 'price' => 'From ₹999'),
                array('label' => 'Cooling hose replacement', 'price' => 'From ₹1,499'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
            'lube' => array(
                array('label' => 'Express oil + filter (hatchback)', 'price' => '₹1,499'),
                array('label' => 'Oil + filter (sedan)', 'price' => '₹1,999'),
                array('label' => 'Synthetic oil change (SUV)', 'price' => '₹2,499'),
                array('label' => 'Luxury / Premium', 'price' => 'On request'),
            ),
        );
        return isset($defaults[$service_key]) ? $defaults[$service_key] : array();
    }
}

if (!function_exists('marketing_service_faqs')) {
    /**
     * @param string $service_key
     * @param string $city_name
     * @return array<int,array{q:string,a:string}>
     */
    function marketing_service_faqs($service_key, $city_name = 'Pune')
    {
        $pickup = 'Yes — we offer free doorstep pick-up and delivery across Pune, Indore, and Nashik. Book online and we collect your car at a time that suits you.';
        $brands = 'Yes. YMO services all major brands including Maruti, Hyundai, Tata, Honda, Mahindra, Toyota, Kia, BMW, Mercedes, Audi, and more.';
        $warranty = 'All servicing work comes with a service warranty. Ask our team for details when you book.';

        $map = array(
            'complete-car-servicing' => array(
                array('q' => 'How much does a complete car service cost at YMO in '.$city_name.'?', 'a' => 'Complete car servicing at YMO starts from ₹1,999. The final price depends on your car\'s make, model, and oil type. You\'ll get a transparent estimate before we begin.'),
                array('q' => 'Does YMO offer free pickup for car servicing?', 'a' => $pickup),
                array('q' => 'How long does a full car service take?', 'a' => 'Most periodic services are completed within 4–6 hours. We send WhatsApp updates with photos throughout the process.'),
                array('q' => 'Do you service all car brands?', 'a' => $brands),
                array('q' => 'Is there a warranty on car servicing at YMO?', 'a' => $warranty),
            ),
            'ac' => array(
                array('q' => 'How much does car AC service cost in '.$city_name.'?', 'a' => 'Car AC servicing at YMO starts from ₹999 for a basic check and gas top-up. Full AC diagnostics and compressor work are quoted upfront based on your vehicle.'),
                array('q' => 'Does YMO offer free pick-up for AC repair?', 'a' => $pickup),
                array('q' => 'How long does car AC service take?', 'a' => 'Most AC services are completed within 2–4 hours. Complex leak repairs may take longer — we confirm timing before starting.'),
                array('q' => 'Do you service AC for all car brands?', 'a' => $brands),
                array('q' => 'Is there a warranty on AC work at YMO?', 'a' => $warranty),
            ),
            'brakes' => array(
                array('q' => 'How much does brake repair cost at YMO in '.$city_name.'?', 'a' => 'Brake inspection starts from ₹499. Pad replacement and full brake service are quoted upfront based on your car model and parts needed.'),
                array('q' => 'Does YMO offer free pick-up for brake service?', 'a' => $pickup),
                array('q' => 'How long does brake repair take?', 'a' => 'Most brake pad replacements are done within 2–3 hours. We share photos of worn parts before replacing anything.'),
                array('q' => 'Do you service brakes on all car brands?', 'a' => $brands),
                array('q' => 'Is there a warranty on brake work at YMO?', 'a' => $warranty),
            ),
            'denting' => array(
                array('q' => 'How much does denting and painting cost at YMO?', 'a' => 'Panel denting and painting at YMO starts from ₹3,000 per panel with colour matching, anti-rust treatment, and a paint warranty. Full-body quotes are shared after inspection.'),
                array('q' => 'Does YMO offer free pick-up for denting work?', 'a' => $pickup),
                array('q' => 'How long does denting and painting take?', 'a' => 'Single-panel jobs typically take 2–3 days. Full-body repaints take longer — we give a clear timeline before work begins.'),
                array('q' => 'Do you match the original car colour?', 'a' => 'Yes. We use industry-approved paints with colour-match processes and quality paint booth finishing.'),
                array('q' => 'Is there a warranty on denting and painting?', 'a' => 'Yes. Paint and panel work includes a service warranty — ask our team for current coverage details.'),
            ),
            'engine' => array(
                array('q' => 'How much does engine service cost at YMO in '.$city_name.'?', 'a' => 'Engine diagnostics start from ₹999. Tune-ups and major repairs are quoted after inspection — no work begins without your approval.'),
                array('q' => 'Does YMO offer free pick-up for engine repair?', 'a' => $pickup),
                array('q' => 'How long does engine repair take?', 'a' => 'Diagnostics are usually same-day. Major repairs depend on parts availability — we keep you updated on WhatsApp.'),
                array('q' => 'Do you service all engine types and brands?', 'a' => $brands),
                array('q' => 'Is there a warranty on engine work at YMO?', 'a' => $warranty),
            ),
            'interior' => array(
                array('q' => 'How much does interior deep cleaning cost in '.$city_name.'?', 'a' => 'Interior deep cleaning at YMO starts from ₹2,500 including vacuum, upholstery cleaning, dashboard polish, and odour treatment.'),
                array('q' => 'Does YMO offer free pick-up for interior cleaning?', 'a' => $pickup),
                array('q' => 'How long does interior cleaning take?', 'a' => 'Most interior deep cleans take 4–6 hours depending on vehicle size and condition.'),
                array('q' => 'Do you clean all car interiors?', 'a' => 'Yes — hatchbacks, sedans, SUVs, and luxury cars. We use fabric-safe products suited to your upholstery type.'),
                array('q' => 'Is there a warranty on detailing work?', 'a' => 'We stand behind our detailing quality. Ask our team about satisfaction coverage when you book.'),
            ),
            'polishing' => array(
                array('q' => 'How much does car rubbing and polishing cost in '.$city_name.'?', 'a' => 'YMO 3-stage rubbing and polishing starts from ₹6,500 — restores gloss, removes fine scratches, and protects your paint.'),
                array('q' => 'Does YMO offer free pick-up for polishing?', 'a' => $pickup),
                array('q' => 'How long does rubbing and polishing take?', 'a' => 'A full 3-stage polish typically takes 6–8 hours. We confirm timing when you book.'),
                array('q' => 'Does polishing remove deep scratches?', 'a' => 'Polishing removes fine swirl marks and light scratches. Deep dents or paint damage may need denting and painting — we advise during inspection.'),
                array('q' => 'Is there a warranty on polishing work?', 'a' => $warranty),
            ),
            'belts' => array(
                array('q' => 'How much does belt and hose service cost at YMO?', 'a' => 'Belt inspection starts from ₹499. Replacement costs depend on your car model — we quote before any work begins.'),
                array('q' => 'Does YMO offer free pick-up for belt service?', 'a' => $pickup),
                array('q' => 'How often should car belts be replaced?', 'a' => 'Most manufacturers recommend timing belt replacement between 60,000–100,000 km. We inspect wear during every periodic service.'),
                array('q' => 'Do you service all car brands?', 'a' => $brands),
                array('q' => 'Is there a warranty on belt and hose work?', 'a' => $warranty),
            ),
            'lube' => array(
                array('q' => 'How much does an oil change cost at YMO in '.$city_name.'?', 'a' => 'Express oil and filter changes start from ₹1,499 depending on oil grade and vehicle. Synthetic oil changes are quoted upfront.'),
                array('q' => 'Does YMO offer free pick-up for oil changes?', 'a' => $pickup),
                array('q' => 'How long does an oil change take?', 'a' => 'Most oil and filter services are completed within 1–2 hours including a basic health check.'),
                array('q' => 'Do you use genuine oil and filters?', 'a' => 'Yes. We use quality engine oils and filters suited to your manufacturer specifications.'),
                array('q' => 'Is there a warranty on oil change service?', 'a' => $warranty),
            ),
        );
        return isset($map[$service_key]) ? $map[$service_key] : array();
    }
}

if (!function_exists('marketing_enrich_page')) {
    /** @return array */
    function marketing_enrich_page($path, array $page)
    {
        $path = trim((string) $path, '/');
        $svc = marketing_service_for_path($path);
        $city_name = 'Pune';
        if (!empty($page['city_slug'])) {
            $city = marketing_city_by_slug($page['city_slug']);
            if ($city && !empty($city['name'])) {
                $city_name = $city['name'];
            }
        }

        if ($svc) {
            if (empty($page['service_key'])) {
                $page['service_key'] = $svc['key'];
            }
            if (empty($page['page_type'])) {
                $page['page_type'] = 'service';
            }
            if (empty($page['city_slug']) && strpos($path, '-in-pune') !== FALSE) {
                $page['city_slug'] = 'pune';
            }
            $price = !empty($svc['price_from']) ? (int) $svc['price_from'] : 0;
            if ($price > 0) {
                $short = marketing_service_short_title($svc['key'], $svc['title']);
                $page['meta_title'] = $short.' in '.$city_name.' | From ₹'.number_format($price).' | YMO';
                if (empty($page['title']) || strlen($page['title']) > 65) {
                    $page['title'] = $page['meta_title'];
                }
            }
            if (empty($page['faq']) || !is_array($page['faq'])) {
                $page['faq'] = marketing_service_faqs($svc['key'], $city_name);
            }
            $tiers = marketing_service_pricing_tiers($svc['key']);
            if ($tiers) {
                $page['pricing_tiers'] = $tiers;
            }
            $desc_map = array(
                'complete-car-servicing' => 'Periodic car maintenance from ₹1,999 in '.$city_name.'. Oil & filter change, brake check, AC filter, free pick-up. Book online with YMO.',
                'ac'                     => 'AC gas recharge, compressor check & filter cleaning in '.$city_name.' from ₹999. Free pick-up included. Book online with YMO.',
                'brakes'                   => 'Brake pad replacement & inspection in '.$city_name.' from ₹499. Free pick-up, upfront pricing. Book online with YMO.',
                'denting'                  => 'Panel denting & painting from ₹3,000 in '.$city_name.'. Colour match, paint warranty, free pick-up. Book with YMO.',
                'engine'                   => 'Engine diagnostics, tune-ups & repair in '.$city_name.'. Expert mechanics, free pick-up, transparent pricing. Book with YMO.',
                'interior'                 => 'Interior deep cleaning from ₹2,500 in '.$city_name.'. Vacuum, upholstery, odour removal. Free pick-up with YMO.',
                'polishing'                => '3-stage car rubbing & polishing from ₹6,500 in '.$city_name.'. Restore gloss, free pick-up. Book with YMO.',
            );
            if (isset($desc_map[$svc['key']])) {
                $page['meta_description'] = $desc_map[$svc['key']];
            }
        }

        if ($path === '') {
            $page['meta_title'] = 'Car Servicing in Pune, Indore & Nashik | Free Pickup | YMO';
            $page['meta_description'] = 'Expert car servicing and repair in Pune, Indore & Nashik. Free doorstep pickup, transparent pricing from ₹1,999. 4.8★ rated. Book online with YMO.';
        }

        if (!empty($page['page_type']) && $page['page_type'] === 'locality' && !empty($page['locality_label'])) {
            $label = $page['locality_label'];
            if (empty($page['meta_title'])) {
                $page['meta_title'] = 'Car Service in '.$label.', Pune | Free Pickup | YMO';
            }
            if (strpos($page['meta_description'] ?? '', 'Book now fo!') !== FALSE) {
                $page['meta_description'] = 'Expert car servicing & repair in '.$label.', Pune. Free doorstep pickup, transparent pricing, trained mechanics. Book online with YMO.';
            }
        }

        if ($path === 'premium-luxury-car-service-pune') {
            $page['meta_title'] = 'Luxury Car Service in Pune | BMW, Mercedes, Audi | YMO';
            $page['meta_description'] = 'Specialist luxury car servicing in Pune for BMW, Mercedes-Benz, Audi, Jaguar & more. Workshop-grade care, genuine-spec parts, free pickup.';
        }

        if ($path === 'locations/pune') {
            $page['meta_title'] = 'Car Servicing in Pune | Free Pickup | YMO';
            $page['meta_description'] = 'Book car servicing in Pune — Baner, Wakad, Hinjewadi, Kharadi, Kothrud, Viman Nagar & 14 areas. Doorstep pick-up from ₹1,999. 4.8★ rated.';
        }

        if ($path === 'locations/indore') {
            $page['meta_title'] = 'Car Servicing in Indore | Free Pickup | YMO';
        }

        if ($path === 'locations/nashik') {
            $page['meta_title'] = 'Car Servicing in Nashik | Free Pickup | YMO';
        }

        return $page;
    }
}

if (!function_exists('marketing_service_short_title')) {
    function marketing_service_short_title($key, $fallback = '')
    {
        $map = array(
            'complete-car-servicing' => 'Complete Car Servicing',
            'ac'                     => 'Car AC Service',
            'brakes'                 => 'Car Brake Repair',
            'denting'                => 'Car Denting & Painting',
            'engine'                 => 'Car Engine Service',
            'interior'               => 'Interior Deep Cleaning',
            'polishing'              => 'Car Rubbing & Polishing',
            'belts'                  => 'Belts & Hoses Service',
            'lube'                   => 'Oil & Filter Change',
        );
        return isset($map[$key]) ? $map[$key] : $fallback;
    }
}

if (!function_exists('marketing_enrich_pages')) {
    /** @return array<string,array> */
    function marketing_enrich_pages(array $pages)
    {
        foreach ($pages as $path => $page) {
            if (!is_array($page)) {
                continue;
            }
            $pages[$path] = marketing_enrich_page($path, $page);
        }
        return $pages;
    }
}

if (!function_exists('marketing_whatsapp_cta_enabled')) {
    function marketing_whatsapp_cta_enabled()
    {
        $uri = trim((string) uri_string(), '/');
        $blocked = array('quick-book', 'packages', 'login', 'signup');
        foreach ($blocked as $prefix) {
            if ($uri === $prefix || strpos($uri, $prefix.'/') === 0) {
                return FALSE;
            }
        }
        return TRUE;
    }
}
