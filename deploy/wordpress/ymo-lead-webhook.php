<?php
/**
 * Plugin Name: YMO CRM Lead Webhook
 * Description: Forwards Contact Form 7 submissions to YMO booking CRM webhook.
 * Version: 1.0.0
 *
 * Install: copy to wp-content/mu-plugins/ymo-lead-webhook.php
 * Configure YMO_WEBHOOK_URL and YMO_WEBHOOK_SECRET below.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- Configure these ---
const YMO_WEBHOOK_URL    = 'https://booking.yourmechaniconline.com/api/webhooks/website';
const YMO_WEBHOOK_SECRET = 'your-crm-webhook-secret-from-env';

/**
 * Contact Form 7 — adjust field names to match your form.
 */
add_action('wpcf7_mail_sent', function ($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }
    $data = $submission->get_posted_data();

    $payload = array(
        'name'    => isset($data['your-name']) ? (string) $data['your-name'] : '',
        'mobile'  => isset($data['your-mobile']) ? (string) $data['your-mobile'] : '',
        'email'   => isset($data['your-email']) ? (string) $data['your-email'] : '',
        'message' => isset($data['your-message']) ? (string) $data['your-message'] : '',
        'source'  => 'website',
    );

    ymo_send_lead_webhook($payload);
});

function ymo_send_lead_webhook(array $payload)
{
    if (YMO_WEBHOOK_SECRET === '' || YMO_WEBHOOK_SECRET === 'your-crm-webhook-secret-from-env') {
        return;
    }

    $body = wp_json_encode($payload);
    $sig  = hash_hmac('sha256', $body, YMO_WEBHOOK_SECRET);

    wp_remote_post(YMO_WEBHOOK_URL, array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type'     => 'application/json',
            'X-CRM-Signature'  => 'sha256='.$sig,
        ),
        'body' => $body,
    ));
}
