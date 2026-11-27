<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_lead_model extends CI_Model
{
    /** TRUE when the last ingest() added to an open lead instead of creating one. */
    public $last_ingest_merged = FALSE;

    /** Set by ingest_chat_message() so a merge doesn't repeat the chat text. */
    protected $_ingest_from_chat = FALSE;

    const TABLE = 'crm_leads';

    public function pipeline_stages()
    {
        return array_keys(crm_lead_stages());
    }

    /**
     * CRM v6: stages are set by people (log a call / book), never by dates.
     * Kept as no-ops so older callers (cron) keep working.
     */
    public function recalculate_stage($lead)
    {
        return NULL;
    }

    public function recalculate_open_stages($limit = 5000)
    {
        return 0;
    }

    public function find($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->get(self::TABLE)
            ->row_array();
    }

    public function find_detailed($id)
    {
        return $this->db
            ->select('l.*, s.label AS source_label, s.slug AS source_slug,
                      a.name AS assignee_name, a.email AS assignee_email', FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->join('admin_users a', 'a.id = l.assigned_to', 'left')
            ->where('l.id', (int) $id)
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->get()
            ->row_array();
    }

    public function paginate(array $filters, $limit, $offset)
    {
        $this->_apply_filters($filters);
        $this->db
            ->select('l.*, s.label AS source_label, a.name AS assignee_name', FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->join('admin_users a', 'a.id = l.assigned_to', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE);
        if (($filters['sort'] ?? '') === 'call_first') {
            // Who to call first: overdue, then never contacted, then due today, then open without a date.
            $end_today = $this->db->escape(date('Y-m-d 23:59:59'));
            $now = $this->db->escape(date('Y-m-d H:i:s'));
            $this->db->order_by("CASE WHEN l.stage IN ('booked','lost') THEN 9"
                ." WHEN l.next_follow_up_at IS NOT NULL AND l.next_follow_up_at < $now THEN 0"
                ." WHEN l.stage = 'new' THEN 1"
                ." WHEN l.next_follow_up_at <= $end_today THEN 2"
                ." WHEN l.next_follow_up_at IS NULL THEN 3 ELSE 4 END", '', FALSE)
                ->order_by('l.priority', 'DESC')
                ->order_by('l.next_follow_up_at IS NULL', 'ASC', FALSE)
                ->order_by('l.next_follow_up_at', 'ASC')
                ->order_by('l.created_at', 'ASC');
        }
        $rows = $this->db
            ->order_by("FIELD(l.stage, 'new', 'contacted', 'quote_sent', 'booked', 'lost')", '', FALSE)
            ->order_by('l.priority', 'DESC')
            ->order_by('l.next_follow_up_at IS NULL', 'ASC', FALSE)
            ->order_by('l.next_follow_up_at', 'ASC')
            ->order_by('l.created_at', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();

        $this->_apply_filters($filters);
        $this->db->from(self::TABLE.' l')->where('l.deleted_at IS NULL', NULL, FALSE);
        $total = (int) $this->db->count_all_results();

        return array('rows' => $rows, 'total' => $total);
    }

    public function for_pipeline(array $filters = array())
    {
        $stages = $this->pipeline_stages();
        $out = array();
        foreach ($stages as $stage) {
            $f = $filters;
            $f['stage'] = $stage;
            if (in_array($stage, array('booked', 'lost'), TRUE) && empty($f['closed_since'])) {
                $f['closed_since'] = date('Y-m-d H:i:s', strtotime('-30 days'));
            }
            $this->_apply_filters($f);
            $out[$stage] = $this->db
                ->select('l.*, s.label AS source_label, a.name AS assignee_name', FALSE)
                ->from(self::TABLE.' l')
                ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
                ->join('admin_users a', 'a.id = l.assigned_to', 'left')
                ->where('l.deleted_at IS NULL', NULL, FALSE)
                ->order_by('l.priority', 'DESC')
                ->order_by('l.next_follow_up_at IS NULL', 'ASC', FALSE)
                ->order_by('l.next_follow_up_at', 'ASC')
                ->order_by('l.updated_at', 'DESC')
                ->limit(50)
                ->get()
                ->result_array();
        }
        return $out;
    }

    public function stage_counts(array $filters = array())
    {
        $stages = $this->pipeline_stages();
        $counts = array();
        foreach ($stages as $stage) {
            $f = $filters;
            $f['stage'] = $stage;
            if (in_array($stage, array('booked', 'lost'), TRUE) && !empty($filters['board']) && empty($f['closed_since'])) {
                $f['closed_since'] = date('Y-m-d H:i:s', strtotime('-30 days'));
            }
            $this->_apply_filters($f);
            $this->db->from(self::TABLE.' l')->where('l.deleted_at IS NULL', NULL, FALSE);
            $counts[$stage] = (int) $this->db->count_all_results();
        }
        return $counts;
    }

    /** New leads nobody has worked yet (oldest first). */
    public function list_new($limit = 10, $assigned_to = NULL)
    {
        $this->db
            ->select('l.id, l.name, l.mobile, l.created_at, l.assigned_to, s.label AS source_label, a.name AS assignee_name', FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->join('admin_users a', 'a.id = l.assigned_to', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->where('l.stage', 'new')
            ->order_by('l.created_at', 'ASC')
            ->limit((int) $limit);
        if ($assigned_to) {
            $this->db->group_start()
                ->where('l.assigned_to', (int) $assigned_to)
                ->or_where('l.assigned_to IS NULL', NULL, FALSE)
                ->group_end();
        }
        return $this->db->get()->result_array();
    }

    public function count_new($assigned_to = NULL)
    {
        $this->db->from(self::TABLE)->where('deleted_at IS NULL', NULL, FALSE)->where('stage', 'new');
        if ($assigned_to) {
            $this->db->group_start()
                ->where('assigned_to', (int) $assigned_to)
                ->or_where('assigned_to IS NULL', NULL, FALSE)
                ->group_end();
        }
        return (int) $this->db->count_all_results();
    }

    /**
     * Leads created since $from per source with how many got booked.
     *
     * @return array<int,array{label:string,leads:int,booked:int,lost:int}>
     */
    public function source_conversion($from)
    {
        return $this->db
            ->select("s.label, COUNT(l.id) AS leads,
                      SUM(CASE WHEN l.stage = 'booked' THEN 1 ELSE 0 END) AS booked,
                      SUM(CASE WHEN l.stage = 'lost' THEN 1 ELSE 0 END) AS lost", FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->where('l.created_at >=', $from)
            ->group_by('s.id, s.label')
            ->order_by('leads', 'DESC')
            ->get()
            ->result_array();
    }

    /** Most recent open lead for a mobile number (last 10 digits). */
    public function find_open_by_mobile($mobile)
    {
        $m = crm_mobile10($mobile);
        if (strlen($m) !== 10) {
            return NULL;
        }
        return $this->db
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->where_in('stage', crm_lead_open_stages())
            ->like('mobile', $m, 'before')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get(self::TABLE)
            ->row_array();
    }

    /** All leads (any stage) for a mobile number — customer history. */
    public function for_mobile($mobile, $limit = 20)
    {
        $m = crm_mobile10($mobile);
        if (strlen($m) !== 10) {
            return array();
        }
        return $this->db
            ->select('l.id, l.name, l.stage, l.created_at, l.booking_id, l.message, s.label AS source_label', FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->like('l.mobile', $m, 'before')
            ->order_by('l.id', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    /**
     * Move a lead to a stage; keeps the legacy status column in sync.
     */
    public function set_stage($id, $stage, $lost_reason = NULL)
    {
        $patch = array('stage' => $stage);
        if ($stage === 'lost') {
            $patch['lost_reason'] = $lost_reason ?: 'other';
        } else {
            $patch['lost_reason'] = NULL;
        }
        if (in_array($stage, array('booked', 'lost'), TRUE)) {
            $patch['next_follow_up_at'] = NULL;
        }
        $this->update($id, $patch);
    }

    /** Lead turned into a booking: link everything together. */
    public function mark_booked($id, $booking_id, $user_id = NULL, $contact_id = NULL)
    {
        $patch = array(
            'stage'             => 'booked',
            'lost_reason'       => NULL,
            'booking_id'        => (int) $booking_id,
            'next_follow_up_at' => NULL,
        );
        if ($user_id) {
            $patch['converted_user_id'] = (int) $user_id;
        }
        if ($contact_id) {
            $patch['converted_contact_id'] = (int) $contact_id;
        }
        $this->update($id, $patch);
    }

    public function create(array $payload)
    {
        $payload = $this->_filter_known_columns($this->_normalize_followup_payload($payload));
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        if (empty($payload['stage'])) {
            $payload['stage'] = 'new';
        }
        $payload['status'] = crm_lead_stage_status($payload['stage']);
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $payload)
    {
        $payload = $this->_filter_known_columns($this->_normalize_followup_payload($payload));
        if (!empty($payload['stage'])) {
            $payload['status'] = crm_lead_stage_status($payload['stage']);
            $this->_stamp_stage_times($payload['stage']);
        }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update(self::TABLE, $payload);
    }

    public function assign($id, $admin_id)
    {
        $this->update($id, array('assigned_to' => $admin_id ? (int) $admin_id : NULL));
    }

    public function update_stage($id, $stage, $lock = NULL)
    {
        $this->set_stage($id, $stage);
    }

    public function soft_delete($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    /** Link a lead to its customer record (no stage change). */
    public function mark_converted($id, $contact_id, $user_id = NULL)
    {
        $patch = array(
            'converted_contact_id' => (int) $contact_id,
            'updated_at'           => date('Y-m-d H:i:s'),
        );
        if ($user_id) {
            $patch['converted_user_id'] = (int) $user_id;
        }
        $this->db->where('id', (int) $id)->update(self::TABLE, $patch);
    }

    public function list_sources()
    {
        return $this->db->order_by('id', 'ASC')->get('crm_lead_sources')->result_array();
    }

    public function source_id_by_slug($slug)
    {
        $row = $this->db->get_where('crm_lead_sources', array('slug' => $slug))->row_array();
        return $row ? (int) $row['id'] : 0;
    }

    /**
     * Idempotent lead ingest from webhooks (Meta, website, WhatsApp).
     */
    public function ingest($source_slug, array $fields, $external_id = NULL, $provider = NULL)
    {
        $source_id = $this->source_id_by_slug($source_slug);
        if ($source_id <= 0) {
            return 0;
        }

        if ($external_id && $provider) {
            $existing = $this->db
                ->where('external_provider', $provider)
                ->where('external_lead_id', $external_id)
                ->where('deleted_at IS NULL', NULL, FALSE)
                ->get(self::TABLE)
                ->row_array();
            if ($existing) {
                return (int) $existing['id'];
            }
        }

        // Same person with a lead still open (New / Contacted / Quote sent):
        // add this enquiry to that lead instead of creating a duplicate.
        $this->last_ingest_merged = FALSE;
        $open = !empty($fields['mobile']) ? $this->find_open_by_mobile($fields['mobile']) : NULL;
        if ($open) {
            $this->_merge_into_open_lead($open, $source_id, $fields, $external_id, $provider);
            $this->last_ingest_merged = TRUE;
            return (int) $open['id'];
        }

        $payload = array(
            'source_id'         => $source_id,
            'name'              => $fields['name'] ?? 'Unknown',
            'mobile'            => preg_replace('/\D/', '', (string) ($fields['mobile'] ?? '')),
            'email'             => strtolower(trim((string) ($fields['email'] ?? ''))),
            'company'           => $fields['company'] ?? NULL,
            'address'           => $fields['address'] ?? NULL,
            'car_type'          => $fields['car_type'] ?? NULL,
            'message'           => $fields['message'] ?? NULL,
            'stage'             => 'new',
            'status'            => 'open',
            'external_lead_id'  => $external_id,
            'external_provider' => $provider,
            'payload_json'      => !empty($fields['raw']) ? json_encode($fields['raw']) : NULL,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        );
        $payload = $this->_apply_raw_address_car($payload, $fields['raw'] ?? NULL);
        $payload = $this->_apply_ad_click($payload, $fields['raw'] ?? NULL);
        $payload = $this->_filter_known_columns($payload);
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    /**
     * Ingest chat/DM: one lead per sender; further messages append activity.
     *
     * @param string $activity_type crm_lead_activities.type (whatsapp, webhook, …)
     */
    public function ingest_chat_message($source_slug, array $fields, $sender_id, $message_id, $provider, $activity_type = 'webhook', $channel = NULL)
    {
        $this->load->model('crm_lead_activity_model');
        if ($message_id && $this->crm_lead_activity_model->exists_for_external_message($provider, $message_id)) {
            $row = $this->db
                ->where('external_provider', $provider)
                ->where('external_lead_id', $sender_id)
                ->where('deleted_at IS NULL', NULL, FALSE)
                ->get(self::TABLE)
                ->row_array();
            return $row ? (int) $row['id'] : 0;
        }

        $existing = $this->db
            ->where('external_provider', $provider)
            ->where('external_lead_id', $sender_id)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->get(self::TABLE)
            ->row_array();

        $text = (string) ($fields['text'] ?? '');
        if ($text === '' && !empty($fields['message'])) {
            $text = function_exists('crm_chat_strip_legacy_prefix')
                ? crm_chat_strip_legacy_prefix($fields['message'])
                : (string) $fields['message'];
        }

        $meta = array(
            'direction'  => 'inbound',
            'channel'    => $channel ?: ($provider === 'whatsapp' ? 'whatsapp' : 'instagram'),
            'provider'   => $provider,
            'message_id' => $message_id,
            'sender_id'  => $sender_id,
            'text'       => $text,
        );
        $body = $text;

        if ($existing) {
            if ($body !== '') {
                $this->crm_lead_activity_model->add(
                    (int) $existing['id'],
                    NULL,
                    $activity_type,
                    $body,
                    $meta
                );
            }
            return (int) $existing['id'];
        }

        $this->_ingest_from_chat = TRUE;
        $lead_id = $this->ingest($source_slug, $fields, $sender_id, $provider);
        $this->_ingest_from_chat = FALSE;
        if ($lead_id && $body !== '') {
            $this->crm_lead_activity_model->add($lead_id, NULL, $activity_type, $body, $meta);
        }
        return $lead_id;
    }

    public function export_all(array $filters = array())
    {
        $this->_apply_filters($filters);
        return $this->db
            ->select('l.*, s.label AS source_label', FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->order_by('l.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    protected function _normalize_followup_payload(array $payload)
    {
        if (array_key_exists('next_follow_up_at', $payload)) {
            $v = trim((string) $payload['next_follow_up_at']);
            if ($v === '') {
                $payload['next_follow_up_at'] = NULL;
            } else {
                $ts = strtotime(str_replace('T', ' ', $v));
                $payload['next_follow_up_at'] = $ts ? date('Y-m-d H:i:s', $ts) : NULL;
            }
        }
        return $payload;
    }

    protected function _apply_raw_address_car(array $payload, $raw)
    {
        if (!is_array($raw)) {
            return $payload;
        }
        if (empty($payload['address'])) {
            $parts = array_filter(array(
                trim((string) ($raw['area'] ?? '')),
                trim((string) ($raw['city'] ?? '')),
            ));
            if ($parts) {
                $payload['address'] = implode(', ', $parts);
            }
        }
        if (empty($payload['car_type'])) {
            $make = trim((string) ($raw['make_name'] ?? ''));
            $variant = trim((string) ($raw['variant'] ?? ''));
            $car = trim($make.' '.$variant);
            if ($car !== '') {
                $payload['car_type'] = $car;
            }
        }
        return $payload;
    }

    /**
     * Google click id from a website lead's attribution (raw.utm) → lead columns.
     * Ignored until Crm_ad_click_model has added the columns.
     */
    protected function _apply_ad_click(array $payload, $raw)
    {
        if (!is_array($raw) || empty($raw['utm']) || !is_array($raw['utm'])) {
            return $payload;
        }
        $this->load->model('crm_ad_click_model');
        $click = Crm_ad_click_model::click_from_attr($raw['utm']);
        if ($click && $this->crm_ad_click_model->ensure_schema()) {
            $payload['ad_click_type'] = $click[0];
            $payload['ad_click_id'] = $click[1];
            $payload['ad_match'] = 'form';
        }
        return $payload;
    }

    /**
     * A repeat enquiry from someone whose lead is still open: note it on that
     * lead, fill in details it was missing, and link the chat thread / ad click.
     */
    protected function _merge_into_open_lead(array $open, $source_id, array $fields, $external_id, $provider)
    {
        $this->load->model('crm_lead_activity_model');
        $src = $this->db->select('label')->where('id', (int) $source_id)->get('crm_lead_sources')->row_array();
        $label = $src ? $src['label'] : 'another channel';
        $msg = trim((string) ($fields['message'] ?? ''));
        $body = 'Enquired again ('.$label.').';
        if (!$this->_ingest_from_chat && $msg !== '') {
            $body .= "\n".$msg;
        }
        $this->crm_lead_activity_model->add((int) $open['id'], NULL, 'system', $body,
            array('source' => 'repeat_enquiry', 'provider' => $provider, 'source_id' => (int) $source_id));

        $patch = array();
        if ($external_id && $provider && empty($open['external_lead_id'])) {
            // Later messages in this chat go straight to this lead.
            $patch['external_provider'] = $provider;
            $patch['external_lead_id'] = $external_id;
        }
        $email = strtolower(trim((string) ($fields['email'] ?? '')));
        if ($email !== '' && trim((string) $open['email']) === '') {
            $patch['email'] = $email;
        }
        $extra = $this->_apply_raw_address_car(array(), $fields['raw'] ?? NULL);
        foreach (array('address', 'car_type') as $k) {
            if (!empty($extra[$k]) && trim((string) ($open[$k] ?? '')) === '') {
                $patch[$k] = $extra[$k];
            }
        }
        if ($patch) {
            $this->update((int) $open['id'], $patch);
        }
        $raw = $fields['raw'] ?? NULL;
        if (is_array($raw) && !empty($raw['utm']) && is_array($raw['utm'])) {
            $this->load->model('crm_ad_click_model');
            $click = Crm_ad_click_model::click_from_attr($raw['utm']);
            if ($click) {
                $this->crm_ad_click_model->set_lead_click((int) $open['id'], $click[0], $click[1], 'form');
            }
        }
    }

    /** Staff logged the lead as genuinely interested (call outcome "Interested"). */
    public function mark_qualified($id)
    {
        if ($this->db->field_exists('qualified_at', self::TABLE)) {
            $this->db->set('qualified_at', 'COALESCE(qualified_at, '.$this->db->escape(date('Y-m-d H:i:s')).')', FALSE)
                ->where('id', (int) $id)->update(self::TABLE);
        }
    }

    /**
     * First time a lead is quoted / booked (Google Ads offline conversions).
     * Applied to the next update() write via the query builder.
     */
    protected function _stamp_stage_times($stage)
    {
        static $has = NULL;
        if ($has === NULL) {
            $has = $this->db->field_exists('qualified_at', self::TABLE) && $this->db->field_exists('booked_at', self::TABLE);
        }
        if (!$has) {
            return;
        }
        $now = $this->db->escape(date('Y-m-d H:i:s'));
        if (in_array($stage, array('quote_sent', 'booked'), TRUE)) {
            $this->db->set('qualified_at', 'COALESCE(qualified_at, '.$now.')', FALSE);
        }
        if ($stage === 'booked') {
            $this->db->set('booked_at', 'COALESCE(booked_at, '.$now.')', FALSE);
        }
    }

    protected function _filter_known_columns(array $payload)
    {
        static $columns = NULL;
        if ($columns === NULL) {
            if (!$this->db->table_exists(self::TABLE)) {
                $columns = array();
            } else {
                $columns = $this->db->list_fields(self::TABLE);
            }
        }
        if (!$columns) {
            return $payload;
        }
        return array_intersect_key($payload, array_flip($columns));
    }

    protected function _apply_filters(array $filters)
    {
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('l.name', $q)
                ->or_like('l.mobile', $q)
                ->or_like('l.email', $q)
                ->or_like('l.company', $q);
            if ($this->db->field_exists('address', self::TABLE)) {
                $this->db->or_like('l.address', $q);
            }
            if ($this->db->field_exists('car_type', self::TABLE)) {
                $this->db->or_like('l.car_type', $q);
            }
            $this->db->group_end();
        }
        if (!empty($filters['source_slug'])) {
            $sid = $this->source_id_by_slug($filters['source_slug']);
            if ($sid > 0) {
                $this->db->where('l.source_id', $sid);
            }
        }
        if (!empty($filters['source_id'])) {
            $this->db->where('l.source_id', (int) $filters['source_id']);
        }
        if (!empty($filters['stage'])) {
            if ($filters['stage'] === 'open') {
                $this->db->where_in('l.stage', crm_lead_open_stages());
            } else {
                $this->db->where('l.stage', $filters['stage']);
            }
        }
        if (!empty($filters['closed_since'])) {
            $this->db->where('l.updated_at >=', $filters['closed_since']);
        }
        if (!empty($filters['due'])) {
            $this->db->where_in('l.stage', crm_lead_open_stages());
            if ($filters['due'] === 'overdue') {
                $this->db->where('l.next_follow_up_at <', date('Y-m-d 00:00:00'));
            } elseif ($filters['due'] === 'today') {
                $this->db->where('l.next_follow_up_at >=', date('Y-m-d 00:00:00'))
                    ->where('l.next_follow_up_at <=', date('Y-m-d 23:59:59'));
            } elseif ($filters['due'] === 'none') {
                $this->db->where('l.next_follow_up_at IS NULL', NULL, FALSE);
            }
        }
        if (!empty($filters['status'])) {
            $this->db->where('l.status', $filters['status']);
        }
        if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '') {
            if ($filters['assigned_to'] === 'unassigned') {
                $this->db->where('l.assigned_to IS NULL', NULL, FALSE);
            } else {
                $this->db->where('l.assigned_to', (int) $filters['assigned_to']);
            }
        }
        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $this->db->where('l.priority', (int) $filters['priority']);
        }
        if (!empty($filters['mine']) && !empty($filters['admin_id'])) {
            if ($filters['mine'] === 'with_unassigned') {
                // "My leads" on the list: mine plus nobody's yet, so new leads aren't missed.
                $this->db->group_start()
                    ->where('l.assigned_to', (int) $filters['admin_id'])
                    ->or_where('l.assigned_to IS NULL', NULL, FALSE)
                    ->group_end();
            } else {
                $this->db->where('l.assigned_to', (int) $filters['admin_id']);
            }
        }
    }
}
