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
        $v2  = function_exists('marketing_is_theme_v2') && marketing_is_theme_v2('', array());
        $html = '<div class="row g-4'.($v2 ? ' ymo-v2-card-grid' : '').'">';
        foreach ($rows as $row) {
            $stars = str_repeat('★', (int) ($row['rating'] ?? 5));
            $review_card_class = 'md-card-elevated h-100 p-4';
            if ($v2) {
                $review_card_class .= ' ymo-v2-card ymo-v2-card--static ymo-v2-card--quote';
            }
            $html .= '<div class="col-md-4 d-flex"><div class="'.$review_card_class.'">';
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
                'quick_answer'     => 'Quick answers about booking and servicing with YMO.',
                'hero_split'       => TRUE,
                'og_image'         => marketing_default_content_hero_image(),
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
                'quick_answer'     => 'Four simple steps from booking to delivery.',
                'hero_split'       => TRUE,
                'hero_eyebrow'     => 'Doorstep car service · 4 steps',
                'hero_eyebrow_icon'=> 'route',
                'og_image'         => marketing_default_content_hero_image(),
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
        $step_shell = 'md-card-outlined p-4 h-100';
        $step_row   = 'row g-4';
        if (function_exists('marketing_is_theme_v2') && marketing_is_theme_v2('', array())) {
            $step_shell .= ' ymo-v2-card ymo-v2-card--static ymo-v2-card--step';
            $step_row   .= ' ymo-v2-card-grid';
        }
        return '<div class="'.$step_row.'">'
            .'<div class="col-md-6 col-lg-3 d-flex"><div class="'.$step_shell.'"><span class="ymo-v2-card__step md-label-lg d-block mb-2">1. Book</span><p class="md-body-md mb-0">Choose a service or package online and confirm your slot.</p></div></div>'
            .'<div class="col-md-6 col-lg-3 d-flex"><div class="'.$step_shell.'"><span class="ymo-v2-card__step md-label-lg d-block mb-2">2. Free pick-up</span><p class="md-body-md mb-0">We collect your car from your doorstep at the scheduled time.</p></div></div>'
            .'<div class="col-md-6 col-lg-3 d-flex"><div class="'.$step_shell.'"><span class="ymo-v2-card__step md-label-lg d-block mb-2">3. Updates &amp; approval</span><p class="md-body-md mb-0">WhatsApp photos and estimates — no surprise work without your OK.</p></div></div>'
            .'<div class="col-md-6 col-lg-3 d-flex"><div class="'.$step_shell.'"><span class="ymo-v2-card__step md-label-lg d-block mb-2">4. Delivery</span><p class="md-body-md mb-0">We return your car with invoice and service summary.</p></div></div>'
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
        $quote_services = array(
            'Battery replacement',
            'Clutch repair',
            'Suspension repair',
            'Tyres & alignment',
        );
        $major_note = marketing_business_field('major_service_note');

        $html = '<div class="ymo-v2-pricing-matrix">';
        $html .= '<div class="table-responsive ymo-v2-pricing-matrix__scroll">';
        $html .= '<table class="table ymo-v2-pricing-table__grid md-body-md">';
        $html .= '<thead><tr>';
        $html .= '<th scope="col" class="ymo-v2-pricing-table__service">Service</th>';
        foreach (array('Hatchback', 'Sedan', 'SUV', 'Luxury') as $label) {
            $html .= '<th scope="col" class="ymo-v2-pricing-table__price">'.html_escape($label).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        $vehicle_labels = array(
            'hatchback' => 'Hatchback',
            'sedan'     => 'Sedan',
            'suv'       => 'SUV',
            'luxury'    => 'Luxury',
        );
        foreach ($services as $name => $cols) {
            $html .= '<tr><th scope="row" class="ymo-v2-pricing-table__service">'.html_escape($name).'</th>';
            foreach ($cols as $key => $price) {
                $label = isset($vehicle_labels[$key]) ? $vehicle_labels[$key] : '';
                $html .= '<td class="ymo-v2-pricing-table__price" data-label="'.html_escape($label).'">'
                    .html_escape($price).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        $html .= '<div class="ymo-v2-pricing-matrix__callout">';
        $html .= '<h3 class="md-title-md mb-2">Major / high-km service</h3>';
        $html .= '<p class="md-body-md mb-0">'.html_escape($major_note).'</p>';
        $html .= '</div>';

        $html .= '<div class="ymo-v2-pricing-matrix__quoted">';
        $html .= '<h3 class="md-title-md mb-1">Quoted after inspection</h3>';
        $html .= '<p class="md-body-md ymo-muted mb-3">Same for all vehicle types — we diagnose first, then share an itemised estimate.</p>';
        $html .= '<ul class="ymo-v2-pricing-matrix__quote-list md-body-md mb-0">';
        foreach ($quote_services as $label) {
            $html .= '<li>'.html_escape($label).'</li>';
        }
        $html .= '</ul></div>';

        $html .= '</div>';
        $html .= '<p class="md-body-sm ymo-muted ymo-v2-pricing-matrix__footnote">Same indicative prices apply across Pune, Indore, and Nashik unless noted on the service page.</p>';

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
            'electrical-repair' => array(
                'Fault-code scan and electrical system diagnosis',
                'Battery, starter motor and alternator checks',
                'Wiring, fuse and connector repair',
                'Lights, horn, power windows and central locking',
                'Quote approved before any part is replaced',
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

if (!function_exists('marketing_oil_change_service_body_html')) {
    /** @param string $city_slug pune|indore|nashik */
    function marketing_oil_change_service_body_html($city_slug, $city_name = '')
    {
        $city_slug = trim((string) $city_slug);
        if ($city_name === '') {
            $city_name = ucfirst($city_slug);
        }
        $phone = marketing_business_phone_display();
        $hub = 'locations/'.$city_slug;
        $items = array(
            'Engine oil and filter (grade matched to your car)',
            'Sump drain and refill to manufacturer capacity',
            'Visual check — belts, hoses, and fluid leaks',
            'Tyre pressure set to door-plate spec',
            'Coolant, brake fluid, and washer fluid top-up where low',
            'Battery terminal visual check',
            'WhatsApp photo update when the job is done',
            'Used oil handled and disposed at our workshop',
            'Typical turnaround 1–2 hours at our workshop',
            'Free doorstep pick-up in '.$city_name,
        );
        $footer = '<div class="ymo-v2-supplement-meta">'
            .'<p class="ymo-v2-supplement-meta__hours md-body-md mb-0">'
            .'<span class="mi mi-sm mi-leading" aria-hidden="true">schedule</span>'
            .'Open now slots: Mon–Sat 9 AM – 8 PM · Call '.html_escape($phone).'</p>'
            .'<p class="ymo-v2-supplement-meta__links md-body-md mb-0">'
            .'<a href="/'.$hub.'">Car service in '.$city_name.'</a> · '
            .'<a href="/services/complete-car-servicing'.($city_slug === 'pune' ? '' : '-in-'.$city_slug).'">Complete servicing</a>'
            .'</p></div>';
        if (function_exists('marketing_build_v2_supplement_block')) {
            return marketing_build_v2_supplement_block(
                'What\'s included',
                $items,
                $footer,
                'Express oil and filter service with workshop checks — ideal between full periodic services.'
            );
        }

        return '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">What\'s included</h2>'
            .'<ul class="md-body-md"><li>'.implode('</li><li>', array_map('html_escape', $items)).'</li></ul>'
            .$footer.'</div>';
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
                'body'             => marketing_oil_change_service_body_html($meta['city'], $meta['name']),
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
            'electrical-repair'          => 'Car Electrical Repair',
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
            'quick_answer'     => 'Annual maintenance plans for Pune, Indore, and Nashik — scheduled periodic visits, free pick-up each time, and upfront pricing from ₹5,499/year.',
            'hero_split'       => TRUE,
            'hero_eyebrow'     => 'Maintenance plans · Pune, Indore & Nashik',
            'hero_eyebrow_icon'=> 'autorenew',
            'og_image'         => '/assets/img/marketing/2022/03/complete-car-servicing-in-pune-1-scaled.jpg',
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

if (!function_exists('marketing_default_content_hero_image')) {
    /** Workshop hero used on trust pages and blogs when no city-specific image is set. */
    function marketing_default_content_hero_image()
    {
        return '/assets/img/marketing/2022/03/complete-car-servicing-in-pune-1-scaled.jpg';
    }
}

if (!function_exists('marketing_apply_content_hero_split')) {
    /**
     * Split hero + og_image for FAQ, guides, blogs, and other content pages (not about/privacy).
     *
     * @param string $path
     * @param array  $page
     * @return array
     */
    function marketing_apply_content_hero_split($path, array $page)
    {
        $path = trim((string) $path, '/');
        if (in_array($path, array('about-us', 'privacy-policy', ''), TRUE)) {
            return $page;
        }

        $type = isset($page['page_type']) ? (string) $page['page_type'] : '';
        $split_paths = array('faq', 'how-it-works', 'pricing', 'reviews', 'warranty', 'why-choose-ymo');
        $wants_split = !empty($page['hero_split'])
            || $type === 'blog'
            || $type === 'faq'
            || in_array($path, $split_paths, TRUE)
            || strpos($path, 'blog/') === 0;

        if (!$wants_split) {
            return $page;
        }

        $page['hero_split'] = TRUE;

        $og = !empty($page['og_image']) ? (string) $page['og_image'] : '';
        if ($og === '' || strpos($og, 'revslider/main/image_01') !== FALSE) {
            $city_slug = !empty($page['city_slug']) ? (string) $page['city_slug'] : '';
            $city_img = $city_slug !== '' ? marketing_city_hero_og_image($city_slug) : '';
            $page['og_image'] = $city_img !== '' ? $city_img : marketing_default_content_hero_image();
        }

        if (empty($page['hero_eyebrow'])) {
            if ($path === 'faq') {
                $page['hero_eyebrow'] = 'Help centre · YMO';
                $page['hero_eyebrow_icon'] = 'help_outline';
            } elseif ($path === 'how-it-works') {
                $page['hero_eyebrow'] = 'Doorstep car service · 4 steps';
                $page['hero_eyebrow_icon'] = 'route';
            } elseif ($path === 'pricing') {
                $page['hero_eyebrow'] = 'Transparent pricing · Pune, Indore & Nashik';
                $page['hero_eyebrow_icon'] = 'payments';
            } elseif ($type === 'blog' || strpos($path, 'blog/') === 0) {
                $page['hero_eyebrow'] = 'Car service guide';
                $page['hero_eyebrow_icon'] = 'menu_book';
            }
        }

        if ($path === 'how-it-works' && empty($page['quick_answer']) && !empty($page['intro'])) {
            $page['quick_answer'] = (string) $page['intro'];
        }
        if ($path === 'faq' && empty($page['quick_answer']) && !empty($page['intro'])) {
            $page['quick_answer'] = (string) $page['intro'];
        }

        return $page;
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
        $oil_city_map = array(
            'services/car-oil-change'           => array('pune', 'Pune'),
            'services/car-oil-change-in-indore' => array('indore', 'Indore'),
            'services/car-oil-change-in-nashik' => array('nashik', 'Nashik'),
        );
        if (isset($oil_city_map[$path]) && function_exists('marketing_oil_change_service_body_html')) {
            $page['body'] = marketing_oil_change_service_body_html($oil_city_map[$path][0], $oil_city_map[$path][1]);
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
        if ($path === 'why-choose-ymo') {
            $page['meta_title'] = 'Why Choose YMO Over Doorstep Mechanics | YMO';
            $page['title'] = $page['meta_title'];
        }
        $page = marketing_apply_content_hero_split($path, $page);
        $page = marketing_apply_keyword_targets($path, $page);
        $page = marketing_apply_keyword_blog_links($path, $page);
        $page = marketing_apply_location_titles($path, $page);
        if ($path === 'blog/complete-vs-periodic-car-service') {
            $page['meta_title'] = 'Complete vs Periodic Car Service | YMO Guide';
            if (!empty($page['title']) && (function_exists('mb_strlen') ? mb_strlen($page['title'], 'UTF-8') : strlen($page['title'])) > 60) {
                $page['title'] = $page['meta_title'];
            }
        }
        if ($path === '2023/07/18/know-the-benefits-of-regular-oil-changes-for-your-car-in-summer') {
            $page['suppress_body_h1'] = TRUE;
            $page['meta_title'] = 'Summer Oil Change Benefits for Your Car | YMO';
            $page['title'] = $page['meta_title'];
            $page['meta_description'] = 'Why summer oil changes matter for engine cooling, fuel use, and wear — plus how to book an oil change in Pune with free pick-up from YMO.';
            $page['meta_robots'] = 'noindex, follow';
            if (!empty($page['body'])) {
                $page['body'] = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $page['body'], 1);
            }
            $page['body'] = ($page['body'] ?? '')
                .'<p class="md-body-md mt-4"><a href="/blog/best-oil-change-service-in-pune">Read our updated oil change guide for Pune</a> · '
                .'<a href="/services/car-oil-change">Book car oil change</a></p>';
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
        // Count characters, not bytes: ₹ and — are 3 bytes each and were truncating valid titles.
        if (marketing_title_len($title) <= 60) {
            return $title;
        }
        $short = preg_replace('/\s*-\s*Your Mechanic Online\s*$/i', ' | YMO', $title);
        if (marketing_title_len($short) <= 60) {
            return $short;
        }
        return mb_substr($short, 0, 57).'…';
    }
}

if (!function_exists('marketing_trim_meta_description')) {
    function marketing_trim_meta_description($desc)
    {
        $desc = trim((string) $desc);
        if (marketing_title_len($desc) <= 155) {
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

if (!function_exists('marketing_locality_facts_config')) {
    /** @return array<string,array<string,array>> */
    function marketing_locality_facts_config()
    {
        static $facts = NULL;
        if ($facts !== NULL) {
            return $facts;
        }
        $file = APPPATH.'config/marketing_locality_facts.php';
        $facts = is_file($file) ? require $file : array();
        return is_array($facts) ? $facts : array();
    }
}

if (!function_exists('marketing_locality_facts_for_page')) {
    /**
     * @return array{landmarks:string,drive_context:string,local_jobs:string}|null
     */
    function marketing_locality_facts_for_page(array $page)
    {
        $city_slug = !empty($page['city_slug']) ? $page['city_slug'] : '';
        $loc_key = !empty($page['locality_slug']) ? $page['locality_slug'] : '';
        if ($city_slug === '' || $loc_key === '') {
            return NULL;
        }
        $cfg = marketing_locality_facts_config();
        if (!isset($cfg[$city_slug][$loc_key]) || !is_array($cfg[$city_slug][$loc_key])) {
            return NULL;
        }
        $row = $cfg[$city_slug][$loc_key];
        $out = array(
            'landmarks'     => isset($row['landmarks']) ? (string) $row['landmarks'] : '',
            'drive_context' => isset($row['drive_context']) ? (string) $row['drive_context'] : '',
            'local_jobs'    => isset($row['local_jobs']) ? (string) $row['local_jobs'] : '',
        );
        if ($out['landmarks'] === '' && $out['drive_context'] === '' && $out['local_jobs'] === '') {
            return NULL;
        }
        return $out;
    }
}

if (!function_exists('marketing_locality_uniqueness_placeholder_html')) {
    function marketing_locality_uniqueness_placeholder_html($path, array $page)
    {
        if (strpos($page['body'] ?? '', 'ymo-locality-unique') !== FALSE) {
            return '';
        }
        $label = !empty($page['locality_label']) ? $page['locality_label'] : 'this area';
        $city_slug = !empty($page['city_slug']) ? $page['city_slug'] : 'pune';
        $city_name = ucfirst($city_slug);
        $facts = marketing_locality_facts_for_page($page);

        $html = '<div class="ymo-content-section mb-5 ymo-locality-unique"><h2 class="md-headline-md mb-2">Car service in '.$label.'</h2>';
        if ($facts !== NULL) {
            $html .= '<p class="md-body-md mb-3"><strong>Landmarks we use for pick-up:</strong> '
                .html_escape($facts['landmarks']).'. Share your society, office, or shop name plus a pin — our driver calls before arrival.</p>';
            if ($facts['drive_context'] !== '') {
                $html .= '<p class="md-body-md mb-3"><strong>Timing:</strong> '.html_escape($facts['drive_context']).' Same-day slots Mon–Sat, 9 AM–8 PM.</p>';
            }
            if ($facts['local_jobs'] !== '') {
                $html .= '<p class="md-body-md mb-3"><strong>Often booked here:</strong> '.html_escape($facts['local_jobs']).'</p>';
            }
        } else {
            $html .= '<p class="md-body-md mb-3">Free pick-up and drop across '.$label.' and nearby streets in '.$city_name.'. '
                .'Same-day slots Mon–Sat, 9 AM–8 PM; we call before collection.</p>';
        }
        $html .= '<p class="md-body-md mb-0"><a href="/locations/'.$city_slug.'">All '.$city_name.' areas</a> · '
            .'<a href="/services">Service catalogue</a> · <a href="/brands">Brands we service</a>.</p></div>';
        return $html;
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
if (!function_exists('marketing_title_len')) {
    function marketing_title_len($s)
    {
        return function_exists('mb_strlen') ? mb_strlen((string) $s, 'UTF-8') : strlen((string) $s);
    }
}

if (!function_exists('marketing_title_pick')) {
    /** First candidate that fits in 60 characters (last one is returned as-is). */
    function marketing_title_pick(array $candidates)
    {
        foreach ($candidates as $c) {
            if (marketing_title_len($c) <= 60) {
                return $c;
            }
        }
        return end($candidates);
    }
}

if (!function_exists('marketing_apply_location_titles')) {
    /**
     * Final title pass: every city / area page names its location in the <title>
     * ("{Service} in {Area}, {City}"), and short titles use the spare space.
     * Runs after all other enrichment so it has the last word.
     */
    function marketing_apply_location_titles($path, array $page)
    {
        $static = array(
            'about-us'      => 'About YMO | Car Service in Pune, Indore & Nashik',
            'pricing'       => 'Car Service Price List | Pune, Indore & Nashik | YMO',
            'how-it-works'  => 'How Doorstep Car Service Works in Pune, Indore & Nashik',
            'car-amc-plans' => 'Car AMC Plans in Pune, Indore & Nashik | YMO',
            'reviews'       => 'Customer Reviews | Car Service in Pune, Indore & Nashik',
            'faq'           => 'Car Service FAQ | Pune, Indore & Nashik | YMO',
            'brands'        => 'Car Brands We Service in Pune, Indore & Nashik | YMO',
            'blog'          => 'Car Care Blog & Cost Guides | Pune, Indore, Nashik | YMO',
            'ymo-spares'    => 'Car Spare Parts Online in India | YMO Spares',
        );
        if (isset($static[$path])) {
            $page['meta_title'] = $static[$path];
            $page['title'] = $static[$path];
            return $page;
        }

        if (!empty($page['title_locked'])) {
            // Hand-written title (e.g. Hinjewadi / Hinjawadi spelling) — leave it alone.
            return $page;
        }

        $type = isset($page['page_type']) ? $page['page_type'] : '';
        $city_slug = isset($page['city_slug']) ? $page['city_slug'] : '';
        if ($city_slug === '' || !in_array($type, array('locality', 'service', 'brand'), TRUE)) {
            return $page;
        }
        $city = marketing_city_by_slug($city_slug);
        if (!$city || empty($city['name'])) {
            return $page;
        }
        $city_name = $city['name'];

        $area = !empty($page['locality_label']) ? (string) $page['locality_label'] : '';
        if ($area === '' && !empty($page['locality_slug']) && !empty($city['localities'][$page['locality_slug']]['label'])) {
            $area = (string) $city['localities'][$page['locality_slug']]['label'];
        }
        $place = $area !== '' ? $area.', '.$city_name : $city_name;

        // H1 should name the location too.
        if (!empty($page['h1']) && stripos($page['h1'], $area !== '' ? $area : $city_name) === FALSE) {
            $h1 = preg_replace('/\s*[|@].*$/u', '', (string) $page['h1']);
            $page['h1'] = trim($h1).' in '.$place;
        } elseif (!empty($page['h1']) && $area !== '' && stripos($page['h1'], $city_name) === FALSE) {
            $h1 = preg_replace('/\s*[|@].*$/u', '', (string) $page['h1']);
            $page['h1'] = preg_replace('/\b'.preg_quote($area, '/').'\b(?!,)/u', $area.', '.$city_name, trim($h1), 1);
        }

        $current = !empty($page['meta_title']) ? (string) $page['meta_title'] : (string) ($page['title'] ?? '');
        // Without meta_title the layout appends " - Your Mechanic Online"; measure what actually renders.
        $rendered = $current;
        if (empty($page['meta_title']) && stripos($current, 'Your Mechanic Online') === FALSE
            && !preg_match('/(?:\||-|—)\s*YMO\s*$/iu', $current)) {
            $rendered = $current.' - Your Mechanic Online';
        }
        $has_place = stripos($current, 'in '.$place) !== FALSE;
        if ($has_place && marketing_title_len($rendered) <= 60) {
            // Already "… in {Area}, {City}" and fits: only pad very short brand titles.
            if ($type === 'brand' && substr_count($current, '|') === 1) {
                $padded = preg_replace('/\s*\|\s*YMO\s*$/u', ' | Free Pickup | YMO', $current);
                if (marketing_title_len($padded) <= 60) {
                    $page['meta_title'] = $page['title'] = $padded;
                }
            }
            return $page;
        }

        $segments = array_map('trim', explode('|', $current));
        $middle = count($segments) > 2 ? implode(' | ', array_slice($segments, 1, -1)) : '';

        if ($type === 'locality') {
            $core = 'Car Service in '.$place;
            $middle = 'From ₹2,499';
        } else {
            // Service / brand label = first segment minus any trailing location words.
            $label = $segments[0];
            $strip = array_filter(array($area, $city_name));
            foreach ($strip as $word) {
                $label = preg_replace('/\s*(?:,|\bin\b)?\s*'.preg_quote($word, '/').'\s*$/iu', '', $label);
            }
            $label = preg_replace('/\s*(?:,|\bin\b)\s*$/iu', '', trim($label));
            if ($type === 'service' && !preg_match('/\bcar\b/i', $label)) {
                $label = 'Car '.$label;
            }
            $core = $label.' in '.$place;
        }

        $candidates = array();
        if ($middle !== '') {
            $candidates[] = $core.' | '.$middle.' | YMO';
        } else {
            $candidates[] = $core.' | Free Pickup | YMO';
        }
        $candidates[] = $core.' | YMO';
        $candidates[] = $core;
        $page['meta_title'] = marketing_title_pick($candidates);
        $page['title'] = $page['meta_title'];

        return $page;
    }
}
if (!function_exists('marketing_kw_section')) {
    /** One content block in the site's standard markup. */
    function marketing_kw_section($heading, array $paragraphs, $extra = '')
    {
        $html = '<div class="ymo-content-section ymo-kw-section mb-5"><h2 class="md-headline-md mb-3">'.$heading.'</h2>';
        $last = count($paragraphs) - 1;
        foreach ($paragraphs as $i => $p) {
            $html .= '<p class="md-body-md '.($i === $last && $extra === '' ? 'mb-0' : 'mb-3').'">'.$p.'</p>';
        }
        return $html.$extra.'</div>';
    }
}

if (!function_exists('marketing_kw_add_faq')) {
    /** Prepend FAQ items, skipping any whose question already exists. */
    function marketing_kw_add_faq(array $page, array $items)
    {
        $existing = array();
        foreach (($page['faq'] ?? array()) as $row) {
            $existing[strtolower(trim($row['q'] ?? ''))] = TRUE;
        }
        $new = array();
        foreach ($items as $row) {
            if (!isset($existing[strtolower($row['q'])])) {
                $new[] = $row;
            }
        }
        $page['faq'] = array_merge($new, $page['faq'] ?? array());
        return $page;
    }
}

if (!function_exists('marketing_kw_city_service_paths')) {
    /** Site paths of the main services for a city. */
    function marketing_kw_city_service_paths($city)
    {
        $s = $city === 'pune' ? '' : '-in-'.$city;
        return array(
            'complete'   => $city === 'pune' ? 'services/complete-car-servicing' : 'services/complete-car-servicing-in-'.$city,
            'ac'         => 'services/car-air-conditioner-servicing-in-'.$city,
            'denting'    => $city === 'pune' ? 'services/car-denting-and-painting-3000' : 'services/car-denting-and-painting-in-'.$city.'-3000',
            'detailing'  => 'services/car-car-wash-detailing'.$s,
            'interior'   => 'services/car-interior-cleaning-in-'.$city.'-2500',
            'polishing'  => 'services/car-rubbing-and-polishing-in-'.$city.'-6500',
            'ceramic'    => $city === 'pune' ? 'services/car-ceramic-coating' : '',
            'electrical' => 'services/car-electrical-repair'.$s,
            'battery'    => 'services/car-battery-replacement'.$s,
            'alignment'  => 'services/car-tyres-wheel-alignment'.$s,
            'oil'        => 'services/car-oil-change'.$s,
            'insurance'  => 'services/car-insurance-claim-assistance'.$s,
            'towing'     => 'services/car-towing-roadside-assistance'.$s,
        );
    }
}

if (!function_exists('marketing_apply_keyword_targets')) {
    /**
     * Idempotent wrapper: marketing_pages_data() re-runs enrichment on pages loaded from the
     * registry cache, so body sections added here must not be added a second time.
     */
    function marketing_apply_keyword_targets($path, array $page)
    {
        $orig = isset($page['body']) ? (string) $page['body'] : '';
        $out = marketing_apply_keyword_targets_once($path, $page);
        if (strpos($orig, 'ymo-kw-section') !== FALSE) {
            $out['body'] = $orig;
        }
        return $out;
    }
}

if (!function_exists('marketing_apply_keyword_targets_once')) {
    /**
     * Keyword targeting from the Nov 2026 Keyword Planner research.
     * City hubs: car service / car garage / car repair / car mechanic / service center + city.
     * Services: wording people search ("car ac repair", "wheel alignment", "car detailing").
     * Pune areas with demand: "car garage {area}". Brand pages: "{brand} service center" alternative.
     * Indore/Nashik locality pages: noindex (no search demand); hubs carry those cities.
     */
    function marketing_apply_keyword_targets_once($path, array $page)
    {
        $path = trim((string) $path, '/');
        $type = $page['page_type'] ?? '';
        $city_slug = $page['city_slug'] ?? '';
        $names = array('pune' => 'Pune', 'indore' => 'Indore', 'nashik' => 'Nashik');
        $city_name = $names[$city_slug] ?? '';
        $workshop = array(
            'pune'   => 'Our workshop is in Rajiv Gandhi Infotech Park, Phase 1, Hinjewadi (Hinjawadi)',
            'indore' => 'Your car is serviced at our Indore workshop',
            'nashik' => 'Your car is serviced at our Nashik workshop',
        );
        $link = function ($href, $text) {
            return '<a href="/'.html_escape(ltrim($href, '/')).'">'.$text.'</a>';
        };
        $lock = function (array $page, array $candidates) {
            $page['meta_title'] = marketing_title_pick($candidates);
            $page['title'] = $page['meta_title'];
            $page['title_locked'] = TRUE;
            return $page;
        };

        // ---- City hubs ------------------------------------------------------------
        if (preg_match('#^locations/(pune|indore|nashik)$#', $path, $m)) {
            $c = $m[1];
            $n = $names[$c];
            $sp = marketing_kw_city_service_paths($c);
            $garage_first = $c === 'nashik';
            $page = $lock($page, $garage_first
                ? array('Car Garage & Car Service in '.$n.' | From ₹2,499 | YMO', 'Car Garage & Car Service in '.$n.' | YMO')
                : array('Car Service & Car Garage in '.$n.' | From ₹2,499 | YMO', 'Car Service & Car Garage in '.$n.' | YMO'));
            $page['h1'] = $garage_first
                ? 'Car garage, car service & repair in '.$n
                : 'Car service, repair & car garage in '.$n;
            $page['meta_description'] = $c === 'pune'
                ? 'Car service, car repair and a full multi-brand car garage in Pune. Workshop in Hinjewadi Phase 1, free pick-up, WhatsApp updates, from ₹2,499.'
                : ($garage_first ? 'Car garage and car service in '.$n : 'Car service and car garage in '.$n).'. Multi-brand car repair, AC, denting and more with free pick-up, WhatsApp updates and prices from ₹2,499.';
            $page['intro'] = 'A multi-brand car garage and car service center for '.$n.': periodic service, car repair, AC, denting and electrical work, with free pick-up and drop.';
            $page['quick_answer'] = 'Your Mechanic Online is a multi-brand car garage in '.$n.'. '.$workshop[$c].'. Free pick-up and drop, WhatsApp photo updates at every stage, and complete car service from ₹2,499.';

            $services = array(
                array($sp['complete'], 'Complete car service', 'from ₹2,499'),
                array($sp['ac'], 'Car AC repair &amp; service', 'from ₹999'),
                array($sp['denting'], 'Car denting &amp; painting', 'from ₹4,500 per panel'),
                array($sp['electrical'], 'Car electrical repair', 'quote after diagnosis'),
                array($sp['battery'], 'Car battery replacement', 'quote after test'),
                array($sp['alignment'], 'Wheel alignment &amp; tyres', 'quote after inspection'),
                array($sp['detailing'], 'Car detailing &amp; car wash', 'quote after inspection'),
                array($sp['oil'], 'Car oil change', 'from ₹1,499'),
            );
            if ($sp['ceramic'] !== '') {
                $services[] = array($sp['ceramic'], 'Ceramic coating', 'quote after inspection');
            }
            $items = '';
            foreach ($services as $row) {
                $items .= '<li class="mb-2">'.$link($row[0], $row[1]).' <span class="ymo-muted">('.$row[2].')</span></li>';
            }
            $area_line = $c === 'pune'
                ? 'Free pick-up covers Hinjewadi, Wakad, Baner, Aundh, Kothrud, Pimple Saudagar, Kharadi, Hadapsar and the other neighbourhoods listed below.'
                : 'Free pick-up covers the neighbourhoods listed below and nearby areas of '.$n.'.';
            $section = marketing_kw_section(
                ($garage_first ? 'Car garage and car service in ' : 'Car service, repair and car garage in ').$n,
                array(
                    'Your Mechanic Online is a multi-brand car garage and car service center for '.$n.'. '.$workshop[$c].'. We pick your car up from home or office, service or repair it at the workshop, and drop it back, with WhatsApp photos and videos at every stage and an estimate you approve before any work starts.',
                    'Need a car mechanic in '.$n.' for a specific problem? Our mechanics handle periodic servicing, car repair, AC repair, denting and painting, electrical faults, batteries, wheel alignment and detailing for all major brands, from hatchbacks to luxury cars.',
                    'Searching for a car garage or car service near you? '.$area_line,
                ),
                '<h3 class="md-title-md mb-2">Popular car services in '.$n.'</h3><ul class="md-body-md mb-0">'.$items.'</ul>'
            );
            $page['body'] = $section.($page['body'] ?? '');
            $faq = array(
                array('q' => 'Is Your Mechanic Online a car garage or a doorstep mechanic?', 'a' => 'A workshop-based car garage. We pick up your car, service or repair it at our '.$n.' workshop with proper equipment, and drop it back. Most jobs are done at the workshop, not in your parking.'),
                array('q' => 'How much does car service cost in '.$n.'?', 'a' => 'Complete car service starts from ₹2,499. AC service starts from ₹999, brake work from ₹499 and denting from ₹4,500 per panel. You approve the estimate first, and on most bookings you pay after delivery.'),
            );
            if ($c === 'pune') {
                array_unshift($faq, array('q' => 'Where is your car garage in Pune?', 'a' => 'Our workshop is in Rajiv Gandhi Infotech Park, Phase 1, Hinjewadi (Hinjawadi), Pune 411057. We pick up cars from across Pune and Pimpri-Chinchwad.'));
            }
            $page = marketing_kw_add_faq($page, $faq);
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // ---- Older Pune area pages built without a locality label: name the area in the heading.
        if ($type === 'locality' && $city_slug === 'pune' && !empty($page['locality_slug'])) {
            $cfg = marketing_cities_config();
            $label = $page['locality_label'] ?? ($cfg['pune']['localities'][$page['locality_slug']]['label'] ?? '');
            if ($label !== '') {
                $page['locality_label'] = $label;
                $page['body'] = str_replace('>Car service in this area<', '>Car service in '.html_escape($label).'<', $page['body'] ?? '');
            }
        }

        // ---- Indore / Nashik locality pages: noindex, point to the city hub -------------
        if ($type === 'locality' && in_array($city_slug, array('indore', 'nashik'), TRUE)) {
            $page['meta_robots'] = 'noindex, follow';
            return $page;
        }

        // ---- Pune areas with "car garage {area}" demand ---------------------------------
        $garage_areas = array(
            'wakad'           => 'Wakad cars are picked up and serviced at our workshop in Hinjewadi Phase 1, a short drive away.',
            'baner'           => 'Baner cars are picked up and serviced at our workshop, then dropped back.',
            'kothrud'         => 'Kothrud cars are picked up and serviced at our workshop, then dropped back.',
            'pimple_saudagar' => 'Pimple Saudagar cars are picked up and serviced at our workshop, then dropped back.',
            'kharadi'         => 'Kharadi cars are picked up and serviced at our workshop, then dropped back.',
            'hadapsar'        => 'Hadapsar cars are picked up and serviced at our workshop, then dropped back.',
            'wagholi'         => 'Wagholi cars are picked up and serviced at our workshop, then dropped back.',
        );
        $loc = $page['locality_slug'] ?? '';
        if ($type === 'locality' && $city_slug === 'pune' && isset($garage_areas[$loc])) {
            $cities = marketing_cities_config();
            $area = $page['locality_label'] ?? ($cities['pune']['localities'][$loc]['label'] ?? ucwords(str_replace('_', ' ', $loc)));
            $sp = marketing_kw_city_service_paths('pune');
            $page = $lock($page, array(
                'Car Service & Garage in '.$area.', Pune | From ₹2,499 | YMO',
                'Car Service & Garage in '.$area.', Pune | YMO',
                'Car Garage & Service in '.$area.', Pune',
            ));
            $page['h1'] = 'Car service & car garage in '.$area.', Pune';
            $page['intro'] = 'Car service, repair and a proper car garage for '.$area.': free pick-up and drop, upfront pricing from ₹2,499 and WhatsApp updates at every stage.';
            $page['meta_description'] = 'Car garage and car service in '.$area.', Pune. Free pick-up, multi-brand repair, AC, denting and periodic service from ₹2,499. WhatsApp updates.';
            $section = marketing_kw_section('Car garage near '.$area, array(
                'Looking for a car garage near you in '.$area.'? '.$garage_areas[$loc].' You get WhatsApp photos and videos while the work is done, and you approve the estimate before anything starts.',
                'Popular with '.$area.' customers: '.$link($sp['complete'], 'complete car service').', '.$link($sp['ac'], 'car AC repair').', '.$link($sp['denting'], 'denting and painting').' and '.$link($sp['electrical'], 'electrical repair').'. See all '.$link('locations/pune', 'car service in Pune').'.',
            ));
            $page['body'] = $section.($page['body'] ?? '');
            $page = marketing_kw_add_faq($page, array(
                array('q' => 'Is there a YMO car garage near '.$area.'?', 'a' => $garage_areas[$loc].' Pick-up and drop are free.'),
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // ---- Brand pages: independent alternative to the brand service center ---------------
        $alt_brands = array('tata', 'maruti-suzuki', 'hyundai', 'kia', 'honda', 'toyota', 'mg', 'volkswagen', 'skoda', 'mahindra');
        if ($type === 'brand' && $city_name !== '' && preg_match('#^([a-z-]+)-car-service-in-(pune|indore|nashik)$#', $path, $bm)
            && in_array($bm[1], $alt_brands, TRUE)) {
            $brand = $page['brand_name'] ?? ucwords(str_replace('-', ' ', $bm[1]));
            $page = $lock($page, array(
                $brand.' Car Service in '.$city_name.' | Multi-Brand Service Center',
                $brand.' Service Center Alternative in '.$city_name.' | YMO',
                $brand.' Car Service in '.$city_name.' | YMO',
            ));
            $page['meta_description'] = $brand.' car service in '.$city_name.' at an independent multi-brand garage, an alternative to the '.$brand.' service center. Free pick-up, upfront prices.';
            $section = marketing_kw_section('Looking for a '.$brand.' service center in '.$city_name.'?', array(
                'Your Mechanic Online is an independent multi-brand car garage, not an authorised '.$brand.' dealer. We service '.$brand.' cars to the manufacturer\'s schedule using OEM, OES or brand-approved parts, explained on your estimate before anything is fitted.',
                'You get upfront pricing, free pick-up and drop, and WhatsApp photo updates at every stage. See '.$link('locations/'.$city_slug, 'car service in '.$city_name).' for everything else we do.',
            ));
            $page['body'] = $section.($page['body'] ?? '');
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // ---- Service pages ------------------------------------------------------------
        if ($city_name === '' || $type !== 'service') {
            return $page;
        }

        // Indore/Nashik copies of quote-only services: city-swapped Pune text with no measurable
        // search demand. Keep live for visitors, keep out of the index. Electrical, detailing, wheel
        // alignment and towing stay indexed (Keyword Planner shows demand in both cities).
        if (in_array($city_slug, array('indore', 'nashik'), TRUE)
            && preg_match('#^services/car-(clutch-repair|suspension-repair|insurance-claim-assistance|pre-purchase-inspection|battery-replacement)-in-(indore|nashik)$#', $path)) {
            $page['meta_robots'] = 'noindex, follow';
            return $page;
        }
        $sp = marketing_kw_city_service_paths($city_slug);
        $hub = $link('locations/'.$city_slug, 'car service in '.$city_name);

        // Area service pages (e.g. car AC repair in Baner): name the area in the description too.
        if (!empty($page['locality_label']) && !empty($page['meta_description'])
            && stripos($page['meta_description'], $page['locality_label']) === FALSE) {
            $page['meta_description'] = preg_replace('/ in '.preg_quote($city_name, '/').'\b/', ' in '.$page['locality_label'].', '.$city_name, $page['meta_description'], 1);
        }
        $near = 'Looking for ';

        // Car AC (main city pages; area AC pages keep their own copy).
        if (preg_match('#^services/car-air-conditioner-servicing-in-(pune|indore|nashik)$#', $path)) {
            $page['h1'] = 'Car AC Repair & Service in '.$city_name;
            $blog = $city_slug === 'pune'
                ? ' Prices are broken down in our '.$link('blog/car-ac-service-cost-pune', 'car AC service cost guide').'.'
                : '';
            $page['body'] = ($page['body'] ?? '').marketing_kw_section('Car AC repair in '.$city_name, array(
                'If your car AC is blowing warm air, cooling slowly, making a noise or smelling musty, bring it to our '.$city_name.' workshop. We find the cause first, whether it is a gas leak, the compressor, condenser, cooling coil, blower or an electrical fault, and quote before any repair. AC gas recharge and service start from ₹999.'.$blog,
                $near.'car AC repair near you? Pick-up and drop are free. AC faults that turn out to be electrical are handled by our '.$link($sp['electrical'], 'car electrical repair').' team. See all '.$hub.'.',
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // Denting & painting.
        if ($path === $sp['denting']) {
            $blog = $city_slug === 'pune' ? $link('blog/car-denting-painting-cost-pune', 'denting and painting cost guide')
                : $link('blog/car-denting-painting-cost-'.$city_slug, 'denting and painting cost guide');
            $page['body'] = ($page['body'] ?? '').marketing_kw_section('Car dent repair and painting in '.$city_name, array(
                'From parking dents and door dings to bumper damage and full-panel repaints, our '.$city_name.' workshop repairs the panel and refinishes it with colour-matched paint. Denting and painting starts from ₹4,500 per panel; see the '.$blog.' for typical prices.',
                $near.'car dent repair near you? Pick-up is free, and for accident damage we help with your '.$link($sp['insurance'], 'insurance claim').'. See all '.$hub.'.',
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // Car wash & detailing.
        if ($path === $sp['detailing']) {
            $page = $lock($page, array(
                'Car Detailing & Car Wash in '.$city_name.' | Free Pickup | YMO',
                'Car Detailing & Car Wash in '.$city_name.' | YMO',
            ));
            $page['h1'] = 'Car Detailing & Car Wash in '.$city_name;
            $page['meta_description'] = 'Car detailing and car wash in '.$city_name.': foam wash, interior deep cleaning, polishing'.($sp['ceramic'] ? ' and ceramic coating' : '').'. Free pick-up, quote after inspection.';
            $ceramic = $sp['ceramic'] ? ', or protect it with '.$link($sp['ceramic'], 'ceramic coating') : '';
            $page['body'] = ($page['body'] ?? '').marketing_kw_section('Car detailing in '.$city_name, array(
                'Detailing goes further than a wash: '.$link($sp['interior'], 'interior deep cleaning').' of seats, carpets and roof lining, and '.$link($sp['polishing'], '3-stage rubbing and polishing').' (from ₹6,500) to bring back the gloss'.$ceramic.'.',
            )).marketing_kw_section('Car wash in '.$city_name, array(
                'An exterior foam wash with interior vacuum and glass cleaning can be booked on its own or added to any service visit. '.$near.'a car wash or car detailing near you? Pick-up and drop are free. See all '.$hub.'.',
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // Ceramic coating (Pune only).
        if ($sp['ceramic'] !== '' && $path === $sp['ceramic']) {
            $page = $lock($page, array('Ceramic Coating in Pune | Car Paint Protection | YMO', 'Ceramic Coating in Pune | YMO'));
            $page['h1'] = 'Ceramic Coating in Pune';
            $page['meta_description'] = 'Ceramic coating in Pune: paint decontamination, polish prep and ceramic layers for long-lasting gloss and protection. Free pick-up, quote after inspection.';
            $page['body'] = ($page['body'] ?? '').marketing_kw_section('Ceramic coating for your car in Pune', array(
                'A ceramic coating bonds to the paint and protects it from UV, bird droppings, tree sap and monsoon grime, and makes washing easier. Coating only works on clean, corrected paint, so we decontaminate first and usually start with '.$link($sp['polishing'], '3-stage rubbing and polishing').' (from ₹6,500).',
                $near.'car polish or ceramic coating near you? Pick-up is free, and we quote after inspecting your paint. See '.$link($sp['detailing'], 'car detailing').' and all '.$hub.'.',
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // Battery replacement.
        if ($path === $sp['battery']) {
            $page['meta_description'] = 'Car battery replacement in '.$city_name.': battery and charging test first, the right battery for your car, old battery disposal. Free pick-up, upfront quote.';
            $page['body'] = ($page['body'] ?? '').marketing_kw_section('Car battery replacement in '.$city_name, array(
                'Slow starts, dim lights or a car that will not start in the morning usually point to the battery, but sometimes the alternator or a fault draining power. We test the battery and charging system first, so you only replace what has actually failed.',
                $near.'a car battery shop near you? We quote the right battery for your model before fitting it. If the car will not start at all, see '.$link($sp['towing'], 'towing and roadside assistance').'. See all '.$hub.'.',
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // Wheel alignment & tyres.
        if ($path === $sp['alignment']) {
            $page = $lock($page, array(
                'Wheel Alignment & Tyres in '.$city_name.' | Upfront Quote | YMO',
                'Wheel Alignment & Tyres in '.$city_name.' | YMO',
            ));
            $page['h1'] = 'Wheel Alignment, Balancing & Tyres in '.$city_name;
            $page['meta_description'] = 'Wheel alignment and balancing in '.$city_name.', plus tyre inspection and replacement quotes. Fix pulling, vibration and uneven wear. Free pick-up.';
            $page['body'] = ($page['body'] ?? '').marketing_kw_section('Wheel alignment in '.$city_name, array(
                'If your car pulls to one side, the steering wheel sits off-centre, or the tyres wear unevenly, the wheels need alignment. Vibration at speed usually means balancing. We align and balance at our '.$city_name.' workshop and check the tyres at the same time.',
                $near.'wheel alignment near you? Pick-up and drop are free, and replacement tyres are quoted by size and brand before fitting. See all '.$hub.'.',
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        // Car towing & roadside assistance (Pune): "towing service pune" 100-1K/month.
        if ($city_slug === 'pune' && $path === $sp['towing']) {
            $page = $lock($page, array(
                'Car Towing Service in Pune | Roadside Assistance | YMO',
                'Car Towing Service in Pune | YMO',
            ));
            $page['h1'] = 'Car Towing & Roadside Assistance in Pune';
            $page['intro'] = 'Car towing and roadside help in Pune: breakdowns, accidents and cars that will not start, towed to our Hinjewadi workshop or a garage you choose.';
            $page['quick_answer'] = 'YMO arranges car towing in Pune to our workshop in Hinjewadi Phase 1 or a garage you choose, and helps on the spot with jump-starts and flat tyres where it is safe. The towing charge is confirmed before help is sent.';
            $page['meta_description'] = 'Car towing service in Pune: breakdown and accident towing to our Hinjewadi workshop or your garage, jump-starts and flat tyres. Charge confirmed upfront.';
            $phone = function_exists('marketing_business_phone_display') ? marketing_business_phone_display() : '';
            $cfg = marketing_cities_config();
            $areas = array();
            foreach (($cfg['pune']['localities'] ?? array()) as $loc) {
                if (!empty($loc['slug']) && !empty($loc['label'])) {
                    $areas[] = $link($loc['slug'], html_escape($loc['label']));
                }
            }
            $steps = '<ol class="md-body-md mb-0">'
                .'<li class="mb-2">Call us'.($phone !== '' ? ' on <strong>'.html_escape($phone).'</strong>' : '').' or message us on WhatsApp with your location pin and what happened.</li>'
                .'<li class="mb-2">We confirm the towing charge before anyone is sent.</li>'
                .'<li class="mb-2">If it is safe and simple, such as a flat battery or a flat tyre, we try to get you moving on the spot.</li>'
                .'<li class="mb-2">Otherwise the car is towed to our workshop in Hinjewadi Phase 1, or to a garage you choose.</li>'
                .'<li class="mb-0">At our workshop we diagnose the fault and send you the estimate with photos on WhatsApp before any repair starts.</li>'
                .'</ol>';
            $page['body'] = ($page['body'] ?? '')
                .marketing_kw_section('Car towing service in Pune', array(
                    'If your car breaks down, will not start, overheats, or cannot be driven after an accident, we arrange a tow so you are not left looking for help at the roadside. Cars are brought to our workshop in Rajiv Gandhi Infotech Park, Phase 1, Hinjewadi, where our mechanics find the fault and fix it, or to any garage you prefer.',
                    'Common reasons people call us: a dead battery, a clutch or gearbox failure, an engine warning light with loss of power, overheating in traffic, a burst tyre without a usable spare, or accident damage. For simple problems, such as a flat battery or a tyre change, we help on the spot where it is safe.',
                ))
                .marketing_kw_section('How towing works', array(), $steps)
                .marketing_kw_section('Where we tow from in Pune', array(
                    'Towing and roadside help cover the same Pune areas as our car service: '.implode(', ', $areas).'.',
                    'After the tow, related repairs are handled at the same workshop: '.$link($sp['battery'], 'battery replacement').', '.$link($sp['electrical'], 'electrical repair').', '.$link($sp['denting'], 'denting and painting').' and help with your '.$link($sp['insurance'], 'insurance claim').' after an accident. See all '.$hub.'.',
                ));
            $page = marketing_kw_add_faq($page, array(
                array('q' => 'Where will my car be towed?', 'a' => 'To our workshop in Rajiv Gandhi Infotech Park, Phase 1, Hinjewadi, Pune, or to any garage you choose.'),
                array('q' => 'How much does car towing cost in Pune?', 'a' => 'It depends mainly on the distance and your car. We confirm the towing charge before help is sent, so there are no surprises.'),
                array('q' => 'Which areas in Pune do you cover for towing?', 'a' => 'The same areas as our car service, including Hinjewadi, Wakad, Baner, Aundh, Bavdhan, Kothrud, Warje, Pimple Saudagar, Viman Nagar, Kharadi, Koregaon Park, Kalyani Nagar, Hadapsar, Magarpatta and Wagholi.'),
                array('q' => 'Can you jump-start my car instead of towing it?', 'a' => 'Often, yes. If the problem is a flat battery or a flat tyre and it is safe to work where the car is, we try to get you moving on the spot first.'),
                array('q' => 'Will you repair the car after towing it?', 'a' => 'If it comes to our workshop, we diagnose the fault and send you an estimate with photos on WhatsApp. Nothing is repaired until you approve it.'),
            ));
            $page['updated_at'] = '2026-11-02';
            return $page;
        }

        // Car oil change: own title (it duplicated the lube/oil/filters page).
        if ($path === $sp['oil']) {
            $page = $lock($page, array(
                'Car Oil Change in '.$city_name.' | From ₹1,499 | YMO',
                'Car Oil Change in '.$city_name.' | YMO',
            ));
            return $page;
        }

        // Car electrical repair (new page).
        if ($path === $sp['electrical']) {
            $page = $lock($page, array(
                'Car Electrical Repair in '.$city_name.' | Upfront Quote | YMO',
                'Car Electrical Repair in '.$city_name.' | YMO',
            ));
            $page['h1'] = 'Car Electrical Repair in '.$city_name;
            $page['intro'] = 'Car electrical repair in '.$city_name.': starting problems, warning lights, wiring faults and electrical accessories, diagnosed before we quote.';
            $page['meta_description'] = 'Car electrical repair in '.$city_name.': starting problems, battery drain, warning lights, wiring, lights, power windows and sensors. Free pick-up, upfront quote.';
            $page['body'] = ($page['body'] ?? '').marketing_kw_section('Car electrical problems we fix in '.$city_name, array(
                'Modern cars rely on electrics for almost everything. We diagnose and repair cars that will not start or keep draining the battery, starter motor and alternator faults, dashboard warning lights, lights, horn, power windows and central locking that stop working, sensor faults, and wiring damaged by accidents or rodents.',
                'We scan for fault codes and trace the fault first, then quote before any repair. '.$near.'a car electrician near you? Pick-up is free. Related: '.$link($sp['battery'], 'battery replacement').' and '.$link($sp['ac'], 'car AC repair').'. See all '.$hub.'.',
            ));
            $page['updated_at'] = '2026-11-01';
            return $page;
        }

        return $page;
    }
}

if (!function_exists('marketing_apply_keyword_blog_links')) {
    /** Cost guides link to the service page they price, with keyword anchor text. */
    function marketing_apply_keyword_blog_links($path, array $page)
    {
        $map = array(
            'blog/car-ac-service-cost-pune'            => array('services/car-air-conditioner-servicing-in-pune', 'Book car AC repair in Pune'),
            'blog/car-ac-gas-recharge-cost-pune-2026'  => array('services/car-air-conditioner-servicing-in-pune', 'Book car AC repair in Pune'),
            'blog/car-denting-painting-cost-pune'      => array('services/car-denting-and-painting-3000', 'Book car denting and painting in Pune'),
            'blog/car-denting-painting-cost-indore'    => array('services/car-denting-and-painting-in-indore-3000', 'Book car denting and painting in Indore'),
            'blog/car-denting-painting-cost-nashik'    => array('services/car-denting-and-painting-in-nashik-3000', 'Book car denting and painting in Nashik'),
            'blog/car-service-cost-pune'               => array('locations/pune', 'Car service and car garage in Pune'),
            'blog/car-service-cost-indore'             => array('locations/indore', 'Car service and car garage in Indore'),
            'blog/car-service-cost-nashik'             => array('locations/nashik', 'Car garage and car service in Nashik'),
        );
        $path = trim((string) $path, '/');
        if (!isset($map[$path])) {
            return $page;
        }
        list($href, $text) = $map[$path];
        if (strpos($page['body'] ?? '', 'href="/'.$href.'"') !== FALSE && strpos($page['body'] ?? '', $text) !== FALSE) {
            return $page;
        }
        $page['body'] = ($page['body'] ?? '').'<div class="md-card-filled p-4 my-4"><p class="md-body-md mb-0"><a href="/'.html_escape($href).'">'.html_escape($text).'</a></p></div>';
        return $page;
    }
}
