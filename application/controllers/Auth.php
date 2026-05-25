<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer authentication: signup with mobile OTP, optional email OTP, login,
 * forgot/reset password. Sessions live in `ci_sessions` (DB-backed).
 *
 * Routes (see config/routes.php):
 *   GET/POST /signup           Auth::signup
 *   GET/POST /signup/verify    Auth::verify_otp
 *   POST     /signup/resend    Auth::resend_otp
 *   GET/POST /login            Auth::login
 *   POST     /logout           Auth::logout
 *   GET/POST /forgot-password  Auth::forgot
 *   GET/POST /reset-password   Auth::reset
 */
class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->library('otp_service');
    }

    // --- Signup ------------------------------------------------------------

    public function signup()
    {
        if (!empty($this->user)) {
            redirect(site_url('account'));
        }

        $this->form_validation->set_rules('name',     'Name',     'trim|required|min_length[2]|max_length[120]');
        $this->form_validation->set_rules('mobile',   'Mobile',   'trim|required|regex_match[/^[6-9]\d{9}$/]');
        $this->form_validation->set_rules('email',    'Email',    'trim|required|valid_email|max_length[180]');
        $this->form_validation->set_rules('area',     'Area',     'trim|required|max_length[120]');
        $allowed_cities = (array) $this->config->item('ymo_service_cities');
        $this->form_validation->set_rules('city', 'City',
            'trim|required|max_length[80]|in_list['.implode(',', $allowed_cities).']',
            array('in_list' => 'Please pick one of our serviceable cities.')
        );
        $this->form_validation->set_rules('password', 'Password', 'required|min_length['.(int) $this->config->item('auth_password_min').']');
        $this->form_validation->set_rules('confirm',  'Confirm Password', 'required|matches[password]');
        $this->form_validation->set_rules('terms',    'Terms',    'required');

        if ($this->input->method() !== 'post' || !$this->form_validation->run()) {
            return $this->render('auth/signup', array('title' => 'Sign up'), 'layout/auth');
        }

        $mobile = $this->input->post('mobile');
        $email  = strtolower($this->input->post('email'));

        if ($this->user_model->find_by_mobile($mobile)) {
            $this->flash('error', 'An account with that mobile number already exists. Please sign in.');
            redirect(site_url('login'));
        }
        if ($this->user_model->find_by_email($email)) {
            $this->flash('error', 'An account with that email already exists. Please sign in.');
            redirect(site_url('login'));
        }

        $user_id = $this->user_model->create(array(
            'name'     => $this->input->post('name'),
            'mobile'   => $mobile,
            'email'    => $email,
            'area'     => $this->input->post('area'),
            'city'     => $this->input->post('city'),
            'password' => $this->input->post('password'),
        ));

        // Dev shortcut — skip OTP entirely (config-gated, off in production).
        if (ENVIRONMENT !== 'production' && $this->config->item('dev_auto_verify_otp')) {
            $this->user_model->mark_mobile_verified($user_id);
            $this->_set_session($user_id);
            $this->flash('success', 'Welcome! (Dev mode: mobile auto-verified, OTP skipped.)');
            redirect(site_url('account'));
        }

        $this->_set_session($user_id);

        $issued = $this->otp_service->issue('sms', $mobile, 'signup');
        if (!$issued['ok']) {
            $this->flash('warning', $issued['reason'] ?: 'Could not send OTP — please retry.');
        } else {
            $this->flash('success', 'OTP sent to your mobile.');
        }
        redirect(site_url('signup/verify'));
    }

    public function verify_otp()
    {
        $user = $this->_require_session_user();
        if (!empty($user['mobile_verified_at'])) {
            redirect(site_url('account'));
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('code', 'Code', 'trim|required|exact_length[6]|numeric');
            if ($this->form_validation->run()) {
                $r = $this->otp_service->verify('sms', $user['mobile'], 'signup', $this->input->post('code'));
                if ($r['ok']) {
                    $this->user_model->mark_mobile_verified($user['id']);
                    $this->_set_session($user['id']);
                    $this->flash('success', 'Mobile verified — welcome aboard!');
                    redirect(site_url('account'));
                }
                $this->flash('error', $r['reason']);
            }
        }

        $this->render('auth/verify', array(
            'title'     => 'Verify your mobile',
            'mobile'    => $user['mobile'],
            'cooldown'  => (int) $this->config->item('otp_resend_cooldown'),
        ), 'layout/auth');
    }

    public function resend_otp()
    {
        $user = $this->_require_session_user();
        $r = $this->otp_service->issue('sms', $user['mobile'], 'signup');
        $this->flash($r['ok'] ? 'success' : 'error',
            $r['ok'] ? 'A fresh OTP has been sent.' : $r['reason']);
        redirect(site_url('signup/verify'));
    }

    // --- Login -------------------------------------------------------------

    public function login()
    {
        if (!empty($this->user)) {
            redirect(site_url('account'));
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('identifier', 'Mobile or email', 'trim|required');
            $this->form_validation->set_rules('password',   'Password',        'required');

            if ($this->form_validation->run()) {
                $u = $this->user_model->find_by_login(trim($this->input->post('identifier')));

                if (!$u || !$u['is_active']) {
                    $this->flash('error', 'No account found with those details.');
                } elseif ($this->user_model->is_locked($u)) {
                    $minutes = ceil((strtotime($u['locked_until']) - time()) / 60);
                    $this->flash('error', "Account temporarily locked. Try again in $minutes minute(s).");
                } elseif (!password_verify($this->input->post('password'), $u['password_hash'])) {
                    $this->user_model->record_failed_login($u['id']);
                    $this->flash('error', 'Incorrect password.');
                } else {
                    $this->user_model->record_login($u['id']);

                    // Dev shortcut: backfill verification on login too, so
                    // accounts created before dev_auto_verify_otp was on
                    // don't get stuck at the OTP wall.
                    if (ENVIRONMENT !== 'production'
                        && $this->config->item('dev_auto_verify_otp')
                        && empty($u['mobile_verified_at'])) {
                        $this->user_model->mark_mobile_verified($u['id']);
                    }

                    $this->_set_session($u['id']);

                    if (!ymo_user_is_verified($this->user)) {
                        $this->flash('warning', 'Please verify your mobile to continue.');
                        redirect(site_url('signup/verify'));
                    }

                    $next = $this->input->get('next') ?: $this->input->post('next');
                    redirect($next ? site_url($next) : site_url('account'));
                }
            }
        }

        $this->render('auth/login', array(
            'title' => 'Sign in',
            'next'  => $this->input->get('next'),
        ), 'layout/auth');
    }

    public function logout()
    {
        $this->session->sess_destroy();
        $this->flash('success', 'Signed out. Drive safe!');
        redirect(site_url('/'));
    }

    // --- Forgot / reset password -------------------------------------------

    public function forgot()
    {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|regex_match[/^[6-9]\d{9}$/]');
            if ($this->form_validation->run()) {
                $mobile = $this->input->post('mobile');
                $u = $this->user_model->find_by_mobile($mobile);
                if ($u) {
                    $this->otp_service->issue('sms', $mobile, 'reset');
                }
                // Don't leak account existence.
                $this->session->set_userdata('reset_mobile', $mobile);
                $this->flash('info', 'If an account exists, an OTP has been sent.');
                redirect(site_url('reset-password'));
            }
        }
        $this->render('auth/forgot', array('title' => 'Reset password'), 'layout/auth');
    }

    public function reset()
    {
        $mobile = $this->session->userdata('reset_mobile');
        if (!$mobile) {
            redirect(site_url('forgot-password'));
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('code',     'Code',     'trim|required|exact_length[6]|numeric');
            $this->form_validation->set_rules('password', 'Password', 'required|min_length['.(int) $this->config->item('auth_password_min').']');
            $this->form_validation->set_rules('confirm',  'Confirm',  'required|matches[password]');

            if ($this->form_validation->run()) {
                $r = $this->otp_service->verify('sms', $mobile, 'reset', $this->input->post('code'));
                if ($r['ok']) {
                    $u = $this->user_model->find_by_mobile($mobile);
                    if ($u) {
                        $this->user_model->update_password($u['id'], $this->input->post('password'));
                    }
                    $this->session->unset_userdata('reset_mobile');
                    $this->flash('success', 'Password updated. Please sign in.');
                    redirect(site_url('login'));
                }
                $this->flash('error', $r['reason']);
            }
        }

        $this->render('auth/reset', array(
            'title'  => 'Set a new password',
            'mobile' => $mobile,
        ), 'layout/auth');
    }

    // --- helpers -----------------------------------------------------------

    /**
     * Hydrate the session with the freshly persisted user row (so changes
     * to verification flags and name propagate immediately).
     */
    protected function _set_session($user_id)
    {
        $u = $this->user_model->find($user_id);
        if (!$u) {
            return;
        }
        unset($u['password_hash']);
        $this->session->set_userdata('user', $u);
        $this->user = $u;
    }

    protected function _require_session_user()
    {
        if (empty($this->user) || empty($this->user['id'])) {
            redirect(site_url('login'));
        }
        return $this->user;
    }
}
