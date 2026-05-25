<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vehicle_model extends CI_Model
{
    const TABLE = 'vehicles';

    public function for_user($user_id, $include_deleted = FALSE)
    {
        $this->db->select('v.*, m.name AS make_name')
                 ->from(self::TABLE.' v')
                 ->join('vehicle_makes m', 'm.id = v.make_id', 'left')
                 ->where('v.user_id', (int) $user_id)
                 ->order_by('v.created_at', 'DESC');
        if (!$include_deleted) {
            $this->db->where('v.deleted_at IS NULL', NULL, FALSE);
        }
        return $this->db->get()->result_array();
    }

    public function find_for_user($id, $user_id)
    {
        return $this->db->select('v.*, m.name AS make_name')
                        ->from(self::TABLE.' v')
                        ->join('vehicle_makes m', 'm.id = v.make_id', 'left')
                        ->where(array('v.id' => (int) $id, 'v.user_id' => (int) $user_id))
                        ->where('v.deleted_at IS NULL', NULL, FALSE)
                        ->get()->row_array();
    }

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $patch)
    {
        $patch['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update(self::TABLE, $patch);
    }

    public function soft_delete($id)
    {
        return $this->update($id, array('deleted_at' => date('Y-m-d H:i:s')));
    }

    public function makes()
    {
        return $this->db->where('is_active', 1)
                        ->order_by('sort_order')
                        ->order_by('name')
                        ->get('vehicle_makes')->result_array();
    }
}
