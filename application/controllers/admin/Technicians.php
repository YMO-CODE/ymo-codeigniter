<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Setup → Technicians: who can use the phone screen (/tech), their workshop,
 * language and PIN. Permissions: team.view to see, team.manage (or bookings.edit) to change.
 */
class Technicians extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('crm_technician_model');
        $this->load->helper('review');
        $this->load->library('audit');
    }

    protected function _perm_view()
    {
        $this->require_perm(crm_can('team.view') ? 'team.view' : 'bookings.edit');
    }

    protected function _perm_edit()
    {
        $this->require_perm(crm_can('team.manage') ? 'team.manage' : 'bookings.edit');
    }

    public function index()
    {
        $this->_perm_view();
        $this->render('admin/technicians/index', array(
            'title'     => 'Technicians',
            'rows'      => $this->crm_technician_model->all(),
            'sessions'  => $this->crm_technician_model->active_session_counts(),
            'workshops' => ymo_review_workshops(),
            'tech_url'  => admin_url('tech'),
            'can_edit'  => crm_can('team.manage') || crm_can('bookings.edit'),
        ));
    }

    public function create()
    {
        $this->_perm_edit();
        if ($this->input->method() === 'post') {
            return $this->_save(NULL);
        }
        $this->render('admin/technicians/form', array(
            'title' => 'Add technician', 't' => NULL, 'workshops' => ymo_review_workshops(),
        ));
    }

    public function edit($id)
    {
        $this->_perm_edit();
        $t = $this->crm_technician_model->find($id);
        if (!$t) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            return $this->_save($t);
        }
        $this->render('admin/technicians/form', array(
            'title' => $t['name'], 't' => $t, 'workshops' => ymo_review_workshops(),
        ));
    }

    protected function _save($t)
    {
        $back = $t ? admin_url('technicians/'.(int) $t['id'].'/edit') : admin_url('technicians/new');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|regex_match[/^[6-9]\d{9}$/]');
        $this->form_validation->set_rules('pin', 'PIN', $t ? 'trim|regex_match[/^\d{4}$/]' : 'trim|required|regex_match[/^\d{4}$/]');
        if (!$this->form_validation->run()) {
            $this->flash('error', strip_tags(validation_errors(' ', ' ')) ?: 'Check the form.');
            redirect($back);
        }
        $pin = (string) $this->input->post('pin');
        if ($pin !== '' && preg_match('/^(\d)\1{3}$|^(0123|1234|2345|3456|4567|5678|6789|9876|4321)$/', $pin)) {
            $this->flash('error', 'Choose a PIN that is harder to guess than '.$pin.'.');
            redirect($back);
        }
        $workshops = ymo_review_workshops();
        $ws = (string) $this->input->post('workshop');
        $d = array(
            'name'      => $this->input->post('name'),
            'mobile'    => $this->input->post('mobile'),
            'workshop'  => isset($workshops[$ws]) ? $ws : '',
            'lang'      => isset(Crm_technician_model::LANGS[$this->input->post('lang')]) ? $this->input->post('lang') : 'hi',
            'is_active' => $this->input->post('is_active') === '0' ? 0 : 1,
        );
        if ($t) {
            if (!$this->crm_technician_model->update($t['id'], $d)) {
                $this->flash('error', 'Another technician already uses that mobile number.');
                redirect($back);
            }
            $id = (int) $t['id'];
            if (!$d['is_active']) {
                $this->crm_technician_model->revoke_sessions($id);
            }
        } else {
            $id = $this->crm_technician_model->create($d, (int) $this->admin['id']);
            if (!$id) {
                $this->flash('error', 'Another technician already uses that mobile number.');
                redirect($back);
            }
        }
        if ($pin !== '') {
            $this->crm_technician_model->set_pin($id, $pin);
            if ($t) {
                $this->crm_technician_model->revoke_sessions($id);   // a new PIN signs out old phones
            }
        }
        $this->audit->log('admin', $this->admin['id'], $t ? 'technician.update' : 'technician.create', 'technician', $id, array('pin_changed' => $pin !== ''));
        $this->flash('success', $t ? 'Saved '.$d['name'].'.'.($pin !== '' ? ' New PIN set; their phone needs to sign in again.' : '') : $d['name'].' added. They sign in at '.admin_url('tech').' with their mobile and PIN.');
        redirect(admin_url('technicians'));
    }

    /** POST: sign out all of a technician's phones (lost phone, left the job). */
    public function signout($id)
    {
        $this->_perm_edit();
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
        $t = $this->crm_technician_model->find($id);
        if (!$t) {
            show_404();
        }
        $n = $this->crm_technician_model->revoke_sessions($id);
        $this->audit->log('admin', $this->admin['id'], 'technician.signout', 'technician', (int) $id);
        $this->flash('success', $t['name'].': signed out of '.$n.' phone'.($n === 1 ? '' : 's').'.');
        redirect(admin_url('technicians'));
    }
}
