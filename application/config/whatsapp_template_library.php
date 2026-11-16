<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Ready-to-use WhatsApp templates (CRM v5).
|
| These are NOT sent to Meta by the app. They are shown on Admin → WhatsApp
| templates with copy buttons so they can be pasted into WhatsApp Manager
| (Message templates → Create template) exactly as written. Once approved,
| press "Sync" and they become usable.
|
|  - `event` = the booking notification that uses it automatically
|    (config crm_whatsapp_templates). Templates without an event are for
|    Campaigns: pick them in the campaign form and fill the variables there.
|  - `examples` are the sample values Meta asks for when you submit.
|  - Bodies never start/end with a variable and never place two variables
|    side by side (Meta rejects both).
|  - Marketing templates carry a "Stop promotions" quick reply; tapping it
|    (or replying STOP) sets WhatsApp opt-out on the contact.
*/

$config['wa_template_library'] = array(

    /* ---------------- Booking notifications (Utility) ---------------- */

    array(
        'name'     => 'booking_confirmed',
        'category' => 'UTILITY',
        'event'    => 'booking_confirmed',
        'header'   => array('type' => 'text', 'text' => 'Booking confirmed'),
        'body'     => "Hi {{1}}, your booking {{2}} for {{3}} is confirmed.\n\nOur team will call you shortly to confirm the pickup time. You can reply here with any questions.",
        'examples' => array('Rahul', 'YMO-24117', 'Comprehensive Car Service'),
        'footer'   => 'Your Mechanic Online',
        'buttons'  => array(),
    ),
    array(
        'name'     => 'booking_status',
        'category' => 'UTILITY',
        'event'    => 'booking_status',
        'header'   => NULL,
        'body'     => "Update on your booking {{1}}: the status is now *{{2}}*.\n\nReply here if you have any questions.",
        'examples' => array('YMO-24117', 'In progress'),
        'footer'   => 'Your Mechanic Online',
        'buttons'  => array(),
    ),
    array(
        'name'     => 'service_update',
        'category' => 'UTILITY',
        'event'    => 'service_update',
        'header'   => array('type' => 'image'),
        'body'     => "Update on your car (booking {{1}}):\n\n{{2}}\n\nReply here to approve or ask a question.",
        'examples' => array('YMO-24117', 'Front brake pads are worn to 2 mm. We recommend replacing them today.'),
        'footer'   => 'Your Mechanic Online',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Approve'),
            array('type' => 'QUICK_REPLY', 'text' => 'Call me'),
        ),
    ),
    array(
        'name'     => 'estimate_sent',
        'category' => 'UTILITY',
        'event'    => 'estimate_sent',
        'header'   => array('type' => 'document'),
        'body'     => "The estimate for booking {{1}} is attached.\n\n{{2}}\n\nTap Approve to go ahead, or Call me and we will explain each item.",
        'examples' => array('YMO-24117', 'Includes brake pads, labour and wheel alignment.'),
        'footer'   => 'Your Mechanic Online',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Approve'),
            array('type' => 'QUICK_REPLY', 'text' => 'Call me'),
        ),
    ),
    array(
        'name'     => 'invoice_sent',
        'category' => 'UTILITY',
        'event'    => 'invoice_sent',
        'header'   => array('type' => 'document'),
        'body'     => "Your service invoice for booking {{1}} is attached.\n\nTotal amount: Rs {{2}}\n\nThank you for choosing Your Mechanic Online.",
        'examples' => array('YMO-24117', '4,850.00'),
        'footer'   => 'Your Mechanic Online',
        'buttons'  => array(),
    ),
    array(
        'name'     => 'service_reminder',
        'category' => 'UTILITY',
        'event'    => 'service_reminder',
        'header'   => NULL,
        'body'     => "Hi {{1}}, your car {{2}} is due for its next service.\n\nReply BOOK and we will arrange a doorstep pickup at a time that suits you.",
        'examples' => array('Rahul', 'MH12AB1234'),
        'footer'   => 'Your Mechanic Online',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Book service'),
            array('type' => 'QUICK_REPLY', 'text' => 'Remind me later'),
        ),
    ),
    array(
        'name'     => 'review_request',
        'category' => 'MARKETING',
        'event'    => 'review_request',
        'header'   => NULL,
        'body'     => "Hi {{1}}, thank you for servicing your car with us (booking {{2}}).\n\nHow was your experience? Tap a button below - it takes 2 seconds.",
        'examples' => array('Rahul', 'YMO-24117'),
        'footer'   => 'Your Mechanic Online',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Excellent'),
            array('type' => 'QUICK_REPLY', 'text' => 'Good'),
            array('type' => 'QUICK_REPLY', 'text' => 'Needs improvement'),
        ),
    ),

    /* ---------------- Campaigns (Marketing) ---------------- */

    array(
        'name'     => 'offer_doorstep_service',
        'category' => 'MARKETING',
        'event'    => NULL,
        'header'   => array('type' => 'image'),
        'body'     => "Hi {{1}}, here is this month's offer: {{2}}\n\nWe pick up your car from home, service it at our workshop and drop it back the same day. Offer valid till {{3}}.\n\nTap Book now and we will call you to fix a slot.",
        'examples' => array('Rahul', '20% off on a comprehensive car service.', '30 November'),
        'campaign_hint' => '{{1}} = {first_name}, {{2}} = the offer line, {{3}} = last date. Image header needs a public https link to the offer creative.',
        'footer'   => 'Reply STOP to opt out',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Book now'),
            array('type' => 'QUICK_REPLY', 'text' => 'Stop promotions'),
        ),
    ),
    array(
        'name'     => 'winback_service_due',
        'category' => 'MARKETING',
        'event'    => NULL,
        'header'   => NULL,
        'body'     => "Hi {{1}}, it has been a while since your car's last service with us.\n\nBook a doorstep service this week and get {{2}}. Reply YES and we will call you back.",
        'examples' => array('Rahul', 'a free car wash and 21-point inspection'),
        'campaign_hint' => '{{1}} = {first_name}, {{2}} = the incentive. Good for the "all contacts" or tag segments.',
        'footer'   => 'Reply STOP to opt out',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Yes, call me'),
            array('type' => 'QUICK_REPLY', 'text' => 'Stop promotions'),
        ),
    ),
    array(
        'name'     => 'seasonal_checkup',
        'category' => 'MARKETING',
        'event'    => NULL,
        'header'   => array('type' => 'text', 'text' => 'Car check-up offer'),
        'body'     => "Hi {{1}}, get your car ready for {{2}} with our doorstep check-up for just Rs {{3}}.\n\nCovers brakes, battery, tyres, AC and fluids. Reply YES to book a slot.",
        'examples' => array('Rahul', 'the monsoon', '499'),
        'campaign_hint' => '{{1}} = {first_name}, {{2}} = season or festival (the monsoon / Diwali road trips), {{3}} = price.',
        'footer'   => 'Reply STOP to opt out',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Book check-up'),
            array('type' => 'QUICK_REPLY', 'text' => 'Stop promotions'),
        ),
    ),
    array(
        'name'     => 'referral_invite',
        'category' => 'MARKETING',
        'event'    => NULL,
        'header'   => NULL,
        'body'     => "Hi {{1}}, enjoyed our service? Refer a friend to Your Mechanic Online and you get Rs {{2}} credit when they book their first service.\n\nJust share our number or website with them and ask them to mention your name.",
        'examples' => array('Rahul', '500'),
        'campaign_hint' => '{{1}} = {first_name}, {{2}} = referral credit amount.',
        'footer'   => 'Reply STOP to opt out',
        'buttons'  => array(
            array('type' => 'URL', 'text' => 'Visit website', 'url' => 'https://www.yourmechaniconline.com/'),
            array('type' => 'QUICK_REPLY', 'text' => 'Stop promotions'),
        ),
    ),
    array(
        'name'     => 'lead_follow_up',
        'category' => 'MARKETING',
        'event'    => NULL,
        'header'   => NULL,
        'body'     => "Hi {{1}}, thanks for your interest in Your Mechanic Online.\n\nWe still have doorstep service slots available this week in {{2}}. Reply with your car model and we will share an exact quote.",
        'examples' => array('Rahul', 'Hinjewadi and Wakad'),
        'campaign_hint' => 'Use with the "open leads" segment. {{1}} = {first_name}, {{2}} = area(s).',
        'footer'   => 'Reply STOP to opt out',
        'buttons'  => array(
            array('type' => 'QUICK_REPLY', 'text' => 'Get a quote'),
            array('type' => 'QUICK_REPLY', 'text' => 'Stop promotions'),
        ),
    ),
);

/*
| Quick messages for the booking page "WhatsApp update to customer" card.
| Picking one fills the message box; staff edit before sending.
*/
$config['wa_booking_quick_messages'] = array(
    'Car picked up'       => 'We have picked up your car and it is on the way to our workshop. We will share an update once the inspection is done.',
    'Inspection photos'   => 'Inspection done. Sharing photos of what we found - please have a look.',
    'Extra work needed'   => 'During the service we found an additional issue (see photos). We recommend fixing it today. Tap Approve to go ahead or reply with any questions.',
    'Estimate attached'   => 'Please find the estimate attached. Reply to approve, or call us and we will explain each item.',
    'Work in progress'    => 'Your car service is in progress. Sharing a quick update from the workshop.',
    'Ready for delivery'  => 'Your car is serviced, washed and ready. We will drop it back at the agreed time. Sharing final photos.',
    'Delivered - thanks'  => 'Your car has been delivered. Thank you for choosing Your Mechanic Online! Reply here if you notice anything.',
);
