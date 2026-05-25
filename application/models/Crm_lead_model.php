<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_lead_model extends CI_Model
{
    const TABLE = 'crm_leads';

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
        $stages = array('new', 'contacted', 'qualified', 'proposal', 'won', 'lost');
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
                ->order_by('l.updated_at', 'DESC')
                ->limit(50)
                ->get()
                ->result_array();
        }
        return $out;
    }

    public function stage_counts(array $filters = array())
    {
        $stages = array('new', 'contacted', 'qualified', 'proposal', 'won', 'lost');
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

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $payload)
    {
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update(self::TABLE, $payload);
    }

    public function assign($id, $admin_id)
    {
        $this->update($id, array('assigned_to' => $admin_id ? (int) $admin_id : NULL));
    }

    public function update_stage($id, $stage)
    {
        $this->update($id, array('stage' => $stage));
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
            'stage'                => 'won',
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
            'message'           => $fields['message'] ?? NULL,
            'external_lead_id'  => $external_id,
            'external_provider' => $provider,
            'payload_json'      => !empty($fields['raw']) ? json_encode($fields['raw']) : NULL,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        );
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
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

    protected function _apply_filters(array $filters)
    {
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('l.name', $q)
                ->or_like('l.mobile', $q)
                ->or_like('l.email', $q)
                ->or_like('l.company', $q)
                ->group_end();
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
