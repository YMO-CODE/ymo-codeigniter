<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public webhook endpoints for lead ingestion (no admin session).
 * CSRF excluded via config/csrf_exclude_uris.
 */
class Webhooks extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'crm_lead_model', 'crm_lead_activity_model', 'crm_integration_model',
        ));
    }

    /**
     * Meta / Instagram Lead Ads webhook.
     * GET: hub verification. POST: lead payload.
     */
    public function meta()
    {
        if ($this->input->method() === 'get') {
            return $this->_meta_verify();
        }
        return $this->_meta_ingest();
    }

    /** Generic website / landing-page form webhook (HMAC signed JSON). */
    public function website()
    {
        $raw = file_get_contents('php://input');
        $sig = $this->input->get_request_header('X-CRM-Signature', TRUE);
        if (!crm_verify_webhook_hmac($raw, $sig)) {
            $this->crm_integration_model->log('website', 'auth_failed', array(), 'inbound', 403);
            return $this->_json(array('error' => 'Invalid signature'), 403);
        }

        $data = json_decode($raw, TRUE);
        if (!is_array($data)) {
            return $this->_json(array('error' => 'Invalid JSON'), 400);
        }

        $source = isset($data['source']) ? $data['source'] : 'website';
        if (!in_array($source, array('website', 'landing', 'referral', 'cold_call'), TRUE)) {
            $source = 'website';
        }

        $lead_id = $this->crm_lead_model->ingest($source, array(
            'name'    => $data['name'] ?? ($data['full_name'] ?? 'Website enquiry'),
            'mobile'  => $data['mobile'] ?? ($data['phone'] ?? ''),
            'email'   => $data['email'] ?? '',
            'company' => $data['company'] ?? NULL,
            'message' => $data['message'] ?? ($data['comments'] ?? NULL),
            'raw'     => $data,
        ), $data['id'] ?? NULL, 'website_form');

        if ($lead_id) {
            $this->crm_lead_activity_model->add($lead_id, NULL, 'webhook', 'Lead captured from website form');
        }
        $this->crm_integration_model->log('website', 'lead_created', $data, 'inbound', 200);

        return $this->_json(array('ok' => TRUE, 'lead_id' => $lead_id));
    }

    /** WhatsApp enquiry webhook (JSON body, HMAC optional). */
    public function whatsapp()
    {
        $raw = file_get_contents('php://input');
        $sig = $this->input->get_request_header('X-CRM-Signature', TRUE);
        if ($this->config->item('crm_webhook_hmac_secret') && !crm_verify_webhook_hmac($raw, $sig)) {
            $this->crm_integration_model->log('whatsapp', 'auth_failed', array(), 'inbound', 403);
            return $this->_json(array('error' => 'Invalid signature'), 403);
        }

        $data = json_decode($raw, TRUE);
        if (!is_array($data)) {
            return $this->_json(array('error' => 'Invalid JSON'), 400);
        }

        $mobile = $data['from'] ?? ($data['mobile'] ?? ($data['wa_id'] ?? ''));
        $text   = $data['text'] ?? ($data['message'] ?? ($data['body'] ?? ''));

        $lead_id = $this->crm_lead_model->ingest('whatsapp', array(
            'name'    => $data['name'] ?? ('WhatsApp '.$mobile),
            'mobile'  => $mobile,
            'email'   => $data['email'] ?? '',
            'message' => $text,
            'raw'     => $data,
        ), $data['message_id'] ?? NULL, 'whatsapp');

        if ($lead_id) {
            $this->crm_lead_activity_model->add($lead_id, NULL, 'whatsapp', 'Inbound WhatsApp: '.$text);
        }
        $this->crm_integration_model->log('whatsapp', 'message_received', $data, 'inbound', 200);

        return $this->_json(array('ok' => TRUE, 'lead_id' => $lead_id));
    }

    protected function _meta_verify()
    {
        $token    = $this->input->get('hub_verify_token');
        $challenge = $this->input->get('hub_challenge');
        $mode     = $this->input->get('hub_mode');

        $expected = $this->config->item('crm_meta_verify_token');
        if ($mode === 'subscribe' && $expected && hash_equals($expected, (string) $token)) {
            header('Content-Type: text/plain');
            echo $challenge;
            return;
        }
        show_error('Forbidden', 403);
    }

    protected function _meta_ingest()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, TRUE);
        $this->crm_integration_model->log('meta', 'webhook_received', $data ?: array(), 'inbound', 200);

        if (empty($data['entry'])) {
            return $this->_json(array('ok' => TRUE));
        }

        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] ?? array() as $change) {
                if (($change['field'] ?? '') !== 'leadgen') {
                    continue;
                }
                $val = $change['value'] ?? array();
                $leadgen_id = $val['leadgen_id'] ?? NULL;
                $form_id    = $val['form_id'] ?? '';
                $source     = (strpos(strtolower($form_id), 'instagram') !== FALSE) ? 'instagram' : 'meta';

                $fields = $this->_parse_meta_lead_fields($val);
                if (empty($fields['mobile']) && empty($fields['email']) && $leadgen_id) {
                    $fields = $this->_fetch_meta_lead($leadgen_id, $fields);
                }
                $lead_id = $this->crm_lead_model->ingest($source, $fields, $leadgen_id, 'meta');
                if ($lead_id) {
                    $this->crm_lead_activity_model->add($lead_id, NULL, 'webhook',
                        'Lead captured from '.($source === 'instagram' ? 'Instagram' : 'Meta').' Ads'
                    );
                }
            }
        }

        return $this->_json(array('ok' => TRUE));
    }

    protected function _parse_meta_lead_fields(array $val)
    {
        $name = $email = $mobile = $message = '';
        foreach ($val['field_data'] ?? array() as $field) {
            $n = strtolower($field['name'] ?? '');
            $v = isset($field['values'][0]) ? $field['values'][0] : '';
            if (strpos($n, 'full_name') !== FALSE || $n === 'name') {
                $name = $v;
            } elseif (strpos($n, 'email') !== FALSE) {
                $email = $v;
            } elseif (strpos($n, 'phone') !== FALSE || strpos($n, 'mobile') !== FALSE) {
                $mobile = $v;
            } else {
                $message .= ($message ? "\n" : '').$n.': '.$v;
            }
        }
        return array(
            'name'    => $name ?: 'Meta lead',
            'email'   => $email,
            'mobile'  => $mobile,
            'message' => $message ?: NULL,
            'raw'     => $val,
        );
    }

    protected function _fetch_meta_lead($leadgen_id, array $fallback)
    {
        $token = $this->config->item('crm_meta_access_token');
        if (!$token) {
            return $fallback;
        }
        $url = 'https://graph.facebook.com/v19.0/'.urlencode($leadgen_id).'?access_token='.urlencode($token);
        $json = @file_get_contents($url);
        if (!$json) {
            return $fallback;
        }
        $data = json_decode($json, TRUE);
        if (!is_array($data)) {
            return $fallback;
        }
        return $this->_parse_meta_lead_fields($data);
    }

    protected function _json(array $payload, $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
