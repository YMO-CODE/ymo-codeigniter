<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer_notifier — one entry point for transactional customer messages.
 *
 *   $this->customer_notifier->notify($mobile, 'booking_status', array('ref' => …, 'status' => …),
 *       array('context' => 'admin.booking_status', 'booking_id' => 12));
 *
 * When WhatsApp notifications are switched on (CRM_WHATSAPP_NOTIFY=1, plus
 * the Cloud API token/phone id) and the event has a template mapped in
 * config('crm_whatsapp_templates'), the approved WhatsApp template is sent
 * first. If that is off, unmapped or fails, the existing DLT SMS template is
 * sent instead — so with WhatsApp unconfigured behaviour is exactly the SMS
 * flow the app had before.
 */
class Customer_notifier
{
    /** @var CI_Controller */
    protected $CI;

    /** @var string|null */
    protected $last_error = NULL;

    /** @var string|null 'whatsapp' | 'sms' | NULL */
    protected $last_channel = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library(array('sms_gateway', 'crm_messaging'));
        $this->CI->load->model('crm_wa_message_model');
    }

    public function last_error()
    {
        return $this->last_error;
    }

    public function last_channel()
    {
        return $this->last_channel;
    }

    /** True when booking notifications will go out on WhatsApp first. */
    public function whatsapp_enabled()
    {
        return (bool) $this->CI->config->item('crm_whatsapp_notify_enabled')
            && $this->CI->crm_messaging->can_send_whatsapp();
    }

    /**
     * @param string $mobile   Customer mobile (any Indian format).
     * @param string $event    booking_confirmed|booking_status|service_reminder|review_request|invoice_sent|…
     * @param array  $vars     Semantic variables (same keys the SMS templates use).
     * @param array  $opts     context (log label), booking_id, document (abs path),
     *                         document_name, image (abs path), sms_fallback (default TRUE),
     *                         force_whatsapp (ignore CRM_WHATSAPP_NOTIFY, e.g. manual
     *                         sends from the booking page), admin_id
     * @return bool TRUE when at least one channel accepted the message.
     */
    public function notify($mobile, $event, array $vars = array(), array $opts = array())
    {
        $this->last_error = NULL;
        $this->last_channel = NULL;
        $context = isset($opts['context']) ? (string) $opts['context'] : 'notify.'.$event;
        $wa_error = NULL;

        $wa_on = !empty($opts['force_whatsapp'])
            ? $this->CI->crm_messaging->can_send_whatsapp()
            : $this->whatsapp_enabled();
        if ($wa_on) {
            $tpl = $this->_template_for($event);
            if ($tpl) {
                $res = $this->_send_whatsapp($mobile, $event, $tpl, $vars, $opts);
                if ($res['ok']) {
                    $this->last_channel = 'whatsapp';
                    log_message('info', sprintf('[wa] %s ok template=%s to=%s', $context, $tpl['name'], $mobile));
                    return TRUE;
                }
                $wa_error = $res['error'] ?: 'WhatsApp send failed';
                log_message('error', sprintf('[wa] %s fail template=%s to=%s err=%s — falling back to SMS',
                    $context, $tpl['name'], $mobile, $wa_error));
            }
        }

        if (array_key_exists('sms_fallback', $opts) && !$opts['sms_fallback']) {
            $this->last_error = $wa_error ?: 'WhatsApp not enabled for this event.';
            return FALSE;
        }

        $ok = $this->CI->sms_gateway->send_template($mobile, $event, $vars);
        ymo_sms_log($context, $event, $mobile, $ok, $this->CI->sms_gateway);
        if ($ok) {
            $this->last_channel = 'sms';
            return TRUE;
        }
        $this->last_error = trim(($wa_error ? 'WhatsApp: '.$wa_error.' / ' : '')
            .'SMS: '.($this->CI->sms_gateway->last_error() ?: 'fail'));
        return FALSE;
    }

    /** @return array|null Normalised template config for an event. */
    protected function _template_for($event)
    {
        $map = (array) $this->CI->config->item('crm_whatsapp_templates');
        if (empty($map[$event]['name'])) {
            return NULL;
        }
        $tpl = $map[$event];
        $tpl['lang']   = !empty($tpl['lang']) ? $tpl['lang'] : 'en';
        $tpl['params'] = isset($tpl['params']) ? (array) $tpl['params'] : array();
        $tpl['header'] = isset($tpl['header']) ? strtolower((string) $tpl['header']) : NULL;

        // If templates have been synced, only use one Meta has approved.
        $this->CI->load->model('crm_wa_template_model');
        if ($this->CI->crm_wa_template_model->table_ready()) {
            $row = $this->CI->crm_wa_template_model->find_by_name($tpl['name'], $tpl['lang']);
            if ($row && $row['status'] !== 'APPROVED') {
                return NULL;
            }
            if ($row) {
                $tpl['header'] = $row['header_type'] && $row['header_type'] !== 'text' ? $row['header_type'] : NULL;
                $tpl['param_count'] = (int) $row['param_count'];
            }
        }
        return $tpl;
    }

    protected function _send_whatsapp($mobile, $event, array $tpl, array $vars, array $opts)
    {
        $params = array();
        foreach ($tpl['params'] as $key) {
            $params[] = isset($vars[$key]) ? (string) $vars[$key] : '';
        }
        if (isset($tpl['param_count'])) {
            $params = array_slice(array_pad($params, $tpl['param_count'], '-'), 0, $tpl['param_count']);
        }

        $header = NULL;
        $media_path = NULL;
        if ($tpl['header'] === 'document' || $tpl['header'] === 'image') {
            $key = $tpl['header'];
            $file = isset($opts[$key]) ? (string) $opts[$key] : '';
            if ($file === '' || !is_file($file)) {
                return array('ok' => FALSE, 'message_id' => NULL,
                    'error' => 'Template needs a '.$key.' header but no file was supplied.');
            }
            $mime = $key === 'document' ? 'application/pdf' : $this->_image_mime($file);
            $media_id = $this->CI->crm_messaging->upload_whatsapp_media($file, $mime);
            if (!$media_id) {
                return array('ok' => FALSE, 'message_id' => NULL,
                    'error' => $this->CI->crm_messaging->last_error() ?: 'Media upload failed');
            }
            $header = array('type' => $key, 'id' => $media_id);
            if ($key === 'document') {
                $header['filename'] = !empty($opts['document_name']) ? (string) $opts['document_name'] : basename($file);
            }
            $media_path = $file;
        }

        $res = $this->CI->crm_messaging->send_whatsapp_template($mobile, $tpl['name'], $tpl['lang'], $params, $header);

        $this->CI->crm_wa_message_model->log(array(
            'wa_message_id' => $res['message_id'],
            'phone'         => $mobile,
            'direction'     => 'out',
            'kind'          => 'template',
            'template_name' => $tpl['name'],
            'body'          => $event.': '.implode(' | ', $params),
            'media_path'    => $media_path ? $this->_rel_path($media_path) : NULL,
            'context_type'  => !empty($opts['booking_id']) ? 'booking' : NULL,
            'context_id'    => !empty($opts['booking_id']) ? (int) $opts['booking_id'] : NULL,
            'status'        => $res['ok'] ? 'sent' : 'failed',
            'error'         => $res['ok'] ? NULL : $this->CI->crm_messaging->last_error(),
            'admin_id'      => !empty($opts['admin_id']) ? (int) $opts['admin_id'] : NULL,
        ));

        $res['error'] = $res['ok'] ? NULL : $this->CI->crm_messaging->last_error();
        return $res;
    }

    protected function _image_mime($file)
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return $ext === 'png' ? 'image/png' : 'image/jpeg';
    }

    protected function _rel_path($abs)
    {
        $root = realpath(FCPATH.'..');
        $real = realpath($abs);
        if ($root && $real && strpos($real, $root) === 0) {
            return ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');
        }
        return basename($abs);
    }
}
