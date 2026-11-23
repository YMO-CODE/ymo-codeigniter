<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google review helpers: which workshop a customer belongs to and the link
 * that opens Google's "write a review" box for it. Config: config/reviews.php.
 */

if (!function_exists('ymo_review_workshops')) {
    /** @return array<string,array> slug => workshop */
    function ymo_review_workshops()
    {
        $ci = &get_instance();
        $ci->config->load('reviews', TRUE);
        return (array) $ci->config->item('review_workshops', 'reviews');
    }
}

if (!function_exists('ymo_review_setting')) {
    function ymo_review_setting($key, $default = NULL)
    {
        $ci = &get_instance();
        $ci->config->load('reviews', TRUE);
        $v = $ci->config->item($key, 'reviews');
        return $v === NULL ? $default : $v;
    }
}

if (!function_exists('ymo_review_workshop_for')) {
    /**
     * Best guess at the workshop that served a customer, from their city/area.
     * Pune defaults to Hinjewadi unless the area is one Warje serves.
     */
    function ymo_review_workshop_for($city = '', $area = '')
    {
        $city = strtolower(trim((string) $city));
        $area = strtolower(trim((string) $area));
        $all = ymo_review_workshops();
        foreach ($all as $slug => $w) {
            if ($w['city'] !== 'pune' && $city !== '' && strpos($city, $w['city']) !== FALSE) {
                return $slug;
            }
        }
        if ($area !== '' && !empty($all['warje']['areas'])) {
            foreach ($all['warje']['areas'] as $a) {
                if (strpos($area, $a) !== FALSE) {
                    return 'warje';
                }
            }
        }
        return 'hinjewadi';
    }
}

if (!function_exists('ymo_review_google_url')) {
    /** Google's "write a review" box for a workshop. */
    function ymo_review_google_url($slug)
    {
        $all = ymo_review_workshops();
        $w = isset($all[$slug]) ? $all[$slug] : (isset($all['hinjewadi']) ? $all['hinjewadi'] : NULL);
        if (!$w || empty($w['place_id'])) {
            return 'https://www.google.com/search?q=Your+Mechanic+Online+review';
        }
        return 'https://search.google.com/local/writereview?placeid='.rawurlencode($w['place_id']);
    }
}

if (!function_exists('ymo_review_short_url')) {
    /** Our own short link (https://www.yourmechaniconline.com/review/hinjewadi) — used in WhatsApp/SMS/email. */
    function ymo_review_short_url($slug)
    {
        $all = ymo_review_workshops();
        $slug = isset($all[$slug]) ? $slug : 'hinjewadi';
        $base = function_exists('marketing_canonical_url') ? marketing_canonical_url('') : 'https://www.yourmechaniconline.com/';
        return rtrim($base, '/').'/review/'.$slug;
    }
}
