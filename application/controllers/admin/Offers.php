<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Offers extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('offer_model');
        $this->load->library('upload');
    }

    public function index()
    {
        $this->require_perm('offers.view');
        $this->render('admin/offers/index', array(
            'title'    => 'Site offers',
            'offers'   => $this->offer_model->list_all(),
            'can_edit' => crm_can('offers.edit'),
        ));
    }

    public function create()
    {
        $this->require_perm('offers.edit');
        if ($this->input->method() === 'post' && $this->_validate()) {
            $payload = $this->_collect();
            $image   = $this->_handle_image_upload();
            if ($image === FALSE) {
                $this->_render_form();
                return;
            }
            if ($image) {
                $payload['image_path'] = $image;
            }
            $this->offer_model->create($payload);
            $this->flash('success', 'Offer added.');
            redirect(admin_url('offers'));
        }
        $this->_render_form();
    }

    public function edit($id)
    {
        $this->require_perm('offers.edit');
        $offer = $this->offer_model->find($id);
        if (!$offer) {
            show_404();
        }

        if ($this->input->method() === 'post' && $this->_validate()) {
            $patch = $this->_collect();
            if ($this->input->post('remove_image')) {
                if (!empty($offer['image_path'])) {
                    $path = FCPATH.ltrim($offer['image_path'], '/');
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                $patch['image_path'] = NULL;
            } else {
                $image = $this->_handle_image_upload();
                if ($image === FALSE) {
                    $this->_render_form($offer);
                    return;
                }
                if ($image) {
                    if (!empty($offer['image_path'])) {
                        $old = FCPATH.ltrim($offer['image_path'], '/');
                        if (is_file($old)) {
                            @unlink($old);
                        }
                    }
                    $patch['image_path'] = $image;
                }
            }
            $this->offer_model->update($id, $patch);
            $this->flash('success', 'Offer updated.');
            redirect(admin_url('offers'));
        }
        $this->_render_form($offer);
    }

    public function delete($id)
    {
        $this->require_perm('offers.edit');
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
        $offer = $this->offer_model->find($id);
        if (!$offer) {
            show_404();
        }
        $this->offer_model->delete($id);
        $this->flash('success', 'Offer removed.');
        redirect(admin_url('offers'));
    }

    protected function _validate()
    {
        $this->form_validation->set_rules('title', 'Title', 'trim|required|max_length[160]');
        $this->form_validation->set_rules('body', 'Body', 'trim|required');
        $this->form_validation->set_rules('cta_label', 'Button label', 'trim|max_length[80]');
        $this->form_validation->set_rules('cta_url', 'Button URL', 'trim|max_length[500]');
        $this->form_validation->set_rules('sort_order', 'Sort order', 'trim|integer');
        return $this->form_validation->run();
    }

    protected function _collect()
    {
        $starts = trim((string) $this->input->post('starts_at'));
        $ends   = trim((string) $this->input->post('ends_at'));

        return array(
            'title'      => trim($this->input->post('title')),
            'body'       => trim($this->input->post('body')),
            'cta_label'  => trim($this->input->post('cta_label')) ?: NULL,
            'cta_url'    => trim($this->input->post('cta_url')) ?: NULL,
            'starts_at'  => $starts !== '' ? date('Y-m-d H:i:s', strtotime($starts)) : NULL,
            'ends_at'    => $ends !== '' ? date('Y-m-d H:i:s', strtotime($ends)) : NULL,
            'sort_order' => (int) $this->input->post('sort_order'),
            'is_active'  => $this->input->post('is_active') ? 1 : 0,
        );
    }

    /**
     * @return string|null|false Relative path, null if no file, false on error
     */
    protected function _handle_image_upload()
    {
        if (empty($_FILES['image']['name'])) {
            return NULL;
        }

        $path = $this->config->item('offer_upload_path');
        if (!is_dir($path)) {
            @mkdir($path, 0755, TRUE);
        }

        $this->upload->initialize(array(
            'upload_path'   => $path,
            'allowed_types' => $this->config->item('upload_allowed_types'),
            'max_size'      => (int) $this->config->item('upload_max_kb'),
            'encrypt_name'  => TRUE,
        ));

        if (!$this->upload->do_upload('image')) {
            $this->flash('error', strip_tags($this->upload->display_errors('', '')));
            return FALSE;
        }

        $data = $this->upload->data();
        return 'uploads/offers/'.$data['file_name'];
    }

    protected function _render_form($offer = NULL)
    {
        $this->render('admin/offers/form', array(
            'title' => $offer ? 'Edit offer' : 'New offer',
            'offer' => $offer,
        ));
    }
}
