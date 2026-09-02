<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Legacy WordPress URL → new marketing path (301).
 * Keys: lowercase path without leading slash. Values: new path (site_url relative).
 *
 * Exact rules from GSC long-tail are in marketing_redirects_option_a.php (auto-generated).
 */

$config['marketing_redirect_prefixes'] = array(
    // Prefix catch-alls removed — unlisted tag/category/gallery paths now 410 via Legacy.php.
    // Explicit 301s live in marketing_redirects_option_a.php and marketing_consolidations.php.
);

$config['marketing_redirects_exact'] = array(
    'contact-us/' => 'contact-us',
    'about-us/'   => 'about-us',
    'privacy-policy/' => 'privacy-policy',
    // Legacy WP slugs with ₹ (CI3 cannot route unicode URI segments)
    'services/car-rubbing-and-polishing-in-pune-₹6500' => 'services/car-rubbing-and-polishing-in-pune-6500',
    'services/car-rubbing-and-polishing-in-pune-%e2%82%b96500' => 'services/car-rubbing-and-polishing-in-pune-6500',
    'services/car-interior-cleaning-in-pune-₹2000' => 'services/car-interior-cleaning-in-pune-2500',
    'services/car-interior-cleaning-in-pune-%e2%82%b92000' => 'services/car-interior-cleaning-in-pune-2500',
    'services/car-interior-cleaning-in-pune-2000' => 'services/car-interior-cleaning-in-pune-2500',
    'services/car-interior-cleaning-in-pune-2000/' => 'services/car-interior-cleaning-in-pune-2500',
);

$option_a_file = __DIR__.'/marketing_redirects_option_a.php';
if (is_file($option_a_file)) {
    $generated = require $option_a_file;
    if (is_array($generated)) {
        $config['marketing_redirects_exact'] = array_merge(
            $config['marketing_redirects_exact'],
            $generated
        );
    }
}

$consolidations_file = __DIR__.'/marketing_consolidations.php';
if (is_file($consolidations_file)) {
    $consolidations = require $consolidations_file;
    if (is_array($consolidations)) {
        $config['marketing_redirects_exact'] = array_merge(
            $config['marketing_redirects_exact'],
            $consolidations
        );
    }
}
