<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Team extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('admin_model', 'crm_role_model'));
        $this->load->library('audit');
    }

    public function index()
    {
        $this->require_perm('team.view');
        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;
        $q       = $this->input->get('q');

        $result = $this->admin_model->paginate($q, $perPage, $offset);

        $this->render('admin/team/index', array(
            'title'     => 'Team members',
            'rows'      => $result['rows'],
            'total'     => $result['total'],
            'page'      => $page,
            'pages'     => max(1, (int) ceil($result['total'] / $perPage)),
            'q'         => $q,
            'can_manage'=> crm_can('team.manage'),
        ));
    }

    public function create()
    {
        $this->require_perm('team.manage');
        if ($this->input->method() === 'post') {
            return $this->_save(NULL);
        }
        $this->_form(NULL);
    }

    public function edit($id)
    {
        $this->require_perm('team.manage');
        $user = $this->admin_model->find_detailed($id);
        if (!$user) { show_404(); }
        if ($this->input->method() === 'post') {
            return $this->_save($id);
        }
        $this->_form($user);
    }

    public function reset_password($id)
    {
        $this->require_perm('team.manage');
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }
        $user = $this->admin_model->find($id);
        if (!$user) { show_404(); }

        $plain = $this->input->post('password');
        if ($plain === '' || $plain === NULL) {
            $plain = $this->_random_password(16);
            $generated = TRUE;
        } else {
            $min = (int) $this->config->item('auth_password_min');
            if ($min > 0 && strlen($plain) < $min) {
                $this->flash('error', "Password must be at least $min characters.");
                redirect(admin_url('team/'.$id.'/edit'));
            }
            $generated = FALSE;
        }

        $this->admin_model->update_password($id, $plain);
        $this->audit->log('admin', $this->admin['id'], 'team.reset_password', 'admin_user', $id, array());

        if ($generated) {
            $this->flash('success', 'New password for '.$user['email'].': '.$plain);
        } else {
            $this->flash('success', 'Password updated.');
        }
        redirect(admin_url('team/'.$id.'/edit'));
    }

    public function deactivate($id)
    {
        $this->require_perm('team.manage');
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }
        $user = $this->admin_model->find($id);
        if (!$user) { show_404(); }
        if ((int) $id === (int) $this->admin['id']) {
            $this->flash('error', 'You cannot deactivate your own account.');
            redirect(admin_url('team'));
        }
        $this->admin_model->update($id, array('is_active' => 0));
        $this->audit->log('admin', $this->admin['id'], 'team.deactivate', 'admin_user', $id, array());
        $this->flash('success', 'Team member deactivated.');
        redirect(admin_url('team'));
    }

    public function activate($id)
    {
        $this->require_perm('team.manage');
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }
        $user = $this->admin_model->find($id);
        if (!$user) { show_404(); }
        $this->admin_model->update($id, array('is_active' => 1));
        $this->audit->log('admin', $this->admin['id'], 'team.activate', 'admin_user', $id, array());
        $this->flash('success', 'Team member reactivated.');
        redirect(admin_url('team'));
    }

    protected function _form($user)
    {
        $this->render('admin/team/form', array(
            'title' => $user ? 'Edit team member' : 'Add team member',
            'user'  => $user,
            'roles' => $this->crm_role_model->list_for_select(),
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[180]');
        $this->form_validation->set_rules('crm_role_id', 'Role', 'required|integer');

        if (!$id) {
            $this->form_validation->set_rules('password', 'Password', 'trim|min_length[8]');
        }

        if (!$this->form_validation->run()) {
            $this->_form($id ? $this->admin_model->find_detailed($id) : NULL);
            return;
        }

        $crm_role_id = (int) $this->input->post('crm_role_id');
        $legacy_role = $this->crm_role_model->legacy_role_for_crm_role_id($crm_role_id);

        if ($id) {
            $existing = $this->admin_model->find_by_email($this->input->post('email'));
            if ($existing && (int) $existing['id'] !== (int) $id) {
                $this->flash('error', 'That email is already in use.');
                redirect(admin_url('team/'.$id.'/edit'));
            }
            $this->admin_model->update($id, array(
                'name'        => $this->input->post('name'),
                'email'       => $this->input->post('email'),
                'crm_role_id' => $crm_role_id,
                'role'        => $legacy_role,
                'is_active'   => $this->input->post('is_active') ? 1 : 0,
            ));
            $this->audit->log('admin', $this->admin['id'], 'team.update', 'admin_user', $id, array());
            $this->flash('success', 'Team member updated.');
            redirect(admin_url('team/'.$id.'/edit'));
        }

        if ($this->admin_model->find_by_email($this->input->post('email'))) {
            $this->flash('error', 'That email is already registered.');
            $this->_form(NULL);
            return;
        }

        $plain = $this->input->post('password');
        if ($plain === '' || $plain === NULL) {
            $plain = $this->_random_password(16);
            $show_pass = TRUE;
        } else {
            $show_pass = FALSE;
        }

        $new_id = $this->admin_model->create(array(
            'name'        => $this->input->post('name'),
            'email'       => $this->input->post('email'),
            'password'    => $plain,
            'crm_role_id' => $crm_role_id,
            'role'        => $legacy_role,
            'is_active'   => 1,
        ));
        $this->audit->log('admin', $this->admin['id'], 'team.create', 'admin_user', $new_id, array());

        if ($show_pass) {
            $this->flash('success', 'Team member created. Temporary password: '.$plain);
        } else {
            $this->flash('success', 'Team member created.');
        }
        redirect(admin_url('team/'.$new_id.'/edit'));
    }

    protected function _random_password($length = 16)
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#%^*';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }
}
