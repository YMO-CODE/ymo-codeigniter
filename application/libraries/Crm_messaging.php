<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Meta Cloud API messaging — WhatsApp + Instagram DM outbound.
 *
 *   $this->crm_messaging->send_whatsapp_text($to_e164, $body);
 *   $this->crm_messaging->send_whatsapp_template($to, $name, $lang, $params, $header);
 *   $this->crm_messaging->send_whatsapp_media($to, 'image', $media_id, $caption);
 *   $this->crm_messaging->upload_whatsapp_media($abs_path, $mime);
 *   $this->crm_messaging->fetch_whatsapp_templates();
 *   $this->crm_messaging->send_instagram_text($recipient_igsid, $body);
 *
 * Only talks to Meta when CRM_META_ACCESS_TOKEN (+ phone number id / WABA id
 * for WhatsApp) are configured; otherwise every call returns ok=FALSE.
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
        $v = trim((string) $this->CI->config->item('crm_meta_graph_version'));
        if ($v !== '') {
            $this->graph_version = $v;
        }
    }

    public function last_error()
    {
        return $this->last_error;
    }

    public function can_send_whatsapp()
    {
        return $this->_wa_token() !== '' && $this->_whatsapp_phone_id() !== '';
    }

    public function can_sync_whatsapp_templates()
    {
        return $this->_wa_token() !== '' && $this->_waba_id() !== '';
    }

    public function can_send_instagram()
    {
        return $this->_access_token() !== '';
    }

    /**
     * @param string $to_e164 Digits only with country code, e.g. 919876543210
     * @return array{ok:bool,message_id:string|null}
     */
    public function send_whatsapp_text($to_e164, $body, $preview_url = NULL)
    {
        $phone_id = $this->_whatsapp_phone_id();
        if (!$phone_id || !$this->_wa_token()) {
            $this->last_error = 'WhatsApp messaging not configured (CRM_WHATSAPP_PHONE_NUMBER_ID / CRM_WHATSAPP_ACCESS_TOKEN).';
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        $to = $this->_wa_to($to_e164);
        if ($to === '') {
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        return $this->_post_messages($phone_id, array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => array('preview_url' => $preview_url === NULL ? (strpos((string) $body, 'https://') !== FALSE) : (bool) $preview_url, 'body' => (string) $body),
        ));
    }

    /**
     * Send an approved template (works outside the 24h customer-service window).
     *
     * @param string[]   $body_params Values for {{1}}, {{2}}… in the template body.
     * @param array|null $header      array('type' => 'image'|'document'|'video'|'text',
     *                                 'id' => media id | 'link' => https url | 'text' => …,
     *                                 'filename' => for documents)
     * @return array{ok:bool,message_id:string|null}
     */
    public function send_whatsapp_template($to_e164, $name, $lang = 'en', array $body_params = array(), $header = NULL, array $url_buttons = array())
    {
        if (!$this->_wa_ready()) {
            return array('ok' => FALSE, 'message_id' => NULL);
        }
        $to = $this->_wa_to($to_e164);
        if ($to === '') {
            return array('ok' => FALSE, 'message_id' => NULL);
        }
        $name = trim((string) $name);
        if ($name === '') {
            $this->last_error = 'WhatsApp template name missing.';
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        $components = array();
        if (is_array($header) && !empty($header['type'])) {
            $htype = strtolower($header['type']);
            if ($htype === 'text') {
                $components[] = array(
                    'type'       => 'header',
                    'parameters' => array(array('type' => 'text', 'text' => (string) ($header['text'] ?? ''))),
                );
            } elseif (in_array($htype, array('image', 'document', 'video'), TRUE)) {
                $obj = array();
                if (!empty($header['id'])) {
                    $obj['id'] = (string) $header['id'];
                } elseif (!empty($header['link'])) {
                    $obj['link'] = (string) $header['link'];
                }
                if ($htype === 'document' && !empty($header['filename'])) {
                    $obj['filename'] = (string) $header['filename'];
                }
                if ($obj) {
                    $components[] = array(
                        'type'       => 'header',
                        'parameters' => array(array('type' => $htype, $htype => $obj)),
                    );
                }
            }
        }
        if ($body_params) {
            $params = array();
            foreach (array_values($body_params) as $v) {
                // Meta rejects empty params and newlines/tabs inside template variables.
                $v = trim(preg_replace('/[\r\n\t]+/', ' ', (string) $v));
                $v = preg_replace('/ {5,}/', '    ', $v);
                $params[] = array('type' => 'text', 'text' => $v !== '' ? $v : '-');
            }
            $components[] = array('type' => 'body', 'parameters' => $params);
        }
        // Dynamic URL buttons: the value replaces {{1}} at the end of the button URL.
        foreach (array_values($url_buttons) as $i => $suffix) {
            if ((string) $suffix === '') {
                continue;
            }
            $components[] = array(
                'type' => 'button', 'sub_type' => 'url', 'index' => (string) $i,
                'parameters' => array(array('type' => 'text', 'text' => (string) $suffix)),
            );
        }

        $template = array(
            'name'     => $name,
            'language' => array('code' => $lang ?: 'en'),
        );
        if ($components) {
            $template['components'] = $components;
        }

        return $this->_post_messages($this->_whatsapp_phone_id(), array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'template',
            'template'          => $template,
        ));
    }

    /**
     * Free-form media message (only delivered inside the 24h customer-service window).
     *
     * @param string $type     image|document|video|audio
     * @param string $media_id From upload_whatsapp_media()
     * @return array{ok:bool,message_id:string|null}
     */
    public function send_whatsapp_media($to_e164, $type, $media_id, $caption = '', $filename = '')
    {
        if (!$this->_wa_ready()) {
            return array('ok' => FALSE, 'message_id' => NULL);
        }
        $to = $this->_wa_to($to_e164);
        if ($to === '') {
            return array('ok' => FALSE, 'message_id' => NULL);
        }
        $type = strtolower((string) $type);
        if (!in_array($type, array('image', 'document', 'video', 'audio'), TRUE)) {
            $this->last_error = 'Unsupported WhatsApp media type: '.$type;
            return array('ok' => FALSE, 'message_id' => NULL);
        }
        $obj = array('id' => (string) $media_id);
        if ($caption !== '' && $type !== 'audio') {
            $obj['caption'] = (string) $caption;
        }
        if ($type === 'document' && $filename !== '') {
            $obj['filename'] = (string) $filename;
        }

        return $this->_post_messages($this->_whatsapp_phone_id(), array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => $type,
            $type               => $obj,
        ));
    }

    /**
     * Upload a local file to WhatsApp media storage.
     *
     * @return string|null Media id (valid ~30 days) or NULL on failure.
     */
    public function upload_whatsapp_media($abs_path, $mime)
    {
        if (!$this->_wa_ready()) {
            return NULL;
        }
        if (!is_file($abs_path) || !is_readable($abs_path)) {
            $this->last_error = 'Media file not found: '.basename((string) $abs_path);
            return NULL;
        }

        $url = $this->_graph_url($this->_whatsapp_phone_id().'/media');
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => array(
                'messaging_product' => 'whatsapp',
                'type'              => (string) $mime,
                'file'              => new CURLFile($abs_path, (string) $mime, basename($abs_path)),
            ),
            CURLOPT_HTTPHEADER     => array('Authorization: Bearer '.$this->_wa_token()),
            CURLOPT_TIMEOUT        => 60,
        ));
        $decoded = $this->_exec($ch);
        if (!$decoded || empty($decoded['id'])) {
            return NULL;
        }
        return (string) $decoded['id'];
    }

    /**
     * Pull all message templates for the WhatsApp Business Account.
     *
     * @return array[]|null Raw template objects from Meta, NULL on failure.
     */
    public function fetch_whatsapp_templates()
    {
        if (!$this->can_sync_whatsapp_templates()) {
            $this->last_error = 'Template sync needs CRM_WHATSAPP_ACCESS_TOKEN and CRM_WHATSAPP_WABA_ID.';
            return NULL;
        }

        $out  = array();
        $next = $this->_graph_url($this->_waba_id().'/message_templates', array(
            'limit'  => 100,
            'fields' => 'id,name,language,status,category,components',
        ));
        $guard = 0;
        while ($next && $guard++ < 20) {
            $ch = curl_init($next);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HTTPHEADER     => array('Authorization: Bearer '.$this->_wa_token()),
                CURLOPT_TIMEOUT        => 30,
            ));
            $decoded = $this->_exec($ch);
            if ($decoded === NULL) {
                return NULL;
            }
            foreach ($decoded['data'] ?? array() as $tpl) {
                $out[] = $tpl;
            }
            $next = $decoded['paging']['next'] ?? NULL;
        }
        return $out;
    }

    /**
     * Submit a template from config/whatsapp_template_library.php to Meta for approval.
     * Supports text headers, body examples, footer, QUICK_REPLY / URL / PHONE_NUMBER buttons.
     * @return string|null Meta template id
     */
    public function create_whatsapp_template(array $t, $lang = 'en')
    {
        if (!$this->can_sync_whatsapp_templates()) {
            $this->last_error = 'Template submit needs CRM_WHATSAPP_ACCESS_TOKEN and CRM_WHATSAPP_WABA_ID.';
            return NULL;
        }
        $components = array();
        if (!empty($t['header']) && is_array($t['header'])) {
            if ($t['header']['type'] !== 'text') {
                $this->last_error = 'Templates with a '.$t['header']['type'].' header need a sample file: create this one in WhatsApp Manager.';
                return NULL;
            }
            $components[] = array('type' => 'HEADER', 'format' => 'TEXT', 'text' => (string) $t['header']['text']);
        }
        $body = array('type' => 'BODY', 'text' => (string) $t['body']);
        if (!empty($t['examples'])) {
            $body['example'] = array('body_text' => array(array_values(array_map('strval', $t['examples']))));
        }
        $components[] = $body;
        if (!empty($t['footer'])) {
            $components[] = array('type' => 'FOOTER', 'text' => (string) $t['footer']);
        }
        if (!empty($t['buttons'])) {
            $btns = array();
            foreach ($t['buttons'] as $b) {
                $type = strtoupper($b['type']);
                $x = array('type' => $type, 'text' => (string) $b['text']);
                if ($type === 'URL') {
                    $x['url'] = (string) $b['url'];
                    if (!empty($b['example'])) {
                        $x['example'] = array((string) $b['example']);
                    }
                } elseif ($type === 'PHONE_NUMBER') {
                    $x['phone_number'] = (string) $b['phone_number'];
                }
                $btns[] = $x;
            }
            $components[] = array('type' => 'BUTTONS', 'buttons' => $btns);
        }
        $ch = curl_init($this->_graph_url($this->_waba_id().'/message_templates'));
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => json_encode(array(
                'name'       => (string) $t['name'],
                'language'   => $lang,
                'category'   => strtoupper((string) $t['category']),
                'components' => $components,
            )),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'Authorization: Bearer '.$this->_wa_token()),
            CURLOPT_TIMEOUT        => 30,
        ));
        $res = $this->_exec($ch);
        return $res && !empty($res['id']) ? (string) $res['id'] : NULL;
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

    /**
     * WhatsApp spend reported by Meta (pricing_analytics) between two UNIX times.
     *
     * @return array{cost: float, volume: int, by_category: array}|null  NULL on error
     */
    public function fetch_whatsapp_spend($start, $end, $granularity = 'DAILY')
    {
        if (!$this->_waba_id() || !$this->_wa_token()) {
            $this->last_error = 'WhatsApp spend needs CRM_WHATSAPP_WABA_ID and an access token.';
            return NULL;
        }
        // pricing_analytics needs a recent Graph version, whatever the app default is.
        $field = sprintf('pricing_analytics.start(%d).end(%d).granularity(%s).dimensions(PRICING_CATEGORY)',
            (int) $start, (int) $end, $granularity);
        $url = 'https://graph.facebook.com/v22.0/'.rawurlencode($this->_waba_id()).'?'.http_build_query(array('fields' => $field));
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => array('Authorization: Bearer '.$this->_wa_token()),
            CURLOPT_TIMEOUT        => 20,
        ));
        $decoded = $this->_exec($ch);
        if ($decoded === NULL) {
            return NULL;
        }
        $out = array('cost' => 0.0, 'volume' => 0, 'by_category' => array());
        foreach ($decoded['pricing_analytics']['data'] ?? array() as $series) {
            foreach ($series['data_points'] ?? array() as $p) {
                $cat = strtolower((string) ($p['pricing_category'] ?? 'other'));
                $cost = (float) ($p['cost'] ?? 0);
                $vol  = (int) ($p['volume'] ?? 0);
                $out['cost'] += $cost;
                $out['volume'] += $vol;
                if (!isset($out['by_category'][$cat])) {
                    $out['by_category'][$cat] = array('cost' => 0.0, 'volume' => 0);
                }
                $out['by_category'][$cat]['cost'] += $cost;
                $out['by_category'][$cat]['volume'] += $vol;
            }
        }
        return $out;
    }

    protected function _post_messages($node, array $payload, $token = NULL)
    {
        if ($token === NULL) {
            // WhatsApp sends go to the phone-number node; Instagram to 'me'.
            $token = $node === $this->_whatsapp_phone_id() ? $this->_wa_token() : $this->_access_token();
        }
        $ch = curl_init($this->_graph_url($node.'/messages'));
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token,
            ),
            CURLOPT_TIMEOUT        => 20,
        ));

        $decoded = $this->_exec($ch);
        if ($decoded === NULL) {
            return array('ok' => FALSE, 'message_id' => NULL);
        }

        $mid = isset($decoded['message_id']) ? (string) $decoded['message_id'] : NULL;
        if (!$mid && !empty($decoded['messages'][0]['id'])) {
            $mid = (string) $decoded['messages'][0]['id'];
        }

        return array('ok' => TRUE, 'message_id' => $mid);
    }

    /** Run a prepared curl handle; returns decoded JSON or NULL (sets last_error). */
    protected function _exec($ch)
    {
        $this->last_error = NULL;
        $response = curl_exec($ch);
        $http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->last_error = 'Meta API request failed: '.$err;
            log_message('error', '[crm_messaging] '.$this->last_error);
            return NULL;
        }

        $decoded = json_decode((string) $response, TRUE);
        if ($http >= 400 || !is_array($decoded)) {
            $msg = is_array($decoded) && !empty($decoded['error']['message'])
                ? $decoded['error']['message']
                : substr((string) $response, 0, 300);
            if (is_array($decoded) && !empty($decoded['error']['error_data']['details'])) {
                $msg .= ' ('.$decoded['error']['error_data']['details'].')';
            }
            $this->last_error = 'Meta API HTTP '.$http.': '.$msg;
            log_message('error', '[crm_messaging] '.$this->last_error);
            return NULL;
        }
        return $decoded;
    }

    protected function _graph_url($path, array $query = array())
    {
        $parts = array_map('rawurlencode', explode('/', trim($path, '/')));
        $url = 'https://graph.facebook.com/'.$this->graph_version.'/'.implode('/', $parts);
        return $query ? $url.'?'.http_build_query($query) : $url;
    }

    protected function _wa_ready()
    {
        if (!$this->can_send_whatsapp()) {
            $this->last_error = 'WhatsApp messaging not configured (CRM_WHATSAPP_PHONE_NUMBER_ID / CRM_WHATSAPP_ACCESS_TOKEN).';
            return FALSE;
        }
        return TRUE;
    }

    protected function _wa_to($number)
    {
        $to = function_exists('crm_whatsapp_e164') ? crm_whatsapp_e164($number) : preg_replace('/\D/', '', (string) $number);
        if ($to === '') {
            $this->last_error = 'Invalid WhatsApp recipient number.';
        }
        return $to;
    }

    protected function _access_token()
    {
        return trim((string) $this->CI->config->item('crm_meta_access_token'));
    }

    /**
     * WhatsApp Cloud API token (system user). Separate from the Page token used
     * for lead ads / Instagram; falls back to CRM_META_ACCESS_TOKEN if unset.
     */
    protected function _wa_token()
    {
        $t = trim((string) $this->CI->config->item('crm_whatsapp_access_token'));
        return $t !== '' ? $t : $this->_access_token();
    }

    protected function _whatsapp_phone_id()
    {
        return trim((string) $this->CI->config->item('crm_whatsapp_phone_number_id'));
    }

    protected function _waba_id()
    {
        return trim((string) $this->CI->config->item('crm_whatsapp_waba_id'));
    }
}
