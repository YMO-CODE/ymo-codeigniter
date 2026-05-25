<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Packages extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('package_model');
    }

    public function index()
    {
        $this->require_perm('packages.view');
        $this->render('admin/packages/index', array(
            'title'    => 'Service packages',
            'packages' => $this->package_model->list_all(),
            'can_edit' => crm_can('packages.edit'),
        ));
    }

    public function create()
    {
        $this->require_perm('packages.edit');
        if ($this->input->method() === 'post' && $this->_validate()) {
            $payload = $this->_collect();
            $features = $this->_features_from_post();
            $this->package_model->create($payload, $features);
            $this->flash('success', 'Package added.');
            redirect(admin_url('packages'));
        }
        $this->_render_form();
    }

    public function edit($id)
    {
        $this->require_perm('packages.edit');
        $package = $this->package_model->find($id);
        if (!$package) { show_404(); }

        if ($this->input->method() === 'post' && $this->_validate()) {
            $patch    = $this->_collect();
            $features = $this->_features_from_post();
            $this->package_model->update($id, $patch, $features);
            $this->flash('success', 'Package updated.');
            redirect(admin_url('packages'));
        }
        $package['features'] = $this->package_model->features_for($id);
        $this->_render_form($package);
    }

    public function delete($id)
    {
        $this->require_perm('packages.edit');
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }
        $package = $this->package_model->find($id);
        if (!$package) { show_404(); }
        $this->package_model->delete($id);
        $this->flash('success', 'Package removed.');
        redirect(admin_url('packages'));
    }

    // --- helpers -----------------------------------------------------------

    protected function _validate()
    {
        $this->form_validation->set_rules('name',       'Name',       'trim|required|max_length[120]');
        $this->form_validation->set_rules('summary',    'Summary',    'trim|max_length[500]');
        $this->form_validation->set_rules('price',      'Price',      'trim|required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('sort_order', 'Sort order', 'trim|integer');
        return $this->form_validation->run();
    }

    protected function _collect()
    {
        return array(
            'name'       => trim($this->input->post('name')),
            'summary'    => trim($this->input->post('summary')),
            'price'      => (float) $this->input->post('price'),
            'sort_order' => (int) $this->input->post('sort_order'),
            'is_active'  => $this->input->post('is_active') ? 1 : 0,
        );
    }

    protected function _features_from_post()
    {
        $raw = (array) $this->input->post('features');
        return array_values(array_filter(array_map('trim', $raw), 'strlen'));
    }

    protected function _render_form($package = NULL)
    {
        $this->render('admin/packages/form', array(
            'title'   => $package ? 'Edit package' : 'New package',
            'package' => $package,
        ));
    }
}
