<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_role_model extends CI_Model
{
    const TABLE = 'crm_roles';

    public function all()
    {
        return $this->db->order_by('sort_order', 'ASC')->order_by('label', 'ASC')->get(self::TABLE)->result_array();
    }

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_by_slug($slug)
    {
        return $this->db->get_where(self::TABLE, array('slug' => $slug))->row_array();
    }

    public function list_for_select()
    {
        return $this->db
            ->select('id, slug, label')
            ->order_by('sort_order', 'ASC')
            ->order_by('label', 'ASC')
            ->get(self::TABLE)
            ->result_array();
    }

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        if (empty($payload['slug'])) {
            $payload['slug'] = crm_slug($payload['label']);
        }
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $payload)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, $payload);
    }

    public function delete($id)
    {
        $row = $this->find($id);
        if (!$row || $row['slug'] === 'admin') {
            return FALSE;
        }
        if ($this->user_count($id) > 0) {
            return FALSE;
        }
        $this->db->where('role_id', (int) $id)->delete('crm_role_permissions');
        $this->db->where('id', (int) $id)->delete(self::TABLE);
        return TRUE;
    }

    public function user_count($role_id)
    {
        return (int) $this->db->where('crm_role_id', (int) $role_id)->count_all_results('admin_users');
    }

    public function all_with_counts()
    {
        $roles = $this->all();
        foreach ($roles as &$r) {
            $r['user_count'] = $this->user_count($r['id']);
        }
        return $roles;
    }

    /** @return int[] permission ids */
    public function permission_ids_for_role($role_id)
    {
        $rows = $this->db
            ->select('permission_id')
            ->where('role_id', (int) $role_id)
            ->get('crm_role_permissions')
            ->result_array();
        return array_map('intval', array_column($rows, 'permission_id'));
    }

    public function sync_permissions($role_id, array $perm_ids)
    {
        $this->db->where('role_id', (int) $role_id)->delete('crm_role_permissions');
        foreach ($perm_ids as $pid) {
            $pid = (int) $pid;
            if ($pid > 0) {
                $this->db->insert('crm_role_permissions', array(
                    'role_id'       => (int) $role_id,
                    'permission_id' => $pid,
                ));
            }
        }
    }

    public function list_all_permissions()
    {
        return $this->db->order_by('perm_key', 'ASC')->get('crm_permissions')->result_array();
    }

    /** Group permissions by prefix for the role form matrix. */
    public function permissions_grouped()
    {
        $groups = array();
        foreach ($this->list_all_permissions() as $p) {
            $parts = explode('.', $p['perm_key'], 2);
            $group = $parts[0];
            if (!isset($groups[$group])) {
                $groups[$group] = array();
            }
            $groups[$group][] = $p;
        }
        return $groups;
    }

    /** Map crm_role_id → legacy admin_users.role enum value. */
    public function legacy_role_for_crm_role_id($crm_role_id)
    {
        $row = $this->find($crm_role_id);
        return ($row && $row['slug'] === 'admin') ? 'admin' : 'staff';
    }
}
