<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$first_name = strtok($booking['user_name'], ' ');
$body = '
    <h2 style="margin:0 0 12px;font-size:20px;">Hi '.htmlspecialchars($first_name, ENT_QUOTES, "UTF-8").', your booking is confirmed.</h2>
    <p style="margin:0 0 16px;color:#3a3f46;">Reference: <strong>'.htmlspecialchars($booking['reference']).'</strong></p>

    <table cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:16px;">
        <tr><td style="padding:6px 0;color:#6b7280;width:140px;">Service</td>
            <td style="padding:6px 0;"><strong>'.htmlspecialchars($booking['package_name']).'</strong></td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Estimated price</td>
            <td style="padding:6px 0;">&#8377; '.number_format((float)$booking['package_price']).'</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Vehicle</td>
            <td style="padding:6px 0;">'.htmlspecialchars($booking['vehicle_make'].' '.$booking['vehicle_variant']).'<br>
                <span style="font-family:monospace;color:#6b7280;font-size:12px;">'.htmlspecialchars($booking['vehicle_number']).'</span></td></tr>';

if (!empty($booking['preferred_date'])) {
    $body .= '<tr><td style="padding:6px 0;color:#6b7280;">Preferred date</td>
              <td style="padding:6px 0;">'.htmlspecialchars(date("d M Y", strtotime($booking["preferred_date"]))).'</td></tr>';
}
if (!empty($booking['remarks'])) {
    $body .= '<tr><td style="padding:6px 0;color:#6b7280;vertical-align:top;">Remarks</td>
              <td style="padding:6px 0;">'.nl2br(htmlspecialchars($booking['remarks'])).'</td></tr>';
}
$body .= '</table>

    <p style="margin:0 0 16px;">Our team will call you shortly to schedule pick-up. No payment is required online - pricing is confirmed before service.</p>
    <p style="margin:0 0 4px;">
        <a href="'.htmlspecialchars(site_url('account/bookings')).'" style="background:#3a6f37;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;display:inline-block;">View my bookings</a>
    </p>
';
$subject = 'Booking confirmed - '.$booking['reference'];
include __DIR__.'/_layout.php';
