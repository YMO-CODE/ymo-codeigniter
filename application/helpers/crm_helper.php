<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('crm_refresh_permissions')) {
    /**
     * Load CRM permission keys into session admin payload (once per session flag).
     */
    function crm_refresh_permissions()
    {
        $ci = &get_instance();
        if (!$ci->session) {
            return;
        }
        $admin = $ci->session->userdata('admin');
        if (empty($admin['id'])) {
            return;
        }
        if (!empty($admin['crm_perms_loaded'])) {
            return;
        }
        if (!isset($ci->crm_rbac_model)) {
            $ci->load->model('crm_rbac_model');
        }
        $keys = $ci->crm_rbac_model->permission_keys_for_admin($admin);
        $admin['crm_permissions'] = $keys;
        $admin['crm_perms_loaded'] = 1;
        $slug = $ci->crm_rbac_model->crm_role_slug_for_admin($admin);
        if ($slug) {
            $admin['crm_role_slug'] = $slug;
        }
        $ci->session->set_userdata('admin', $admin);
    }
}

if (!function_exists('crm_can')) {
    /**
     * @param string $perm_key e.g. leads.view
     */
    function crm_can($perm_key)
    {
        $ci = &get_instance();
        if (!$ci->session) {
            return FALSE;
        }
        $admin = $ci->session->userdata('admin');
        if (empty($admin['id'])) {
            return FALSE;
        }
        if (empty($admin['crm_perms_loaded'])) {
            crm_refresh_permissions();
            $admin = $ci->session->userdata('admin');
        }
        $perms = isset($admin['crm_permissions']) ? (array) $admin['crm_permissions'] : array();
        if (in_array('*', $perms, TRUE)) {
            return TRUE;
        }
        return in_array($perm_key, $perms, TRUE);
    }
}

if (!function_exists('crm_slug')) {
    /** @param string $str */
    function crm_slug($str)
    {
        $s = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $str), '-'));
        return $s !== '' ? $s : 'tag';
    }
}

if (!function_exists('crm_verify_webhook_hmac')) {
    /**
     * Verify X-CRM-Signature header (sha256 HMAC of raw body).
     */
    function crm_verify_webhook_hmac($raw_body, $signature_header)
    {
        $ci = &get_instance();
        $secret = $ci->config->item('crm_webhook_hmac_secret');
        if ($secret === '' || $secret === NULL) {
            return ENVIRONMENT !== 'production';
        }
        if (!$signature_header) {
            return FALSE;
        }
        $expected = hash_hmac('sha256', $raw_body, $secret);
        $given = preg_replace('/^sha256=/', '', trim($signature_header));
        return hash_equals($expected, $given);
    }
}

if (!function_exists('crm_csv_download')) {
    /**
     * Stream a CSV download and exit.
     *
     * @param string   $filename
     * @param string[] $headers
     * @param array[]  $rows
     */
    function crm_csv_download($filename, array $headers, array $rows)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}

if (!function_exists('crm_parse_contacts_csv')) {
    /**
     * Parse a contacts import CSV file.
     *
     * @param string $path Absolute or relative path readable by PHP
     * @return array[] Rows with keys: name, mobile, email, company, notes, tags
     */
    function crm_parse_contacts_csv($path)
    {
        $fh = fopen($path, 'r');
        if (!$fh) {
            return array();
        }

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            return array();
        }

        $map = array();
        foreach ($header as $i => $col) {
            $key = strtolower(trim(preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', $col))));
            if ($key !== '') {
                $map[$key] = $i;
            }
        }
        if (!isset($map['name'])) {
            fclose($fh);
            return array();
        }
        if (isset($map['workshop'])) {
            $map['company'] = $map['workshop'];
        }

        $rows = array();
        while (($line = fgetcsv($fh)) !== FALSE) {
            $name = trim((string) ($line[$map['name']] ?? ''));
            if ($name === '') {
                continue;
            }
            $rows[] = array(
                'name'    => $name,
                'mobile'  => isset($map['mobile']) ? (string) ($line[$map['mobile']] ?? '') : '',
                'email'   => isset($map['email']) ? (string) ($line[$map['email']] ?? '') : '',
                'company' => isset($map['company']) ? (string) ($line[$map['company']] ?? '') : '',
                'notes'   => isset($map['notes']) ? (string) ($line[$map['notes']] ?? '') : '',
                'tags'    => isset($map['tags']) ? (string) ($line[$map['tags']] ?? '') : '',
            );
        }
        fclose($fh);
        return $rows;
    }
}

if (!function_exists('crm_lead_stages')) {
    /**
     * The lead pipeline (CRM v6). Follow-up timing is NOT a stage — it lives
     * in the lead's follow-up task.
     *
     * @return array<string,string> slug => label
     */
    function crm_lead_stages()
    {
        return array(
            'new'        => 'New',
            'contacted'  => 'Contacted',
            'quote_sent' => 'Quote sent',
            'booked'     => 'Booked',
            'lost'       => 'Lost',
        );
    }
}

if (!function_exists('crm_lead_open_stages')) {
    /** Stages that still need work (shown in follow-ups and the board). */
    function crm_lead_open_stages()
    {
        return array('new', 'contacted', 'quote_sent');
    }
}

if (!function_exists('crm_lead_manual_stages')) {
    /** Stages staff can pick by hand (all of them in v6). */
    function crm_lead_manual_stages()
    {
        return array_keys(crm_lead_stages());
    }
}

if (!function_exists('crm_lead_stage_status')) {
    /** Legacy status column value that mirrors a stage. */
    function crm_lead_stage_status($stage)
    {
        if ($stage === 'booked') {
            return 'converted';
        }
        if ($stage === 'lost') {
            return 'junk';
        }
        return 'open';
    }
}

if (!function_exists('crm_lead_lost_reasons')) {
    /** @return array<string,string> */
    function crm_lead_lost_reasons()
    {
        return array(
            'not_interested' => 'Not interested',
            'price'          => 'Price too high',
            'no_response'    => 'No response',
            'went_elsewhere' => 'Went elsewhere',
            'out_of_area'    => 'Outside service area',
            'spam'           => 'Spam / wrong number',
            'other'          => 'Other',
        );
    }
}

if (!function_exists('crm_lead_call_outcomes')) {
    /**
     * "Log a call" outcomes → resulting stage (NULL = keep current stage,
     * except a New lead becomes Contacted).
     *
     * @return array<string,array{label:string,stage:?string}>
     */
    function crm_lead_call_outcomes()
    {
        return array(
            'no_answer'  => array('label' => 'No answer / busy',   'stage' => NULL,         'next' => 'tomorrow', 'icon' => 'phone_missed'),
            'interested' => array('label' => 'Interested',         'stage' => 'contacted',  'next' => '2d',       'icon' => 'thumb_up'),
            'quote_sent' => array('label' => 'Quote sent',         'stage' => 'quote_sent', 'next' => '2d',       'icon' => 'request_quote'),
            'booked'     => array('label' => 'Booked',             'stage' => 'booked',     'next' => 'none',     'icon' => 'event_available'),
            'lost'       => array('label' => 'Not going ahead',    'stage' => 'lost',       'next' => 'none',     'icon' => 'block'),
            'note'       => array('label' => 'Just a note',        'stage' => NULL,         'next' => '',         'icon' => 'sticky_note_2'),
        );
    }
}

if (!function_exists('crm_lead_stage_label')) {
    function crm_lead_stage_label($slug)
    {
        $stages = crm_lead_stages();
        $legacy = array(
            'hot_lead' => 'Contacted', 'warm_lead' => 'New', 'followup_next_week' => 'Contacted',
            'followup_next_month' => 'Contacted', 'later' => 'Contacted',
        );
        if (isset($stages[$slug])) {
            return $stages[$slug];
        }
        return isset($legacy[$slug]) ? $legacy[$slug] : ucwords(str_replace('_', ' ', (string) $slug));
    }
}

if (!function_exists('crm_lead_stage_badge')) {
    /** Bootstrap badge classes per stage. */
    function crm_lead_stage_badge($slug)
    {
        $map = array(
            'new'        => 'bg-primary',
            'contacted'  => 'bg-info text-dark',
            'quote_sent' => 'bg-warning text-dark',
            'booked'     => 'bg-success',
            'lost'       => 'bg-secondary',
        );
        return isset($map[$slug]) ? $map[$slug] : 'bg-light text-dark';
    }
}

if (!function_exists('crm_mobile10')) {
    /** Last 10 digits of an Indian mobile (matching key across tables). */
    function crm_mobile10($number)
    {
        $d = preg_replace('/\D+/', '', (string) $number);
        return strlen($d) >= 10 ? substr($d, -10) : $d;
    }
}

if (!function_exists('crm_tel_link')) {
    function crm_tel_link($number)
    {
        $d = crm_mobile10($number);
        return strlen($d) === 10 ? 'tel:+91'.$d : '';
    }
}

if (!function_exists('crm_wa_link')) {
    /** Click-to-chat link (opens WhatsApp on the staff member's phone/desktop). */
    function crm_wa_link($number)
    {
        $d = crm_mobile10($number);
        return strlen($d) === 10 ? 'https://wa.me/91'.$d : '';
    }
}

if (!function_exists('crm_is_meta_whatsapp_cloud_payload')) {
    /**
     * True when JSON is Meta WhatsApp Cloud API webhook (not flat third-party JSON).
     */
    function crm_is_meta_whatsapp_cloud_payload(array $data)
    {
        if (empty($data['entry']) || !is_array($data['entry'])) {
            return FALSE;
        }
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] ?? array() as $change) {
                if (($change['field'] ?? '') === 'messages') {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
}

if (!function_exists('crm_normalize_whatsapp_payloads')) {
    /**
     * Normalize flat JSON or Meta Cloud API WhatsApp webhook into CRM message rows.
     *
     * @return array<int,array{from:string,name:string,text:string,message_id:string,raw:array}>
     */
    function crm_normalize_whatsapp_payloads(array $data)
    {
        $out = array();

        if (!empty($data['entry']) && is_array($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                foreach ($entry['changes'] ?? array() as $change) {
                    if (($change['field'] ?? '') !== 'messages') {
                        continue;
                    }
                    $val = $change['value'] ?? array();
                    $contacts = array();
                    foreach ($val['contacts'] ?? array() as $c) {
                        $wa = (string) ($c['wa_id'] ?? '');
                        if ($wa !== '') {
                            $contacts[$wa] = (string) ($c['profile']['name'] ?? '');
                        }
                    }
                    foreach ($val['messages'] ?? array() as $msg) {
                        $from = (string) ($msg['from'] ?? '');
                        $text = crm_whatsapp_message_text($msg);
                        if ($from === '' || $text === '') {
                            continue;
                        }
                        $out[] = array(
                            'from'       => $from,
                            'name'       => $contacts[$from] ?? ('WhatsApp '.$from),
                            'text'       => $text,
                            'message_id' => (string) ($msg['id'] ?? ''),
                            'raw'        => $msg,
                        );
                    }
                }
            }
            if ($out) {
                return $out;
            }
        }

        $from = (string) ($data['from'] ?? ($data['mobile'] ?? ($data['wa_id'] ?? '')));
        $text = (string) ($data['text'] ?? ($data['message'] ?? ($data['body'] ?? '')));
        if ($from !== '' && $text !== '') {
            $out[] = array(
                'from'       => $from,
                'name'       => (string) ($data['name'] ?? ('WhatsApp '.$from)),
                'text'       => $text,
                'message_id' => (string) ($data['message_id'] ?? ''),
                'raw'        => $data,
            );
        }

        return $out;
    }
}

if (!function_exists('crm_whatsapp_message_text')) {
    /**
     * Readable text for an inbound WhatsApp Cloud API message: text body,
     * quick-reply button / interactive reply title, or a media caption /
     * placeholder (so photos customers send still reach the lead timeline).
     */
    function crm_whatsapp_message_text(array $msg)
    {
        $type = (string) ($msg['type'] ?? 'text');
        switch ($type) {
            case 'text':
                return trim((string) ($msg['text']['body'] ?? ''));
            case 'button':
                return trim((string) ($msg['button']['text'] ?? ($msg['button']['payload'] ?? '')));
            case 'interactive':
                $i = $msg['interactive'] ?? array();
                $r = $i['button_reply'] ?? ($i['list_reply'] ?? array());
                return trim((string) ($r['title'] ?? ($r['id'] ?? '')));
            case 'image':
            case 'video':
            case 'document':
                $cap = trim((string) ($msg[$type]['caption'] ?? ''));
                $label = '['.ucfirst($type).' received'.($type === 'document' && !empty($msg['document']['filename'])
                    ? ': '.$msg['document']['filename'] : '').']';
                return $cap !== '' ? $label.' '.$cap : $label;
            case 'audio':
                return '[Voice note received]';
            case 'location':
                $loc = $msg['location'] ?? array();
                $parts = array_filter(array(
                    $loc['name'] ?? '', $loc['address'] ?? '',
                    (isset($loc['latitude'], $loc['longitude']) ? $loc['latitude'].','.$loc['longitude'] : ''),
                ));
                return '[Location] '.implode(' - ', $parts);
            default:
                return '';
        }
    }
}

if (!function_exists('crm_whatsapp_e164')) {
    /**
     * Normalise an Indian mobile to WhatsApp's digits-only E.164 form:
     * 9876543210 / 09876543210 / +91 98765 43210 -> 919876543210.
     */
    function crm_whatsapp_e164($number)
    {
        $d = preg_replace('/\D+/', '', (string) $number);
        if ($d === '') {
            return '';
        }
        if (strlen($d) === 11 && $d[0] === '0') {
            $d = substr($d, 1);
        }
        if (strlen($d) === 10) {
            $d = '91'.$d;
        }
        return $d;
    }
}

if (!function_exists('crm_whatsapp_parse_statuses')) {
    /**
     * Extract delivery receipts from a Meta WhatsApp Cloud API webhook.
     *
     * @return array<int,array{id:string,status:string,recipient:string,timestamp:int,error:string}>
     */
    function crm_whatsapp_parse_statuses(array $data)
    {
        $out = array();
        foreach ($data['entry'] ?? array() as $entry) {
            foreach ($entry['changes'] ?? array() as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }
                foreach ($change['value']['statuses'] ?? array() as $st) {
                    $id = (string) ($st['id'] ?? '');
                    if ($id === '') {
                        continue;
                    }
                    $err = '';
                    if (!empty($st['errors'][0])) {
                        $e = $st['errors'][0];
                        $err = trim(($e['code'] ?? '').' '.($e['title'] ?? '')
                            .(!empty($e['error_data']['details']) ? ': '.$e['error_data']['details'] : ''));
                    }
                    $out[] = array(
                        'id'        => $id,
                        'status'    => strtolower((string) ($st['status'] ?? '')),
                        'recipient' => (string) ($st['recipient_id'] ?? ''),
                        'timestamp' => (int) ($st['timestamp'] ?? time()),
                        'error'     => $err,
                    );
                }
            }
        }
        return $out;
    }
}

if (!function_exists('crm_whatsapp_is_opt_out')) {
    /** True when an inbound WhatsApp text is an unsubscribe keyword (STOP etc.). */
    function crm_whatsapp_is_opt_out($text)
    {
        $t = strtolower(trim(preg_replace('/[^a-zA-Z ]+/', ' ', (string) $text)));
        $t = preg_replace('/\s+/', ' ', $t);
        $ci = &get_instance();
        $words = (array) $ci->config->item('crm_whatsapp_opt_out_words');
        if (!$words) {
            $words = array('stop', 'unsubscribe');
        }
        return $t !== '' && in_array($t, $words, TRUE);
    }
}

if (!function_exists('crm_parse_meta_messaging_events')) {
    /**
     * Extract inbound Instagram/Messenger DM events from Meta webhook JSON.
     *
     * @return array<int,array{sender_id:string,name:string,text:string,message_id:string,channel:string,raw:array}>
     */
    function crm_parse_meta_messaging_events(array $data)
    {
        $out = array();

        foreach ($data['entry'] ?? array() as $entry) {
            foreach ($entry['messaging'] ?? array() as $event) {
                $parsed = _crm_parse_meta_messaging_event($event, 'messaging');
                if ($parsed) {
                    $out[] = $parsed;
                }
            }
            foreach ($entry['changes'] ?? array() as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }
                $val = $change['value'] ?? array();
                $channel = (($val['messaging_product'] ?? '') === 'instagram') ? 'instagram' : 'messenger';
                $contacts = array();
                foreach ($val['contacts'] ?? array() as $c) {
                    $id = (string) ($c['id'] ?? ($c['wa_id'] ?? ''));
                    if ($id !== '') {
                        $contacts[$id] = (string) ($c['profile']['name'] ?? '');
                    }
                }
                foreach ($val['messages'] ?? array() as $msg) {
                    if (($msg['type'] ?? 'text') !== 'text') {
                        continue;
                    }
                    $sender = (string) ($msg['from'] ?? '');
                    $text = (string) ($msg['text']['body'] ?? '');
                    if ($sender === '' || $text === '') {
                        continue;
                    }
                    $out[] = array(
                        'sender_id'  => $sender,
                        'name'       => $contacts[$sender] ?? ('Instagram '.$sender),
                        'text'       => $text,
                        'message_id' => (string) ($msg['id'] ?? ''),
                        'channel'    => $channel,
                        'raw'        => $msg,
                    );
                }
            }
        }

        $object = strtolower((string) ($data['object'] ?? ''));
        if ($object === 'instagram' && empty($out)) {
            foreach ($data['entry'] ?? array() as $entry) {
                foreach ($entry['messaging'] ?? array() as $event) {
                    $parsed = _crm_parse_meta_messaging_event($event, 'instagram');
                    if ($parsed) {
                        $out[] = $parsed;
                    }
                }
            }
        }

        return $out;
    }
}

if (!function_exists('_crm_parse_meta_messaging_event')) {
    /** @return array|null */
    function _crm_parse_meta_messaging_event(array $event, $default_channel)
    {
        if (!empty($event['message']['is_echo'])) {
            return NULL;
        }
        $message = $event['message'] ?? array();
        $text = (string) ($message['text'] ?? '');
        if ($text === '' && empty($message['attachments'])) {
            return NULL;
        }
        if ($text === '' && !empty($message['attachments'])) {
            $text = '[Attachment]';
        }
        $sender = (string) ($event['sender']['id'] ?? '');
        if ($sender === '') {
            return NULL;
        }
        return array(
            'sender_id'  => $sender,
            'name'       => 'Instagram '.$sender,
            'text'       => $text,
            'message_id' => (string) ($message['mid'] ?? ($message['id'] ?? '')),
            'channel'    => $default_channel === 'instagram' ? 'instagram' : 'messenger',
            'raw'        => $event,
        );
    }
}

if (!function_exists('crm_lead_chat_channel')) {
    /** @return string|null whatsapp|instagram */
    function crm_lead_chat_channel($lead)
    {
        if (!$lead || !is_array($lead)) {
            return NULL;
        }
        $provider = (string) ($lead['external_provider'] ?? '');
        $slug     = (string) ($lead['source_slug'] ?? '');

        if ($provider === 'whatsapp' || ($slug === 'whatsapp' && !empty($lead['mobile']))) {
            return 'whatsapp';
        }
        if ($provider === 'instagram_dm' && !empty($lead['external_lead_id'])) {
            return 'instagram';
        }
        return NULL;
    }
}

if (!function_exists('crm_lead_chat_recipient')) {
    function crm_lead_chat_recipient($lead, $channel)
    {
        if ($channel === 'whatsapp') {
            return preg_replace('/\D/', '', (string) ($lead['mobile'] ?? ''));
        }
        if ($channel === 'instagram') {
            return (string) ($lead['external_lead_id'] ?? '');
        }
        return '';
    }
}

if (!function_exists('crm_chat_strip_legacy_prefix')) {
    function crm_chat_strip_legacy_prefix($body)
    {
        $body = trim((string) $body);
        $prefixes = array(
            'Inbound WhatsApp: ',
            'Instagram DM: ',
            'Messenger: ',
        );
        foreach ($prefixes as $prefix) {
            if (stripos($body, $prefix) === 0) {
                return trim(substr($body, strlen($prefix)));
            }
        }
        return $body;
    }
}

if (!function_exists('crm_chat_activity_meta')) {
    /** @return array */
    function crm_chat_activity_meta($activity)
    {
        if (empty($activity['meta_json'])) {
            return array();
        }
        $meta = json_decode($activity['meta_json'], TRUE);
        return is_array($meta) ? $meta : array();
    }
}

if (!function_exists('crm_chat_activity_text')) {
    function crm_chat_activity_text($activity)
    {
        $meta = crm_chat_activity_meta($activity);
        if (!empty($meta['text'])) {
            return (string) $meta['text'];
        }
        return crm_chat_strip_legacy_prefix($activity['body'] ?? '');
    }
}

if (!function_exists('crm_lead_chat_messages')) {
    /**
     * Chat-thread rows from lead activities (inbound/outbound WhatsApp + Instagram DMs).
     *
     * @param array[] $activities
     * @return array[]
     */
    function crm_lead_chat_messages(array $activities)
    {
        $out = array();
        foreach ($activities as $a) {
            $type = (string) ($a['type'] ?? '');
            $meta = crm_chat_activity_meta($a);
            $is_chat = ($type === 'whatsapp')
                || ($type === 'webhook' && !empty($meta['direction']))
                || ($type === 'webhook' && (
                    stripos((string) ($a['body'] ?? ''), 'Instagram DM:') === 0
                    || stripos((string) ($a['body'] ?? ''), 'Messenger:') === 0
                ))
                || stripos((string) ($a['body'] ?? ''), 'Inbound WhatsApp:') === 0;

            if (!$is_chat) {
                continue;
            }

            $direction = $meta['direction'] ?? NULL;
            if (!$direction) {
                $direction = !empty($a['admin_id']) ? 'outbound' : 'inbound';
            }

            $channel = $meta['channel'] ?? NULL;
            if (!$channel && $type === 'whatsapp') {
                $channel = 'whatsapp';
            }
            if (!$channel && stripos((string) ($a['body'] ?? ''), 'Instagram DM:') === 0) {
                $channel = 'instagram';
            }

            $out[] = array(
                'id'         => (int) ($a['id'] ?? 0),
                'direction'  => $direction,
                'channel'    => $channel,
                'text'       => crm_chat_activity_text($a),
                'admin_name' => $a['admin_name'] ?? NULL,
                'created_at' => $a['created_at'] ?? '',
            );
        }

        usort($out, function ($x, $y) {
            return strcmp($x['created_at'], $y['created_at']);
        });

        return $out;
    }
}

if (!function_exists('crm_lead_field')) {
    /**
     * Read a lead column, falling back to payload_json from web forms (quick-book, etc.).
     *
     * @param array  $lead
     * @param string $key  address|car_type
     */
    function crm_lead_field(array $lead, $key)
    {
        if (!empty($lead[$key])) {
            return (string) $lead[$key];
        }
        if (empty($lead['payload_json'])) {
            return '';
        }
        $raw = json_decode((string) $lead['payload_json'], TRUE);
        if (!is_array($raw)) {
            return '';
        }
        if ($key === 'address') {
            $parts = array_filter(array(
                trim((string) ($raw['area'] ?? '')),
                trim((string) ($raw['city'] ?? '')),
            ));
            return implode(', ', $parts);
        }
        if ($key === 'car_type') {
            $make = trim((string) ($raw['make_name'] ?? ''));
            $variant = trim((string) ($raw['variant'] ?? ''));
            return trim($make.' '.$variant);
        }
        return '';
    }
}

if (!function_exists('crm_notify_admin_new_lead')) {
    /**
     * Email admin when a public form creates a CRM lead. Failures are logged only.
     *
     * @param int    $lead_id
     * @param string $source_label e.g. "Quick book", "Contact form"
     * @param array  $lead         Keys: name, mobile, email (optional), message (optional)
     */
    function crm_notify_admin_new_lead($lead_id, $source_label, array $lead)
    {
        $lead_id = (int) $lead_id;
        if ($lead_id <= 0) {
            return;
        }

        $ci = &get_instance();
        $admin_email = $ci->config->item('ymo_admin_notify');
        if (!$admin_email) {
            return;
        }

        $ci->load->library('mailer');
        $name = trim((string) ($lead['name'] ?? 'Unknown'));
        $subject = 'New lead - '.$source_label.' - '.$name;
        $ok = $ci->mailer->send_view($admin_email, $subject, 'emails/lead_admin_new', array(
            'lead_id'      => $lead_id,
            'source_label' => $source_label,
            'lead'         => $lead,
        ));
        if (!$ok) {
            log_message(
                'error',
                '[crm] Lead admin notify failed for #'.$lead_id.': '.$ci->mailer->last_error()
            );
        }
    }
}

if (!function_exists('crm_pagination_items')) {
    /**
     * Build page numbers for admin pagination with ellipsis after the first block.
     *
     * @param int $current   Active page (1-based)
     * @param int $total     Total pages
     * @param int $first_block Number of initial pages to show before ellipsis
     * @return array<int|string> Page numbers or 'ellipsis'
     */
    function crm_pagination_items($current, $total, $first_block = 10)
    {
        $current = max(1, (int) $current);
        $total = max(1, (int) $total);
        $first_block = max(1, (int) $first_block);

        if ($total <= $first_block) {
            return range(1, $total);
        }

        if ($current <= $first_block) {
            $items = range(1, $first_block);
            $items[] = 'ellipsis';
            $items[] = $total;
            return $items;
        }

        if ($current > $total - $first_block) {
            $items = array(1, 'ellipsis');
            for ($i = max(2, $total - $first_block + 1); $i <= $total; $i++) {
                $items[] = $i;
            }
            return $items;
        }

        $items = array(1, 'ellipsis');
        $start = max($first_block + 1, $current - 2);
        $end = min($total - 1, $current + 2);
        for ($i = $start; $i <= $end; $i++) {
            $items[] = $i;
        }
        $items[] = 'ellipsis';
        $items[] = $total;
        return $items;
    }
}

if (!function_exists('crm_time_ago')) {
    /** "5 min ago", "3 h ago", "2 days ago", or a date for anything older than a week. */
    function crm_time_ago($datetime)
    {
        $ts = is_numeric($datetime) ? (int) $datetime : strtotime((string) $datetime);
        if (!$ts) {
            return '';
        }
        $d = max(0, time() - $ts);
        if ($d < 60)     { return 'just now'; }
        if ($d < 3600)   { return floor($d / 60).' min ago'; }
        if ($d < 86400)  { return floor($d / 3600).' h ago'; }
        if ($d < 604800) { $n = (int) floor($d / 86400); return $n.' day'.($n === 1 ? '' : 's').' ago'; }
        return date('d M', $ts);
    }
}

if (!function_exists('crm_list_pager')) {
    /**
     * HTML for a pager under an in-page list (views/admin/partials/pager.php).
     * Rendered in its own scope so its variables don't leak into later views.
     * @param array $o page, pages, param, anchor, path, [total, per, noun]
     */
    function crm_list_pager(array $o)
    {
        $o += array('page' => 1, 'pages' => 1, 'param' => 'p', 'anchor' => '', 'path' => '', 'total' => NULL, 'per' => 0, 'noun' => '');
        $render = function ($page, $pages, $param, $anchor, $path, $total, $per, $noun) {
            ob_start();
            include APPPATH.'views/admin/partials/pager.php';
            return ob_get_clean();
        };
        return $render($o['page'], $o['pages'], $o['param'], $o['anchor'], $o['path'], $o['total'], $o['per'], $o['noun']);
    }
}

if (!function_exists('crm_whatsapp_referral')) {
    /**
     * Where an inbound WhatsApp Cloud API message came from, when Meta says so:
     * Click-to-WhatsApp ads and boosted posts add a "referral" block to the
     * first message (ad id, headline, link, click id).
     * @return array|null {kind: meta_ad|meta_post, platform: instagram|facebook|meta,
     *                     ad_id, headline, body, source_url, ctwa_clid, media_type}
     */
    function crm_whatsapp_referral(array $raw)
    {
        $ref = isset($raw['referral']) && is_array($raw['referral']) ? $raw['referral'] : NULL;
        if (!$ref) {
            return NULL;
        }
        $url = (string) ($ref['source_url'] ?? '');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $platform = 'meta';
        if (strpos($host, 'instagram') !== FALSE || strpos($host, 'instagr.am') !== FALSE) {
            $platform = 'instagram';
        } elseif (strpos($host, 'facebook') !== FALSE || strpos($host, 'fb.me') !== FALSE || strpos($host, 'fb.com') !== FALSE) {
            $platform = 'facebook';
        }
        $type = strtolower((string) ($ref['source_type'] ?? 'ad'));
        $clip = function ($s, $n) { $s = trim((string) $s); return function_exists('mb_substr') ? mb_substr($s, 0, $n) : substr($s, 0, $n); };
        return array(
            'kind'       => $type === 'post' ? 'meta_post' : 'meta_ad',
            'platform'   => $platform,
            'ad_id'      => $clip($ref['source_id'] ?? '', 64),
            'headline'   => $clip($ref['headline'] ?? '', 190),
            'body'       => $clip($ref['body'] ?? '', 500),
            'source_url' => $clip($url, 500),
            'ctwa_clid'  => $clip($ref['ctwa_clid'] ?? '', 190),
            'media_type' => $clip($ref['media_type'] ?? '', 20),
        );
    }
}

if (!function_exists('crm_whatsapp_website_origin')) {
    /**
     * Messages pre-filled by the website's WhatsApp buttons (see
     * whatsapp_cta.php, mobile_cta_bar.php, callback_form.php): those chats
     * started on the website. Returns 'website' or NULL.
     */
    function crm_whatsapp_website_origin($text)
    {
        $t = strtolower(trim(preg_replace('/\s+/', ' ', (string) $text)));
        foreach (array('hi ymo, i would like to book a car service', 'hi ymo, i just asked for a call back') as $p) {
            if (strpos($t, $p) === 0) {
                return 'website';
            }
        }
        return NULL;
    }
}

if (!function_exists('crm_wa_source_label')) {
    /** Short label for a WhatsApp conversation source row (crm_wa_sources). */
    function crm_wa_source_label($src)
    {
        if (!$src || empty($src['kind'])) {
            return '';
        }
        $plat = array('instagram' => 'Instagram', 'facebook' => 'Facebook', 'meta' => 'Meta');
        $p = isset($plat[$src['platform'] ?? '']) ? $plat[$src['platform']] : 'Meta';
        switch ($src['kind']) {
            case 'meta_ad':   return $p.' ad';
            case 'meta_post': return $p.' post';
            case 'website':   return 'Website';
            case 'direct':    return 'Direct';
        }
        return '';
    }
}
