<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$name   = (string) ($lead['name'] ?? '');
$mobile = (string) ($lead['mobile'] ?? '');
$email  = (string) ($lead['email'] ?? '');
$message = (string) ($lead['message'] ?? '');

$body = '
    <h2 style="margin:0 0 12px;font-size:18px;">New lead &mdash; '.htmlspecialchars($source_label).'</h2>

    <table cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:16px;">
        <tr><td style="padding:6px 0;color:#6b7280;width:140px;">Name</td>
            <td style="padding:6px 0;">'.htmlspecialchars($name).'</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;">Mobile</td>
            <td style="padding:6px 0;">'.htmlspecialchars($mobile).'</td></tr>';

if ($email !== '') {
    $body .= '<tr><td style="padding:6px 0;color:#6b7280;">Email</td>
              <td style="padding:6px 0;">'.htmlspecialchars($email).'</td></tr>';
}

if ($message !== '') {
    $body .= '<tr><td style="padding:6px 0;color:#6b7280;vertical-align:top;">Details</td>
              <td style="padding:6px 0;">'.nl2br(htmlspecialchars($message)).'</td></tr>';
}

$body .= '</table>

    <p><a href="'.htmlspecialchars(admin_url('leads/'.$lead_id)).'" style="background:#111418;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;display:inline-block;">Open in CRM</a></p>
';
$subject = 'New lead - '.$source_label;
include __DIR__.'/_layout.php';
