<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$body = '
    <h2 style="margin:0 0 12px;font-size:18px;">New booking #'.htmlspecialchars($booking['reference']).'</h2>

    <table cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:16px;">
        <tr><td style="padding:6px 0;color:#6b7280;width:140px;">Customer</td>
            <td style="padding:6px 0;">'.htmlspecialchars($booking['user_name']).' &middot; '
                .htmlspecialchars($booking['user_mobile']).' &middot; '
                .htmlspecialchars($booking['user_email']).'</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Service</td>
            <td style="padding:6px 0;">'.htmlspecialchars($booking['package_name']).' (&#8377; '.number_format((float)$booking['package_price']).')</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Vehicle</td>
            <td style="padding:6px 0;">'.htmlspecialchars($booking['vehicle_make'].' '.$booking['vehicle_variant'])
            .' &middot; <span style="font-family:monospace">'.htmlspecialchars($booking['vehicle_number']).'</span></td></tr>';

if (!empty($booking['preferred_date'])) {
    $body .= '<tr><td style="padding:6px 0;color:#6b7280;">Preferred date</td>
              <td style="padding:6px 0;">'.htmlspecialchars(date("d M Y", strtotime($booking["preferred_date"]))).'</td></tr>';
}
if (!empty($booking['remarks'])) {
    $body .= '<tr><td style="padding:6px 0;color:#6b7280;vertical-align:top;">Remarks</td>
              <td style="padding:6px 0;">'.nl2br(htmlspecialchars($booking['remarks'])).'</td></tr>';
}
$body .= '</table>

    <p><a href="'.htmlspecialchars(admin_url('bookings/'.$booking['id'])).'" style="background:#111418;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;display:inline-block;">Open in admin</a></p>
';
$subject = 'New booking - '.$booking['reference'];
include __DIR__.'/_layout.php';
