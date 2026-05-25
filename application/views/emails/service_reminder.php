<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$first = strtok($reminder['user_name'], ' ');
$body = '
    <h2 style="margin:0 0 12px;font-size:20px;">Time for your next car service, '.htmlspecialchars($first).'?</h2>
    <p style="margin:0 0 16px;color:#3a3f46;">It\'s been a few months since we last serviced your '
        .htmlspecialchars($reminder['vehicle_make'].' '.$reminder['vehicle_variant']).' (<span style="font-family:monospace">'
        .htmlspecialchars($reminder['vehicle_number']).'</span>).</p>
    <p style="margin:0 0 16px;">Regular servicing keeps things smooth, prevents costly repairs, and keeps your warranty intact.</p>

    <p>
        <a href="'.htmlspecialchars(site_url("packages")).'" style="background:#3a6f37;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;display:inline-block;">Book your next service</a>
    </p>
    <p style="margin:16px 0 0;color:#6b7280;font-size:13px;">Last booking: <strong>#'.htmlspecialchars($reminder['reference']).'</strong> &middot; '
        .htmlspecialchars($reminder['package_name']).'</p>
';
$subject = 'Time for your next car service';
include __DIR__.'/_layout.php';
