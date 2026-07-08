<?php
/**
 * Plugin Name: YMO Site Offers Popup
 * Description: Shows promotional offer popups from the YMO booking admin on every WordPress page.
 * Version: 1.0.0
 *
 * Install: copy to wp-content/mu-plugins/ymo-offers.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- Configure if your booking subdomain differs ---
const YMO_OFFERS_API    = 'https://booking.yourmechaniconline.com/api/offers/active';
const YMO_OFFERS_ASSETS = 'https://booking.yourmechaniconline.com/assets';
const YMO_OFFERS_CACHE  = 300; // seconds (5 minutes)

/**
 * Fetch active offers from booking API (cached server-side).
 *
 * @return array{ok:bool,offers:array<int,array>}
 */
function ymo_offers_fetch_payload()
{
    $bypass = isset($_GET['ymo_offers_refresh']) && current_user_can('manage_options');
    $cache_key = 'ymo_active_offers';

    if (!$bypass) {
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $response = wp_remote_get(YMO_OFFERS_API, array(
        'timeout'   => 8,
        'sslverify' => true,
        'headers'   => array('Accept' => 'application/json'),
    ));

    $payload = array('ok' => true, 'offers' => array());

    if (!is_wp_error($response)) {
        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code === 200 && $body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded) && isset($decoded['offers']) && is_array($decoded['offers'])) {
                $payload = array(
                    'ok'     => !empty($decoded['ok']),
                    'offers' => $decoded['offers'],
                );
            }
        }
    }

    if (!$bypass) {
        set_transient($cache_key, $payload, YMO_OFFERS_CACHE);
    }

    return $payload;
}

add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }

    $payload = ymo_offers_fetch_payload();
    if (empty($payload['offers'])) {
        return;
    }

    $css = YMO_OFFERS_ASSETS . '/css/ymo-offers.css';
    $js  = YMO_OFFERS_ASSETS . '/js/ymo-offers.js';

    echo '<link rel="stylesheet" href="' . esc_url($css) . '">' . "\n";
    echo '<script>window.YMO_OFFERS_BOOTSTRAP = ' . wp_json_encode($payload) . ';</script>' . "\n";
    echo '<script src="' . esc_url($js) . '" defer></script>' . "\n";
}, 99);
