<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * YMOChat: live website visitors and website chat.
 *
 *  - ymochat_visitors   one row per browser (vid kept in the visitor's browser),
 *                       with IP, location, device, ad/referral source and the
 *                       page they are on now. "Online" = seen in the last 60 s.
 *  - ymochat_pageviews  every page a visitor opened.
 *  - ymochat_messages   chat: sender is visitor, agent or system.
 *
 * Tables are created on first use (see ensure_schema), so the feature works
 * without running database/crm_migration_v9_ymochat.sql by hand.
 */
class Ymochat_model extends CI_Model
{
    const ONLINE_SECONDS   = 60;   // seen within this many seconds = online now
    const NEW_VISIT_GAP    = 1800; // 30 min without activity starts a new visit
    const MAX_MSG_LEN      = 1000;
    const MAX_MSGS_WINDOW  = 30;   // per visitor per 10 minutes

    protected static $schema_ok = NULL;

    /** Same as database/crm_migration_v9_ymochat.sql (the database folder is not in the app container). */
    const SCHEMA_SQL = <<<'SQL'
CREATE TABLE IF NOT EXISTS `ymochat_visitors` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vid`           CHAR(32)      NOT NULL,
    `ip`            VARCHAR(45)   NOT NULL DEFAULT '',
    `city`          VARCHAR(80)   NOT NULL DEFAULT '',
    `region`        VARCHAR(80)   NOT NULL DEFAULT '',
    `country`       VARCHAR(4)    NOT NULL DEFAULT '',
    `lat`           DECIMAL(9,6)  NULL,
    `lon`           DECIMAL(9,6)  NULL,
    `device`        VARCHAR(16)   NOT NULL DEFAULT '',
    `browser`       VARCHAR(40)   NOT NULL DEFAULT '',
    `user_agent`    VARCHAR(255)  NOT NULL DEFAULT '',
    `source`        VARCHAR(24)   NOT NULL DEFAULT '',
    `source_detail` VARCHAR(160)  NOT NULL DEFAULT '',
    `referrer`      VARCHAR(255)  NOT NULL DEFAULT '',
    `landing`       VARCHAR(255)  NOT NULL DEFAULT '',
    `attribution`   TEXT          NULL,
    `current_url`   VARCHAR(255)  NOT NULL DEFAULT '',
    `current_title` VARCHAR(200)  NOT NULL DEFAULT '',
    `visits`        INT UNSIGNED  NOT NULL DEFAULT 1,
    `pages`         INT UNSIGNED  NOT NULL DEFAULT 0,
    `name`          VARCHAR(120)  NOT NULL DEFAULT '',
    `mobile`        VARCHAR(15)   NOT NULL DEFAULT '',
    `lead_id`       BIGINT UNSIGNED NULL,
    `unread_agent`  INT UNSIGNED  NOT NULL DEFAULT 0,
    `last_msg_at`   DATETIME      NULL,
    `visit_started_at` DATETIME   NOT NULL,
    `first_seen`    DATETIME      NOT NULL,
    `last_seen`     DATETIME      NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_vid` (`vid`),
    KEY `idx_last_seen` (`last_seen`),
    KEY `idx_last_msg` (`last_msg_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ymochat_pageviews` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visitor_id`  BIGINT UNSIGNED NOT NULL,
    `url`         VARCHAR(255) NOT NULL DEFAULT '',
    `title`       VARCHAR(200) NOT NULL DEFAULT '',
    `created_at`  DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_visitor` (`visitor_id`, `id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ymochat_messages` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visitor_id`  BIGINT UNSIGNED NOT NULL,
    `sender`      VARCHAR(8)   NOT NULL,
    `admin_id`    INT UNSIGNED NULL,
    `body`        TEXT         NOT NULL,
    `created_at`  DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_visitor` (`visitor_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;


    // --- schema ------------------------------------------------------------

    public function ensure_schema()
    {
        if (self::$schema_ok !== NULL) {
            return self::$schema_ok;
        }
        if ($this->db->table_exists('ymochat_visitors') && $this->db->table_exists('ymochat_messages')
            && $this->db->table_exists('ymochat_pageviews')) {
            return self::$schema_ok = TRUE;
        }
        $sql = self::SCHEMA_SQL;
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if (!$this->db->query($stmt)) {
                log_message('error', 'YMOChat: could not create tables.');
                return self::$schema_ok = FALSE;
            }
        }
        $this->db->data_cache = array();
        return self::$schema_ok = TRUE;
    }

    // --- visitor side --------------------------------------------------------

    /**
     * Record a page view or heartbeat from the website tracker.
     *
     * @param array  $in  vid, url, title, pv (1 = page load), ref, attr (array of utm/gclid…)
     * @return array|null visitor row (+ 'is_new_visit'), NULL when ignored
     */
    public function beat(array $in, $ip, $ua)
    {
        $vid = isset($in['vid']) ? strtolower((string) $in['vid']) : '';
        if (!preg_match('/^[a-f0-9]{32}$/', $vid) || self::is_bot($ua) || !$this->ensure_schema()) {
            return NULL;
        }
        $now   = date('Y-m-d H:i:s');
        $url   = self::clip(self::clean_url(isset($in['url']) ? $in['url'] : ''), 255);
        $title = self::clip(isset($in['title']) ? strip_tags((string) $in['title']) : '', 200);
        $pv    = !empty($in['pv']);
        $attr  = isset($in['attr']) && is_array($in['attr']) ? self::clean_attr($in['attr']) : array();
        $ref   = self::clip(isset($in['ref']) ? (string) $in['ref'] : '', 255);
        $ip    = self::clip((string) $ip, 45);

        $row = $this->db->get_where('ymochat_visitors', array('vid' => $vid), 1)->row_array();

        if (!$row) {
            $src = self::detect_source($attr, $ref, $url);
            $geo = $this->_geo($ip);
            $dev = self::device($ua);
            $data = array(
                'vid' => $vid, 'ip' => $ip,
                'city' => $geo['city'], 'region' => $geo['region'], 'country' => $geo['country'],
                'lat' => $geo['lat'], 'lon' => $geo['lon'],
                'device' => $dev['device'], 'browser' => $dev['browser'], 'user_agent' => self::clip((string) $ua, 255),
                'source' => $src['source'], 'source_detail' => $src['detail'],
                'referrer' => $ref, 'landing' => $url,
                'attribution' => $attr ? json_encode($attr) : NULL,
                'current_url' => $url, 'current_title' => $title,
                'visits' => 1, 'pages' => $pv ? 1 : 0,
                'visit_started_at' => $now, 'first_seen' => $now, 'last_seen' => $now,
            );
            $this->db->insert('ymochat_visitors', $data);
            $data['id'] = (int) $this->db->insert_id();
            if ($pv) {
                $this->_pageview($data['id'], $url, $title, $now);
            }
            $data['is_new_visit'] = TRUE;
            $this->_maybe_purge();
            return $data;
        }

        $upd = array('last_seen' => $now, 'current_url' => $url, 'current_title' => $title);
        $new_visit = (time() - strtotime($row['last_seen'])) > self::NEW_VISIT_GAP;
        if ($new_visit) {
            $upd['visits'] = (int) $row['visits'] + 1;
            $upd['visit_started_at'] = $now;
            $upd['landing'] = $url;
            $upd['referrer'] = $ref;
            $src = self::detect_source($attr, $ref, $url);
            $upd['source'] = $src['source'];
            $upd['source_detail'] = $src['detail'];
            $upd['attribution'] = $attr ? json_encode($attr) : NULL;
        } elseif ($attr && self::is_ad_attr($attr) && $row['source'] !== 'google_ads') {
            // Same visit but they came back through an ad: the ad wins.
            $src = self::detect_source($attr, $ref, $url);
            $upd['source'] = $src['source'];
            $upd['source_detail'] = $src['detail'];
            $upd['attribution'] = json_encode($attr);
        }
        if ($ip !== '' && $ip !== $row['ip']) {
            $geo = $this->_geo($ip);
            $upd += array('ip' => $ip, 'city' => $geo['city'], 'region' => $geo['region'],
                'country' => $geo['country'], 'lat' => $geo['lat'], 'lon' => $geo['lon']);
        }
        if ($pv) {
            $upd['pages'] = (int) $row['pages'] + 1;
            $this->_pageview((int) $row['id'], $url, $title, $now);
        }
        $this->db->where('id', (int) $row['id'])->update('ymochat_visitors', $upd);
        $row = array_merge($row, $upd);
        $row['is_new_visit'] = $new_visit;
        return $row;
    }

    public function visitor_by_vid($vid)
    {
        $vid = strtolower((string) $vid);
        if (!preg_match('/^[a-f0-9]{32}$/', $vid) || !$this->ensure_schema()) {
            return NULL;
        }
        return $this->db->get_where('ymochat_visitors', array('vid' => $vid), 1)->row_array() ?: NULL;
    }

    public function visitor($id)
    {
        if (!$this->ensure_schema()) {
            return NULL;
        }
        return $this->db->get_where('ymochat_visitors', array('id' => (int) $id), 1)->row_array() ?: NULL;
    }

    /** Messages for the visitor's chat window, after a message id. */
    public function messages($visitor_id, $after_id = 0, $limit = 200, $with_events = TRUE)
    {
        $this->db->select('m.id, m.sender, m.body, m.created_at, a.name AS agent_name', FALSE)
            ->from('ymochat_messages m')
            ->join('admin_users a', 'a.id = m.admin_id', 'left')
            ->where('m.visitor_id', (int) $visitor_id)
            ->where('m.id >', (int) $after_id);
        if (!$with_events) {
            $this->db->where('m.sender !=', 'event');
        }
        $rows = $this->db->order_by('m.id', 'ASC')->limit($limit)->get()->result_array();
        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['agent_name'] = $r['agent_name'] ? self::first_name($r['agent_name']) : '';
        }
        unset($r);
        return $rows;
    }

    /** @return int|string message id, or an error string */
    public function visitor_message(array $visitor, $body)
    {
        $body = self::clip(trim(strip_tags((string) $body)), self::MAX_MSG_LEN);
        if ($body === '') {
            return 'empty';
        }
        $recent = $this->db->where('visitor_id', (int) $visitor['id'])->where('sender', 'visitor')
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 600))
            ->count_all_results('ymochat_messages');
        if ($recent >= self::MAX_MSGS_WINDOW) {
            return 'rate';
        }
        $id = $this->_message((int) $visitor['id'], 'visitor', $body, NULL);
        $this->db->set('unread_agent', 'unread_agent + 1', FALSE)
            ->set('last_msg_at', date('Y-m-d H:i:s'))
            ->where('id', (int) $visitor['id'])->update('ymochat_visitors');
        return $id;
    }

    public function agent_message($visitor_id, $body, $admin_id)
    {
        $body = self::clip(trim((string) $body), self::MAX_MSG_LEN);
        if ($body === '') {
            return 0;
        }
        $id = $this->_message((int) $visitor_id, 'agent', $body, (int) $admin_id);
        $this->db->set('unread_agent', 0)->set('last_msg_at', date('Y-m-d H:i:s'))
            ->where('id', (int) $visitor_id)->update('ymochat_visitors');
        return $id;
    }

    public function system_message($visitor_id, $body)
    {
        return $this->_message((int) $visitor_id, 'system', self::clip((string) $body, self::MAX_MSG_LEN), NULL);
    }

    /** Things the visitor did that the team should see (not shown to the visitor): tapped Call, WhatsApp… */
    public function event($visitor_id, $what)
    {
        $labels = array('call' => 'Tapped the Call button', 'whatsapp' => 'Tapped WhatsApp', 'booking' => 'Opened the booking page', 'callback_form' => 'Sent the call-back form');
        if (!isset($labels[$what])) {
            return 0;
        }
        return $this->_message((int) $visitor_id, 'event', $labels[$what], NULL);
    }

    public function mark_read($visitor_id)
    {
        $this->db->set('unread_agent', 0)->where('id', (int) $visitor_id)->update('ymochat_visitors');
    }

    /**
     * Save name + mobile the visitor gave in chat and turn them into a CRM lead
     * (Google Ads source when their visit came from an ad).
     *
     * @return int lead id (0 when the CRM is unavailable)
     */
    public function save_contact(array $visitor, $name, $mobile)
    {
        $mobile = ymo_indian_mobile($mobile);
        $name   = self::clip(trim(strip_tags((string) $name)), 120);
        if ($mobile === '') {
            return 0;
        }
        $upd = array('mobile' => $mobile);
        if ($name !== '') {
            $upd['name'] = $name;
        }
        $this->db->where('id', (int) $visitor['id'])->update('ymochat_visitors', $upd);
        $visitor = array_merge($visitor, $upd);

        if (!empty($visitor['lead_id']) || !$this->db->table_exists('crm_leads')) {
            return (int) $visitor['lead_id'];
        }
        $this->load->model(array('crm_lead_model', 'crm_lead_activity_model'));
        $attr = !empty($visitor['attribution']) ? (json_decode($visitor['attribution'], TRUE) ?: array()) : array();
        $is_ad = $visitor['source'] === 'google_ads';
        $source = ($is_ad && $this->crm_lead_model->source_id_by_slug('google_ads') > 0) ? 'google_ads' : 'website';
        $chat = $this->messages((int) $visitor['id'], 0, 20);
        $lines = array();
        foreach ($chat as $m) {
            if ($m['sender'] === 'visitor' || $m['sender'] === 'agent') {
                $lines[] = ($m['sender'] === 'agent' ? 'YMO' : 'Visitor').': '.$m['body'];
            }
        }
        $where = trim($visitor['city'].($visitor['region'] !== '' ? ', '.$visitor['region'] : ''), ', ');
        $message = 'YMOChat. '.($where !== '' ? 'Location: '.$where.'. ' : '')
            .'Source: '.self::source_label($visitor['source'], $visitor['source_detail']).'. '
            .'Page: '.$visitor['current_url'].($lines ? "\n".implode("\n", array_slice($lines, -8)) : '');
        $lead_id = (int) $this->crm_lead_model->ingest($source, array(
            'name'    => $visitor['name'] !== '' ? $visitor['name'] : 'Website visitor',
            'mobile'  => $mobile,
            'email'   => '',
            'message' => $message,
            'raw'     => array('ymochat_visitor' => (int) $visitor['id'], 'ip' => $visitor['ip'], 'utm' => $attr),
        ), NULL, 'ymochat');
        if (!$lead_id) {
            return 0;
        }
        $this->db->where('id', (int) $visitor['id'])->update('ymochat_visitors', array('lead_id' => $lead_id));
        $this->crm_lead_activity_model->add($lead_id, NULL, 'note', 'Shared their number in YMOChat ('.$where.', '
            .self::source_label($visitor['source'], $visitor['source_detail']).').', array('source' => 'ymochat'));
        if (function_exists('crm_notify_admin_new_lead')) {
            crm_notify_admin_new_lead($lead_id, $is_ad ? 'YMOChat (Google Ads)' : 'YMOChat', array(
                'name' => $visitor['name'], 'mobile' => $mobile, 'email' => '', 'message' => $message,
            ));
        }
        return $lead_id;
    }

    // --- CRM side ------------------------------------------------------------

    /**
     * Visitors for the YMOChat screen: everyone online now, plus anyone with a
     * chat or a visit in the last $hours hours.
     */
    public function board($hours = 24, $limit = 150)
    {
        if (!$this->ensure_schema()) {
            return array();
        }
        $since = date('Y-m-d H:i:s', time() - $hours * 3600);
        $rows = $this->db->select('v.*, (SELECT COUNT(*) FROM ymochat_messages m WHERE m.visitor_id = v.id) AS msg_count', FALSE)
            ->from('ymochat_visitors v')
            ->group_start()->where('v.last_seen >=', $since)->or_where('v.last_msg_at >=', $since)->group_end()
            ->order_by('v.last_seen', 'DESC')->limit($limit)->get()->result_array();
        $online_after = time() - self::ONLINE_SECONDS;
        foreach ($rows as &$r) {
            $r = $this->present($r, $online_after);
        }
        unset($r);
        return $rows;
    }

    /** Small summary polled by every CRM page: who is online, newest arrivals, unread. */
    public function pulse()
    {
        if (!$this->ensure_schema()) {
            return array('online' => 0, 'unread' => 0, 'latest' => array());
        }
        $after = date('Y-m-d H:i:s', time() - self::ONLINE_SECONDS);
        $online = $this->db->where('last_seen >=', $after)->count_all_results('ymochat_visitors');
        $unread = (int) $this->db->select_sum('unread_agent')->get('ymochat_visitors')->row()->unread_agent;
        $latest = $this->db->select('id, city, region, source, source_detail, current_title, current_url, device, visits, name, visit_started_at, unread_agent, last_msg_at')
            ->where('last_seen >=', $after)->order_by('visit_started_at', 'DESC')->limit(20)
            ->get('ymochat_visitors')->result_array();
        foreach ($latest as &$l) {
            $l['id'] = (int) $l['id'];
            $l['visit_key'] = $l['id'].'@'.$l['visit_started_at'];
            $l['source_label'] = self::source_label($l['source'], $l['source_detail']);
            $l['unread_agent'] = (int) $l['unread_agent'];
        }
        unset($l);
        return array('online' => (int) $online, 'unread' => $unread, 'latest' => $latest);
    }

    public function pageviews($visitor_id, $limit = 40)
    {
        return $this->db->select('url, title, created_at')->where('visitor_id', (int) $visitor_id)
            ->order_by('id', 'DESC')->limit($limit)->get('ymochat_pageviews')->result_array();
    }

    public function present(array $r, $online_after = NULL)
    {
        $online_after = $online_after === NULL ? time() - self::ONLINE_SECONDS : $online_after;
        $r['id'] = (int) $r['id'];
        $r['online'] = strtotime($r['last_seen']) >= $online_after;
        $r['unread_agent'] = (int) $r['unread_agent'];
        $r['visits'] = (int) $r['visits'];
        $r['pages'] = (int) $r['pages'];
        $r['source_label'] = self::source_label($r['source'], $r['source_detail']);
        $r['location'] = trim($r['city'].($r['region'] !== '' && $r['region'] !== $r['city'] ? ', '.$r['region'] : '')
            .($r['country'] !== '' && $r['country'] !== 'IN' ? ' ('.$r['country'].')' : ''), ', ');
        $r['on_site_secs'] = max(0, strtotime($r['last_seen']) - strtotime($r['visit_started_at']));
        $r['visit_key'] = $r['id'].'@'.$r['visit_started_at'];
        unset($r['user_agent']);
        return $r;
    }

    // --- helpers -------------------------------------------------------------

    public static function source_label($source, $detail)
    {
        $map = array(
            'google_ads' => 'Google Ads', 'google' => 'Google search', 'instagram' => 'Instagram',
            'facebook' => 'Facebook', 'whatsapp' => 'WhatsApp', 'search' => 'Search engine',
            'referral' => 'Link', 'direct' => 'Direct', 'campaign' => 'Campaign',
        );
        $label = isset($map[$source]) ? $map[$source] : ($source !== '' ? ucfirst($source) : 'Direct');
        return $detail !== '' ? $label.': '.$detail : $label;
    }

    public static function is_ad_attr(array $attr)
    {
        return !empty($attr['gclid']) || !empty($attr['gbraid']) || !empty($attr['wbraid'])
            || (isset($attr['utm_medium']) && strtolower($attr['utm_medium']) === 'cpc');
    }

    /** @return array{source:string,detail:string} */
    public static function detect_source(array $attr, $ref, $url)
    {
        $us = isset($attr['utm_source']) ? strtolower($attr['utm_source']) : '';
        $term = isset($attr['utm_term']) ? $attr['utm_term'] : '';
        if (self::is_ad_attr($attr) && ($us === '' || strpos($us, 'google') !== FALSE)) {
            return array('source' => 'google_ads', 'detail' => self::clip($term !== '' ? '"'.$term.'"' : (isset($attr['utm_campaign']) ? $attr['utm_campaign'] : ''), 160));
        }
        $host = strtolower((string) parse_url($ref, PHP_URL_HOST));
        $self = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($us !== '') {
            foreach (array('instagram', 'facebook', 'whatsapp') as $k) {
                if (strpos($us, $k) !== FALSE || ($k === 'facebook' && $us === 'fb') || ($k === 'instagram' && $us === 'ig')) {
                    return array('source' => $k, 'detail' => self::clip(isset($attr['utm_campaign']) ? $attr['utm_campaign'] : (isset($attr['utm_medium']) ? $attr['utm_medium'] : ''), 160));
                }
            }
            return array('source' => 'campaign', 'detail' => self::clip($attr['utm_source'].(isset($attr['utm_campaign']) ? ' / '.$attr['utm_campaign'] : ''), 160));
        }
        if ($host === '' || ($self !== '' && $host === $self) || strpos($host, 'yourmechaniconline.com') !== FALSE) {
            return array('source' => 'direct', 'detail' => '');
        }
        if (preg_match('/(^|\.)instagram\.com$/', $host)) { return array('source' => 'instagram', 'detail' => ''); }
        if (preg_match('/(^|\.)(facebook\.com|fb\.com)$/', $host)) { return array('source' => 'facebook', 'detail' => ''); }
        if (preg_match('/(^|\.)(whatsapp\.com|wa\.me)$/', $host)) { return array('source' => 'whatsapp', 'detail' => ''); }
        if (preg_match('/(^|\.)google\.[a-z.]+$/', $host)) { return array('source' => 'google', 'detail' => ''); }
        if (preg_match('/(^|\.)(bing\.com|duckduckgo\.com|yahoo\.com)$/', $host)) { return array('source' => 'search', 'detail' => $host); }
        return array('source' => 'referral', 'detail' => self::clip($host, 160));
    }

    /** @return array{device:string,browser:string} */
    public static function device($ua)
    {
        $ua = (string) $ua;
        $device = preg_match('/iPad|Tablet/i', $ua) ? 'tablet' : (preg_match('/Mobi|Android|iPhone/i', $ua) ? 'mobile' : 'desktop');
        $os = preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/iPhone|iPad|iOS/i', $ua) ? 'iPhone'
            : (preg_match('/Windows/i', $ua) ? 'Windows' : (preg_match('/Mac OS X/i', $ua) ? 'Mac' : (preg_match('/Linux/i', $ua) ? 'Linux' : ''))));
        $br = preg_match('/SamsungBrowser/i', $ua) ? 'Samsung' : (preg_match('/Edg\//i', $ua) ? 'Edge'
            : (preg_match('/OPR\//i', $ua) ? 'Opera' : (preg_match('/Chrome\//i', $ua) ? 'Chrome'
            : (preg_match('/Firefox\//i', $ua) ? 'Firefox' : (preg_match('/Safari\//i', $ua) ? 'Safari' : '')))));
        return array('device' => $device, 'browser' => trim($os.' '.$br));
    }

    public static function is_bot($ua)
    {
        return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|mediapartners|adsbot|lighthouse|pagespeed|headlesschrome|facebookexternalhit|preview|python|curl|wget/i', (string) $ua);
    }

    protected static function clean_attr(array $attr)
    {
        $out = array();
        foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid') as $k) {
            if (isset($attr[$k]) && is_string($attr[$k]) && trim($attr[$k]) !== '') {
                $out[$k] = self::clip(trim(strip_tags($attr[$k])), 160);
            }
        }
        return $out;
    }

    /** Path + query without ad-click ids (they are long and useless to read). */
    protected static function clean_url($url)
    {
        $url = (string) $url;
        $parts = parse_url($url);
        if (!$parts) {
            return '';
        }
        $out = (isset($parts['scheme']) ? $parts['scheme'].'://' : '').(isset($parts['host']) ? $parts['host'] : '').(isset($parts['path']) ? $parts['path'] : '/');
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $q);
            foreach (array('gclid', 'gbraid', 'wbraid', 'gad_source', 'gad_campaignid', 'fbclid', 'msclkid') as $k) {
                unset($q[$k]);
            }
            if ($q) {
                $out .= '?'.http_build_query($q);
            }
        }
        return $out;
    }

    protected static function clip($s, $n)
    {
        $s = (string) $s;
        return function_exists('mb_substr') ? mb_substr($s, 0, $n) : substr($s, 0, $n);
    }

    protected static function first_name($name)
    {
        $p = preg_split('/\s+/', trim((string) $name));
        return $p ? $p[0] : '';
    }

    protected function _geo($ip)
    {
        $out = array('city' => '', 'region' => '', 'country' => '', 'lat' => NULL, 'lon' => NULL);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $out;
        }
        $this->load->library('city_geo');
        $g = $this->city_geo->lookup_db($ip);
        if (!$g) {
            return $out;
        }
        return array(
            'city' => self::clip($g['city'], 80), 'region' => self::clip($g['region'], 80),
            'country' => self::clip($g['country'], 4), 'lat' => $g['lat'], 'lon' => $g['lon'],
        );
    }

    protected function _pageview($visitor_id, $url, $title, $now)
    {
        $this->db->insert('ymochat_pageviews', array('visitor_id' => (int) $visitor_id, 'url' => $url, 'title' => $title, 'created_at' => $now));
    }

    protected function _message($visitor_id, $sender, $body, $admin_id)
    {
        $this->db->insert('ymochat_messages', array(
            'visitor_id' => (int) $visitor_id, 'sender' => $sender, 'admin_id' => $admin_id,
            'body' => $body, 'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    /** Now and then, drop page views older than 90 days and silent visitors older than 90 days. */
    protected function _maybe_purge()
    {
        if (mt_rand(1, 200) !== 1) {
            return;
        }
        $cut = date('Y-m-d H:i:s', time() - 90 * 86400);
        $this->db->where('created_at <', $cut)->delete('ymochat_pageviews');
        $this->db->query('DELETE v FROM ymochat_visitors v LEFT JOIN ymochat_messages m ON m.visitor_id = v.id
            WHERE v.last_seen < ? AND m.id IS NULL AND v.lead_id IS NULL', array($cut));
    }
}
