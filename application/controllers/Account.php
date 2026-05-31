<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Authenticated customer area: profile + booking history.
 */
class Account extends Customer_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('booking_model', 'user_model', 'booking_invoice_model'));
    }

    public function index()
    {
        $recent = $this->booking_model->for_user($this->user['id'], 5);
        $this->render('account/index', array(
            'title'  => 'My account',
            'recent' => $recent,
        ));
    }

    public function profile()
    {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name', 'Name', 'trim|required|min_length[2]|max_length[120]');
            $this->form_validation->set_rules('area', 'Area', 'trim|required|max_length[120]');
            $this->form_validation->set_rules('city', 'City', 'trim|required|max_length[80]');
            if ($this->form_validation->run()) {
                $this->user_model->update($this->user['id'], array(
                    'name' => $this->input->post('name'),
                    'area' => $this->input->post('area'),
                    'city' => $this->input->post('city'),
                ));
                $fresh = $this->user_model->find($this->user['id']);
                unset($fresh['password_hash']);
                $this->session->set_userdata('user', $fresh);
                ymo_stamp_deploy_session();
                $this->flash('success', 'Profile updated.');
                redirect(site_url('account/profile'));
            }
        }
        $this->render('account/profile', array('title' => 'My profile'));
    }

    public function bookings()
    {
        $perPage = (int) $this->config->item('booking_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;

        $rows  = $this->booking_model->for_user($this->user['id'], $perPage, $offset);
        $total = $this->booking_model->count_for_user($this->user['id']);

        $this->render('account/bookings', array(
            'title'    => 'My bookings',
            'rows'     => $rows,
            'page'     => $page,
            'pages'    => max(1, (int) ceil($total / $perPage)),
            'total'    => $total,
        ));
    }

    public function booking_view($id)
    {
        $booking = $this->booking_model->find_detailed($id);
        if (!$booking || (int) $booking['user_id'] !== (int) $this->user['id']) {
            show_404();
        }
        // Prefer the snapshot taken at booking time so admin edits don't rewrite history.
        $features = array();
        if (!empty($booking['package_snapshot'])) {
            $snap = json_decode($booking['package_snapshot'], TRUE);
            if (is_array($snap) && !empty($snap['features'])) {
                $features = $snap['features'];
            }
        }
        $invoices = $this->booking_invoice_model->for_booking($id);
        $this->render('account/booking_view', array(
            'title'    => 'Booking '.$booking['reference'],
            'booking'  => $booking,
            'features' => $features,
            'invoices' => $invoices,
        ));
    }

    public function download_invoice($booking_id, $invoice_id)
    {
        $invoice = $this->booking_invoice_model->find_for_booking_user(
            $invoice_id,
            $booking_id,
            $this->user['id']
        );
        if (!$invoice) {
            show_404();
        }
        if (empty($invoice['pdf_path']) || !file_exists(FCPATH.$invoice['pdf_path'])) {
            show_error('Invoice PDF not found.', 404);
        }
        $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $invoice['invoice_number']).'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.$filename.'"');
        header('Content-Length: '.filesize(FCPATH.$invoice['pdf_path']));
        readfile(FCPATH.$invoice['pdf_path']);
        exit;
    }
}
