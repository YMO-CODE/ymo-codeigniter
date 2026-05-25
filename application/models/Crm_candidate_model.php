<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_candidate_model extends CI_Model
{
    const TABLE = 'crm_candidates';
    const DOC_TABLE = 'crm_candidate_documents';
    const INT_TABLE = 'crm_interviews';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_detailed($id)
    {
        return $this->db
            ->select('c.*, a.name AS assignee_name', FALSE)
            ->from(self::TABLE.' c')
            ->join('admin_users a', 'a.id = c.assigned_to', 'left')
            ->where('c.id', (int) $id)
            ->get()
            ->row_array();
    }

    public function paginate(array $filters, $limit, $offset)
    {
        if (!empty($filters['stage'])) {
            $this->db->where('stage', $filters['stage']);
        }
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('name', $q)
                ->or_like('email', $q)
                ->or_like('mobile', $q)
                ->or_like('position', $q)
                ->group_end();
        }
        $rows = $this->db
            ->select('c.*, a.name AS assignee_name', FALSE)
            ->from(self::TABLE.' c')
            ->join('admin_users a', 'a.id = c.assigned_to', 'left')
            ->order_by('c.updated_at', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();

        if (!empty($filters['stage'])) {
            $this->db->where('stage', $filters['stage']);
        }
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('name', $q)
                ->or_like('email', $q)
                ->or_like('mobile', $q)
                ->or_like('position', $q)
                ->group_end();
        }
        $total = (int) $this->db->count_all_results(self::TABLE.' c');

        return array('rows' => $rows, 'total' => $total);
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

    public function documents_for($candidate_id)
    {
        return $this->db
            ->where('candidate_id', (int) $candidate_id)
            ->order_by('uploaded_at', 'DESC')
            ->get(self::DOC_TABLE)
            ->result_array();
    }

    public function add_document($candidate_id, $file_path, $original_name, $mime_type)
    {
        $this->db->insert(self::DOC_TABLE, array(
            'candidate_id'  => (int) $candidate_id,
            'file_path'     => $file_path,
            'original_name' => $original_name,
            'mime_type'     => $mime_type,
            'uploaded_at'   => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function interviews_for($candidate_id)
    {
        return $this->db
            ->select('i.*, a.name AS creator_name', FALSE)
            ->from(self::INT_TABLE.' i')
            ->join('admin_users a', 'a.id = i.created_by', 'left')
            ->where('i.candidate_id', (int) $candidate_id)
            ->order_by('i.scheduled_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function schedule_interview(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::INT_TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update_interview_status($id, $status)
    {
        $this->db->where('id', (int) $id)->update(self::INT_TABLE, array('status' => $status));
    }
}
