<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reminder_model extends CI_Model
{
    const TABLE = 'booking_reminders';

    public function create(array $payload)
    {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function for_booking($booking_id, $type = NULL)
    {
        $this->db->where('booking_id', (int) $booking_id);
        if ($type) {
            $this->db->where('type', $type);
        }
        return $this->db->order_by('id', 'DESC')->get(self::TABLE)->result_array();
    }

    public function find_pending_review_for_booking($booking_id)
    {
        return $this->db->where(array(
            'booking_id' => (int) $booking_id,
            'type'       => 'review',
            'status'     => 'pending',
        ))->get(self::TABLE)->row_array();
    }

    /**
     * Pull next-service reminders that are due. Used by the cron worker.
     */
    public function due_next_service($limit)
    {
        return $this->db->select('r.*, b.user_id, b.reference, b.preferred_date, b.created_at AS booking_created_at,
                                  u.name AS user_name, u.mobile AS user_mobile, u.email AS user_email,
                                  v.vehicle_number, v.variant AS vehicle_variant, m.name AS vehicle_make,
                                  p.name AS package_name', FALSE)
                        ->from(self::TABLE.' r')
                        ->join('bookings b',         'b.id = r.booking_id', 'inner')
                        ->join('users u',            'u.id = b.user_id', 'inner')
                        ->join('vehicles v',         'v.id = b.vehicle_id', 'left')
                        ->join('vehicle_makes m',    'm.id = v.make_id', 'left')
                        ->join('service_packages p', 'p.id = b.package_id', 'left')
                        ->where('r.status', 'pending')
                        ->where('r.type',   'next_service')
                        ->where('r.scheduled_at <=', date('Y-m-d H:i:s'))
                        ->order_by('r.scheduled_at', 'ASC')
                        ->limit((int) $limit)
                        ->get()->result_array();
    }

    public function mark_sent($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'status'  => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function mark_failed($id, $error)
    {
        $this->db->set('attempts', 'attempts + 1', FALSE)
                 ->where('id', (int) $id)
                 ->update(self::TABLE, array(
                     'status'     => 'failed',
                     'last_error' => substr($error, 0, 500),
                 ));
    }

    public function reset_to_pending($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array('status' => 'pending'));
    }

    public function due_this_week_count()
    {
        return (int) $this->db->where('status', 'pending')
                              ->where('type', 'next_service')
                              ->where('scheduled_at <=', date('Y-m-d', strtotime('+7 days')))
                              ->count_all_results(self::TABLE);
    }
}
