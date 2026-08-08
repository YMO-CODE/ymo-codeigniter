<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_lead_model extends CI_Model
{
    const TABLE = 'crm_leads';

    public function pipeline_stages()
    {
        return array_keys(crm_lead_stages());
    }

    /**
     * Compute time-based stage from next_follow_up_at (open leads only).
     */
    public function compute_stage_from_followup($next_follow_up_at)
    {
        if (!$next_follow_up_at) {
            return 'later';
        }
        $due = strtotime((string) $next_follow_up_at);
        if ($due === FALSE) {
            return 'later';
        }
        $today = strtotime(date('Y-m-d'));
        $due_day = strtotime(date('Y-m-d', $due));
        $days = (int) round(($due_day - $today) / 86400);

        if ($days <= 0) {
            return 'hot_lead';
        }
        if ($days <= 7) {
            return 'followup_next_week';
        }
        if ($days <= 30) {
            return 'followup_next_month';
        }
        return 'later';
    }

    /**
     * Recalculate stage for one lead when not locked and status is open.
     *
     * @param array|int $lead Row or id
     * @return string|null New stage if updated, null if unchanged
     */
    public function recalculate_stage($lead)
    {
        if (!is_array($lead)) {
            $lead = $this->find((int) $lead);
        }
        if (!$lead || $lead['status'] !== 'open' || !empty($lead['stage_locked'])) {
            return NULL;
        }
        if ($lead['stage'] === 'warm_lead') {
            return NULL;
        }
        $new = $this->compute_stage_from_followup($lead['next_follow_up_at'] ?? NULL);
        if ($new === $lead['stage']) {
            return NULL;
        }
        $this->update((int) $lead['id'], array('stage' => $new));
        return $new;
    }

    /** Batch recalc for all open, unlocked leads (cron). */
    public function recalculate_open_stages($limit = 5000)
    {
        $rows = $this->db
            ->select('id, stage, next_follow_up_at, stage_locked, status')
            ->from(self::TABLE)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->where('status', 'open')
            ->where('stage_locked', 0)
            ->where('stage !=', 'warm_lead')
            ->limit((int) $limit)
            ->get()
            ->result_array();

        $updated = 0;
        foreach ($rows as $lead) {
            if ($this->recalculate_stage($lead) !== NULL) {
                $updated++;
            }
        }
        return $updated;
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
        $rows = $this->db
            ->select('l.*, s.label AS source_label, a.name AS assignee_name', FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->join('admin_users a', 'a.id = l.assigned_to', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
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
            $this->_apply_filters($f);
            $this->db->from(self::TABLE.' l')->where('l.deleted_at IS NULL', NULL, FALSE);
            $counts[$stage] = (int) $this->db->count_all_results();
        }
        return $counts;
    }

    /** Open hot leads (for dashboard). */
    public function count_hot_open($assigned_to = NULL)
    {
        $this->db
            ->from(self::TABLE)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->where('status', 'open')
            ->where('stage', 'hot_lead');
        if ($assigned_to) {
            $this->db->where('assigned_to', (int) $assigned_to);
        }
        return (int) $this->db->count_all_results();
    }

    public function list_by_stage_bucket($stage, $limit = 10, $assigned_to = NULL)
    {
        $this->db
            ->select('l.id, l.name, l.mobile, l.stage, l.next_follow_up_at, s.label AS source_label', FALSE)
            ->from(self::TABLE.' l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->where('l.status', 'open')
            ->where('l.stage', $stage)
            ->order_by('l.next_follow_up_at IS NULL', 'ASC', FALSE)
            ->order_by('l.next_follow_up_at', 'ASC')
            ->limit((int) $limit);
        if ($assigned_to) {
            $this->db->where('l.assigned_to', (int) $assigned_to);
        }
        return $this->db->get()->result_array();
    }

    public function create(array $payload)
    {
        $payload = $this->_filter_known_columns($this->_normalize_followup_payload($payload));
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        if (empty($payload['stage'])) {
            $payload['stage'] = 'warm_lead';
        }
        $this->db->insert(self::TABLE, $payload);
        $id = (int) $this->db->insert_id();
        $this->recalculate_stage($this->find($id));
        return $id;
    }

    public function update($id, array $payload)
    {
        $payload = $this->_filter_known_columns($this->_normalize_followup_payload($payload));
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update(self::TABLE, $payload);
        if (array_key_exists('next_follow_up_at', $payload) && empty($payload['stage_locked'])) {
            $lead = $this->find($id);
            if ($lead && $lead['status'] === 'open' && empty($lead['stage_locked']) && $lead['stage'] !== 'warm_lead') {
                $this->recalculate_stage($lead);
            }
        }
    }

    public function assign($id, $admin_id)
    {
        $this->update($id, array('assigned_to' => $admin_id ? (int) $admin_id : NULL));
    }

    public function update_stage($id, $stage, $lock = NULL)
    {
        $patch = array('stage' => $stage);
        if ($lock !== NULL) {
            $patch['stage_locked'] = $lock ? 1 : 0;
        }
        $this->update($id, $patch);
    }

    public function update_status($id, $status)
    {
        $this->update($id, array('status' => $status));
    }

    public function soft_delete($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function mark_converted($id, $contact_id, $user_id = NULL)
    {
        $patch = array(
            'status'               => 'converted',
            'stage'                => 'quote_sent',
            'stage_locked'         => 1,
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

        $payload = array(
            'source_id'         => $source_id,
            'name'              => $fields['name'] ?? 'Unknown',
            'mobile'            => preg_replace('/\D/', '', (string) ($fields['mobile'] ?? '')),
            'email'             => strtolower(trim((string) ($fields['email'] ?? ''))),
            'company'           => $fields['company'] ?? NULL,
            'address'           => $fields['address'] ?? NULL,
            'car_type'          => $fields['car_type'] ?? NULL,
            'message'           => $fields['message'] ?? NULL,
            'stage'             => 'warm_lead',
            'external_lead_id'  => $external_id,
            'external_provider' => $provider,
            'payload_json'      => !empty($fields['raw']) ? json_encode($fields['raw']) : NULL,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        );
        $payload = $this->_apply_raw_address_car($payload, $fields['raw'] ?? NULL);
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

        $lead_id = $this->ingest($source_slug, $fields, $sender_id, $provider);
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
            if (!empty($payload['next_follow_up_at']) && empty($payload['stage_locked'])) {
                $manual = isset($payload['stage']) ? $payload['stage'] : NULL;
                if (!in_array($manual, crm_lead_manual_stages(), TRUE)) {
                    $payload['stage'] = $this->compute_stage_from_followup($payload['next_follow_up_at']);
                    $payload['stage_locked'] = 0;
                }
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
            $this->db->where('l.stage', $filters['stage']);
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
            $this->db->where('l.assigned_to', (int) $filters['admin_id']);
        }
    }
}
