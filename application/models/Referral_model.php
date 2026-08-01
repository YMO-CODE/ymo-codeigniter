<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referral_model extends CI_Model
{
    const TABLE = 'referrals';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_by_booking($booking_id)
    {
        return $this->db->get_where(self::TABLE, array('booking_id' => (int) $booking_id))->row_array();
    }

    /**
     * Has this customer already used a referral (completed or pending)?
     */
    public function referred_user_has_active_or_completed($user_id)
    {
        return (bool) $this->db->where('referred_user_id', (int) $user_id)
            ->where_in('status', array('pending', 'completed'))
            ->limit(1)
            ->count_all_results(self::TABLE);
    }

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function mark_completed($id, $referrer_credit, $referred_credit)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'status'                 => 'completed',
            'referrer_credit_amount' => $referrer_credit,
            'referred_credit_amount' => $referred_credit,
            'completed_at'           => date('Y-m-d H:i:s'),
        ));
    }

    public function mark_cancelled($id)
    {
        $this->db->where('id', (int) $id)->where('status', 'pending')
            ->update(self::TABLE, array('status' => 'cancelled'));
    }

    public function mark_referrer_notified($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'referrer_notified_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function mark_referred_notified($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'referred_notified_at' => date('Y-m-d H:i:s'),
        ));
    }

    /** @return array{completed:int,pending:int} */
    public function stats_for_referrer($referrer_user_id)
    {
        $rows = $this->db->select('status, COUNT(*) AS cnt')
            ->where('referrer_user_id', (int) $referrer_user_id)
            ->group_by('status')
            ->get(self::TABLE)
            ->result_array();

        $stats = array('completed' => 0, 'pending' => 0);
        foreach ($rows as $row) {
            if ($row['status'] === 'completed') {
                $stats['completed'] = (int) $row['cnt'];
            } elseif ($row['status'] === 'pending') {
                $stats['pending'] = (int) $row['cnt'];
            }
        }
        return $stats;
    }

    public function find_detailed($id)
    {
        return $this->db->select('r.*,
            ru.name AS referrer_name, ru.mobile AS referrer_mobile, ru.email AS referrer_email,
            rd.name AS referred_name, rd.mobile AS referred_mobile, rd.email AS referred_email,
            b.reference AS booking_reference', FALSE)
            ->from(self::TABLE.' r')
            ->join('users ru', 'ru.id = r.referrer_user_id', 'left')
            ->join('users rd', 'rd.id = r.referred_user_id', 'left')
            ->join('bookings b', 'b.id = r.booking_id', 'left')
            ->where('r.id', (int) $id)
            ->get()->row_array();
    }
}
