<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model
{
    const TABLE = 'admin_users';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_detailed($id)
    {
        return $this->db
            ->select('a.*, r.label AS crm_role_label, r.slug AS crm_role_slug', FALSE)
            ->from(self::TABLE.' a')
            ->join('crm_roles r', 'r.id = a.crm_role_id', 'left')
            ->where('a.id', (int) $id)
            ->get()
            ->row_array();
    }

    public function find_by_email($email)
    {
        return $this->db->get_where(self::TABLE, array('email' => strtolower($email)))->row_array();
    }

    public function paginate($q, $limit, $offset)
    {
        if ($q) {
            $like = $this->db->escape_like_str($q);
            $this->db->group_start()
                ->like('a.name', $like)
                ->or_like('a.email', $like)
                ->group_end();
        }
        $rows = $this->db
            ->select('a.*, r.label AS crm_role_label', FALSE)
            ->from(self::TABLE.' a')
            ->join('crm_roles r', 'r.id = a.crm_role_id', 'left')
            ->order_by('a.name', 'ASC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();

        if ($q) {
            $like = $this->db->escape_like_str($q);
            $this->db->group_start()
                ->like('a.name', $like)
                ->or_like('a.email', $like)
                ->group_end();
        }
        $this->db->from(self::TABLE.' a');
        $total = (int) $this->db->count_all_results();

        return array('rows' => $rows, 'total' => $total);
    }

    public function create(array $payload)
    {
        $payload['password_hash'] = password_hash($payload['password'], PASSWORD_BCRYPT);
        unset($payload['password']);
        $payload['email']      = strtolower($payload['email']);
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $payload)
    {
        unset($payload['password'], $payload['password_hash']);
        $payload['updated_at'] = date('Y-m-d H:i:s');
        if (isset($payload['email'])) {
            $payload['email'] = strtolower($payload['email']);
        }
        $this->db->where('id', (int) $id)->update(self::TABLE, $payload);
    }

    public function update_password($id, $plain)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'password_hash'      => password_hash($plain, PASSWORD_BCRYPT),
            'failed_login_count' => 0,
            'locked_until'       => NULL,
            'updated_at'         => date('Y-m-d H:i:s'),
        ));
    }

    public function record_login($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'last_login_at'      => date('Y-m-d H:i:s'),
            'failed_login_count' => 0,
            'locked_until'       => NULL,
        ));
    }

    public function record_failed_login($id)
    {
        $ci      = &get_instance();
        $limit   = (int) $ci->config->item('auth_login_attempts');
        $minutes = (int) $ci->config->item('auth_lockout_minutes');

        $row = $this->find($id);
        if (!$row) { return; }
        $count = (int) $row['failed_login_count'] + 1;
        $patch = array('failed_login_count' => $count);
        if ($count >= $limit) {
            $patch['locked_until'] = date('Y-m-d H:i:s', time() + $minutes * 60);
        }
        $this->db->where('id', (int) $id)->update(self::TABLE, $patch);
    }

    public function is_locked(array $admin)
    {
        return !empty($admin['locked_until']) && strtotime($admin['locked_until']) > time();
    }

    public function count_active()
    {
        return (int) $this->db->where('is_active', 1)->count_all_results(self::TABLE);
    }

    /** @return array[] Active admins for lead assignment dropdowns. */
    public function list_active()
    {
        return $this->db
            ->select('id, name, email, crm_role_id')
            ->where('is_active', 1)
            ->order_by('name', 'ASC')
            ->get(self::TABLE)
            ->result_array();
    }
}
