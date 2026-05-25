<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vehicle CRUD for the signed-in customer. Soft-deletes preserve history
 * so a deleted vehicle remains attached to its prior bookings.
 */
class Vehicles extends Customer_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('vehicle_model');
        $this->load->helper('file');
    }

    public function index()
    {
        $this->render('vehicles/index', array(
            'title'    => 'My vehicles',
            'vehicles' => $this->vehicle_model->for_user($this->user['id']),
        ));
    }

    public function create()
    {
        if ($this->input->method() === 'post' && $this->_validate(NULL)) {
            $payload = $this->_collect_payload();
            $payload['user_id'] = $this->user['id'];

            $upload = $this->_upload_image();
            if ($upload === FALSE) {
                return $this->_render_form();
            }
            if (!empty($upload)) {
                $payload['image_path'] = $upload;
            }

            $this->vehicle_model->create($payload);
            $this->flash('success', 'Vehicle added.');
            redirect(site_url('vehicles'));
        }
        $this->_render_form();
    }

    public function edit($id)
    {
        $vehicle = $this->vehicle_model->find_for_user($id, $this->user['id']);
        if (!$vehicle) {
            show_404();
        }

        if ($this->input->method() === 'post' && $this->_validate($id)) {
            $patch = $this->_collect_payload();

            $upload = $this->_upload_image();
            if ($upload === FALSE) {
                return $this->_render_form($vehicle);
            }
            if (!empty($upload)) {
                $patch['image_path'] = $upload;
                if (!empty($vehicle['image_path'])) {
                    @unlink($this->config->item('upload_vehicle_path').basename($vehicle['image_path']));
                }
            }

            $this->vehicle_model->update($id, $patch);
            $this->flash('success', 'Vehicle updated.');
            redirect(site_url('vehicles'));
        }
        $this->_render_form($vehicle);
    }

    public function delete($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
        $vehicle = $this->vehicle_model->find_for_user($id, $this->user['id']);
        if (!$vehicle) {
            show_404();
        }
        $this->vehicle_model->soft_delete($id);
        $this->flash('success', 'Vehicle removed.');
        redirect(site_url('vehicles'));
    }

    // --- helpers -----------------------------------------------------------

    protected function _validate($existing_id)
    {
        $this->form_validation->set_rules('make_id',        'Make',           'trim|required|integer');
        $this->form_validation->set_rules('variant',        'Variant',        'trim|required|min_length[2]|max_length[120]');
        $this->form_validation->set_rules('vehicle_number', 'Vehicle number', 'trim|required|max_length[20]|callback__valid_plate');

        return $this->form_validation->run();
    }

    public function _valid_plate($value)
    {
        // Indian plate formats — accept with or without spaces/dashes; store uppercase + collapsed
        $clean = strtoupper(preg_replace('/[\s\-]/', '', $value));
        if (!preg_match('/^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{4}$/', $clean)) {
            $this->form_validation->set_message('_valid_plate', 'Enter a valid Indian vehicle number (e.g. MH12AB1234).');
            return FALSE;
        }
        $_POST['vehicle_number'] = $clean;
        return TRUE;
    }

    protected function _collect_payload()
    {
        return array(
            'make_id'        => (int) $this->input->post('make_id'),
            'variant'        => trim($this->input->post('variant')),
            'vehicle_number' => trim($this->input->post('vehicle_number')),
        );
    }

    /**
     * Handle image upload. Returns relative path, '' if no file, or FALSE on
     * validation failure (in which case a flash is set).
     *
     * @return string|false
     */
    protected function _upload_image()
    {
        if (empty($_FILES['image']['name'])) {
            return '';
        }

        $dir = $this->config->item('upload_vehicle_path');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, TRUE);
        }

        $config = array(
            'upload_path'   => $dir,
            'allowed_types' => $this->config->item('upload_allowed_types'),
            'max_size'      => (int) $this->config->item('upload_max_kb'),
            'encrypt_name'  => TRUE,
            'remove_spaces' => TRUE,
        );
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('image')) {
            $this->flash('error', strip_tags($this->upload->display_errors()));
            return FALSE;
        }

        $info = $this->upload->data();
        $this->_resize($info['full_path']);

        return $this->config->item('upload_vehicle_url').$info['file_name'];
    }

    protected function _resize($abs_path)
    {
        $max_w = (int) $this->config->item('upload_image_max_w');
        if ($max_w <= 0) {
            return;
        }
        $cfg = array(
            'image_library'   => 'gd2',
            'source_image'    => $abs_path,
            'maintain_ratio'  => TRUE,
            'width'           => $max_w,
            'height'          => $max_w,
            'master_dim'      => 'auto',
            'quality'         => '85%',
        );
        $this->load->library('image_lib', $cfg);
        $this->image_lib->resize();
        $this->image_lib->clear();
    }

    protected function _render_form($vehicle = NULL)
    {
        $this->render('vehicles/form', array(
            'title'   => $vehicle ? 'Edit vehicle' : 'Add a vehicle',
            'vehicle' => $vehicle,
            'makes'   => $this->vehicle_model->makes(),
        ));
    }
}
