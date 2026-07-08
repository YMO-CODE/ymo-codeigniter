<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Your Mechanic Online — application configuration
|--------------------------------------------------------------------------
|
| All application-specific knobs live here. Real secrets are read from
| environment variables (see .env.example) so they never land in version
| control. Defaults below are safe stand-ins for local development.
|
*/

// --- Branding / contact -----------------------------------------------------
$config['ymo_brand_name']     = 'Your Mechanic Online';
$config['ymo_brand_short']    = 'YMO';
$config['ymo_support_phone']  = '+91-7744-065904';
$config['ymo_support_email']  = getenv('YMO_SUPPORT_EMAIL') ?: 'support@yourmechaniconline.com';
$config['ymo_admin_notify']   = getenv('YMO_ADMIN_NOTIFY')  ?: 'admin@yourmechaniconline.com';
$config['ymo_marketing_url']  = 'https://www.yourmechaniconline.com';
$config['ymo_app_url']        = getenv('YMO_APP_URL') ?: 'https://booking.yourmechaniconline.com';

// Cities where service is currently offered. Used to populate the signup
// dropdown and to validate the chosen city. Add a new row here when expanding.
$config['ymo_service_cities'] = array('Indore', 'Pune', 'Nashik');

// --- OTP --------------------------------------------------------------------
$config['otp_length']            = 6;
$config['otp_ttl_seconds']       = 600;     // 10 minutes
$config['otp_resend_cooldown']   = 30;      // seconds between resend
$config['otp_max_attempts']      = 5;       // per code
$config['otp_ip_hourly_limit']   = 20;      // per IP per hour
$config['otp_mobile_hourly_limit'] = 5;     // per mobile per hour

// In non-production environments, skip OTP entirely and auto-mark the
// mobile as verified at signup time. Also makes the magic code '000000'
// pass `Otp_service::verify()` in case some flow still hits the verify step.
// Hard-disabled in production no matter what.
$config['dev_auto_verify_otp'] = (ENVIRONMENT !== 'production');

// --- Auth -------------------------------------------------------------------
$config['auth_login_attempts']    = 5;      // before lockout
$config['auth_lockout_minutes']   = 15;
$config['auth_password_min']      = 8;

// --- Reminders --------------------------------------------------------------
// Default service reminder window in months (proposal mentions 4 or 8)
$config['reminder_months']        = 4;
$config['reminder_review_days']   = 3;      // review prompt earliest = N days after completion
$config['cron_chunk_size']        = 200;

// --- SMS gateway (MSG91) ----------------------------------------------------
$config['sms_driver']             = getenv('YMO_SMS_DRIVER') ?: 'msg91';
$config['sms_msg91_authkey']      = getenv('YMO_MSG91_AUTHKEY') ?: '';
$config['sms_msg91_sender']       = getenv('YMO_MSG91_SENDER')  ?: 'YMOCAR';
$config['sms_msg91_route']        = getenv('YMO_MSG91_ROUTE')   ?: '4'; // transactional
$config['sms_msg91_country']      = '91';

// DLT-registered template IDs (must be filled in production)
$config['sms_templates'] = array(
	'otp'                => getenv('YMO_TPL_OTP')             ?: '',
	'booking_confirmed'  => getenv('YMO_TPL_BOOKING_OK')      ?: '',
	'booking_status'     => getenv('YMO_TPL_BOOKING_STATUS')  ?: '',
	'service_reminder'   => getenv('YMO_TPL_SERVICE_REMIND')  ?: '',
	'review_request'     => getenv('YMO_TPL_REVIEW')          ?: '',
	'invoice_sent'       => getenv('YMO_TPL_INVOICE')         ?: '',
	'crm_campaign'       => getenv('YMO_TPL_CRM_CAMPAIGN')    ?: '',
);

// --- Email (SMTP via PHPMailer) --------------------------------------------
$config['mail_host']      = getenv('YMO_MAIL_HOST')     ?: 'smtp.gmail.com';
$config['mail_port']      = (int) (getenv('YMO_MAIL_PORT') ?: 587);
$config['mail_username']  = getenv('YMO_MAIL_USER')     ?: '';
$config['mail_password']  = getenv('YMO_MAIL_PASS')     ?: '';
$config['mail_encryption']= getenv('YMO_MAIL_ENC')      ?: 'tls';   // tls|ssl
$config['mail_from_email']= getenv('YMO_MAIL_FROM')     ?: 'no-reply@yourmechaniconline.com';
$config['mail_from_name'] = getenv('YMO_MAIL_FROM_NAME')?: 'Your Mechanic Online';

// --- File uploads -----------------------------------------------------------
$config['upload_max_kb']        = 3072;        // 3 MB
$config['upload_image_max_w']   = 1280;
$config['upload_allowed_types'] = 'jpg|jpeg|png|webp';
$config['upload_vehicle_path']  = FCPATH.'uploads/vehicles/';
$config['upload_vehicle_url']   = 'uploads/vehicles/';
$config['offer_upload_path']    = FCPATH.'uploads/offers/';

// CORS origins allowed to fetch GET /api/offers/active from the browser.
$config['offer_cors_origins'] = array(
    'https://yourmechaniconline.com',
    'https://www.yourmechaniconline.com',
);

// --- Misc -------------------------------------------------------------------
$config['booking_per_page']     = 15;
$config['admin_per_page']       = 25;
