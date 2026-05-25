<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRM lead management — list, create, assign, pipeline, activity log.
 */
class Leads extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'crm_lead_model',
            'crm_lead_activity_model',
            'crm_contact_model',
            'admin_model',
        ));
        $this->load->library('audit');
    }

    public function index()
    {
        $this->require_perm('leads.view');

        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;

        $filters = array(
            'q'           => $this->input->get('q'),
            'source_id'   => $this->input->get('source_id'),
            'stage'       => $this->input->get('stage'),
            'status'      => $this->input->get('status'),
            'assigned_to' => $this->input->get('assigned_to'),
            'priority'    => $this->input->get('priority'),
            'mine'        => $this->input->get('mine'),
            'admin_id'    => $this->admin['id'],
        );

        $result = $this->crm_lead_model->paginate($filters, $perPage, $offset);

        $this->render('admin/leads/index', array(
            'title'   => 'Leads',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / $perPage)),
            'filters' => $filters,
            'sources' => $this->crm_lead_model->list_sources(),
            'admins'  => $this->admin_model->list_active(),
            'can_edit'   => crm_can('leads.edit'),
            'can_assign' => crm_can('leads.assign'),
        ));
    }

    public function pipeline()
    {
        $this->require_perm('leads.view');

        $filters = array(
            'status'      => $this->input->get('status') ?: 'open',
            'assigned_to' => $this->input->get('assigned_to'),
            'mine'        => $this->input->get('mine'),
            'admin_id'    => $this->admin['id'],
        );

        $this->render('admin/leads/pipeline', array(
            'title'   => 'Sales pipeline',
            'columns' => $this->crm_lead_model->for_pipeline($filters),
            'counts'  => $this->crm_lead_model->stage_counts($filters),
            'filters' => $filters,
            'admins'  => $this->admin_model->list_active(),
        ));
    }

    public function create()
    {
        $this->require_perm('leads.edit');

        if ($this->input->method() === 'post') {
            return $this->_store();
        }

        $this->render('admin/leads/form', array(
            'title'   => 'New lead',
            'lead'    => NULL,
            'sources' => $this->crm_lead_model->list_sources(),
            'admins'  => $this->admin_model->list_active(),
        ));
    }

    public function edit($id)
    {
        $this->require_perm('leads.edit');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        if ($this->input->method() === 'post') {
            return $this->_update($id, $lead);
        }

        $this->render('admin/leads/form', array(
            'title'   => 'Edit lead',
            'lead'    => $lead,
            'sources' => $this->crm_lead_model->list_sources(),
            'admins'  => $this->admin_model->list_active(),
        ));
    }

    public function view($id)
    {
        $this->require_perm('leads.view');
        $lead = $this->crm_lead_model->find_detailed($id);
        if (!$lead) { show_404(); }

        $contact = $this->crm_contact_model->find_by_lead($id);

        $this->render('admin/leads/view', array(
            'title'      => $lead['name'],
            'lead'       => $lead,
            'activities' => $this->crm_lead_activity_model->for_lead($id),
            'contact'    => $contact,
            'admins'     => $this->admin_model->list_active(),
            'can_edit'   => crm_can('leads.edit'),
            'can_assign' => crm_can('leads.assign'),
            'can_delete' => crm_can('leads.delete'),
            'can_convert'=> crm_can('contacts.edit'),
        ));
    }

    public function assign($id)
    {
        $this->require_perm('leads.assign');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        $assignee = (int) $this->input->post('assigned_to');
        $this->crm_lead_model->assign($id, $assignee ?: NULL);

        $name = 'Unassigned';
        if ($assignee) {
            $admin = $this->admin_model->find($assignee);
            $name = $admin ? $admin['name'] : 'Admin #'.$assignee;
        }

        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'status_change',
            'Lead assigned to '.$name,
            array('field' => 'assigned_to', 'to' => $assignee)
        );
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.assign', 'crm_lead', $id, array(
            'assigned_to' => $assignee,
        ));

        $this->flash('success', 'Lead assigned to '.$name.'.');
        redirect(admin_url('leads/'.$id));
    }

    public function add_activity($id)
    {
        $this->require_perm('leads.edit');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        $this->form_validation->set_rules('type', 'Type', 'required|in_list[note,call,email,sms,whatsapp]');
        $this->form_validation->set_rules('body', 'Note', 'trim|required|min_length[2]|max_length[5000]');
        if (!$this->form_validation->run()) {
            $this->flash('error', 'Enter a valid activity note.');
            redirect(admin_url('leads/'.$id));
        }

        $type = $this->input->post('type');
        $body = $this->input->post('body');
        $this->crm_lead_activity_model->add($id, $this->admin['id'], $type, $body);
        $this->crm_lead_model->update($id, array());

        $this->flash('success', 'Activity logged.');
        redirect(admin_url('leads/'.$id));
    }

    public function update_stage($id)
    {
        $this->require_perm('leads.edit');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        $stage  = $this->input->post('stage');
        $status = $this->input->post('status');
        $valid_stages  = array('new','contacted','qualified','proposal','won','lost');
        $valid_status  = array('open','converted','junk');

        if (!in_array($stage, $valid_stages, TRUE)) {
            $this->flash('error', 'Invalid stage.');
            redirect(admin_url('leads/'.$id));
        }

        $patch = array('stage' => $stage);
        if ($status && in_array($status, $valid_status, TRUE)) {
            $patch['status'] = $status;
        }
        if ($this->input->post('priority') !== NULL && $this->input->post('priority') !== '') {
            $patch['priority'] = (int) $this->input->post('priority');
        }

        $this->crm_lead_model->update($id, $patch);

        $msg = 'Stage updated to '.str_replace('_', ' ', $stage);
        if (!empty($patch['status']) && $patch['status'] !== $lead['status']) {
            $msg .= '; status → '.$patch['status'];
        }
        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'status_change', $msg, $patch);
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.stage', 'crm_lead', $id, $patch);

        $this->flash('success', 'Lead updated.');
        redirect(admin_url('leads/'.$id));
    }

    public function archive($id)
    {
        $this->require_perm('leads.delete');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        $this->crm_lead_model->soft_delete($id);
        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'system', 'Lead archived');
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.archive', 'crm_lead', $id, array());

        $this->flash('success', 'Lead archived.');
        redirect(admin_url('leads'));
    }

    public function convert($id)
    {
        $this->require_perm('contacts.edit');
        $lead = $this->crm_lead_model->find_detailed($id);
        if (!$lead) { show_404(); }

        $existing = $this->crm_contact_model->find_by_lead($id);
        if ($existing) {
            $this->flash('info', 'This lead was already converted to a contact.');
            redirect(admin_url('leads/'.$id));
        }

        $contact_id = $this->crm_contact_model->create_from_lead($lead);
        $this->crm_lead_model->mark_converted($id, $contact_id);
        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'system',
            'Lead converted to contact #'.$contact_id
        );
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.convert', 'crm_lead', $id, array(
            'contact_id' => $contact_id,
        ));

        $this->flash('success', 'Lead converted to contact.');
        redirect(admin_url('leads/'.$id));
    }

    // --- private -----------------------------------------------------------

    protected function _store()
    {
        $this->_validate_form();
        if (!$this->form_validation->run()) {
            $this->render('admin/leads/form', array(
                'title'   => 'New lead',
                'lead'    => NULL,
                'sources' => $this->crm_lead_model->list_sources(),
                'admins'  => $this->admin_model->list_active(),
            ));
            return;
        }

        $id = $this->crm_lead_model->create($this->_payload_from_post());
        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'system', 'Lead created manually');
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.create', 'crm_lead', $id, array());

        $this->flash('success', 'Lead created.');
        redirect(admin_url('leads/'.$id));
    }

    protected function _update($id, array $lead)
    {
        $this->_validate_form();
        if (!$this->form_validation->run()) {
            $this->render('admin/leads/form', array(
                'title'   => 'Edit lead',
                'lead'    => $lead,
                'sources' => $this->crm_lead_model->list_sources(),
                'admins'  => $this->admin_model->list_active(),
            ));
            return;
        }

        $this->crm_lead_model->update($id, $this->_payload_from_post());
        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'system', 'Lead details updated');
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.update', 'crm_lead', $id, array());

        $this->flash('success', 'Lead saved.');
        redirect(admin_url('leads/'.$id));
    }

    protected function _validate_form()
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('mobile', 'Mobile', 'trim|max_length[20]');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email|max_length[180]');
        $this->form_validation->set_rules('company', 'Company', 'trim|max_length[120]');
        $this->form_validation->set_rules('source_id', 'Source', 'required|integer');
        $this->form_validation->set_rules('stage', 'Stage', 'in_list[new,contacted,qualified,proposal,won,lost]');
        $this->form_validation->set_rules('status', 'Status', 'in_list[open,converted,junk]');
        $this->form_validation->set_rules('priority', 'Priority', 'integer');
        if (crm_can('leads.assign')) {
            $this->form_validation->set_rules('assigned_to', 'Assignee', 'integer');
        }
    }

    protected function _payload_from_post()
    {
        $payload = array(
            'source_id' => (int) $this->input->post('source_id'),
            'name'      => $this->input->post('name'),
            'mobile'    => preg_replace('/\D/', '', (string) $this->input->post('mobile')),
            'email'     => strtolower(trim((string) $this->input->post('email'))),
            'company'   => $this->input->post('company') ?: NULL,
            'message'   => $this->input->post('message') ?: NULL,
            'stage'     => $this->input->post('stage') ?: 'new',
            'status'    => $this->input->post('status') ?: 'open',
            'priority'  => (int) $this->input->post('priority'),
        );
        if (crm_can('leads.assign')) {
            $assignee = (int) $this->input->post('assigned_to');
            $payload['assigned_to'] = $assignee > 0 ? $assignee : NULL;
        }
        return $payload;
    }
}
