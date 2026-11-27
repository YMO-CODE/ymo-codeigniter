<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google Ads click → CRM lead attribution, and the offline conversion feed.
 *
 * Website leads (quick book, contact form, YMOChat) already carry the visitor's
 * gclid in payload_json.utm; Crm_lead_model::ingest() copies it onto the lead.
 *
 * WhatsApp taps leave the site, so the Google tag (google_tag.php) adds an
 * invisible reference to the pre-filled WhatsApp text and beacons it here
 * (api/ads/wa-click) together with the visitor's ad click. When the message
 * arrives, the webhook decodes the reference (method "ref"); if it was lost
 * (customer deleted the text), a brand-new WhatsApp number within 15 minutes
 * of an unclaimed tap is matched to it instead (method "time").
 *
 * Google Ads fetches api/google-ads/conversions.csv daily (scheduled upload):
 *   CRM - Qualified lead     lead reached Contacted / Quote sent / Booked
 *   CRM - Booking completed  lead reached Booked (value = invoice or package price)
 */
class Crm_ad_click_model extends CI_Model
{
    const CLICKS = 'crm_ad_wa_clicks';
    const LEADS = 'crm_leads';
    const MATCH_WINDOW_MIN = 15;

    const CONV_QUALIFIED = 'CRM - Qualified lead';
    const CONV_BOOKED = 'CRM - Booking completed';

    /**
     * Invisible characters used as binary digits 0/1: zero-width non-joiner and
     * zero-width joiner. WhatsApp keeps these (Indic typing and emoji need them);
     * it trims other invisible characters, so the code also goes mid-message.
     */
    const ZW = array("\u{200C}", "\u{200D}");

    protected static $schema_ok = NULL;

    // --- schema ------------------------------------------------------------

    public function ensure_schema()
    {
        if (self::$schema_ok !== NULL) {
            return self::$schema_ok;
        }
        if (!$this->db->table_exists(self::LEADS)) {
            return self::$schema_ok = FALSE;
        }
        $ok = TRUE;
        if (!$this->db->table_exists(self::CLICKS)) {
            $ok = (bool) $this->db->query("CREATE TABLE IF NOT EXISTS `".self::CLICKS."` (
                `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ref`         CHAR(8) NOT NULL,
                `click_id`    VARCHAR(255) NOT NULL,
                `click_type`  VARCHAR(10) NOT NULL DEFAULT 'gclid',
                `attribution` TEXT NULL,
                `page`        VARCHAR(200) NOT NULL DEFAULT '',
                `ip`          VARCHAR(45) NOT NULL DEFAULT '',
                `lead_id`     BIGINT UNSIGNED NULL,
                `matched_by`  VARCHAR(10) NULL,
                `claimed_at`  DATETIME NULL,
                `created_at`  DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_ad_wa_ref` (`ref`),
                KEY `idx_ad_wa_unclaimed` (`lead_id`, `created_at`),
                KEY `idx_ad_wa_ip` (`ip`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        $cols = array(
            'ad_click_id'   => "VARCHAR(255) NULL",
            'ad_click_type' => "VARCHAR(10) NULL",
            'ad_match'      => "VARCHAR(10) NULL",
            'qualified_at'  => "DATETIME NULL",
            'booked_at'     => "DATETIME NULL",
        );
        foreach ($cols as $name => $def) {
            if (!$this->db->field_exists($name, self::LEADS)) {
                $ok = $this->db->query('ALTER TABLE `'.self::LEADS.'` ADD COLUMN `'.$name.'` '.$def) && $ok;
            }
        }
        if ($ok && !$this->_has_index(self::LEADS, 'idx_crm_leads_ad_click')) {
            $this->db->query('ALTER TABLE `'.self::LEADS.'` ADD KEY `idx_crm_leads_ad_click` (`ad_click_type`, `created_at`)');
        }
        $this->db->data_cache = array();
        if (!$ok) {
            log_message('error', 'Ad clicks: could not create/alter tables.');
        }
        return self::$schema_ok = $ok;
    }

    protected function _has_index($table, $name)
    {
        $q = $this->db->query('SHOW INDEX FROM `'.$table.'` WHERE Key_name = '.$this->db->escape($name));
        return $q && $q->num_rows() > 0;
    }

    // --- click ids -----------------------------------------------------------

    /**
     * Strongest Google click id in an attribution array.
     *
     * @return array{0:string,1:string}|null [type, id]
     */
    public static function click_from_attr($attr)
    {
        if (!is_array($attr)) {
            return NULL;
        }
        foreach (array('gclid', 'gbraid', 'wbraid') as $k) {
            $v = isset($attr[$k]) && is_scalar($attr[$k]) ? trim((string) $attr[$k]) : '';
            if ($v !== '' && preg_match('/^[A-Za-z0-9_\-\.]{10,255}$/', $v)) {
                return array($k, $v);
            }
        }
        return NULL;
    }

    // --- invisible WhatsApp reference ---------------------------------------

    /** Find an 8-hex reference hidden in a message (32 invisible bits). */
    public static function decode_ref($text)
    {
        if (!preg_match('/[\x{200C}\x{200D}]{32}/u', (string) $text, $m)) {
            return NULL;
        }
        $n = 0;
        foreach (preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY) as $c) {
            $n = ($n * 2) + ($c === self::ZW[1] ? 1 : 0);
        }
        return sprintf('%08x', $n);
    }

    /**
     * Message text without the hidden reference. Only removes a run of 32+
     * (or the older 16-character form), so emoji and Indic text keep theirs.
     */
    public static function strip_ref($text)
    {
        $text = preg_replace('/[\x{200C}\x{200D}]{32,}/u', '', (string) $text);
        $text = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{2060}]{16,}/u', '', $text);
        return trim($text);
    }

    /**
     * A WhatsApp tap from an ad visitor (beacon from the Google tag).
     *
     * @return bool
     */
    public function record_wa_click($ref, array $attr, $page, $ip)
    {
        $ref = strtolower((string) $ref);
        $click = self::click_from_attr($attr);
        if (!preg_match('/^[0-9a-f]{8}$/', $ref) || !$click || !$this->ensure_schema()) {
            return FALSE;
        }
        // A real visitor taps a handful of times at most.
        $recent = (int) $this->db->where('ip', (string) $ip)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 3600))
            ->count_all_results(self::CLICKS);
        if ($recent >= 30) {
            return FALSE;
        }
        $keep = array_intersect_key($attr, array_flip(array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'landing', 'at')));
        $sql = $this->db->insert_string(self::CLICKS, array(
            'ref'         => $ref,
            'click_id'    => $click[1],
            'click_type'  => $click[0],
            'attribution' => json_encode($keep),
            'page'        => mb_substr((string) $page, 0, 200),
            'ip'          => mb_substr((string) $ip, 0, 45),
            'created_at'  => date('Y-m-d H:i:s'),
        ));
        return (bool) $this->db->query(preg_replace('/^INSERT/', 'INSERT IGNORE', $sql));
    }

    /**
     * Link an incoming WhatsApp message's lead to the ad click behind it.
     *
     * @param string|null $ref       reference decoded from the message
     * @param bool        $first_msg first message ever from this number
     * @return string|null 'ref' / 'time' when linked
     */
    public function attach_whatsapp_lead($lead_id, $ref, $first_msg)
    {
        $lead_id = (int) $lead_id;
        if ($lead_id <= 0 || !$this->ensure_schema()) {
            return NULL;
        }
        $row = NULL;
        $method = NULL;
        if ($ref) {
            $row = $this->db->where('ref', $ref)
                ->where('created_at >=', date('Y-m-d H:i:s', time() - 7 * 86400))
                ->get(self::CLICKS)->row_array();
            $method = 'ref';
            if ($row && !empty($row['lead_id']) && (int) $row['lead_id'] !== $lead_id) {
                $row = NULL; // already used by someone else
            }
        }
        if (!$row && !$ref && $first_msg) {
            $row = $this->db->where('lead_id IS NULL', NULL, FALSE)
                ->where('created_at >=', date('Y-m-d H:i:s', time() - self::MATCH_WINDOW_MIN * 60))
                ->order_by('created_at', 'DESC')
                ->limit(1)
                ->get(self::CLICKS)->row_array();
            $method = 'time';
        }
        if (!$row) {
            return NULL;
        }
        $this->db->where('id', (int) $row['id'])->update(self::CLICKS, array(
            'lead_id'    => $lead_id,
            'matched_by' => $method,
            'claimed_at' => date('Y-m-d H:i:s'),
        ));
        if (!$this->set_lead_click($lead_id, $row['click_type'], $row['click_id'], $method)) {
            return NULL;
        }
        return $method;
    }

    /** Store the click on the lead unless it already has one. */
    public function set_lead_click($lead_id, $type, $id, $method)
    {
        if (!$this->ensure_schema()) {
            return FALSE;
        }
        $this->db->where('id', (int) $lead_id)
            ->where('ad_click_id IS NULL', NULL, FALSE)
            ->update(self::LEADS, array(
                'ad_click_id'   => $id,
                'ad_click_type' => $type,
                'ad_match'      => $method,
            ));
        return $this->db->affected_rows() > 0;
    }

    // --- feed ----------------------------------------------------------------

    /**
     * Conversions for the Google Ads scheduled upload (last $days days).
     *
     * @return array<int,array{gclid:string,name:string,time:string,value:float}>
     */
    public function feed_rows($days = 60)
    {
        if (!$this->ensure_schema()) {
            return array();
        }
        $since = date('Y-m-d H:i:s', time() - (int) $days * 86400);
        $has_inv = $this->db->table_exists('booking_invoices');
        $rows = $this->db->query(
            'SELECT l.id, l.ad_click_id, l.qualified_at, l.booked_at, l.booking_id, l.stage, l.lost_reason,
                    b.package_snapshot'.($has_inv ? ', (SELECT MAX(i.grand_total) FROM booking_invoices i WHERE i.booking_id = l.booking_id) AS invoice_total' : ', NULL AS invoice_total').'
               FROM `'.self::LEADS.'` l
          LEFT JOIN bookings b ON b.id = l.booking_id
              WHERE l.deleted_at IS NULL
                AND l.ad_click_type = \'gclid\'
                AND l.ad_click_id IS NOT NULL
                AND (l.qualified_at >= ? OR l.booked_at >= ?)
                AND NOT (l.stage = \'lost\' AND l.lost_reason = \'spam\')
           ORDER BY l.id',
            array($since, $since)
        )->result_array();

        $out = array();
        foreach ($rows as $r) {
            if (!empty($r['qualified_at']) && $r['qualified_at'] >= $since) {
                $out[] = array('gclid' => $r['ad_click_id'], 'name' => self::CONV_QUALIFIED, 'time' => $r['qualified_at'], 'value' => 1.0);
            }
            if (!empty($r['booked_at']) && $r['booked_at'] >= $since) {
                $value = (float) $r['invoice_total'];
                if ($value <= 0 && !empty($r['package_snapshot'])) {
                    $snap = json_decode($r['package_snapshot'], TRUE);
                    $value = isset($snap['price']) ? (float) $snap['price'] : 0.0;
                }
                $out[] = array('gclid' => $r['ad_click_id'], 'name' => self::CONV_BOOKED, 'time' => $r['booked_at'], 'value' => $value > 0 ? $value : 1.0);
            }
        }
        return $out;
    }

    /** Ad-attributed leads for the last $days days, newest first (admin check). */
    public function recent_attributed($days = 30)
    {
        if (!$this->ensure_schema()) {
            return array();
        }
        return $this->db->select('id, name, stage, ad_click_type, ad_match, qualified_at, booked_at, created_at')
            ->where('ad_click_id IS NOT NULL', NULL, FALSE)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - (int) $days * 86400))
            ->order_by('id', 'DESC')
            ->get(self::LEADS)->result_array();
    }
}
