<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$body = '
    <h2 style="margin:0 0 12px;font-size:20px;">Your verification code</h2>
    <p style="margin:0 0 16px;color:#3a3f46;">Use the code below to verify your '
        .($purpose === 'reset' ? 'password reset' : 'account')
    .'. It expires in '.(int) $minutes.' minutes.</p>
    <div style="text-align:center;font-size:32px;font-weight:bold;letter-spacing:6px;background:#f7f8fa;border:1px dashed #d1d5db;padding:18px;border-radius:6px;margin:0 0 16px;">'
        .htmlspecialchars($code, ENT_QUOTES, 'UTF-8').
    '</div>
    <p style="margin:0;color:#6b7280;font-size:13px;">If you did not request this, you can ignore this email.</p>
';
$subject = 'Your verification code';
include __DIR__.'/_layout.php';
