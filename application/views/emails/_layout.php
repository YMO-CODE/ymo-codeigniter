<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$brand = $ci->config->item('ymo_brand_name');
$phone = $ci->config->item('ymo_support_phone');
$email = $ci->config->item('ymo_support_email');
$site  = $ci->config->item('ymo_marketing_url');
$logo  = (defined('FCPATH') && file_exists(FCPATH.'assets/img/logo.png'))
    ? base_url('assets/img/logo.png')
    : rtrim($site, '/').'/assets/img/logo.png';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= html_escape(isset($subject) ? $subject : $brand); ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1d2026;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,.06);">
                <tr><td align="center" style="background:#ffffff;padding:24px 24px 16px;border-bottom:3px solid #3a6f37;">
                    <img src="<?= html_escape($logo); ?>" alt="<?= html_escape($brand); ?>" width="160" style="display:block;border:0;outline:none;text-decoration:none;height:auto;max-width:200px;">
                </td></tr>
                <tr><td style="padding:28px 28px 12px;font-size:15px;line-height:1.55;">
                    <?= $body; ?>
                </td></tr>
                <tr><td style="padding:18px 28px 28px;color:#6b7280;font-size:12px;line-height:1.5;border-top:1px solid #eef0f3;">
                    Need help? Call <a href="tel:<?= html_escape(preg_replace('/[^+\d]/', '', $phone)); ?>" style="color:#3a6f37;text-decoration:none;"><?= html_escape($phone); ?></a> or write to <a href="mailto:<?= html_escape($email); ?>" style="color:#3a6f37;text-decoration:none;"><?= html_escape($email); ?></a>.<br>
                    &copy; <?= date('Y'); ?> <?= html_escape($brand); ?> &middot; <a href="<?= html_escape($site); ?>" style="color:#6b7280;"><?= html_escape($site); ?></a>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
