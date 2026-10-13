<?php

defined('BASEPATH') OR exit('No direct script access allowed');



/**

 * Single source of truth for NAP, warranty copy, and per-city GBP/geo.

 */

return array(

    'brand_name'       => 'Your Mechanic Online',

    'brand_short'      => 'YMO',

    'legal_entity'     => 'YMO Mobility Solutions Private Limited',

    'phone_display'    => '+91 75587 83868',

    'phone_e164'       => '+917558783868',

    'phone_schema'     => '+91-7558783868',

    'email'            => 'contactus@yourmechaniconline.com',

    'whatsapp_e164'    => '917558783868',

    'parts_policy'     => 'We use OEM, OES, or brand-approved parts matched to your car and the job — alternatives are explained on your estimate before anything is fitted. Customer-supplied parts can be fitted on request where safe and appropriate.',

    'payment_policy'   => 'For most bookings you pay when your car is delivered after the job is complete. If special-order parts or large repairs are needed, we may ask for an advance against the approved quote — UPI, card, and cash are accepted.',

    'warranty_badge'   => 'Up to 6 months warranty on eligible service work',

    'warranty_summary' => 'Eligible periodic service and repair work includes a limited warranty on labour and on parts supplied by YMO, subject to fair use and proper maintenance. Claim with your booking reference and photos where helpful — we will inspect and resolve valid issues within a reasonable timeframe.',

    'major_service_note' => 'Major / high-km interval service (e.g. 40,000 km): from ₹4,999 for hatchback — includes deeper checks and interval items beyond a routine periodic service.',

    'dealer_comparison_hatchback' => 'Indicative authorised-dealer periodic service for popular hatchbacks often falls in the ₹4,500–₹8,000+ range depending on brand and city.',

    'content_author_byline' => 'YMO service operations team, last reviewed October 2026.',

    'opening_hours'    => array(

        'days'  => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),

        'opens' => '09:00',

        'closes'=> '20:00',

    ),

    'cities' => array(

        'pune' => array(

            'name'        => 'Pune',

            'postal_code' => '411057',

            'gbp_url'     => 'https://share.google/zdztabyzbml7l1uvZ',

            'geo'         => array('latitude' => '18.5204', 'longitude' => '73.8567'),

        ),

        'indore' => array(

            'name'        => 'Indore',

            'postal_code' => '452016',

            'gbp_url'     => 'https://share.google/qfMNIc64TpYRRm0WP',

            'geo'         => array('latitude' => '22.7196', 'longitude' => '75.8577'),

        ),

        'nashik' => array(

            'name'        => 'Nashik',

            'postal_code' => '422006',

            'gbp_url'     => 'https://share.google/bDyJTged7DRyDycXF',

            'geo'         => array('latitude' => '19.9975', 'longitude' => '73.7898'),

        ),

    ),

);


