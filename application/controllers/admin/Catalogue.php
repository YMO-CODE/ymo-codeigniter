<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalogue: services (labour) and spare parts with their rates and tax,
 * picked from when making estimates and job cards.
 * Uses the packages.view / packages.edit permissions (same "Setup" area as Packages).
 */
class Catalogue extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('crm_catalog_model');
    }

    public function index()
    {
        $this->require_perm('packages.view');
        $type = $this->input->get('type') === 'part' ? 'part' : 'service';
        $f = (string) $this->input->get('f');
        if (!in_array($f, array('', 'no_price', 'hidden'), TRUE)) {
            $f = '';
        }
        $q = trim((string) $this->input->get('q'));
        $per = 50;
        $page = max(1, (int) $this->input->get('p'));
        $res = $this->crm_catalog_model->page($type, $q, $f, $per, ($page - 1) * $per);
        if ($res['total'] && ($page - 1) * $per >= $res['total']) {
            $page = (int) ceil($res['total'] / $per);
            $res = $this->crm_catalog_model->page($type, $q, $f, $per, ($page - 1) * $per);
        }
        $this->render('admin/catalogue/index', array(
            'title'    => 'Catalogue',
            'type'     => $type,
            'f'        => $f,
            'q'        => $q,
            'rows'     => $res['rows'],
            'counts'   => $this->crm_catalog_model->counts(),
            'pager'    => array('page' => $page, 'pages' => (int) ceil($res['total'] / $per), 'total' => $res['total'], 'per' => $per, 'param' => 'p', 'anchor' => 'items', 'path' => 'catalogue', 'noun' => $type === 'part' ? 'parts' : 'services'),
            'can_edit' => crm_can('packages.edit'),
        ));
    }

    public function create()
    {
        $this->require_perm('packages.edit');
        $type = $this->input->get_post('type') === 'part' ? 'part' : 'service';
        if ($this->input->method() === 'post' && $this->_validate()) {
            $d = $this->_collect();
            $d['created_by'] = (int) $this->admin['id'];
            $id = $this->crm_catalog_model->create($d);
            $this->flash('success', ($d['type'] === 'part' ? 'Part' : 'Service').' added.');
            redirect(admin_url('catalogue?type='.$d['type'].'&q='.rawurlencode($d['name'])).'#items');
        }
        $this->_render_form(NULL, $type);
    }

    public function edit($id)
    {
        $this->require_perm('packages.edit');
        $item = $this->crm_catalog_model->find($id);
        if (!$item) {
            show_404();
        }
        if ($this->input->method() === 'post' && $this->_validate()) {
            $d = $this->_collect();
            $this->crm_catalog_model->update($id, $d);
            $this->flash('success', 'Saved.');
            redirect($this->_back($d['type']));
        }
        $this->_render_form($item, $item['type']);
    }

    /** POST catalogue/{id}/toggle — hide from pickers, or show again (never deleted: jobs will refer to items). */
    public function toggle($id)
    {
        $this->require_perm('packages.edit');
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
        $item = $this->crm_catalog_model->find($id);
        if (!$item) {
            show_404();
        }
        $on = (int) $item['is_active'] === 1 ? 0 : 1;
        $this->crm_catalog_model->update($id, array('is_active' => $on));
        $this->flash('success', $on ? 'Shown again.' : 'Hidden. It stays on old jobs but no longer appears when picking items.');
        redirect($this->_back($item['type']));
    }

    // --- helpers -----------------------------------------------------------

    protected function _validate()
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('description', 'Description', 'trim|max_length[5000]');
        $this->form_validation->set_rules('code', 'Code / barcode', 'trim|max_length[64]');
        $this->form_validation->set_rules('category', 'Category', 'trim|max_length[80]');
        $this->form_validation->set_rules('unit', 'Unit', 'trim|max_length[16]');
        $this->form_validation->set_rules('rate', 'Rate', 'trim|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('purchase_rate', 'Purchase rate', 'trim|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('tax_pct', 'Tax %', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[28]');
        $this->form_validation->set_rules('low_stock', 'Low stock alert', 'trim|numeric|greater_than_equal_to[0]');
        return $this->form_validation->run();
    }

    protected function _collect()
    {
        $num = function ($k) {
            $v = trim((string) $this->input->post($k));
            return $v === '' ? NULL : round((float) $v, 2);
        };
        $type = $this->input->post('type') === 'part' ? 'part' : 'service';
        $d = array(
            'type'          => $type,
            'name'          => trim((string) $this->input->post('name')),
            'description'   => trim((string) $this->input->post('description')) ?: NULL,
            'category'      => trim((string) $this->input->post('category')),
            'rate'          => $num('rate'),
            'tax_pct'       => $num('tax_pct'),
            'tax_inclusive' => $this->input->post('tax_inclusive') === '0' ? 0 : 1,
            'is_active'     => $this->input->post('is_active') ? 1 : 0,
        );
        if ($type === 'part') {
            $d += array(
                'code'          => trim((string) $this->input->post('code')),
                'unit'          => strtoupper(trim((string) $this->input->post('unit'))) ?: 'UNT',
                'purchase_rate' => $num('purchase_rate'),
                'low_stock'     => (float) $num('low_stock'),
            );
        }
        return $d;
    }

    protected function _back($type)
    {
        $ref = (string) $this->input->server('HTTP_REFERER');
        $base = admin_url('catalogue');
        if ($ref !== '' && strpos($ref, $base) === 0 && strpos($ref, '/edit') === FALSE && strpos($ref, '/new') === FALSE) {
            return preg_replace('/#.*$/', '', $ref).'#items';
        }
        $back = (string) $this->input->post('back');
        if ($back !== '' && strpos($back, $base) === 0) {
            return $back;
        }
        return $base.'?type='.$type.'#items';
    }

    protected function _render_form($item, $type)
    {
        $ref = (string) $this->input->server('HTTP_REFERER');
        $this->render('admin/catalogue/form', array(
            'title' => $item ? 'Edit '.($type === 'part' ? 'part' : 'service') : 'New '.($type === 'part' ? 'part' : 'service'),
            'item'  => $item,
            'type'  => $type,
            'back'  => (string) $this->input->post('back') ?: (strpos($ref, admin_url('catalogue')) === 0 ? preg_replace('/#.*$/', '', $ref).'#items' : admin_url('catalogue?type='.$type)),
        ));
    }
}
