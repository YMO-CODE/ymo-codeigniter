<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Crm_booking_service — ties bookings, website accounts, customers and leads
 * together (CRM v6).
 *
 *  - Admin "Book service" (from a lead, a customer or blank): finds or
 *    creates the website account by mobile, adds the car if needed, creates
 *    the booking, links the customer record and marks the lead Booked.
 *  - Website bookings: link_booking() is called after the customer books
 *    online so a matching open lead (same mobile) is marked Booked too.
 */
class Crm_booking_service
{
    /** @var CI_Controller */
    protected $CI;

    /** @var string|null */
    protected $last_error = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model(array('user_model', 'vehicle_model', 'booking_model', 'package_model'));
    }

    public function last_error()
    {
        return $this->last_error;
    }

    /** Website account for a mobile (any format), or NULL. */
    public function find_user_by_mobile($mobile)
    {
        $m = crm_mobile10($mobile);
        if (strlen($m) !== 10) {
            return NULL;
        }
        return $this->CI->user_model->find_by_mobile($m);
    }

    /**
     * Create a website account for a phone/walk-in customer. They can set a
     * password later with "Forgot password" (OTP to the same mobile).
     *
     * @return int User id (0 on failure, see last_error())
     */
    public function create_customer($name, $mobile, $email = '', $area = '', $city = '')
    {
        $m = crm_mobile10($mobile);
        if (!preg_match('/^[6-9]\d{9}$/', $m)) {
            $this->last_error = 'Enter a valid 10-digit mobile number.';
            return 0;
        }
        if ($this->CI->user_model->find_by_mobile($m)) {
            $this->last_error = 'An account with this mobile already exists.';
            return 0;
        }
        $email = strtolower(trim((string) $email));
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || $this->CI->user_model->find_by_email($email))) {
            $email = ''; // invalid or used by another account → fall back to placeholder
        }
        $id = $this->CI->user_model->create(array(
            'name'               => trim((string) $name) !== '' ? trim((string) $name) : 'Customer '.$m,
            'mobile'             => $m,
            'email'              => $email !== '' ? $email : ymo_placeholder_email($m),
            'area'               => trim((string) $area),
            'city'               => trim((string) $city) !== '' ? trim((string) $city) : 'Pune',
            'password'           => bin2hex(random_bytes(12)),
            'mobile_verified_at' => date('Y-m-d H:i:s'), // confirmed by staff on the phone
        ));
        if (!$id) {
            $this->last_error = 'Could not create the customer account.';
        }
        return $id;
    }

    /**
     * @param array $data user_id, vehicle_id | (make_id, variant, vehicle_number), package_id,
     *                    preferred_date, remarks, status (pending|confirmed), lead_id, admin_id
     * @return int Booking id (0 on failure)
     */
    public function create_booking(array $data)
    {
        $this->last_error = NULL;
        $user_id = (int) ($data['user_id'] ?? 0);
        $package = $this->CI->package_model->find((int) ($data['package_id'] ?? 0));
        if (!$user_id || !$package) {
            $this->last_error = 'Pick a customer and a package.';
            return 0;
        }

        $vehicle_id = (int) ($data['vehicle_id'] ?? 0);
        if ($vehicle_id) {
            if (!$this->CI->vehicle_model->find_for_user($vehicle_id, $user_id)) {
                $this->last_error = 'That car does not belong to this customer.';
                return 0;
            }
        } else {
            $number = strtoupper(preg_replace('/\s+/', '', (string) ($data['vehicle_number'] ?? '')));
            if (empty($data['make_id']) || trim((string) ($data['variant'] ?? '')) === '' || $number === '') {
                $this->last_error = 'Add the car: make, model and registration number.';
                return 0;
            }
            $vehicle_id = $this->CI->vehicle_model->create(array(
                'user_id'        => $user_id,
                'make_id'        => (int) $data['make_id'],
                'variant'        => trim((string) $data['variant']),
                'vehicle_number' => $number,
            ));
        }

        $active = $this->CI->booking_model->find_active_for_vehicle($vehicle_id, $user_id);
        if ($active) {
            $this->last_error = 'This car already has an open booking (#'.$active['reference'].').';
            return 0;
        }

        $status = in_array($data['status'] ?? '', array('pending', 'confirmed'), TRUE) ? $data['status'] : 'confirmed';
        $booking_id = $this->CI->booking_model->create(array(
            'user_id'          => $user_id,
            'vehicle_id'       => $vehicle_id,
            'package_id'       => (int) $package['id'],
            'package_snapshot' => $this->CI->package_model->snapshot($package),
            'remarks'          => trim((string) ($data['remarks'] ?? '')) ?: NULL,
            'preferred_date'   => !empty($data['preferred_date']) ? date('Y-m-d', strtotime($data['preferred_date'])) : NULL,
            'status'           => $status,
        ));
        if (!$booking_id) {
            $this->last_error = 'Could not save the booking.';
            return 0;
        }

        $this->link_booking($booking_id, !empty($data['lead_id']) ? (int) $data['lead_id'] : NULL,
            !empty($data['admin_id']) ? (int) $data['admin_id'] : NULL);
        return $booking_id;
    }

    /**
     * Link a booking to its customer record and lead. With no $lead_id, the
     * most recent open lead with the same mobile is used (website bookings).
     *
     * @return array{contact_id:int,lead_id:?int}
     */
    public function link_booking($booking_id, $lead_id = NULL, $admin_id = NULL)
    {
        $out = array('contact_id' => 0, 'lead_id' => NULL);
        if (!$this->CI->db->table_exists('crm_leads')) {
            return $out;
        }
        $booking = $this->CI->booking_model->find_detailed($booking_id);
        if (!$booking) {
            return $out;
        }
        $this->CI->load->model(array('crm_contact_model', 'crm_lead_model', 'crm_lead_activity_model', 'crm_task_model'));

        $lead = $lead_id
            ? $this->CI->crm_lead_model->find($lead_id)
            : $this->CI->crm_lead_model->find_open_by_mobile($booking['user_mobile']);

        $contact_id = $this->CI->crm_contact_model->ensure_for_user((int) $booking['user_id'], $lead ? (int) $lead['id'] : NULL);
        $out['contact_id'] = $contact_id;

        if ($lead) {
            $this->CI->crm_lead_model->mark_booked((int) $lead['id'], (int) $booking_id, (int) $booking['user_id'], $contact_id ?: NULL);
            $this->CI->crm_task_model->close_lead_followups((int) $lead['id']);
            $this->CI->crm_lead_activity_model->add((int) $lead['id'], $admin_id, 'status_change',
                'Booked: #'.$booking['reference'].' - '.$booking['package_name'],
                array('booking_id' => (int) $booking_id, 'stage' => 'booked'));
            $out['lead_id'] = (int) $lead['id'];
        }
        return $out;
    }
}
