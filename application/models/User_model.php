<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    const TABLE = 'users';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_by_mobile($mobile)
    {
        return $this->db->get_where(self::TABLE, array('mobile' => $mobile))->row_array();
    }

    public function find_by_email($email)
    {
        return $this->db->get_where(self::TABLE, array('email' => $email))->row_array();
    }

    public function find_by_login($identifier)
    {
        $this->db->where('mobile', $identifier);
        $this->db->or_where('email', $identifier);
        return $this->db->get(self::TABLE)->row_array();
    }

    /**
     * Create a new (unverified) customer.
     *
     * @param array $payload  Pre-validated row matching the schema
     * @return int Inserted user id
     */
    public function create(array $payload)
    {
        $payload['password_hash'] = password_hash($payload['password'], PASSWORD_BCRYPT);
        unset($payload['password']);
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $patch)
    {
        $patch['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update(self::TABLE, $patch);
        return $this->db->affected_rows() > 0;
    }

    public function update_password($id, $plain)
    {
        return $this->update($id, array(
            'password_hash'      => password_hash($plain, PASSWORD_BCRYPT),
            'failed_login_count' => 0,
            'locked_until'       => NULL,
        ));
    }

    public function mark_mobile_verified($id)
    {
        return $this->update($id, array('mobile_verified_at' => date('Y-m-d H:i:s')));
    }

    public function mark_email_verified($id)
    {
        return $this->update($id, array('email_verified_at' => date('Y-m-d H:i:s')));
    }

    public function record_login($id)
    {
        return $this->update($id, array(
            'last_login_at'      => date('Y-m-d H:i:s'),
            'failed_login_count' => 0,
            'locked_until'       => NULL,
        ));
    }

    /**
     * Increment the failed login counter and lock the account once the
     * configured threshold is reached.
     *
     * @return array Updated counters: ['failed_login_count' => int, 'locked_until' => string|null]
     */
    public function record_failed_login($id)
    {
        $ci      = &get_instance();
        $limit   = (int) $ci->config->item('auth_login_attempts');
        $minutes = (int) $ci->config->item('auth_lockout_minutes');

        $row = $this->find($id);
        if (!$row) {
            return array('failed_login_count' => 0, 'locked_until' => NULL);
        }

        $count = (int) $row['failed_login_count'] + 1;
        $patch = array('failed_login_count' => $count);
        if ($count >= $limit) {
            $patch['locked_until'] = date('Y-m-d H:i:s', time() + $minutes * 60);
        }
        $this->update($id, $patch);
        return $patch + array('locked_until' => NULL);
    }

    public function is_locked(array $user)
    {
        return !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
    }

    public function paginate($limit, $offset, $search = NULL)
    {
        if ($search) {
            $like = '%'.$this->db->escape_like_str($search).'%';
            $this->db->where("(name LIKE '$like' OR email LIKE '$like' OR mobile LIKE '$like' OR city LIKE '$like')", NULL, FALSE);
        }
        $this->db->order_by('created_at', 'DESC');
        $rows = $this->db->get(self::TABLE, $limit, $offset)->result_array();

        if ($search) {
            $like = '%'.$this->db->escape_like_str($search).'%';
            $this->db->where("(name LIKE '$like' OR email LIKE '$like' OR mobile LIKE '$like' OR city LIKE '$like')", NULL, FALSE);
        }
        $total = $this->db->count_all_results(self::TABLE);

        return array('rows' => $rows, 'total' => (int) $total);
    }
}
