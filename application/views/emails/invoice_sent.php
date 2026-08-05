<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$first = strtok($invoice['user_name'], ' ');
$fmt = function ($n) { return number_format((float) $n, 2); };
$body = '
    <h2 style="margin:0 0 12px;font-size:20px;">Hi '.htmlspecialchars($first, ENT_QUOTES, 'UTF-8').', your service invoice is ready.</h2>
    <p style="margin:0 0 16px;color:#3a3f46;">Please find the invoice for booking <strong>'.htmlspecialchars($invoice['booking_reference']).'</strong> attached to this email.</p>

    <table cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:16px;">
        <tr><td style="padding:6px 0;color:#6b7280;width:140px;">Invoice #</td>
            <td style="padding:6px 0;"><strong>'.htmlspecialchars($invoice['invoice_number']).'</strong></td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Date</td>
            <td style="padding:6px 0;">'.htmlspecialchars(date('d M Y', strtotime($invoice['created_at']))).'</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Vehicle</td>
            <td style="padding:6px 0;">'.htmlspecialchars(trim($invoice['vehicle_make'].' '.$invoice['vehicle_variant']))
            .' &middot; <span style="font-family:monospace">'.htmlspecialchars($invoice['vehicle_number']).'</span></td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Grand total</td>
            <td style="padding:6px 0;"><strong>&#8377; '.$fmt($invoice['grand_total']).'</strong></td></tr>
    </table>

    <p style="margin:0 0 16px;color:#3a3f46;">Prepared by: '.htmlspecialchars($invoice['created_by_name']).'</p>
    <p style="margin:0;color:#6b7280;font-size:13px;">If you have any questions about this invoice, reply to this email or call our support line.</p>
';
$subject = 'Service invoice '.$invoice['invoice_number'].' - '.$invoice['booking_reference'];
include __DIR__.'/_layout.php';
