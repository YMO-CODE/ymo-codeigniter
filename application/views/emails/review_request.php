<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$first = strtok($booking['user_name'], ' ');
$review_url = function_exists('marketing_gbp_review_url') ? marketing_gbp_review_url() : 'https://share.google/zdztabyzbml7l1uvZ';
$body = '
    <h2 style="margin:0 0 12px;font-size:20px;">How did we do, '.htmlspecialchars($first).'?</h2>
    <p style="margin:0 0 16px;color:#3a3f46;">Thanks for choosing us for your '
        .htmlspecialchars($booking['package_name']).' on '.htmlspecialchars($booking['vehicle_number']).'. We\'d love a quick rating.</p>

    <p style="margin:0 0 24px;">
        <a href="'.htmlspecialchars($review_url, ENT_QUOTES, 'UTF-8').'" style="background:#3a6f37;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;display:inline-block;">Leave a Google review</a>
    </p>
    <p style="margin:0;color:#6b7280;font-size:13px;">Have feedback that\'s not for public eyes? Just hit reply - we read every email.</p>
';
$subject = 'Quick favour - how did we do?';
include __DIR__.'/_layout.php';
