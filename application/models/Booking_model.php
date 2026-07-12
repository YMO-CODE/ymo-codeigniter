<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_model extends CI_Model
{
    const TABLE = 'bookings';

    /** Statuses that block a new booking for the same vehicle. */
    const ACTIVE_VEHICLE_STATUSES = array('pending', 'confirmed', 'in_progress');

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_for_user($id, $user_id)
    {
        return $this->db->where(array('id' => (int) $id, 'user_id' => (int) $user_id))
                        ->get(self::TABLE)->row_array();
    }

    public function find_detailed($id)
    {
        return $this->db->select('b.*, u.name AS user_name, u.mobile AS user_mobile, u.email AS user_email,
                                  v.variant AS vehicle_variant, v.vehicle_number, v.image_path AS vehicle_image,
                                  m.name AS vehicle_make, p.name AS package_name, p.price AS package_price', FALSE)
                        ->from(self::TABLE.' b')
                        ->join('users u',           'u.id = b.user_id', 'left')
                        ->join('vehicles v',        'v.id = b.vehicle_id', 'left')
                        ->join('vehicle_makes m',   'm.id = v.make_id', 'left')
                        ->join('service_packages p','p.id = b.package_id', 'left')
                        ->where('b.id', (int) $id)
                        ->get()->row_array();
    }

    public function for_user($user_id, $limit = NULL, $offset = 0)
    {
        $this->db->select('b.*, p.name AS package_name, p.price AS package_price,
                           v.variant AS vehicle_variant, v.vehicle_number, m.name AS vehicle_make', FALSE)
                 ->from(self::TABLE.' b')
                 ->join('service_packages p','p.id = b.package_id', 'left')
                 ->join('vehicles v',        'v.id = b.vehicle_id', 'left')
                 ->join('vehicle_makes m',   'm.id = v.make_id', 'left')
                 ->where('b.user_id', (int) $user_id)
                 ->order_by('b.created_at', 'DESC');
        if ($limit) {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get()->result_array();
    }

    public function count_for_user($user_id)
    {
        return (int) $this->db->where('user_id', (int) $user_id)->count_all_results(self::TABLE);
    }

    /**
     * Latest open booking for a vehicle (pending / confirmed / in progress).
     *
     * @return array|null
     */
    public function find_active_for_vehicle($vehicle_id, $user_id = NULL)
    {
        $this->db->where('vehicle_id', (int) $vehicle_id)
            ->where_in('status', self::ACTIVE_VEHICLE_STATUSES);
        if ($user_id !== NULL) {
            $this->db->where('user_id', (int) $user_id);
        }
        return $this->db->order_by('created_at', 'DESC')
            ->limit(1)
            ->get(self::TABLE)
            ->row_array();
    }

    /**
     * Map vehicle_id => active booking row (most recent per vehicle).
     *
     * @param int[] $vehicle_ids
     * @return array<int, array>
     */
    public function map_active_for_vehicles(array $vehicle_ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $vehicle_ids))));
        if (empty($ids)) {
            return array();
        }

        $rows = $this->db->select('vehicle_id, reference, status, created_at')
            ->where_in('vehicle_id', $ids)
            ->where_in('status', self::ACTIVE_VEHICLE_STATUSES)
            ->order_by('created_at', 'DESC')
            ->get(self::TABLE)
            ->result_array();

        $map = array();
        foreach ($rows as $row) {
            $vid = (int) $row['vehicle_id'];
            if (!isset($map[$vid])) {
                $map[$vid] = $row;
            }
        }
        return $map;
    }

    public function create(array $payload)
    {
        $payload['reference']  = $this->_make_reference();
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $payload);
        $id = (int) $this->db->insert_id();
        return $id;
    }

    public function update_status($id, $status, $reason = NULL)
    {
        $patch = array(
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        );
        if ($status === 'completed') {
            $patch['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'cancelled' && $reason) {
            $patch['cancelled_reason'] = $reason;
        }
        $this->db->where('id', (int) $id)->update(self::TABLE, $patch);
    }

    /**
     * Admin filtering / pagination.
     */
    public function paginate(array $filters, $limit, $offset)
    {
        $this->_apply_filters($filters);
        $this->db->select('b.*, u.name AS user_name, u.mobile AS user_mobile,
                           v.vehicle_number, p.name AS package_name', FALSE)
                 ->from(self::TABLE.' b')
                 ->join('users u',           'u.id = b.user_id', 'left')
                 ->join('vehicles v',        'v.id = b.vehicle_id', 'left')
                 ->join('service_packages p','p.id = b.package_id', 'left')
                 ->order_by('b.created_at', 'DESC')
                 ->limit((int) $limit, (int) $offset);
        $rows = $this->db->get()->result_array();

        $this->_apply_filters($filters);
        $this->db->from(self::TABLE.' b')
                 ->join('users u',           'u.id = b.user_id', 'left');
        $total = $this->db->count_all_results();

        return array('rows' => $rows, 'total' => (int) $total);
    }

    public function counts_summary()
    {
        $today = date('Y-m-d');
        return array(
            'today'          => (int) $this->db->where('DATE(created_at)', $today)->count_all_results(self::TABLE),
            'pending'        => (int) $this->db->where('status', 'pending')->count_all_results(self::TABLE),
            'in_progress'    => (int) $this->db->where('status', 'in_progress')->count_all_results(self::TABLE),
            'completed_30d'  => (int) $this->db->where('status', 'completed')
                                                ->where('completed_at >=', date('Y-m-d', strtotime('-30 days')))
                                                ->count_all_results(self::TABLE),
        );
    }

    // --- internals ---------------------------------------------------------

    protected function _apply_filters(array $filters)
    {
        if (!empty($filters['status']))     { $this->db->where('b.status', $filters['status']); }
        if (!empty($filters['package_id'])) { $this->db->where('b.package_id', (int) $filters['package_id']); }
        if (!empty($filters['user_id']))    { $this->db->where('b.user_id', (int) $filters['user_id']); }
        if (!empty($filters['from']))       { $this->db->where('b.created_at >=', $filters['from'].' 00:00:00'); }
        if (!empty($filters['to']))         { $this->db->where('b.created_at <=', $filters['to'].' 23:59:59'); }
        if (!empty($filters['q'])) {
            $like = '%'.$this->db->escape_like_str($filters['q']).'%';
            $this->db->where("(b.reference LIKE '$like' OR u.name LIKE '$like' OR u.mobile LIKE '$like')", NULL, FALSE);
        }
    }

    protected function _make_reference()
    {
        $prefix = 'YMO-'.date('Y').'-';
        // Find last reference, increment numerically. Race-tolerant enough for our volume.
        $row = $this->db->select('reference')
                        ->like('reference', $prefix, 'after')
                        ->order_by('id', 'DESC')->limit(1)
                        ->get(self::TABLE)->row_array();
        $next = $row ? ((int) substr($row['reference'], strlen($prefix)) + 1) : 1;
        return $prefix.str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
