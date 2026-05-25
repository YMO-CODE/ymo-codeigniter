<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tasks extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('crm_task_model', 'crm_lead_model', 'crm_contact_model', 'admin_model'));
        $this->load->library('audit');
    }

    public function index()
    {
        $this->require_perm('tasks.view');
        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;

        $filters = array(
            'status'             => $this->input->get('status') ?: 'pending',
            'due'                => $this->input->get('due'),
            'mine'               => $this->input->get('mine'),
            'admin_id'           => $this->admin['id'],
            'assignee_admin_id'  => $this->input->get('assignee'),
        );

        $result = $this->crm_task_model->paginate($filters, $perPage, $offset);

        $this->render('admin/tasks/index', array(
            'title'    => 'Follow-up tasks',
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'page'     => $page,
            'pages'    => max(1, (int) ceil($result['total'] / $perPage)),
            'filters'  => $filters,
            'admins'   => $this->admin_model->list_active(),
            'can_edit' => crm_can('tasks.edit'),
        ));
    }

    public function create()
    {
        $this->require_perm('tasks.edit');
        if ($this->input->method() === 'post') {
            return $this->_save(NULL);
        }
        $this->_form(NULL);
    }

    public function edit($id)
    {
        $this->require_perm('tasks.edit');
        $task = $this->crm_task_model->find($id);
        if (!$task) { show_404(); }
        if ($this->input->method() === 'post') {
            return $this->_save($id);
        }
        $this->_form($task);
    }

    public function done($id)
    {
        $this->require_perm('tasks.edit');
        $task = $this->crm_task_model->find($id);
        if (!$task) { show_404(); }
        $this->crm_task_model->mark_done($id);
        $this->audit->log('admin', $this->admin['id'], 'crm.task.done', 'crm_task', $id, array());
        $this->flash('success', 'Task marked done.');
        redirect(admin_url('tasks'));
    }

    public function skip($id)
    {
        $this->require_perm('tasks.edit');
        $task = $this->crm_task_model->find($id);
        if (!$task) { show_404(); }
        $this->crm_task_model->mark_skipped($id);
        $this->flash('success', 'Task skipped.');
        redirect(admin_url('tasks'));
    }

    protected function _form($task)
    {
        $this->render('admin/tasks/form', array(
            'title' => $task ? 'Edit task' : 'New follow-up',
            'task'  => $task,
            'admins'=> $this->admin_model->list_active(),
            'lead_id'    => (int) $this->input->get('lead_id'),
            'contact_id' => (int) $this->input->get('contact_id'),
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('title', 'Title', 'trim|required|max_length[200]');
        $this->form_validation->set_rules('due_at', 'Due date', 'required');
        $this->form_validation->set_rules('assignee_admin_id', 'Assignee', 'required|integer');
        if (!$this->form_validation->run()) {
            $this->_form($id ? $this->crm_task_model->find($id) : NULL);
            return;
        }

        $payload = array(
            'title'             => $this->input->post('title'),
            'due_at'            => date('Y-m-d H:i:s', strtotime($this->input->post('due_at'))),
            'priority'          => (int) $this->input->post('priority'),
            'assignee_admin_id' => (int) $this->input->post('assignee_admin_id'),
            'lead_id'           => (int) $this->input->post('lead_id') ?: NULL,
            'contact_id'        => (int) $this->input->post('contact_id') ?: NULL,
            'notes'             => $this->input->post('notes') ?: NULL,
        );
        if ($id) {
            $this->crm_task_model->update($id, $payload);
            $this->flash('success', 'Task updated.');
        } else {
            $payload['created_by_admin_id'] = $this->admin['id'];
            $payload['status'] = 'pending';
            $id = $this->crm_task_model->create($payload);
            $this->audit->log('admin', $this->admin['id'], 'crm.task.create', 'crm_task', $id, array());
            $this->flash('success', 'Follow-up scheduled.');
        }
        redirect(admin_url('tasks'));
    }
}
