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

    public function find_by_mobile($mobile)
    {
        $mobile = preg_replace('/\D/', '', (string) $mobile);
        if ($mobile === '') {
            return NULL;
        }
        return $this->db
            ->where('mobile', $mobile)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->get(self::TABLE)
            ->row_array();
    }

    public function find_by_email($email)
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return NULL;
        }
        return $this->db
            ->where('email', $email)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->get(self::TABLE)
            ->row_array();
    }

    /**
     * Find existing contact by mobile, then email.
     *
     * @return array|null
     */
    public function find_existing(array $row)
    {
        if (!empty($row['mobile'])) {
            $found = $this->find_by_mobile($row['mobile']);
            if ($found) {
                return $found;
            }
        }
        if (!empty($row['email'])) {
            return $this->find_by_email($row['email']);
        }
        return NULL;
    }

    /**
     * Import one CSV row. Policy: skip | update | merge_notes.
     *
     * @return array{action:string,id:int|null,message:string}
     */
    public function import_row(array $row, $policy = 'merge_notes')
    {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return array('action' => 'error', 'id' => NULL, 'message' => 'Name is required');
        }

        $payload = array(
            'name'    => mb_substr($name, 0, 120),
            'mobile'  => preg_replace('/\D/', '', (string) ($row['mobile'] ?? '')),
            'email'   => strtolower(trim((string) ($row['email'] ?? ''))),
            'company' => trim((string) ($row['company'] ?? '')) ?: NULL,
            'notes'   => trim((string) ($row['notes'] ?? '')) ?: NULL,
        );

        $existing = $this->find_existing($payload);
        if ($existing) {
            if ($policy === 'skip') {
                return array('action' => 'skipped', 'id' => (int) $existing['id'], 'message' => 'Duplicate');
            }
            if ($policy === 'update') {
                $this->update($existing['id'], $payload);
                $this->_link_user_if_exists($existing['id'], $payload);
                return array('action' => 'updated', 'id' => (int) $existing['id'], 'message' => 'Updated');
            }
            // merge_notes (default)
            $merged = $payload;
            $old_notes = trim((string) ($existing['notes'] ?? ''));
            $new_notes = trim((string) ($payload['notes'] ?? ''));
            if ($new_notes !== '') {
                if ($old_notes === '') {
                    $merged['notes'] = $new_notes;
                } elseif (strpos($old_notes, $new_notes) === FALSE) {
                    $merged['notes'] = $old_notes."\n".$new_notes;
                } else {
                    $merged['notes'] = $old_notes;
                }
            } else {
                $merged['notes'] = $old_notes ?: NULL;
            }
            if (strlen($payload['name']) > strlen($existing['name'])) {
                $merged['name'] = $payload['name'];
            } else {
                $merged['name'] = $existing['name'];
            }
            if ($payload['email'] === '') {
                $merged['email'] = $existing['email'];
            }
            if ($payload['mobile'] === '') {
                $merged['mobile'] = $existing['mobile'];
            }
            if (empty($payload['company']) && !empty($existing['company'])) {
                $merged['company'] = $existing['company'];
            }
            $this->update($existing['id'], $merged);
            $this->_link_user_if_exists($existing['id'], $merged);
            return array('action' => 'merged', 'id' => (int) $existing['id'], 'message' => 'Merged notes');
        }

        $id = $this->create($payload);
        $this->_link_user_if_exists($id, $payload);
        return array('action' => 'created', 'id' => $id, 'message' => 'Created');
    }

    protected function _link_user_if_exists($contact_id, array $payload)
    {
        $contact = $this->find($contact_id);
        if (!$contact || !empty($contact['user_id'])) {
            return;
        }
        $this->load->model('user_model');
        $user = NULL;
        if (!empty($payload['mobile'])) {
            $user = $this->user_model->find_by_mobile($payload['mobile']);
        }
        if (!$user && !empty($payload['email'])) {
            $user = $this->user_model->find_by_email($payload['email']);
        }
        if ($user) {
            $this->update($contact_id, array('user_id' => (int) $user['id']));
        }
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
