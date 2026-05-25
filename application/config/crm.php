<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Embedded CRM defaults (secrets via getenv in production).
 */
$config['crm_webhook_hmac_secret'] = getenv('CRM_WEBHOOK_SECRET') ?: '';
$config['crm_meta_verify_token']  = getenv('CRM_META_VERIFY_TOKEN') ?: '';
$config['crm_meta_access_token']  = getenv('CRM_META_ACCESS_TOKEN') ?: '';
$config['crm_campaign_batch_size'] = 35;
$config['crm_task_reminder_hours'] = 48;
$config['crm_resume_max_kb']       = 4096;
$config['crm_resume_allowed_types']= 'pdf|doc|docx';
$config['crm_resume_upload_path']  = FCPATH.'uploads/crm/resumes/';
$config['crm_sms_campaign_tpl']    = getenv('YMO_TPL_CRM_CAMPAIGN') ?: '';
