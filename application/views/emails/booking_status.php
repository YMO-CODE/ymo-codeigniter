<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$first_name = strtok($booking['user_name'], ' ');
$labels = array(
    'pending'     => 'received',
    'confirmed'   => 'confirmed',
    'in_progress' => 'now in progress',
    'completed'   => 'completed',
    'cancelled'   => 'cancelled',
);
$status_label = isset($labels[$booking['status']]) ? $labels[$booking['status']] : $booking['status'];
$body = '
    <h2 style="margin:0 0 12px;font-size:18px;">Hi '.htmlspecialchars($first_name).',</h2>
    <p style="margin:0 0 16px;">Your booking <strong>#'.htmlspecialchars($booking['reference']).'</strong> is '.htmlspecialchars($status_label).'.</p>
    <p style="margin:0 0 16px;color:#3a3f46;">'.htmlspecialchars($booking['package_name']).' &middot; '.htmlspecialchars($booking['vehicle_number']).'</p>
    <p style="margin:0;"><a href="'.htmlspecialchars(site_url("account/bookings/".$booking["id"])).'" style="color:#3a6f37;text-decoration:none;">View booking details &rarr;</a></p>
';
$subject = 'Booking '.$status_label.' — '.$booking['reference'];
include __DIR__.'/_layout.php';
