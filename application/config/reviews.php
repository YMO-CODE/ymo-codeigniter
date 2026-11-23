<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Google reviews: where customers are sent to leave one, and how the CRM
| answers new reviews (Admin → Google reviews, cron: cli/cron reviews).
|
| place_id comes from Google Maps (Places API). The review link customers get
| is https://www.yourmechaniconline.com/review/<workshop>, which redirects to
| Google's "write a review" box for that listing.
*/

$config['review_workshops'] = array(
    'hinjewadi' => array(
        'label'    => 'Hinjewadi',
        'city'     => 'pune',
        'place_id' => 'ChIJ1X7rK5y7wjsRzLwRob-jb3U',
        'area'     => 'our Hinjewadi workshop',
    ),
    'warje' => array(
        'label'    => 'Warje',
        'city'     => 'pune',
        'place_id' => 'ChIJq7XI77GVwjsRosT1iLyuSHs',
        'area'     => 'our Warje workshop',
        // Customer areas that are served from Warje rather than Hinjewadi.
        'areas'    => array('warje', 'karve nagar', 'karvenagar', 'kothrud', 'sinhagad', 'narhe', 'dhayari', 'nanded', 'shivane', 'bavdhan', 'erandwane', 'vadgaon', 'ambegaon', 'katraj', 'dhankawadi', 'kirkatwadi', 'khadakwasla'),
    ),
    'indore' => array(
        'label'    => 'Indore',
        'city'     => 'indore',
        'place_id' => 'ChIJUTfU14DjYjkRgAAmoXIzzVQ',
        'area'     => 'our Indore workshop',
    ),
    'nashik' => array(
        'label'    => 'Nashik',
        'city'     => 'nashik',
        'place_id' => 'ChIJWzBgMSTr3TsRd_mSlY1G4ho',
        'area'     => 'our Nashik workshop',
    ),
);

// Send the WhatsApp review request as soon as a booking is marked completed
// (otherwise the daily cron sends it reminder_review_days later).
$config['review_request_on_complete'] = TRUE;

// Auto-replies: reviews with these star ratings get an automatic public reply.
// 1–3 stars are never answered automatically; they are sent to the team.
$config['review_auto_reply_stars'] = array(4, 5);
// Reviews with these stars also alert the team (4★: why was a star lost?).
$config['review_alert_stars'] = array(1, 2, 3, 4);
// Safety caps per daily run.
$config['review_max_replies_per_run'] = 25;
// Only auto-reply to reviews from the last N days (older ones are just recorded).
$config['review_auto_reply_max_age_days'] = 400;
