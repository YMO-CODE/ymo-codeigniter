<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_integration_model extends CI_Model
{
    const TABLE = 'crm_integration_logs';

    public function log($provider, $event_type, array $payload = array(), $direction = 'inbound', $http_status = NULL)
    {
        $this->db->insert(self::TABLE, array(
            'provider'     => $provider,
            'direction'    => $direction,
            'event_type'   => $event_type,
            'http_status'  => $http_status,
            'payload_json' => $payload ? json_encode($payload) : NULL,
            'created_at'   => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function recent($limit = 50)
    {
        return $this->db
            ->order_by('created_at', 'DESC')
            ->limit((int) $limit)
            ->get(self::TABLE)
            ->result_array();
    }
}
