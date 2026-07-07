<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_lead_activity_model extends CI_Model
{
    const TABLE = 'crm_lead_activities';

    public function for_lead($lead_id, $limit = 100)
    {
        return $this->db
            ->select('a.*, u.name AS admin_name', FALSE)
            ->from(self::TABLE.' a')
            ->join('admin_users u', 'u.id = a.admin_id', 'left')
            ->where('a.lead_id', (int) $lead_id)
            ->order_by('a.created_at', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    public function add($lead_id, $admin_id, $type, $body, array $meta = array())
    {
        $this->db->insert(self::TABLE, array(
            'lead_id'    => (int) $lead_id,
            'admin_id'   => $admin_id ? (int) $admin_id : NULL,
            'type'       => $type,
            'body'       => $body,
            'meta_json'  => $meta ? json_encode($meta) : NULL,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    /** Skip duplicate webhook deliveries for the same external message id. */
    public function exists_for_external_message($provider, $message_id)
    {
        if (!$message_id || !$provider) {
            return FALSE;
        }
        return (bool) $this->db
            ->where('meta_json LIKE', '%"message_id":"'.$this->db->escape_str($message_id).'"%', NULL, FALSE)
            ->where('meta_json LIKE', '%"provider":"'.$this->db->escape_str($provider).'"%', NULL, FALSE)
            ->limit(1)
            ->count_all_results(self::TABLE);
    }
}
