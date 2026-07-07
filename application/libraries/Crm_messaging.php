<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Meta Cloud API messaging — WhatsApp + Instagram DM outbound.
 *
 *   $this->crm_messaging->send_whatsapp_text($to_e164, $body);
 *   $this->crm_messaging->send_instagram_text($recipient_igsid, $body);
 */
class Crm_messaging
{
    /** @var CI_Controller */
    protected $CI;

    /** @var string|null */
    protected $last_error = NULL;

    /** @var string */
    protected $graph_version = 'v19.0';

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function last_error()
    {
        return $this->last_error;
    }

    public function can_send_whatsapp()
    {
        return $this->_access_token() !== '' && $this->_whatsapp_phone_id() !== '';
    }

    public function can_send_instagram()
    {
        return $this->_access_token() !== '';
    }

    /**
     * @param string $to_e164 Digits only with country code, e.g. 919876543210
     * @return array{ok:bool,message_id:string|null}
     */
    public function send_whatsapp_text($to_e164, $body)
    {
        $phone_id = $this->_whatsapp_phone_id();
        if (!$phone_id || !$this->_access_token()) {
            $this->last_error = 'WhatsApp messaging not configured (CRM_WHATSAPP_PHONE_NUMBER_ID / CRM_META_ACCESS_TOKEN).';
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        $to = preg_replace('/\D/', '', (string) $to_e164);
        if ($to === '') {
            $this->last_error = 'Invalid WhatsApp recipient number.';
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        return $this->_post_messages($phone_id, array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => array('preview_url' => FALSE, 'body' => (string) $body),
        ));
    }

    /**
     * @param string $recipient_igsid Instagram-scoped sender id from webhook
     * @return array{ok:bool,message_id:string|null}
     */
    public function send_instagram_text($recipient_igsid, $body)
    {
        if (!$this->_access_token()) {
            $this->last_error = 'Instagram messaging not configured (CRM_META_ACCESS_TOKEN).';
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        $recipient = trim((string) $recipient_igsid);
        if ($recipient === '') {
            $this->last_error = 'Invalid Instagram recipient id.';
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        return $this->_post_messages('me', array(
            'messaging_type' => 'RESPONSE',
            'recipient'      => array('id' => $recipient),
            'message'        => array('text' => (string) $body),
        ));
    }

    protected function _post_messages($node, array $payload)
    {
        $url = 'https://graph.facebook.com/'.$this->graph_version.'/'.rawurlencode($node).'/messages'
            .'?access_token='.urlencode($this->_access_token());

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_TIMEOUT        => 20,
        ));

        $response = curl_exec($ch);
        $http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->last_error = 'Meta API request failed: '.$err;
            log_message('error', '[crm_messaging] '.$this->last_error);
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        $decoded = json_decode((string) $response, TRUE);
        if ($http >= 400 || !is_array($decoded)) {
            $msg = is_array($decoded) && !empty($decoded['error']['message'])
                ? $decoded['error']['message']
                : (string) $response;
            $this->last_error = 'Meta API HTTP '.$http.': '.$msg;
            log_message('error', '[crm_messaging] '.$this->last_error);
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        $mid = isset($decoded['message_id']) ? (string) $decoded['message_id'] : NULL;
        if (!$mid && !empty($decoded['messages'][0]['id'])) {
            $mid = (string) $decoded['messages'][0]['id'];
        }

        return array('ok' => TRUE, 'message_id' => $mid);
    }

    protected function _access_token()
    {
        return trim((string) $this->CI->config->item('crm_meta_access_token'));
    }

    protected function _whatsapp_phone_id()
    {
        return trim((string) $this->CI->config->item('crm_whatsapp_phone_number_id'));
    }
}
