<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Maps admin_users + crm_roles to permission keys for crm_can().
 */
class Crm_rbac_model extends CI_Model
{
    public function crm_role_slug_for_admin(array $admin)
    {
        if (!$this->db->table_exists('crm_roles')) {
            return (!empty($admin['role']) && $admin['role'] === 'admin') ? 'admin' : 'staff';
        }
        $rid = isset($admin['crm_role_id']) ? (int) $admin['crm_role_id'] : 0;
        if ($rid > 0) {
            $row = $this->db->get_where('crm_roles', array('id' => $rid))->row_array();

            return $row ? $row['slug'] : '';
        }

        return (!empty($admin['role']) && $admin['role'] === 'admin') ? 'admin' : 'sales_executive';
    }

    /**
     * @return string[] permission keys or ['*'] for superuser
     */
    public function permission_keys_for_admin(array $admin)
    {
        if (!$this->db->table_exists('crm_permissions')) {
            return array('*');
        }

        $slug = $this->crm_role_slug_for_admin($admin);
        if ($slug === 'admin') {
            $q = $this->db->select('perm_key')->get('crm_permissions');

            return array_column($q->result_array(), 'perm_key');
        }

        $role_id = isset($admin['crm_role_id']) ? (int) $admin['crm_role_id'] : 0;
        if ($role_id <= 0) {
            $r = $this->db->get_where('crm_roles', array('slug' => $slug))->row_array();
            $role_id = $r ? (int) $r['id'] : 0;
        }
        if ($role_id <= 0) {
            return array('leads.view');
        }

        $rows = $this->db
            ->select('p.perm_key')
            ->from('crm_role_permissions rp')
            ->join('crm_permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $role_id)
            ->get()
            ->result_array();

        return array_column($rows, 'perm_key');
    }
}
