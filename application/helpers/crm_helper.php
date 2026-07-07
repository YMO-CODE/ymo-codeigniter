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
    /** @return array<string,string> slug => label */
    function crm_lead_stages()
    {
        return array(
            'hot_lead'            => 'Hot Lead',
            'warm_lead'           => 'Warm Lead',
            'followup_next_week'  => 'Follow-up Next Week',
            'followup_next_month' => 'Follow-up Next Month',
            'later'               => 'Later',
            'quote_sent'          => 'Quote Sent',
            'lost'                => 'Lost',
        );
    }
}

if (!function_exists('crm_lead_manual_stages')) {
    /** Stages staff can set manually (override auto date-driven stages). */
    function crm_lead_manual_stages()
    {
        return array('warm_lead', 'quote_sent', 'lost');
    }
}

if (!function_exists('crm_lead_stage_label')) {
    function crm_lead_stage_label($slug)
    {
        $stages = crm_lead_stages();
        return isset($stages[$slug]) ? $stages[$slug] : ucwords(str_replace('_', ' ', (string) $slug));
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
                        if (($msg['type'] ?? 'text') !== 'text') {
                            continue;
                        }
                        $from = (string) ($msg['from'] ?? '');
                        $text = (string) ($msg['text']['body'] ?? '');
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
