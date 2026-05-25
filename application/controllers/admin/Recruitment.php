<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recruitment extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('crm_candidate_model', 'admin_model'));
        $this->load->library(array('upload', 'audit'));
    }

    public function index()
    {
        $this->require_perm('recruitment.view');
        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;
        $filters = array(
            'q'     => $this->input->get('q'),
            'stage' => $this->input->get('stage'),
        );
        $result = $this->crm_candidate_model->paginate($filters, $perPage, $offset);

        $this->render('admin/recruitment/index', array(
            'title'    => 'Recruitment',
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'page'     => $page,
            'pages'    => max(1, (int) ceil($result['total'] / $perPage)),
            'filters'  => $filters,
            'can_edit' => crm_can('recruitment.edit'),
        ));
    }

    public function create()
    {
        $this->require_perm('recruitment.edit');
        if ($this->input->method() === 'post') {
            return $this->_save(NULL);
        }
        $this->_form(NULL);
    }

    public function view($id)
    {
        $this->require_perm('recruitment.view');
        $candidate = $this->crm_candidate_model->find_detailed($id);
        if (!$candidate) { show_404(); }

        $this->render('admin/recruitment/view', array(
            'title'       => $candidate['name'],
            'candidate'   => $candidate,
            'documents'   => $this->crm_candidate_model->documents_for($id),
            'interviews'  => $this->crm_candidate_model->interviews_for($id),
            'admins'      => $this->admin_model->list_active(),
            'can_edit'    => crm_can('recruitment.edit'),
        ));
    }

    public function edit($id)
    {
        $this->require_perm('recruitment.edit');
        $candidate = $this->crm_candidate_model->find($id);
        if (!$candidate) { show_404(); }
        if ($this->input->method() === 'post') {
            return $this->_save($id);
        }
        $this->_form($candidate);
    }

    public function upload_resume($id)
    {
        $this->require_perm('recruitment.edit');
        $candidate = $this->crm_candidate_model->find($id);
        if (!$candidate) { show_404(); }

        $path = $this->config->item('crm_resume_upload_path');
        if (!is_dir($path)) {
            @mkdir($path, 0755, TRUE);
        }

        $config = array(
            'upload_path'   => $path,
            'allowed_types' => $this->config->item('crm_resume_allowed_types'),
            'max_size'      => (int) $this->config->item('crm_resume_max_kb'),
            'encrypt_name'  => TRUE,
        );
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('resume')) {
            $this->flash('error', strip_tags($this->upload->display_errors('', '')));
            redirect(admin_url('recruitment/'.$id));
        }

        $data = $this->upload->data();
        $rel = 'uploads/crm/resumes/'.$data['file_name'];
        $this->crm_candidate_model->add_document($id, $rel, $data['orig_name'], $data['file_type']);
        $this->flash('success', 'Resume uploaded.');
        redirect(admin_url('recruitment/'.$id));
    }

    public function schedule_interview($id)
    {
        $this->require_perm('recruitment.edit');
        $candidate = $this->crm_candidate_model->find($id);
        if (!$candidate) { show_404(); }

        $this->form_validation->set_rules('scheduled_at', 'Date/time', 'required');
        if (!$this->form_validation->run()) {
            $this->flash('error', 'Enter interview date and time.');
            redirect(admin_url('recruitment/'.$id));
        }

        $this->crm_candidate_model->schedule_interview(array(
            'candidate_id' => (int) $id,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime($this->input->post('scheduled_at'))),
            'location'     => $this->input->post('location') ?: NULL,
            'notes'        => $this->input->post('notes') ?: NULL,
            'created_by'   => $this->admin['id'],
            'status'       => 'scheduled',
        ));
        $this->crm_candidate_model->update($id, array('stage' => 'interview'));
        $this->flash('success', 'Interview scheduled.');
        redirect(admin_url('recruitment/'.$id));
    }

    protected function _form($candidate)
    {
        $this->render('admin/recruitment/form', array(
            'title'     => $candidate ? 'Edit candidate' : 'Add candidate',
            'candidate' => $candidate,
            'admins'    => $this->admin_model->list_active(),
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email|max_length[180]');
        if (!$this->form_validation->run()) {
            $this->_form($id ? $this->crm_candidate_model->find($id) : NULL);
            return;
        }

        $payload = array(
            'name'        => $this->input->post('name'),
            'email'       => strtolower(trim((string) $this->input->post('email'))),
            'mobile'      => preg_replace('/\D/', '', (string) $this->input->post('mobile')),
            'position'    => $this->input->post('position') ?: NULL,
            'stage'       => $this->input->post('stage') ?: 'applied',
            'notes'       => $this->input->post('notes') ?: NULL,
            'assigned_to' => (int) $this->input->post('assigned_to') ?: NULL,
        );

        if ($id) {
            $this->crm_candidate_model->update($id, $payload);
            $this->flash('success', 'Candidate updated.');
            redirect(admin_url('recruitment/'.$id));
        }

        $id = $this->crm_candidate_model->create($payload);
        $this->audit->log('admin', $this->admin['id'], 'crm.candidate.create', 'crm_candidate', $id, array());
        $this->flash('success', 'Candidate added.');
        redirect(admin_url('recruitment/'.$id));
    }
}
