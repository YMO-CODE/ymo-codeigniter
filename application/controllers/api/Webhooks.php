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
        $this->load->helper('crm');
        $this->load->model(array(
            'crm_lead_model', 'crm_lead_activity_model', 'crm_integration_model',
        ));
    }

    /**
     * Meta webhook: Lead Ads (leadgen) + Instagram/Messenger DMs (messages).
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

    /** WhatsApp enquiry webhook (flat JSON or Meta Cloud API, HMAC optional). */
    public function whatsapp()
    {
        if ($this->input->method() === 'get') {
            return $this->_meta_verify();
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, TRUE);
        $is_meta_cloud = is_array($data) && crm_is_meta_whatsapp_cloud_payload($data);

        // Meta signs Cloud API webhooks with the app secret (X-Hub-Signature-256).
        // Only enforced when CRM_META_APP_SECRET is set.
        $app_secret = (string) $this->config->item('crm_meta_app_secret');
        if ($is_meta_cloud && $app_secret !== '') {
            $hub = (string) $this->input->get_request_header('X-Hub-Signature-256', TRUE);
            $expected = 'sha256='.hash_hmac('sha256', $raw, $app_secret);
            if ($hub === '' || !hash_equals($expected, $hub)) {
                $this->crm_integration_model->log('whatsapp', 'auth_failed', array('reason' => 'x-hub-signature'), 'inbound', 403);
                return $this->_json(array('error' => 'Invalid signature'), 403);
            }
        }

        $sig = $this->input->get_request_header('X-CRM-Signature', TRUE);
        if ($this->config->item('crm_webhook_hmac_secret') && !$is_meta_cloud && !crm_verify_webhook_hmac($raw, $sig)) {
            $this->crm_integration_model->log('whatsapp', 'auth_failed', array(), 'inbound', 403);
            return $this->_json(array('error' => 'Invalid signature'), 403);
        }

        if (!is_array($data)) {
            return $this->_json(array('error' => 'Invalid JSON'), 400);
        }

        $receipts = $is_meta_cloud ? $this->_whatsapp_apply_statuses($data) : 0;

        $messages = crm_normalize_whatsapp_payloads($data);
        if (empty($messages)) {
            return $this->_json(array('ok' => TRUE, 'skipped' => 'no_text_messages', 'receipts' => $receipts));
        }

        $opted_out = 0;
        if ($is_meta_cloud) {
            $this->load->model('crm_wa_message_model');
            foreach ($messages as $msg) {
                $this->crm_wa_message_model->log(array(
                    'wa_message_id' => $msg['message_id'] ?: NULL,
                    'phone'         => $msg['from'],
                    'direction'     => 'in',
                    'kind'          => (string) ($msg['raw']['type'] ?? 'text'),
                    'body'          => $msg['text'],
                    'status'        => 'received',
                ));
                if (crm_whatsapp_is_opt_out($msg['text'])) {
                    $opted_out += $this->_whatsapp_opt_out($msg['from']);
                }
            }
        }

        $lead_ids = array();
        $auto = array();
        if ($is_meta_cloud) {
            $this->load->library('crm_wa_autoreply');
        }
        foreach ($messages as $msg) {
            // "Stop promotions" from someone who never enquired shouldn't become a lead.
            if (crm_whatsapp_is_opt_out($msg['text']) && !$this->_whatsapp_lead_exists($msg['from'])) {
                if ($is_meta_cloud) {
                    $auto[] = $this->crm_wa_autoreply->handle($msg['from'], $msg['name'], $msg['text'], 0);
                }
                continue;
            }
            $lead_id = $this->crm_lead_model->ingest_chat_message(
                'whatsapp',
                array(
                    'name'    => $msg['name'],
                    'mobile'  => $msg['from'],
                    'email'   => '',
                    'text'    => $msg['text'],
                    'message' => $msg['text'],
                    'raw'     => $msg['raw'],
                ),
                $msg['from'],
                $msg['message_id'] ?: NULL,
                'whatsapp',
                'whatsapp',
                'whatsapp'
            );
            if ($lead_id) {
                $lead_ids[] = $lead_id;
            }
            if ($is_meta_cloud) {
                $auto[] = $this->crm_wa_autoreply->handle($msg['from'], $msg['name'], $msg['text'], (int) $lead_id);
            }
        }

        $this->crm_integration_model->log('whatsapp', 'message_received', $data, 'inbound', 200);

        return $this->_json(array(
            'ok'        => TRUE,
            'lead_ids'  => array_values(array_unique($lead_ids)),
            'receipts'  => $receipts,
            'opted_out' => $opted_out,
            'auto'      => array_values(array_filter($auto)),
        ));
    }

    /** Is there already a WhatsApp lead for this sender? */
    protected function _whatsapp_lead_exists($from)
    {
        return (bool) $this->db->from('crm_leads')
            ->where('external_provider', 'whatsapp')
            ->where('external_lead_id', (string) $from)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->count_all_results();
    }

    /** Delivery/read/failed receipts -> message log + campaign recipients. */
    protected function _whatsapp_apply_statuses(array $data)
    {
        $statuses = crm_whatsapp_parse_statuses($data);
        if (!$statuses) {
            return 0;
        }
        $this->load->model(array('crm_wa_message_model', 'crm_campaign_model'));
        $n = 0;
        foreach ($statuses as $st) {
            $n += $this->crm_wa_message_model->apply_status($st['id'], $st['status'], $st['error']);
            $this->crm_campaign_model->apply_wa_status($st['id'], $st['status'], $st['error']);
        }
        return $n;
    }

    /** Customer replied STOP: flag every contact with that number. */
    protected function _whatsapp_opt_out($from)
    {
        $last10 = substr(preg_replace('/\D/', '', (string) $from), -10);
        if (strlen($last10) !== 10 || !$this->db->field_exists('whatsapp_opt_out', 'crm_contacts')) {
            return 0;
        }
        $this->db->like('mobile', $last10, 'before')->update('crm_contacts', array('whatsapp_opt_out' => 1));
        $n = (int) $this->db->affected_rows();
        $this->crm_integration_model->log('whatsapp', 'opt_out', array('from' => $from, 'contacts' => $n), 'inbound', 200);
        return $n;
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

        $lead_ids = array();

        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] ?? array() as $change) {
                $field = $change['field'] ?? '';
                if ($field === 'leadgen') {
                    $id = $this->_meta_ingest_leadgen($change['value'] ?? array());
                    if ($id) {
                        $lead_ids[] = $id;
                    }
                }
            }
        }

        foreach (crm_parse_meta_messaging_events(is_array($data) ? $data : array()) as $event) {
            $name = $event['name'];
            if (strpos($name, 'Instagram ') === 0 && $name === 'Instagram '.$event['sender_id']) {
                $fetched = $this->_fetch_meta_sender_name($event['sender_id']);
                if ($fetched) {
                    $name = $fetched;
                }
            }

            $source = ($event['channel'] === 'instagram') ? 'instagram' : 'meta';
            if ($event['channel'] !== 'instagram') {
                continue;
            }

            $lead_id = $this->crm_lead_model->ingest_chat_message(
                $source,
                array(
                    'name'    => $name,
                    'mobile'  => '',
                    'email'   => '',
                    'text'    => $event['text'],
                    'message' => $event['text'],
                    'raw'     => $event['raw'],
                ),
                $event['sender_id'],
                $event['message_id'] ?: NULL,
                'instagram_dm',
                'webhook',
                'instagram'
            );

            if ($lead_id) {
                $lead_ids[] = $lead_id;
                $this->crm_integration_model->log('meta', 'instagram_dm_received', $event, 'inbound', 200);
            }
        }

        return $this->_json(array('ok' => TRUE, 'lead_ids' => array_values(array_unique($lead_ids))));
    }

    protected function _meta_ingest_leadgen(array $val)
    {
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
        return $lead_id;
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

    protected function _fetch_meta_sender_name($sender_id)
    {
        $token = $this->config->item('crm_meta_access_token');
        if (!$token || !$sender_id) {
            return NULL;
        }
        $url = 'https://graph.facebook.com/v19.0/'.urlencode($sender_id)
            .'?fields=name,username&access_token='.urlencode($token);
        $json = @file_get_contents($url);
        if (!$json) {
            return NULL;
        }
        $data = json_decode($json, TRUE);
        if (!is_array($data)) {
            return NULL;
        }
        if (!empty($data['name'])) {
            return (string) $data['name'];
        }
        if (!empty($data['username'])) {
            return (string) $data['username'];
        }
        return NULL;
    }

    protected function _json(array $payload, $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
