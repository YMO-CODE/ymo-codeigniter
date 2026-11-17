<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Embedded CRM defaults (secrets via getenv in production).
 */
$config['crm_webhook_hmac_secret'] = getenv('CRM_WEBHOOK_SECRET') ?: '';
$config['crm_meta_verify_token']  = getenv('CRM_META_VERIFY_TOKEN') ?: '';
$config['crm_meta_access_token']  = getenv('CRM_META_ACCESS_TOKEN') ?: '';
$config['crm_whatsapp_phone_number_id'] = getenv('CRM_WHATSAPP_PHONE_NUMBER_ID') ?: '';
$config['crm_meta_page_id']       = getenv('CRM_META_PAGE_ID') ?: '';
$config['crm_campaign_batch_size'] = 35;
$config['crm_task_reminder_hours'] = 48;
$config['crm_customer_active_months'] = 12;
$config['crm_service_due_months'] = 6;
$config['crm_resume_max_kb']       = 4096;
$config['crm_resume_allowed_types']= 'pdf|doc|docx';
$config['crm_resume_upload_path']  = FCPATH.'uploads/crm/resumes/';
$config['crm_sms_campaign_tpl']    = getenv('YMO_TPL_CRM_CAMPAIGN') ?: '';

/*
 * WhatsApp Cloud API (CRM v5). Nothing is sent unless the access token and
 * phone number id above are set. Booking/customer notifications only switch
 * from SMS to WhatsApp when CRM_WHATSAPP_NOTIFY=1 (SMS stays the fallback).
 */
$config['crm_meta_graph_version']      = getenv('CRM_META_GRAPH_VERSION') ?: 'v19.0';
// System-user token for WhatsApp (falls back to CRM_META_ACCESS_TOKEN when empty).
$config['crm_whatsapp_access_token']   = getenv('CRM_WHATSAPP_ACCESS_TOKEN') ?: '';
$config['crm_whatsapp_waba_id']        = getenv('CRM_WHATSAPP_WABA_ID') ?: '';
// Meta business portfolio that owns the WhatsApp account (for the Billing link on Today).
$config['crm_meta_business_id']        = getenv('CRM_META_BUSINESS_ID') ?: '3506576792820788';
$config['crm_meta_app_secret']         = getenv('CRM_META_APP_SECRET') ?: '';
$config['crm_whatsapp_notify_enabled'] = getenv('CRM_WHATSAPP_NOTIFY') === '1';
$config['crm_whatsapp_media_path']     = FCPATH.'../storage/whatsapp/';
$config['crm_whatsapp_media_max_kb']   = 5120;
$config['crm_whatsapp_opt_out_words']  = array('stop', 'unsubscribe', 'stop all', 'opt out', 'optout', 'stop promotions');

/*
 * Customer notification event => approved WhatsApp template.
 * `params` lists the variables in {{1}}, {{2}}… order (same names the SMS
 * templates use). `header` = 'document' / 'image' when the template has a
 * media header. Template names/languages are env-overridable so they can
 * match whatever gets approved in WhatsApp Manager. An event with an empty
 * name simply uses SMS.
 */
$config['crm_whatsapp_templates'] = array(
    'booking_confirmed' => array(
        'name'   => getenv('YMO_WA_TPL_BOOKING_CONFIRMED') ?: 'booking_confirmed',
        'lang'   => getenv('YMO_WA_TPL_LANG') ?: 'en',
        'params' => array('name', 'ref', 'package'),
    ),
    'booking_status' => array(
        'name'   => getenv('YMO_WA_TPL_BOOKING_STATUS') ?: 'booking_status',
        'lang'   => getenv('YMO_WA_TPL_LANG') ?: 'en',
        'params' => array('ref', 'status'),
    ),
    'service_reminder' => array(
        'name'   => getenv('YMO_WA_TPL_SERVICE_REMINDER') ?: 'service_reminder',
        'lang'   => getenv('YMO_WA_TPL_LANG') ?: 'en',
        'params' => array('name', 'vehicle'),
    ),
    'review_request' => array(
        'name'   => getenv('YMO_WA_TPL_REVIEW_REQUEST') ?: 'review_request',
        'lang'   => getenv('YMO_WA_TPL_LANG') ?: 'en',
        'params' => array('name', 'ref'),
    ),
    'invoice_sent' => array(
        'name'   => getenv('YMO_WA_TPL_INVOICE_SENT') ?: 'invoice_sent',
        'lang'   => getenv('YMO_WA_TPL_LANG') ?: 'en',
        'params' => array('ref', 'total'),
        'header' => 'document',
    ),
    // Used for booking photo/estimate updates when the customer has not
    // messaged in the last 24h (outside the free-form window).
    'service_update' => array(
        'name'   => getenv('YMO_WA_TPL_SERVICE_UPDATE') ?: 'service_update',
        'lang'   => getenv('YMO_WA_TPL_LANG') ?: 'en',
        'params' => array('ref', 'msg'),
        'header' => 'image',
    ),
    // Estimate / quotation PDF from the booking page outside the 24h window.
    'estimate_sent' => array(
        'name'   => getenv('YMO_WA_TPL_ESTIMATE_SENT') ?: 'estimate_sent',
        'lang'   => getenv('YMO_WA_TPL_LANG') ?: 'en',
        'params' => array('ref', 'msg'),
        'header' => 'document',
    ),
);
