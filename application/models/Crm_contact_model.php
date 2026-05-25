<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_contact_model extends CI_Model
{
    const TABLE = 'crm_contacts';

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
            ->select('c.*, u.name AS user_name, l.name AS lead_name', FALSE)
            ->from(self::TABLE.' c')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->join('crm_leads l', 'l.id = c.converted_from_lead_id', 'left')
            ->where('c.id', (int) $id)
            ->where('c.deleted_at IS NULL', NULL, FALSE)
            ->get()
            ->row_array();
    }

    public function find_by_lead($lead_id)
    {
        return $this->db
            ->where('converted_from_lead_id', (int) $lead_id)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->get(self::TABLE)
            ->row_array();
    }

    public function paginate(array $filters, $limit, $offset)
    {
        $this->_apply_filters($filters);
        $rows = $this->db
            ->select('c.*', FALSE)
            ->from(self::TABLE.' c')
            ->where('c.deleted_at IS NULL', NULL, FALSE)
            ->order_by('c.updated_at', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();

        $this->_apply_filters($filters);
        $this->db->from(self::TABLE.' c')->where('c.deleted_at IS NULL', NULL, FALSE);
        $total = (int) $this->db->count_all_results();

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

    public function soft_delete($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function create_from_lead(array $lead)
    {
        return $this->create(array(
            'name'                   => $lead['name'],
            'mobile'                 => $lead['mobile'],
            'email'                  => $lead['email'],
            'company'                => $lead['company'],
            'notes'                  => $lead['message'],
            'converted_from_lead_id' => (int) $lead['id'],
        ));
    }

    /** All contacts for CSV export. */
    public function export_all(array $filters = array())
    {
        $this->_apply_filters($filters);
        return $this->db
            ->select('c.*')
            ->from(self::TABLE.' c')
            ->where('c.deleted_at IS NULL', NULL, FALSE)
            ->order_by('c.name', 'ASC')
            ->get()
            ->result_array();
    }

    protected function _apply_filters(array $filters)
    {
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('c.name', $q)
                ->or_like('c.mobile', $q)
                ->or_like('c.email', $q)
                ->or_like('c.company', $q)
                ->group_end();
        }
        if (!empty($filters['tag_id'])) {
            $this->db->join('crm_contact_tags ct', 'ct.contact_id = c.id')
                ->where('ct.tag_id', (int) $filters['tag_id']);
        }
    }
}
