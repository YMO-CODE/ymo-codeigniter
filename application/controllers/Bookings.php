<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public booking entry-points + multi-step flow.
 *
 *   /packages                 list of active packages (public)
 *   /book/<slug>              start a booking - sets session draft, sends to step 2
 *   /booking/vehicle          step 2 - pick (or add) a vehicle (auth)
 *   /booking/details          step 3 - remarks + preferred date    (auth)
 *   /booking/confirm          step 4 - review summary               (auth)
 *   /booking/place            POST: persist booking + dispatch notifications
 *   /booking/success/<id>     post-create confirmation page
 *   /booking/rebook/<id>      copy a past booking into the wizard
 *
 * Wizard state lives in the session under the key `booking_draft`.
 */
class Bookings extends MY_Controller
{
    const DRAFT_KEY = 'booking_draft';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('package_model', 'vehicle_model', 'booking_model', 'reminder_model'));
    }

    // --- Step 0: public packages listing -----------------------------------

    public function packages()
    {
        $has_vehicles = TRUE;
        if (!empty($this->user['id'])) {
            $has_vehicles = !empty($this->vehicle_model->for_user($this->user['id']));
        }

        $this->render('bookings/packages', array(
            'title'        => 'Service packages',
            'packages'     => $this->package_model->list_active_with_features(),
            'has_vehicles' => $has_vehicles,
        ));
    }

    // --- Step 1: start a booking from a package slug -----------------------

    public function start($slug)
    {
        $package = $this->package_model->find_by_slug($slug);
        if (!$package) {
            show_404();
        }
        $this->_set_draft(array('package_id' => (int) $package['id']));

        if (empty($this->user) || empty($this->user['id'])) {
            $this->flash('info', 'Sign in to continue your booking — or use the quick book form if you prefer not to create an account.');
            redirect(site_url('login?next='.urlencode('book/'.$slug)));
        }
        if (!ymo_user_is_verified($this->user)) {
            $this->flash('warning', 'Please verify your mobile to continue.');
            redirect(site_url('signup/verify'));
        }

        if (empty($this->vehicle_model->for_user($this->user['id']))) {
            $this->flash('info', 'Add your vehicle first - then pick this package to continue.');
            redirect(site_url('vehicles/new?next='.urlencode('book/'.$slug)));
        }

        redirect(site_url('booking/vehicle'));
    }

    // --- Step 2: vehicle picker --------------------------------------------

    public function vehicle()
    {
        $this->_require_customer();
        $draft = $this->_get_draft();
        if (empty($draft['package_id'])) {
            $this->flash('error', 'Pick a package first.');
            redirect(site_url('packages'));
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('vehicle_id', 'Vehicle', 'required|integer');
            if ($this->form_validation->run()) {
                $vehicle = $this->vehicle_model->find_for_user($this->input->post('vehicle_id'), $this->user['id']);
                if (!$vehicle) {
                    $this->flash('error', 'Pick a vehicle from the list.');
                } elseif ($msg = $this->_vehicle_unavailable_message($vehicle['id'])) {
                    $this->flash('error', $msg);
                } else {
                    $this->_set_draft(array_merge($draft, array('vehicle_id' => (int) $vehicle['id'])));
                    redirect(site_url('booking/details'));
                }
            }
        }

        $vehicles = $this->vehicle_model->for_user($this->user['id']);
        $vehicle_ids = array_column($vehicles, 'id');

        $this->render('bookings/vehicle', array(
            'title'           => 'Pick your car',
            'package'         => $this->package_model->find($draft['package_id']),
            'vehicles'        => $vehicles,
            'active_bookings' => $this->booking_model->map_active_for_vehicles($vehicle_ids),
            'draft'           => $draft,
            'step'            => 2,
        ));
    }

    // --- Step 3: details ---------------------------------------------------

    public function details()
    {
        $this->_require_customer();
        $draft = $this->_get_draft();
        if (empty($draft['package_id']) || empty($draft['vehicle_id'])) {
            redirect(site_url('booking/vehicle'));
        }
        if ($msg = $this->_vehicle_unavailable_message($draft['vehicle_id'])) {
            $this->flash('error', $msg);
            redirect(site_url('booking/vehicle'));
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('preferred_date', 'Preferred date', 'trim|callback__valid_future_date');
            $this->form_validation->set_rules('remarks',        'Remarks',        'trim|max_length[2000]');
            $this->form_validation->set_rules('referral_code',  'Referral code',  'trim|max_length[12]|callback__valid_referral_code');
            if ($this->form_validation->run()) {
                $patch = array(
                    'preferred_date' => $this->input->post('preferred_date') ?: NULL,
                    'remarks'        => trim($this->input->post('remarks')),
                );
                $ref = strtoupper(trim((string) $this->input->post('referral_code')));
                if ($ref !== '') {
                    $patch['referral_code'] = $ref;
                }
                $merged = array_merge($draft, $patch);
                if ($ref === '') {
                    unset($merged['referral_code']);
                }
                $this->_set_draft($merged);
                redirect(site_url('booking/confirm'));
            }
        }

        $this->render('bookings/details', array(
            'title'            => 'Booking details',
            'package'          => $this->package_model->find($draft['package_id']),
            'vehicle'          => $this->vehicle_model->find_for_user($draft['vehicle_id'], $this->user['id']),
            'draft'            => $draft,
            'min_date'         => date('Y-m-d'),
            'step'             => 3,
            'referral_enabled' => (bool) $this->config->item('referral_enabled'),
            'referred_credit'  => (float) $this->config->item('referral_credit_referred'),
        ));
    }

    public function _valid_future_date($value)
    {
        if (empty($value)) { return TRUE; }
        $ts = strtotime($value);
        if (!$ts || date('Y-m-d', $ts) < date('Y-m-d')) {
            $this->form_validation->set_message('_valid_future_date', 'Pick a date today or later.');
            return FALSE;
        }
        return TRUE;
    }

    public function _valid_referral_code($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return TRUE;
        }
        $this->load->library('referral_service');
        if (!$this->referral_service->validate_for_booking($value, $this->user['id'])) {
            $this->form_validation->set_message('_valid_referral_code', $this->referral_service->last_error());
            return FALSE;
        }
        return TRUE;
    }

    // --- Step 4: confirm ---------------------------------------------------

    public function confirm()
    {
        $this->_require_customer();
        $draft = $this->_get_draft();
        if (empty($draft['package_id']) || empty($draft['vehicle_id'])) {
            redirect(site_url('booking/vehicle'));
        }
        if ($msg = $this->_vehicle_unavailable_message($draft['vehicle_id'])) {
            $this->flash('error', $msg);
            redirect(site_url('booking/vehicle'));
        }
        $this->render('bookings/confirm', array(
            'title'           => 'Review &amp; confirm',
            'package'         => $this->package_model->find($draft['package_id']),
            'vehicle'         => $this->vehicle_model->find_for_user($draft['vehicle_id'], $this->user['id']),
            'draft'           => $draft,
            'step'            => 4,
            'referred_credit' => (float) $this->config->item('referral_credit_referred'),
        ));
    }

    // --- Step 5: persist ---------------------------------------------------

    public function place()
    {
        $this->_require_customer();
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }

        $draft = $this->_get_draft();
        if (empty($draft['package_id']) || empty($draft['vehicle_id'])) {
            $this->flash('error', 'Booking session expired. Please start again.');
            redirect(site_url('packages'));
        }

        $package = $this->package_model->find($draft['package_id']);
        $vehicle = $this->vehicle_model->find_for_user($draft['vehicle_id'], $this->user['id']);
        if (!$package || !$vehicle) {
            show_404();
        }
        if ($msg = $this->_vehicle_unavailable_message($vehicle['id'])) {
            $this->flash('error', $msg);
            redirect(site_url('booking/vehicle'));
        }

        $booking_id = $this->booking_model->create(array(
            'user_id'          => $this->user['id'],
            'vehicle_id'       => $vehicle['id'],
            'package_id'       => $package['id'],
            'package_snapshot' => $this->package_model->snapshot($package),
            'remarks'          => isset($draft['remarks']) ? $draft['remarks'] : NULL,
            'preferred_date'   => isset($draft['preferred_date']) ? $draft['preferred_date'] : NULL,
            'status'           => 'pending',
        ));

        if (!empty($draft['referral_code'])) {
            $this->load->library('referral_service');
            if (!$this->referral_service->attach_to_booking($booking_id, $draft['referral_code'], $this->user['id'])) {
                log_message('error', '[referral] attach failed for booking '.$booking_id.': '.$this->referral_service->last_error());
            }
        }

        $this->session->unset_userdata(self::DRAFT_KEY);

        $this->load->library(array('sms_gateway', 'mailer'));
        $booking = $this->booking_model->find_detailed($booking_id);
        $this->_notify_user_on_create($booking);
        $this->_notify_admin_on_create($booking);

        redirect(site_url('booking/success/'.$booking_id));
    }

    public function success($id)
    {
        $this->_require_customer();
        $booking = $this->booking_model->find_detailed($id);
        if (!$booking || (int) $booking['user_id'] !== (int) $this->user['id']) {
            show_404();
        }
        $this->render('bookings/success', array(
            'title'   => 'Booking confirmed',
            'booking' => $booking,
        ));
    }

    // --- Re-book -----------------------------------------------------------

    public function rebook($id)
    {
        $this->_require_customer();
        $booking = $this->booking_model->find_for_user($id, $this->user['id']);
        if (!$booking) {
            show_404();
        }
        if ($msg = $this->_vehicle_unavailable_message($booking['vehicle_id'])) {
            $this->flash('error', $msg);
            redirect(site_url('account/bookings/'.$booking['id']));
        }

        $this->_set_draft(array(
            'package_id' => (int) $booking['package_id'],
            'vehicle_id' => (int) $booking['vehicle_id'],
            'remarks'    => $booking['remarks'],
        ));
        $this->flash('info', 'Re-booking from #'.$booking['reference'].'. Review and confirm.');
        redirect(site_url('booking/confirm'));
    }

    // --- helpers -----------------------------------------------------------

    protected function _require_customer()
    {
        if (empty($this->user) || empty($this->user['id'])) {
            redirect(site_url('login?next='.urlencode(uri_string())));
        }
        if (!ymo_user_is_verified($this->user)) {
            redirect(site_url('signup/verify'));
        }
    }

    protected function _get_draft()
    {
        return (array) $this->session->userdata(self::DRAFT_KEY);
    }

    protected function _set_draft(array $draft)
    {
        $this->session->set_userdata(self::DRAFT_KEY, $draft);
    }

    /**
     * @return string|null Error message when vehicle has an open booking
     */
    protected function _vehicle_unavailable_message($vehicle_id)
    {
        $active = $this->booking_model->find_active_for_vehicle($vehicle_id, $this->user['id']);
        if (!$active) {
            return NULL;
        }
        $label = ucfirst(str_replace('_', ' ', $active['status']));
        return 'This vehicle already has an active booking (#'.$active['reference'].' - '.$label.'). '
            .'Please wait until it is completed or contact us if you need help.';
    }

    protected function _notify_user_on_create(array $booking)
    {
        // SMS confirmation
        $this->sms_gateway->send_template($booking['user_mobile'], 'booking_confirmed', array(
            'name'    => strtok($booking['user_name'], ' '),
            'ref'     => $booking['reference'],
            'package' => $booking['package_name'],
        ));

        // Email confirmation
        $subject = 'Your booking is confirmed - '.$booking['reference'];
        $this->mailer->send_view($booking['user_email'], $subject, 'emails/booking_confirmed', array(
            'booking' => $booking,
        ));
    }

    protected function _notify_admin_on_create(array $booking)
    {
        $admin_email = $this->config->item('ymo_admin_notify');
        if (!$admin_email) { return; }
        $subject = 'New booking #'.$booking['reference'];
        $this->mailer->send_view($admin_email, $subject, 'emails/booking_admin_new', array(
            'booking' => $booking,
        ));
    }
}
