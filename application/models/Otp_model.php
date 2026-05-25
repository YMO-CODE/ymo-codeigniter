<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Persists OTP codes (hashed) plus their lifecycle. The code itself is never
 * stored in plaintext so a leak of this table won't leak codes.
 */
class Otp_model extends CI_Model
{
    const TABLE = 'otp_codes';

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    /**
     * Find the most recent unconsumed OTP for a (channel, destination, purpose).
     */
    public function find_active($channel, $destination, $purpose)
    {
        return $this->db
            ->where(array(
                'channel'     => $channel,
                'destination' => $destination,
                'purpose'     => $purpose,
                'used_at'     => NULL,
            ))
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->order_by('id', 'DESC')
            ->get(self::TABLE)
            ->row_array();
    }

    public function increment_attempt($id)
    {
        $this->db->set('attempts', 'attempts + 1', FALSE)
                 ->where('id', (int) $id)
                 ->update(self::TABLE);
    }

    public function mark_used($id)
    {
        $this->db->where('id', (int) $id)
                 ->update(self::TABLE, array('used_at' => date('Y-m-d H:i:s')));
    }

    /**
     * Counts active OTP requests for rate-limiting purposes within the last $seconds.
     */
    public function count_recent($filters, $seconds = 3600)
    {
        $since = date('Y-m-d H:i:s', time() - $seconds);
        return (int) $this->db->where($filters)
            ->where('created_at >=', $since)
            ->count_all_results(self::TABLE);
    }

    public function purge_expired()
    {
        return $this->db->where('expires_at <', date('Y-m-d H:i:s', strtotime('-1 day')))
                        ->delete(self::TABLE);
    }
}
