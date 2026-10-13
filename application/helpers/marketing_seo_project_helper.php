<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEO project phases 1–7: trust pages, expansion pages, FAQ hub, NAP normalize, blog outlines.
 */

if (!function_exists('marketing_business_config')) {
    /** @return array */
    function marketing_business_config()
    {
        static $cfg = NULL;
        if ($cfg !== NULL) {
            return $cfg;
        }
        $file = APPPATH.'config/marketing_business.php';
        $cfg = is_file($file) ? require $file : array();
        return is_array($cfg) ? $cfg : array();
    }
}

if (!function_exists('marketing_business_field')) {
    /** @return string */
    function marketing_business_field($key, $default = '')
    {
        $c = marketing_business_config();
        return !empty($c[$key]) ? (string) $c[$key] : $default;
    }
}

if (!function_exists('marketing_business_phone_display')) {
    function marketing_business_phone_display()
    {
        $c = marketing_business_config();
        return !empty($c['phone_display']) ? $c['phone_display'] : '+91 75587 83868';
    }
}

if (!function_exists('marketing_business_phone_e164')) {
    function marketing_business_phone_e164()
    {
        $c = marketing_business_config();
        return !empty($c['phone_e164']) ? $c['phone_e164'] : '+917558783868';
    }
}

if (!function_exists('marketing_business_phone_schema')) {
    function marketing_business_phone_schema()
    {
        $c = marketing_business_config();
        return !empty($c['phone_schema']) ? $c['phone_schema'] : '+91-7558783868';
    }
}

if (!function_exists('marketing_business_whatsapp_url')) {
    function marketing_business_whatsapp_url($message = 'Hi YMO, I would like to book a car service')
    {
        $c = marketing_business_config();
        $num = !empty($c['whatsapp_e164']) ? preg_replace('/\D/', '', $c['whatsapp_e164']) : '917558783868';
        return 'https://wa.me/'.$num.'?text='.rawurlencode($message);
    }
}

if (!function_exists('marketing_warranty_badge_text')) {
    function marketing_warranty_badge_text()
    {
        $c = marketing_business_config();
        return !empty($c['warranty_badge']) ? $c['warranty_badge'] : 'Up to 6 months warranty on eligible service work';
    }
}

if (!function_exists('marketing_warranty_badge_html')) {
    function marketing_warranty_badge_html()
    {
        $text = marketing_warranty_badge_text();
        return '<p class="md-label-md mb-0 ymo-warranty-badge"><span class="mi mi-sm mi-leading" aria-hidden="true">verified_user</span>'
            .html_escape($text).'</p>';
    }
}

if (!function_exists('marketing_reviews_config')) {
    /** @return array<int,array> */
    function marketing_reviews_config()
    {
        static $rows = NULL;
        if ($rows !== NULL) {
            return $rows;
        }
        $file = APPPATH.'config/marketing_reviews.php';
        $rows = is_file($file) ? require $file : array();
        return is_array($rows) ? $rows : array();
    }
}

if (!function_exists('marketing_render_reviews_cards')) {
    /** @param int $limit */
    function marketing_render_reviews_cards($limit = 0)
    {
        $rows = marketing_reviews_config();
        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }
        $html = '<div class="row g-4">';
        foreach ($rows as $row) {
            $stars = str_repeat('★', (int) ($row['rating'] ?? 5));
            $html .= '<div class="col-md-4"><div class="md-card-elevated h-100 p-4">';
            $html .= '<p class="md-label-lg mb-2" aria-label="'.html_escape((string) ($row['rating'] ?? 5)).' out of 5 stars">'.$stars.'</p>';
            $html .= '<p class="md-body-md mb-3">'.html_escape($row['text'] ?? '').'</p>';
            $html .= '<p class="md-body-sm ymo-muted mb-0">— '.html_escape($row['author'] ?? '').', '.html_escape($row['city'] ?? '').'</p>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('marketing_gbp_url_for_city')) {
    function marketing_gbp_url_for_city($city_slug)
    {
        $biz = marketing_business_config();
        if (!empty($biz['cities'][$city_slug]['gbp_url'])
            && strpos($biz['cities'][$city_slug]['gbp_url'], '{{TODO') === FALSE) {
            return $biz['cities'][$city_slug]['gbp_url'];
        }
        $city = marketing_city_by_slug($city_slug);
        return ($city && !empty($city['gbp_url'])) ? $city['gbp_url'] : '';
    }
}

if (!function_exists('marketing_apply_business_nap_to_html')) {
    function marketing_apply_business_nap_to_html($html)
    {
        if ($html === '' || !is_string($html)) {
            return $html;
        }
        $display = marketing_business_phone_display();
        $e164 = marketing_business_phone_e164();
        $tel = preg_replace('/[^+\d]/', '', $e164);
        $replacements = array(
            '+91-7744-065904' => $display,
            '+91 7744 065904' => $display,
            '+917744065904'   => $tel,
            '7744065904'      => '7558783868',
            '7744-065904'     => '75587 83868',
            '7558303868'      => '7558783868',
            '7823015407'      => '7558783868',
        );
        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }
}

if (!function_exists('marketing_central_faq_items')) {
    /** @return array<int,array{topic:string,q:string,a:string}> */
    function marketing_central_faq_items()
    {
        $phone = marketing_business_phone_display();
        return array(
            array('topic' => 'Booking', 'q' => 'How do I book car service with YMO?', 'a' => 'Book online at booking.yourmechaniconline.com — choose a package, add your car details, and confirm. We call you to schedule free pick-up.'),
            array('topic' => 'Pickup', 'q' => 'Is doorstep pick-up really free?', 'a' => 'Yes. Free pick-up and drop is included for servicing across Pune, Indore, and Nashik within our listed service areas.'),
            array('topic' => 'Pricing', 'q' => 'How much does car servicing cost?', 'a' => 'Periodic service starts from ₹2,499. AC, denting, polishing, and other jobs are quoted upfront before work begins.'),
            array('topic' => 'Warranty', 'q' => 'Do you offer a service warranty?', 'a' => marketing_business_field('warranty_summary', 'Yes')),
            array('topic' => 'Payment', 'q' => 'When do I pay?', 'a' => marketing_business_field('payment_policy', 'On Delivery')),
            array('topic' => 'Parts', 'q' => 'Do you use genuine parts?', 'a' => marketing_business_field('parts_policy', 'Yes')),
            array('topic' => 'Contact', 'q' => 'What is the best number to reach YMO?', 'a' => 'Call '.$phone.' or use the contact form — Mon–Sat, 9 AM to 8 PM IST.'),
        );
    }
}

if (!function_exists('marketing_trust_legal_pages')) {
    /** @return array<string,array> */
    function marketing_trust_legal_pages()
    {
        $today = '2026-10-05';
        $phone = marketing_business_phone_display();
        $tel = marketing_business_phone_e164();
        $warranty = marketing_business_field('warranty_summary');
        $legal = marketing_business_field('legal_entity', 'Your Mechanic Online');

        $pricing_rows = marketing_pricing_matrix_html();

        return array(
            'terms-and-conditions' => array(
                'title'            => 'Terms & Conditions | YMO',
                'meta_title'       => 'Terms & Conditions | Your Mechanic Online',
                'meta_description' => 'Terms for booking car service with Your Mechanic Online — pick-up, workshop repair, payments, and liability in Pune, Indore & Nashik.',
                'h1'               => 'Terms and conditions',
                'intro'            => 'Please read these terms before booking a service with Your Mechanic Online.',
                'page_type'        => 'minimal',
                'updated_at'       => $today,
                'body'             => '<p><strong>Last updated:</strong> October 2026</p>'
                    .'<h2>Who we are</h2><p>These terms apply when you book car servicing or related services with '.html_escape($legal).' ("YMO", "we", "us") via yourmechaniconline.com or booking.yourmechaniconline.com.</p>'
                    .'<h2>Bookings</h2><p>When you confirm a booking, you agree to provide accurate vehicle and contact details, disclose known faults, and remove personal valuables from the vehicle. We confirm pick-up windows and estimates before starting work beyond the agreed scope.</p>'
                    .'<h2>Pick-up and workshop service</h2><p>Free pick-up and drop applies within our published service areas in Pune, Indore, and Nashik. You authorise our driver to move your vehicle for servicing. We inspect and photograph condition where needed; pre-existing damage is noted before work.</p>'
                    .'<h2>Estimates and approval</h2><p>Prices on the website are indicative. We share itemised estimates for parts and labour before fitting anything beyond the booked package. You may approve or decline additional work.</p>'
                    .'<h2>Payment</h2><p>'.html_escape(marketing_business_field('payment_policy')).'</p>'
                    .'<h2>Liability</h2><p>We carry out work with reasonable skill and care. Our liability is limited to the extent permitted by applicable law; we are not responsible for pre-existing defects, accident damage, or misuse after return.</p>'
                    .'<h2>Cancellations</h2><p>See our <a href="/refund-and-cancellation-policy">refund and cancellation policy</a>.</p>'
                    .'<h2>Contact</h2><p>Questions: <a href="mailto:contactus@yourmechaniconline.com">contactus@yourmechaniconline.com</a> · <a href="tel:'.html_escape(preg_replace('/\s+/', '', $tel)).'">'.html_escape($phone).'</a></p>',
                'view'             => 'marketing/page',
            ),
            'refund-and-cancellation-policy' => array(
                'title'            => 'Refund & Cancellation | YMO',
                'meta_title'       => 'Refund & Cancellation Policy | YMO',
                'meta_description' => 'Cancellation windows and refund timelines for Your Mechanic Online car service bookings in Pune, Indore and Nashik.',
                'h1'               => 'Refund and cancellation policy',
                'intro'            => 'How cancellations and refunds work when you book with YMO.',
                'page_type'        => 'minimal',
                'updated_at'       => $today,
                'body'             => '<h2>Before pick-up</h2><p>If you cancel before our team collects your vehicle, any advance paid for special-order parts may be refunded minus actual procurement costs already incurred. Routine service bookings cancelled at least 4 hours before the scheduled pick-up window are typically fully refundable.</p>'
                    .'<h2>After pick-up, before work starts</h2><p>If you cancel after collection but before workshop work begins, we may charge a logistics fee of up to ₹500 to cover driver time and fuel.</p>'
                    .'<h2>Work in progress or completed</h2><p>Once approved work has started, you are responsible for labour and parts already fitted or ordered for your car. Partial completion is billed for work completed to date.</p>'
                    .'<h2>No-show or unreachable customer</h2><p>If we cannot reach you at pick-up or you are unavailable, the booking may be rescheduled or treated as a no-show.</p>'
                    .'<h2>Refunds</h2><p>Approved refunds are processed to the original payment method where possible within 10 business days.</p>'
                    .'<p>Contact <a href="tel:'.html_escape(preg_replace('/\s+/', '', $tel)).'">'.html_escape($phone).'</a> or <a href="mailto:contactus@yourmechaniconline.com">contactus@yourmechaniconline.com</a> for cancellation help.</p>',
                'view'             => 'marketing/page',
            ),
            'warranty' => array(
                'title'            => 'Service Warranty | YMO',
                'meta_title'       => 'Car Service Warranty | Your Mechanic Online',
                'meta_description' => 'What is covered under Your Mechanic Online service warranty on labour and parts after car servicing in Pune, Indore and Nashik.',
                'h1'               => 'Service warranty',
                'intro'            => html_escape($warranty),
                'page_type'        => 'minimal',
                'updated_at'       => $today,
                'body'             => '<h2>What is covered</h2><p>Eligible periodic service and repair jobs include a limited warranty on labour performed by YMO and on parts we supply, when the vehicle is used normally and maintained as recommended. Typical coverage window: up to 6 months.</p>'
                    .'<h2>What is not covered</h2><p>Accident or collision damage, flood or fire, misuse or racing, pre-existing faults not related to the repair, wear items beyond fair life, customer-supplied parts, and damage from third-party work after handover.</p>'
                    .'<h2>How to claim</h2><p>Contact us at <a href="tel:'.html_escape(preg_replace('/\s+/', '', $tel)).'">'.html_escape($phone).'</a> with your booking reference, a brief description, and photos if useful. We will inspect and, for valid claims, re perform or adjust covered work within 15 days.</p>',
                'view'             => 'marketing/page',
            ),
            'reviews' => array(
                'title'            => 'Customer Reviews | YMO',
                'meta_title'       => 'Customer Reviews | Your Mechanic Online',
                'meta_description' => 'Read what drivers say about Your Mechanic Online car servicing in Pune, Indore and Nashik — doorstep pick-up and workshop care.',
                'h1'               => 'What customers say',
                'intro'            => 'Real feedback from car owners who use YMO for servicing and repairs.',
                'page_type'        => 'minimal',
                'updated_at'       => $today,
                'body'             => marketing_render_reviews_cards(0)
                    .'<div class="mt-4"><h2 class="md-headline-md mb-3">More reviews on Google</h2><ul class="md-body-md">'
                    .'<li><a href="'.html_escape(marketing_gbp_url_for_city('pune')).'" target="_blank" rel="noopener">Pune — Google reviews</a></li>'
                    .'<li><a href="'.html_escape(marketing_gbp_url_for_city('indore')).'" target="_blank" rel="noopener">Indore — Google reviews</a> </li>'
                    .'<li><a href="'.html_escape(marketing_gbp_url_for_city('nashik')).'" target="_blank" rel="noopener">Nashik — Google reviews</a></li></ul></div>',
                'view'             => 'marketing/page',
            ),
            'pricing' => array(
                'title'            => 'Car Service Prices | YMO',
                'meta_title'       => 'Car Service Price List | YMO',
                'meta_description' => 'Indicative car service prices in Pune, Indore and Nashik — hatchback, sedan, SUV and luxury. Transparent estimates from Your Mechanic Online.',
                'h1'               => 'Service pricing guide',
                'intro'            => 'Indicative prices — final quotes depend on your car model and condition. We confirm before starting work.',
                'page_type'        => 'minimal',
                'updated_at'       => $today,
                'body'             => $pricing_rows.'<p class="md-body-md mt-3">Variable jobs (battery, clutch, suspension, insurance assistance) are always quoted after inspection.</p>',
                'view'             => 'marketing/page',
            ),
            'faq' => array(
                'title'            => 'FAQ | Car Service | YMO',
                'meta_title'       => 'Car Service FAQ | Your Mechanic Online',
                'meta_description' => 'Answers about booking, pick-up, pricing, warranty, and parts for Your Mechanic Online doorstep car service in Pune, Indore and Nashik.',
                'h1'               => 'Frequently asked questions',
                'intro'            => 'Quick answers about booking and servicing with YMO.',
                'page_type'        => 'faq',
                'updated_at'       => $today,
                'faq'              => array_map(function ($row) {
                    return array('q' => $row['q'], 'a' => $row['a']);
                }, marketing_central_faq_items()),
                'body'             => '',
                'view'             => 'marketing/page',
            ),
            'how-it-works' => array(
                'title'            => 'How It Works | YMO',
                'meta_title'       => 'How Doorstep Car Service Works | YMO',
                'meta_description' => 'Book online, free pick-up, live WhatsApp updates, and delivery with invoice — how Your Mechanic Online car service works.',
                'h1'               => 'How it works',
                'intro'            => 'Four simple steps from booking to delivery.',
                'page_type'        => 'minimal',
                'updated_at'       => $today,
                'body'             => marketing_how_it_works_steps_html(),
                'view'             => 'marketing/page',
            ),
        );
    }
}

if (!function_exists('marketing_how_it_works_steps_html')) {
    function marketing_how_it_works_steps_html()
    {
        return '<div class="row g-4">'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-outlined p-4 h-100"><span class="md-label-lg d-block mb-2">1. Book</span><p class="md-body-md mb-0">Choose a service or package online and confirm your slot.</p></div></div>'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-outlined p-4 h-100"><span class="md-label-lg d-block mb-2">2. Free pick-up</span><p class="md-body-md mb-0">We collect your car from your doorstep at the scheduled time.</p></div></div>'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-outlined p-4 h-100"><span class="md-label-lg d-block mb-2">3. Updates &amp; approval</span><p class="md-body-md mb-0">WhatsApp photos and estimates — no surprise work without your OK.</p></div></div>'
            .'<div class="col-md-6 col-lg-3"><div class="md-card-outlined p-4 h-100"><span class="md-label-lg d-block mb-2">4. Delivery</span><p class="md-body-md mb-0">We return your car with invoice and service summary.</p></div></div>'
            .'</div>';
    }
}

if (!function_exists('marketing_pricing_matrix_html')) {
    function marketing_pricing_matrix_html()
    {
        $services = array(
            'Complete car servicing' => array('hatchback' => '₹2,499', 'sedan' => '₹2,499', 'suv' => '₹2,999', 'luxury' => '₹24,999'),
            'Oil change'           => array('hatchback' => 'From ₹1,499', 'sedan' => 'From ₹1,499', 'suv' => 'From ₹2,499', 'luxury' => 'From ₹5,600'),
            'AC service'           => array('hatchback' => 'From ₹999', 'sedan' => 'From ₹1,299', 'suv' => 'From ₹1,599', 'luxury' => 'From ₹9,999'),
            'Brake service'        => array('hatchback' => 'From ₹499', 'sedan' => 'From ₹499', 'suv' => 'From ₹899', 'luxury' => 'From ₹2,999'),
            'Denting (per panel)'  => array('hatchback' => 'From ₹4,500', 'sedan' => 'From ₹4,500', 'suv' => 'From ₹5,500', 'luxury' => 'From ₹6,999'),
            'Interior cleaning'    => array('hatchback' => '₹2,500', 'sedan' => '₹2,999', 'suv' => '₹3,499', 'luxury' => 'From ₹3,999'),
            'Rubbing & polishing'  => array('hatchback' => '₹6,500', 'sedan' => '₹6,500', 'suv' => '₹7,999', 'luxury' => 'From ₹9,999'),
        );
        $html = '<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Service</th><th>Hatchback</th><th>Sedan</th><th>SUV</th><th>Luxury</th></tr></thead><tbody>';
        foreach ($services as $name => $cols) {
            $html .= '<tr><td>'.html_escape($name).'</td>';
            foreach ($cols as $price) {
                $html .= '<td>'.html_escape($price).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '<tr><td>Major / high-km service</td><td colspan="4">'.html_escape(marketing_business_field('major_service_note')).'</td></tr>';
        $quote_row = 'Quote after inspection';
        foreach (array('Battery replacement', 'Clutch repair', 'Suspension repair', 'Tyres &amp; alignment') as $label) {
            $html .= '<tr><td>'.html_escape($label).'</td><td>'.$quote_row.'</td><td>'.$quote_row.'</td><td>'.$quote_row.'</td><td>'.$quote_row.'</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<p class="md-body-sm ymo-muted">Same indicative prices apply across Pune, Indore, and Nashik unless noted on the service page.</p>';
        return $html;
    }
}

if (!function_exists('marketing_extra_service_checklist_html')) {
    function marketing_extra_service_checklist_html($key)
    {
        $items = array(
            'battery-replacement' => array(
                'Battery health test (load / CCA where applicable)',
                'Terminal clean and secure mounting',
                'Alternator charging check',
                'OEM/OES battery options quoted before fitment',
                'Safe disposal of old battery',
            ),
            'tyres-wheel-alignment' => array(
                'Tyre tread and sidewall inspection',
                'Rotation recommendation if uneven wear',
                'Wheel alignment and balancing (as needed)',
                'Tyre pressure set to manufacturer spec',
                'Quote for replacement tyres by size and brand',
            ),
            'clutch-repair' => array(
                'Road test and clutch slip / noise diagnosis',
                'Inspection of clutch plate, pressure plate, release bearing',
                'Flywheel skim or replace if required (quoted separately)',
                'Hydraulic clutch line and fluid check',
                'Post-repair road test',
            ),
            'suspension-repair' => array(
                'Noise and vibration diagnosis over bumps',
                'Shock absorber, strut, and bush inspection',
                'Alignment check after suspension work',
                'Quote for parts by model before ordering',
                'Post-repair test drive',
            ),
            'car-wash-detailing' => array(
                'Exterior foam wash and dry',
                'Interior vacuum and wipe-down',
                'Glass and mirror clean',
                'Tyre dressing (optional package)',
                'Add-on: deep interior / engine bay on quote',
            ),
            'ceramic-coating' => array(
                'Paint decontamination and prep wash',
                'Single- or multi-layer ceramic application (package-based)',
                'Cure time and handover care instructions',
                'Inspection under light for high spots',
                'Maintenance visit guidance',
            ),
            'insurance-claim-assistance' => array(
                'Document checklist for your insurer',
                'Estimate and photos for surveyor / claim file',
                'Coordination for repair at YMO workshop',
                'Updates on approval and repair stages',
                'Invoice and delivery after claim settlement',
            ),
            'pre-purchase-inspection' => array(
                'Structured multi-point inspection (engine, transmission, underbody)',
                'OBD scan for stored fault codes where supported',
                'Accident / repaint signs check',
                'Written summary with photos for buyer decision',
                'No repair work without separate booking',
            ),
            'towing-roadside-assistance' => array(
                'Breakdown pickup within serviceable areas',
                'Flat tyre / jump-start where safe on-site',
                'Tow to YMO workshop or your chosen garage (quoted)',
                'Status updates to driver and family contact',
                'Invoice for tow and any roadside labour',
            ),
        );
        if (!isset($items[$key])) {
            return '<ul class="md-body-md"><li>Inspection and diagnosis at collection or workshop</li>'
                .'<li>Written estimate before parts or labour begin</li><li>WhatsApp updates during the job</li>'
                .'<li>Free pick-up and drop in your city service area</li></ul>';
        }
        $html = '<ul class="md-body-md">';
        foreach ($items[$key] as $line) {
            $html .= '<li>'.html_escape($line).'</li>';
        }
        $html .= '</ul><p class="md-body-sm ymo-muted">Your estimate lists exact inclusions for your car before work starts.</p>';
        return $html;
    }
}

if (!function_exists('marketing_luxury_brand_draft_body_html')) {
    function marketing_luxury_brand_draft_body_html($slug, array $brand)
    {
        $parts = marketing_business_field('parts_policy');
        $name = $brand['name'];
        $models = $brand['models'];
        $extra = array(
            'bmw'      => 'Common Pune jobs include oil service (long-life specs), brake fluid intervals, cooling system checks, and iDrive-related electrical diagnostics.',
            'mercedes' => 'Common jobs include ASSYST-based service items, brake and suspension wear on city roads, and AC performance in summer.',
            'audi'     => 'Common jobs include quattro underbody checks, DSG/transmission service intervals, and turbo petrol oil-grade compliance.',
        );
        $cred = 'Work is performed by trained technicians using workshop lifts, computer diagnostics, and brand-appropriate oils and fluids.';
        $price_band = array(
            'bmw'      => 'Periodic service typically from ₹20,000 depending on model and service scope.',
            'mercedes' => 'Periodic service typically from ₹25,000 depending on model and service scope.',
            'audi'     => 'Periodic service typically from ₹20,000 depending on model and service scope.',
        );
        $price_line = isset($price_band[$slug]) ? $price_band[$slug] : 'Periodic service is quoted after we confirm your model and interval.';
        $intro = isset($extra[$slug]) ? $extra[$slug] : '';
        return '<div class="ymo-content-section mb-4">'
            .'<p class="md-body-md mb-3">We service '.$name.' models including '.$models.' in Pune with free pick-up, WhatsApp updates, and upfront quotes before extra parts are fitted.</p>'
            .'<p class="md-body-md mb-3">'.$intro.'</p>'
            .'<h2 class="md-headline-md mb-3">Typical services</h2>'
            .'<ul class="md-body-md mb-3"><li>Periodic / scheduled maintenance</li><li>Brake and suspension work</li><li>AC diagnosis and repair</li><li>Engine and electrical diagnostics</li><li>Denting, painting, and detailing</li></ul>'
            .'<h2 class="md-headline-md mb-3">Parts policy</h2>'
            .'<p class="md-body-md mb-3">'.html_escape($parts).'</p>'
            .'<h2 class="md-headline-md mb-3">Technicians &amp; equipment</h2>'
            .'<p class="md-body-md mb-3">'.$cred.'</p>'
            .'<p class="md-body-md mb-0"><strong>Indicative pricing:</strong> '.html_escape($price_line).'</p></div>';
    }
}

if (!function_exists('marketing_expansion_pages')) {
    /**
     * Phase 3 — generated service, luxury brand, and AMC pages.
     *
     * @return array<string,array>
     */
    function marketing_expansion_pages()
    {
        $today = '2026-10-05';
        $pages = array();
        $phone = marketing_business_phone_display();

        $oil_body = function ($city_name, $city_slug) use ($phone) {
            $hub = 'locations/'.$city_slug;
            return '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">What\'s included</h2>'
                .'<ul class="md-body-md"><li>Engine oil and filter (grade matched to your car)</li>'
                .'<li>Basic health check — fluids, belts visual, tyre pressure</li>'
                .'<li>Typical duration 1–2 hours at our workshop</li>'
                .'<li>Free doorstep pick-up in '.$city_name.'</li></ul></div>'
                .'<p class="md-body-md">Open now slots: Mon–Sat 9 AM – 8 PM · Call '.html_escape($phone).'</p>'
                .'<p class="md-body-md"><a href="/'.$hub.'">Car service in '.$city_name.'</a> · <a href="/services/complete-car-servicing'.($city_slug === 'pune' ? '' : '-in-'.$city_slug).'">Complete servicing</a></p>';
        };

        $oil_defs = array(
            'services/car-oil-change' => array('city' => 'pune', 'name' => 'Pune', 'from' => 1499),
            'services/car-oil-change-in-indore' => array('city' => 'indore', 'name' => 'Indore', 'from' => 1499),
            'services/car-oil-change-in-nashik' => array('city' => 'nashik', 'name' => 'Nashik', 'from' => 1499),
        );
        foreach ($oil_defs as $path => $meta) {
            $pages[$path] = array(
                'title'            => 'Car Oil Change in '.$meta['name'].' | From ₹'.$meta['from'].' | YMO',
                'meta_title'       => 'Oil Change in '.$meta['name'].' | From ₹'.$meta['from'].' | YMO',
                'meta_description' => 'Doorstep oil change in '.$meta['name'].' from ₹'.$meta['from'].'. Oil, filter, checks — book online. Open Mon–Sat. Your Mechanic Online.',
                'h1'               => 'Car oil change in '.$meta['name'],
                'intro'            => 'Express oil and filter change with free pick-up — ideal for "oil change near me" bookings.',
                'body'             => $oil_body($meta['name'], $meta['city']),
                'page_type'        => 'service',
                'city_slug'        => $meta['city'],
                'service_key'      => 'lube',
                'updated_at'       => $today,
                'pricing_tiers'    => marketing_service_pricing_tiers('lube'),
                'view'             => 'marketing/page',
            );
        }

        $luxury_brands = array(
            'bmw'      => array('name' => 'BMW', 'models' => '3 Series, 5 Series, X1, X3'),
            'mercedes' => array('name' => 'Mercedes-Benz', 'models' => 'C-Class, E-Class, GLC'),
            'audi'     => array('name' => 'Audi', 'models' => 'A4, A6, Q3, Q5'),
        );
        foreach ($luxury_brands as $slug => $brand) {
            $path = $slug.'-car-service-in-pune';
            $pages[$path] = array(
                'title'            => $brand['name'].' Service in Pune | YMO',
                'meta_title'       => $brand['name'].' Car Service Pune | From ₹2,999 | YMO',
                'meta_description' => $brand['name'].' servicing in Pune — '.$brand['models'].'. Periodic service from premium workshop pricing; free pick-up. Book Your Mechanic Online.',
                'h1'               => $brand['name'].' car service in Pune',
                'intro'            => 'Specialist periodic service, diagnostics, AC, and body work for '.$brand['name'].' owners in Pune.',
                'body'             => marketing_luxury_brand_draft_body_html($slug, $brand)
                    .'<p class="md-body-md"><a href="/premium-luxury-car-service-pune">All luxury brands in Pune</a> · <a href="/brands">Other brands</a></p>',
                'page_type'        => 'brand',
                'city_slug'        => 'pune',
                'brand_slug'       => $slug,
                'brand_name'       => $brand['name'],
                'updated_at'       => $today,
                'view'             => 'marketing/page',
            );
        }

        // No per-service "from" ₹ — cost varies by make, model, and parts; use price_on_quote + quote tiers.
        $extra_services = array(
            'battery-replacement'        => 'Car Battery Replacement',
            'tyres-wheel-alignment'      => 'Tyres & Wheel Alignment',
            'clutch-repair'              => 'Clutch Repair',
            'suspension-repair'          => 'Suspension Repair',
            'car-wash-detailing'         => 'Car Wash & Detailing',
            'ceramic-coating'            => 'Ceramic Coating',
            'insurance-claim-assistance' => 'Insurance Claim Assistance',
            'pre-purchase-inspection'    => 'Pre-Purchase Inspection',
            'towing-roadside-assistance' => 'Towing & Roadside Assistance',
        );
        $quote_pricing_note = '<p class="md-body-md ymo-muted">Final cost depends on your car\'s make, model, and condition. '
            .'We inspect first and share an upfront quote — you approve before we start work.</p>';
        foreach (array('pune', 'indore', 'nashik') as $city_slug) {
            foreach ($extra_services as $key => $svc_title) {
                $suffix = $city_slug === 'pune' ? '' : '-in-'.$city_slug;
                $path = 'services/car-'.$key.$suffix;
                if ($city_slug !== 'pune' && $key === 'ceramic-coating') {
                    continue;
                }
                $city_name = ucfirst($city_slug);
                $pages[$path] = array(
                    'title'            => $svc_title.' in '.$city_name.' | YMO',
                    'meta_title'       => $svc_title.' '.$city_name.' | Upfront Quote | YMO',
                    'meta_description' => $svc_title.' in '.$city_name.' with free pick-up. Price varies by vehicle — quote after inspection, no surprise work. Book Your Mechanic Online.',
                    'h1'               => $svc_title.' in '.$city_name,
                    'intro'            => 'Doorstep '.$svc_title.' in '.$city_name.' — we quote after inspection because every car is different.',
                    'body'             => '<h2 class="md-headline-md">What\'s included</h2>'.marketing_extra_service_checklist_html($key).$quote_pricing_note,
                    'page_type'        => 'service',
                    'city_slug'        => $city_slug,
                    'price_on_quote'   => TRUE,
                    'pricing_tiers'    => marketing_quote_only_pricing_tiers(),
                    'updated_at'       => $today,
                    'view'             => 'marketing/page',
                );
            }
        }

        $pages['car-amc-plans'] = array(
            'title'            => 'Car AMC Plans | YMO',
            'meta_title'       => 'Car AMC Subscription Plans | YMO',
            'meta_description' => 'Annual maintenance contracts for car servicing in Pune, Indore and Nashik — 2–3 scheduled visits per year with free pick-up. From ₹5,499/year. Book with Your Mechanic Online.',
            'h1'               => 'Car AMC plans',
            'intro'            => 'Save on periodic service with YMO maintenance plans — ideal if you prefer fixed annual budgeting over one-off bookings.',
            'body'             => '<p class="md-body-md mb-4">Indicative annual plans for hatchback, sedan, and SUV owners in Pune, Indore, and Nashik. Final price depends on your car model and oil grade — we confirm before you pay.</p>'
                .'<div class="row g-4">'
                .'<div class="col-md-4"><div class="md-card-elevated p-4"><h3 class="md-title-md">Basic AMC</h3>'
                .'<p class="md-body-md mb-2"><strong>From ₹5,499 / year</strong></p>'
                .'<ul class="md-body-md mb-0"><li>2 periodic services / year (hatchback or sedan)</li><li>Free pick-up &amp; drop each visit</li><li>Standard oil &amp; filter package</li><li>WhatsApp updates</li></ul></div></div>'
                .'<div class="col-md-4"><div class="md-card-elevated p-4"><h3 class="md-title-md">Plus AMC</h3>'
                .'<p class="md-body-md mb-2"><strong>From ₹7,999 / year</strong></p>'
                .'<ul class="md-body-md mb-0"><li>2 periodic + 1 AC check / year</li><li>Brake inspection each visit</li><li>Priority scheduling</li><li>5% off add-on repairs booked in the same year</li></ul></div></div>'
                .'<div class="col-md-4"><div class="md-card-elevated p-4"><h3 class="md-title-md">Premium AMC</h3>'
                .'<p class="md-body-md mb-2"><strong>From ₹11,999 / year (SUV / MUV)</strong></p>'
                .'<ul class="md-body-md mb-0"><li>3 visits / year incl. major-interval check</li><li>SUV / MUV oil quantities included in plan</li><li>Dedicated service coordinator</li><li>Best-effort same-day slots</li></ul></div></div></div>'
                .'<p class="md-body-md mt-4 mb-0">Wear parts (pads, batteries, tyres) and accident damage are billed separately with approval. <a href="/contact-us">Contact us</a> for a quote on your car.</p>',
            'page_type'        => 'minimal',
            'updated_at'       => $today,
            'view'             => 'marketing/page',
        );

        return $pages;
    }
}

if (!function_exists('marketing_complete_service_checklist_html')) {
    function marketing_complete_service_checklist_html()
    {
        return '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">What\'s included</h2>'
            .'<h3 class="md-title-md">Every 10,000 km periodic service (typical for many Maruti, Hyundai, and Tata models)</h3>'
            .'<ul class="md-body-md"><li>Engine oil and filter change</li><li>Air filter check / clean</li><li>Brake cleaning and inspection</li><li>AC filter service</li><li>Coolant top-up where needed</li><li>Tyre pressure and fluid level checks</li><li>Interior vacuum and exterior wash</li><li>Multi-point inspection with WhatsApp updates</li></ul>'
            .'<h3 class="md-title-md mt-4">20,000 km service typically adds</h3>'
            .'<ul class="md-body-md"><li>Brake fluid check</li><li>Wheel alignment recommendation if uneven wear</li><li>Deeper underbody and suspension visual check</li></ul>'
            .'<h3 class="md-title-md mt-4">40,000 km / major interval often adds</h3>'
            .'<ul class="md-body-md"><li>Spark plug replacement (petrol, where applicable)</li><li>Transmission / brake fluid service per manufacturer schedule</li><li>Belt and hose condition review</li><li>Battery load test</li></ul>'
            .'<p class="md-body-sm ymo-muted">Service intervals follow your manufacturer schedule (some brands use 15,000 km). Package inclusions may vary slightly by model — your booking estimate lists exactly what is included.</p></div>';
    }
}

if (!function_exists('marketing_seo_project_enrich_page')) {
    /** @return array */
    function marketing_seo_project_enrich_page($path, array $page)
    {
        $path = trim((string) $path, '/');
        if (!empty($page['body']) && is_string($page['body'])) {
            $page['body'] = marketing_apply_business_nap_to_html($page['body']);
        }
        if (strpos($path, 'services/complete-car-servicing') === 0 && strpos($page['body'] ?? '', 'What\'s included') === FALSE) {
            $page['body'] = ($page['body'] ?? '').marketing_complete_service_checklist_html();
            $page['updated_at'] = '2026-10-05';
        }
        if ($path === 'premium-luxury-car-service-pune') {
            $page['pricing_tiers'] = array(
                array('label' => 'BMW periodic service', 'price' => 'From 20,000'),
                array('label' => 'Mercedes-Benz periodic service', 'price' => 'From 25,000'),
                array('label' => 'Audi periodic service', 'price' => 'From 20,000'),
                array('label' => 'Luxury AC / brake / diagnostics', 'price' => 'On inspection'),
            );
            if (strpos($page['body'] ?? '', 'bmw-car-service-in-pune') === FALSE) {
                $page['body'] = ($page['body'] ?? '')
                    .'<p class="md-body-md mt-3"><a href="/bmw-car-service-in-pune">BMW service</a> · '
                    .'<a href="/mercedes-car-service-in-pune">Mercedes service</a> · '
                    .'<a href="/audi-car-service-in-pune">Audi service</a></p>';
            }
        }
        if (preg_match('#^locations/(pune|indore|nashik)$#', $path)) {
            $page['body'] = ($page['body'] ?? '').marketing_city_hub_discovery_links_html($path);
        }
        if (!empty($page['page_type']) && $page['page_type'] === 'locality') {
            $page['body'] = ($page['body'] ?? '').marketing_locality_uniqueness_placeholder_html($path, $page);
        }
        if ($path === 'blog/best-oil-change-service-in-pune') {
            $cta = '<div class="md-card-filled p-4 my-4"><p class="md-body-md mb-2"><strong>Book oil change in Pune</strong></p>'
                .'<a href="/services/car-oil-change" class="md-btn md-btn--filled">Car oil change service</a></div>';
            if (strpos($page['body'] ?? '', 'services/car-oil-change') === FALSE) {
                $page['body'] = ($page['body'] ?? '').$cta;
            }
        }
        if ($path === 'blog') {
            $page['body'] = ($page['body'] ?? '')
                .'<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Car service cost guides</h2>'
                .'<ul class="md-body-md"><li><a href="/blog/car-service-cost-pune">Car service cost — Pune</a></li>'
                .'<li><a href="/blog/car-service-cost-indore">Indore</a></li>'
                .'<li><a href="/blog/car-service-cost-nashik">Nashik</a></li>'
                .'<li><a href="/blog/car-denting-painting-cost-pune">Denting &amp; painting cost — Pune</a></li>'
                .'<li><a href="/blog/car-ac-service-cost-pune">AC service cost — Pune</a></li></ul></div>';
        }
        if (preg_match('#^blog/(car-service-cost|car-denting-painting-cost|car-ac-service-cost|maruti-swift|hyundai-i20|car-ac-gas-recharge-cost)#', $path)) {
            $page = marketing_blog_cost_guide_outline($path, $page);
            $page['page_type'] = 'blog';
        }
        if ($path === '2023/07/18/know-the-benefits-of-regular-oil-changes-for-your-car-in-summer') {
            $page['suppress_body_h1'] = TRUE;
            if (!empty($page['body'])) {
                $page['body'] = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $page['body'], 1);
            }
        }
        if (!empty($page['meta_title'])) {
            $page['meta_title'] = marketing_trim_meta_title($page['meta_title']);
        }
        if (!empty($page['meta_description'])) {
            $page['meta_description'] = marketing_trim_meta_description($page['meta_description']);
        }
        return $page;
    }
}

if (!function_exists('marketing_trim_meta_title')) {
    function marketing_trim_meta_title($title)
    {
        $title = trim((string) $title);
        if (strlen($title) <= 60) {
            return $title;
        }
        $short = preg_replace('/\s*-\s*Your Mechanic Online\s*$/i', ' | YMO', $title);
        if (strlen($short) <= 60) {
            return $short;
        }
        return mb_substr($short, 0, 57).'…';
    }
}

if (!function_exists('marketing_trim_meta_description')) {
    function marketing_trim_meta_description($desc)
    {
        $desc = trim((string) $desc);
        if (strlen($desc) <= 155) {
            return $desc;
        }
        return mb_substr($desc, 0, 152).'…';
    }
}

if (!function_exists('marketing_city_cost_guide_bundle')) {
    /**
     * Full cost-guide body + FAQ for blog/car-service-cost-{city}.
     *
     * @return array{body:string,faq:array<int,array{q:string,a:string}>}
     */
    function marketing_city_cost_guide_bundle($city_slug)
    {
        $city_slug = strtolower(trim((string) $city_slug));
        $names = array('pune' => 'Pune', 'indore' => 'Indore', 'nashik' => 'Nashik');
        $city_name = isset($names[$city_slug]) ? $names[$city_slug] : ucfirst($city_slug);
        $svc_suffix = $city_slug === 'pune' ? '' : '-in-'.$city_slug;
        $parts = marketing_business_field('parts_policy');
        $major = marketing_business_field('major_service_note');
        $dealer = marketing_business_field('dealer_comparison_hatchback');
        $author = marketing_business_field('content_author_byline');
        $matrix = marketing_pricing_matrix_html();
        $faq = array(
            array(
                'q' => 'Is doorstep car service more expensive in '.$city_name.'?',
                'a' => 'No — YMO pricing is comparable to local workshops, and free pick-up and drop saves time and fuel.',
            ),
            array(
                'q' => 'Are parts included in the service cost?',
                'a' => 'Engine oil and filter are included in standard periodic packages. Wear parts are quoted separately and fitted only with your approval.',
            ),
            array(
                'q' => 'How long does a service take?',
                'a' => 'Most periodic services finish the same day; major intervals or extra repairs may take longer depending on findings.',
            ),
        );
        $body = '<div class="ymo-content-section mb-5">'
            .'<p class="md-body-md mb-3">Indicative car service costs in '.$city_name.' — use the table to budget by body type. Final quotes depend on your model and condition.</p>'
            .'<p class="md-body-sm ymo-muted mb-4">Last updated October 2026. Figures match our published <a href="/pricing">price list</a>; your quote may differ by model and condition.</p>'
            .'<h2 class="md-headline-md mb-3">Price table by car segment ('.$city_name.')</h2>'.$matrix
            .'<h2 class="md-headline-md mt-4 mb-3">What\'s included in each package</h2>'
            .'<p class="md-body-md mb-3"><strong>Periodic / complete servicing</strong> from ₹2,499 (hatchback/sedan) includes oil and filter, brake cleaning, AC filter service, checks, wash, and free pick-up. '
            .'<a href="/services/complete-car-servicing'.$svc_suffix.'">Complete car servicing in '.$city_name.'</a>.</p>'
            .'<p class="md-body-md mb-3"><strong>Oil change</strong> — faster visit between full services: <a href="/services/car-oil-change'.$svc_suffix.'">oil change in '.$city_name.'</a>.</p>'
            .'<p class="md-body-md mb-3"><strong>Major intervals:</strong> '.html_escape($major).'</p>'
            .'<h2 class="md-headline-md mt-4 mb-3">YMO vs authorised service centre</h2>'
            .'<p class="md-body-md mb-3">Free pick-up, WhatsApp approvals, and workshop equipment for AC, body, and brakes. Dealer periodic (hatchback, indicative): '.html_escape($dealer).'</p>'
            .'<p class="md-body-md mb-3"><strong>Parts:</strong> '.html_escape($parts).'</p>'
            .'<div class="md-card-outlined p-4 my-4"><h2 class="md-title-md mb-2">About this guide</h2>'
            .'<p class="md-body-md mb-0">'.html_escape($author).'</p></div>'
            .'<p class="md-body-md mt-3 mb-0"><a href="/locations/'.$city_slug.'">Car servicing in '.$city_name.'</a> · '
            .'<a href="/services/car-air-conditioner-servicing'.$svc_suffix.'">AC servicing</a> · '
            .'<a href="/pricing">Full price list</a></p></div><!-- ymo-cost-guide-expanded -->';
        return array('body' => $body, 'faq' => $faq);
    }
}

if (!function_exists('marketing_blog_cost_guide_outline')) {
    function marketing_blog_cost_guide_outline($path, array $page)
    {
        $page['page_type'] = 'blog';
        $page['updated_at'] = '2026-10-05';
        if (!empty($page['body']) && strpos($page['body'], 'ymo-cost-guide-expanded') !== FALSE) {
            return $page;
        }
        if (preg_match('#^blog/car-service-cost-(pune|indore|nashik)$#', $path, $m)) {
            $bundle = marketing_city_cost_guide_bundle($m[1]);
            $page['body'] = $bundle['body'];
            $page['faq'] = $bundle['faq'];
            return $page;
        }
        $parts = marketing_business_field('parts_policy');
        $author = marketing_business_field('content_author_byline');
        $outline = '<div class="ymo-blog-outline ymo-content-section mt-5">'
            .'<h2 class="md-headline-md mb-3">Price guide by car type</h2>'
            .'<p class="md-body-md mb-3">Example hatchbacks (Maruti Swift, Hyundai i20) usually match hatchback column pricing; sedans (Honda City, Hyundai Verna) and SUVs (Creta, Nexon) use the rows below. See the full <a href="/pricing">YMO price list</a>.</p>'
            .marketing_pricing_matrix_html()
            .'<h2 class="md-headline-md mt-4 mb-3">What\'s included</h2>'
            .'<p class="md-body-md mb-3">Periodic service includes engine oil and filter, brake cleaning, AC filter service, fluid checks, wash, and free pick-up. '
            .'<a href="/services/complete-car-servicing">Complete car servicing</a> · <a href="/services/car-oil-change">Oil change</a>.</p>'
            .'<h2 class="md-headline-md mt-4 mb-3">YMO vs authorised service centre</h2>'
            .'<p class="md-body-md mb-3">Free pick-up and drop, WhatsApp approval before extra parts, and full workshop capability for AC, body, and brakes. Parts policy: '.html_escape($parts).'</p>'
            .'<h2 class="md-headline-md mt-4 mb-3">Quick FAQs</h2>'
            .'<p class="md-body-md mb-3"><em>Is pick-up free?</em> Yes, across our Pune, Indore, and Nashik service areas.</p>'
            .'<p class="md-body-md mb-3"><em>When do I pay?</em> '.html_escape(marketing_business_field('payment_policy')).'</p>'
            .'<p class="md-body-md mb-3"><em>About this guide:</em> '.html_escape($author).'</p></div>';
        if (strpos($path, 'car-denting-painting-cost') !== FALSE) {
            $outline .= '<h3 class="md-title-md mt-4">कार डेंटिंग पेंटिंग कितने रुपए है?</h3>'
                .'<p class="md-body-md">पुणे / इंदौर में एक पैनल डेंटिंग और पेंटिंग आमतौर पर ₹4,500 से शुरू (हैचबैक) — क्षति, रंग मैच और पैनल की संख्या पर निर्भर। YMO मुफ्त पिक-अप और स्पष्ट कोटेशन के साथ काम करता है।</p>';
        }
        $page['body'] = ($page['body'] ?? '').$outline;
        return $page;
    }
}

if (!function_exists('marketing_city_hub_discovery_links_html')) {
    function marketing_city_hub_discovery_links_html($hub_path)
    {
        $city_slug = basename($hub_path);
        $city_name = ucfirst($city_slug);
        $html = '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Brands &amp; services in '.$city_name.'</h2>'
            .'<p class="md-body-md mb-2"><a href="/brands">All car brands we service</a> · <a href="/services">Full service catalogue</a></p>';
        if ($city_slug === 'pune') {
            $html .= '<p class="md-body-md mb-0"><a href="/services/car-oil-change">Oil change</a> · '
                .'<a href="/premium-luxury-car-service-pune">Luxury car service</a> · <a href="/car-amc-plans">AMC plans</a></p>';
        } else {
            $html .= '<p class="md-body-md mb-0"><a href="/services/car-oil-change-in-'.$city_slug.'">Oil change in '.$city_name.'</a></p>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('marketing_locality_uniqueness_placeholder_html')) {
    function marketing_locality_uniqueness_placeholder_html($path, array $page)
    {
        $label = !empty($page['locality_label']) ? $page['locality_label'] : 'this area';
        $city_slug = !empty($page['city_slug']) ? $page['city_slug'] : 'pune';
        $city_name = ucfirst($city_slug);
        $nearby = !empty($page['locality_slug']) ? $page['locality_slug'] : '';
        return '<div class="ymo-content-section mb-5 ymo-locality-unique"><h2 class="md-headline-md mb-2">In '.$label.'</h2>'
            .'<p class="md-body-md mb-3">We offer free pick-up and drop across '.$label.' and neighbouring streets in '.$city_name.'. '
            .'Same-day slots Mon–Sat, 9 AM–8 PM; we call before collection and usually arrive within 60–90 minutes of your confirmed window.</p>'
            .'<p class="md-body-md mb-3"><strong>Popular cars we service here:</strong> Maruti Swift and WagonR, Hyundai i20 and Creta, Tata Nexon, Honda City, and Mahindra SUVs — see all <a href="/brands">brands we service</a>.</p>'
            .'<p class="md-body-md mb-3"><strong>Common jobs in '.$label.':</strong> periodic service before outstation trips, AC gas and cooling checks in summer, brake pad replacement after city driving, and single-panel denting from tight parking.</p>'
            .'<p class="md-body-md mb-3"><strong>For pick-up:</strong> share your society, office, or shop name and a nearby landmark when we confirm — it helps our driver find you quickly.</p>'
            .'<p class="md-body-md mb-0"><strong>More in '.$city_name.':</strong> <a href="/locations/'.$city_slug.'">All areas we serve in '.$city_name.'</a> · <a href="/services">Service catalogue</a>.</p></div>';
    }
}

if (!function_exists('marketing_seo_project_enrich_pages')) {
    function marketing_seo_project_enrich_pages(array $pages)
    {
        foreach ($pages as $path => $page) {
            if (!is_array($page)) {
                continue;
            }
            $pages[$path] = marketing_seo_project_enrich_page($path, $page);
        }
        return $pages;
    }
}
