<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('crm_role_model');
        $this->load->library('audit');
    }

    public function index()
    {
        $this->require_perm('roles.view');
        $this->render('admin/roles/index', array(
            'title'      => 'Roles & permissions',
            'roles'      => $this->crm_role_model->all_with_counts(),
            'can_manage' => crm_can('roles.manage'),
        ));
    }

    public function create()
    {
        $this->require_perm('roles.manage');
        if ($this->input->method() === 'post') {
            return $this->_save(NULL);
        }
        $this->_form(NULL, array());
    }

    public function edit($id)
    {
        $this->require_perm('roles.manage');
        $role = $this->crm_role_model->find($id);
        if (!$role) { show_404(); }
        if ($this->input->method() === 'post') {
            return $this->_save($id);
        }
        $perm_ids = $this->crm_role_model->permission_ids_for_role($id);
        $this->_form($role, $perm_ids);
    }

    public function delete($id)
    {
        $this->require_perm('roles.manage');
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }
        $role = $this->crm_role_model->find($id);
        if (!$role) { show_404(); }
        if ($role['slug'] === 'admin') {
            $this->flash('error', 'The Administrator role cannot be deleted.');
            redirect(admin_url('roles'));
        }
        if ($this->crm_role_model->user_count($id) > 0) {
            $this->flash('error', 'Remove all team members from this role before deleting it.');
            redirect(admin_url('roles'));
        }
        if ($this->crm_role_model->delete($id)) {
            $this->audit->log('admin', $this->admin['id'], 'roles.delete', 'crm_role', $id, array());
            $this->flash('success', 'Role deleted.');
        } else {
            $this->flash('error', 'Could not delete role.');
        }
        redirect(admin_url('roles'));
    }

    protected function _form($role, array $perm_ids)
    {
        $this->render('admin/roles/form', array(
            'title'              => $role ? 'Edit role' : 'New role',
            'role'               => $role,
            'perm_ids'           => $perm_ids,
            'permission_groups'  => $this->crm_role_model->permissions_grouped(),
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('label', 'Role name', 'trim|required|max_length[120]');
        if (!$this->form_validation->run()) {
            $this->_form($id ? $this->crm_role_model->find($id) : NULL, (array) $this->input->post('perm_ids'));
            return;
        }

        $label = $this->input->post('label');
        $slug  = crm_slug($this->input->post('slug') ?: $label);
        $perm_ids = array_map('intval', (array) $this->input->post('perm_ids'));

        if ($id) {
            $existing = $this->crm_role_model->find($id);
            if ($existing['slug'] === 'admin') {
                $slug = 'admin';
            }
            $this->crm_role_model->update($id, array(
                'label'      => $label,
                'slug'       => $slug,
                'sort_order' => (int) $this->input->post('sort_order'),
            ));
            $this->crm_role_model->sync_permissions($id, $perm_ids);
            $this->audit->log('admin', $this->admin['id'], 'roles.update', 'crm_role', $id, array());
            $this->flash('success', 'Role updated.');
            redirect(admin_url('roles/'.$id.'/edit'));
        }

        $dup = $this->crm_role_model->find_by_slug($slug);
        if ($dup) {
            $this->flash('error', 'A role with that slug already exists.');
            $this->_form(NULL, $perm_ids);
            return;
        }

        $new_id = $this->crm_role_model->create(array(
            'label'      => $label,
            'slug'       => $slug,
            'sort_order' => (int) $this->input->post('sort_order') ?: 100,
        ));
        $this->crm_role_model->sync_permissions($new_id, $perm_ids);
        $this->audit->log('admin', $this->admin['id'], 'roles.create', 'crm_role', $new_id, array());
        $this->flash('success', 'Role created.');
        redirect(admin_url('roles/'.$new_id.'/edit'));
    }
}
