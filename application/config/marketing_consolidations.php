<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEO consolidation 301s - thin/duplicate pages → canonical hubs.
 * Keys: path without leading slash. Values: new canonical path.
 */
return array(
    // Duplicate / legacy Pune listing pages
    'car-services-in-pune' => 'locations/pune',
    'car-services-in-pune/' => 'locations/pune',

    // Duplicate spares
    'ymo-car-spares-parts-india' => 'ymo-spares',
    'ymo-car-spares-parts-india/' => 'ymo-spares',

    // Luxury brand × locality → single luxury hub
    'the-best-audi-servicing-in-bavdhan' => 'premium-luxury-car-service-pune',
    'the-best-audi-servicing-in-hinjewadi' => 'premium-luxury-car-service-pune',
    'the-best-audi-servicing-in-wakad' => 'premium-luxury-car-service-pune',
    'the-best-bmw-servicing-in-baner' => 'premium-luxury-car-service-pune',
    'the-best-bmw-servicing-in-wakad' => 'premium-luxury-car-service-pune',
    'the-best-mercedes-servicing-in-hinjewadi' => 'premium-luxury-car-service-pune',
    'the-best-mercedes-servicing-in-wakad' => 'premium-luxury-car-service-pune',
    'best-audi-servicing-in-aundh-pune' => 'premium-luxury-car-service-pune',
    'best-bmw-servicing-in-hinjewadi-luxury-car-care' => 'premium-luxury-car-service-pune',
    'ymo-car-servicing-locations-in-pune/the-best-mercedes-servicing-in-viman-nagar' => 'premium-luxury-car-service-pune',

    // Nested WP hierarchy → flat locality or luxury hub
    'best-car-servicing-in-pune-ymo' => 'locations/pune',
    'best-car-servicing-in-pune-ymo/the-best-audi-servicing-in-viman-nagar' => 'affordable-car-services-viman-nagar-pune',
    'best-car-servicing-in-pune-ymo/the-best-mercedes-servicing-in-viman-nagar' => 'affordable-car-services-viman-nagar-pune',
    'best-car-servicing-in-pune-ymo/top-mercedes-servicing-aundh' => 'car-servicing-in-aundh',
    'best-car-servicing-in-pune-ymo/top-mercedes-servicing-baner' => 'the-best-car-servicing-in-baner',
    'best-car-servicing-in-pune-ymo/top-mercedes-servicing-baner/the-best-audi-servicing-baner-pune' => 'the-best-car-servicing-in-baner',
    'best-car-servicing-in-pune-ymo/top-mercedes-servicing-baner/the-best-audi-servicing-baner-pune/top-mercedes-servicing-in-bavdhan' => 'best-car-servicing-in-bavdhan-pune-expert-care',

    // Missing service pages → closest canonical service (not generic hub)
    'services/ceramic-coating-in-pune' => 'services/car-rubbing-and-polishing-in-pune-6500',
    'services/ceramic-coating-in-pune/' => 'services/car-rubbing-and-polishing-in-pune-6500',
    'services/car-tyre-and-wheel-services' => 'services/complete-car-servicing',
    'services/car-tyre-and-wheel-services/' => 'services/complete-car-servicing',

    // Interior cleaning price slug update (₹2000 → ₹2500)
    'services/car-interior-cleaning-in-indore-2000' => 'services/car-interior-cleaning-in-indore-2500',
    'services/car-interior-cleaning-in-indore-2000/' => 'services/car-interior-cleaning-in-indore-2500',
    'services/car-interior-cleaning-in-nashik-2000' => 'services/car-interior-cleaning-in-nashik-2500',
    'services/car-interior-cleaning-in-nashik-2000/' => 'services/car-interior-cleaning-in-nashik-2500',
);
