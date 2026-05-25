<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin sign-in / sign-out. Uses the same `admin_users` table as the rest of
 * the admin area; sessions are stored in the same `ci_sessions` table but
 * under a separate session key (`admin`).
 */
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin_model');
    }

    public function login()
    {
        if ($this->session->userdata('admin')) {
            redirect(admin_url('dashboard'));
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('email',    'Email',    'trim|required|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run()) {
                $row = $this->admin_model->find_by_email($this->input->post('email'));
                if (!$row || !$row['is_active']) {
                    $this->session->set_flashdata('error', 'No matching admin account.');
                } elseif ($this->admin_model->is_locked($row)) {
                    $minutes = ceil((strtotime($row['locked_until']) - time()) / 60);
                    $this->session->set_flashdata('error', "Account locked for $minutes more minute(s).");
                } elseif (!password_verify($this->input->post('password'), $row['password_hash'])) {
                    $this->admin_model->record_failed_login($row['id']);
                    $this->session->set_flashdata('error', 'Incorrect password.');
                } else {
                    unset($row['password_hash']);
                    $this->session->set_userdata('admin', $row);
                    $this->admin_model->record_login($row['id']);
                    redirect(admin_url('dashboard'));
                }
            }
        }

        $this->load->view('admin/auth/login', array(
            'title' => 'Admin sign-in',
        ));
    }

    public function logout()
    {
        $this->session->unset_userdata('admin');
        redirect(admin_url('login'));
    }
}
