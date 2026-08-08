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
            'crm_tag_model',
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
            'source_slug' => $this->input->get('source'),
            'stage'       => $this->input->get('stage'),
            'status'      => $this->input->get('status'),
            'assigned_to' => $this->input->get('assigned_to'),
            'priority'    => $this->input->get('priority'),
            'mine'        => $this->input->get('mine'),
            'admin_id'    => $this->admin['id'],
        );

        $source_label = NULL;
        if (!empty($filters['source_slug'])) {
            foreach ($this->crm_lead_model->list_sources() as $s) {
                if ($s['slug'] === $filters['source_slug']) {
                    $source_label = $s['label'];
                    break;
                }
            }
        }

        $result = $this->crm_lead_model->paginate($filters, $perPage, $offset);

        $this->render('admin/leads/index', array(
            'title'        => $source_label ? $source_label.' leads' : 'Leads',
            'rows'         => $result['rows'],
            'total'        => $result['total'],
            'page'         => $page,
            'pages'        => max(1, (int) ceil($result['total'] / $perPage)),
            'filters'      => $filters,
            'sources'      => $this->crm_lead_model->list_sources(),
            'admins'       => $this->admin_model->list_active(),
            'stage_labels' => crm_lead_stages(),
            'can_edit'     => crm_can('leads.edit'),
            'can_assign'   => crm_can('leads.assign'),
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
            'title'        => 'Sales pipeline',
            'columns'      => $this->crm_lead_model->for_pipeline($filters),
            'counts'       => $this->crm_lead_model->stage_counts($filters),
            'filters'      => $filters,
            'admins'       => $this->admin_model->list_active(),
            'stage_labels' => crm_lead_stages(),
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

        $activities = $this->crm_lead_activity_model->for_lead($id);

        $this->render('admin/leads/view', array(
            'title'      => $lead['name'],
            'lead'       => $lead,
            'activities' => $activities,
            'chat_messages' => crm_lead_chat_messages($activities),
            'chat_channel'  => crm_lead_chat_channel($lead),
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

    public function send_chat($id)
    {
        $this->require_perm('leads.edit');
        $lead = $this->crm_lead_model->find_detailed($id);
        if (!$lead) { show_404(); }

        $channel = crm_lead_chat_channel($lead);
        if (!$channel) {
            $this->flash('error', 'This lead is not linked to a WhatsApp or Instagram chat thread.');
            redirect(admin_url('leads/'.$id));
        }

        $this->form_validation->set_rules('body', 'Message', 'trim|required|min_length[1]|max_length[4096]');
        if (!$this->form_validation->run()) {
            $this->flash('error', 'Enter a message to send.');
            redirect(admin_url('leads/'.$id));
        }

        $text = trim((string) $this->input->post('body'));
        $recipient = crm_lead_chat_recipient($lead, $channel);
        if ($recipient === '') {
            $this->flash('error', 'Missing chat recipient for this lead.');
            redirect(admin_url('leads/'.$id));
        }

        $this->load->library('crm_messaging');
        if ($channel === 'whatsapp') {
            $result = $this->crm_messaging->send_whatsapp_text($recipient, $text);
            $activity_type = 'whatsapp';
        } else {
            $result = $this->crm_messaging->send_instagram_text($recipient, $text);
            $activity_type = 'webhook';
        }

        if (empty($result['ok'])) {
            $err = $this->crm_messaging->last_error() ?: 'Message could not be sent.';
            $this->flash('error', $err);
            redirect(admin_url('leads/'.$id));
        }

        $meta = array(
            'direction'  => 'outbound',
            'channel'    => $channel,
            'provider'   => $channel === 'whatsapp' ? 'whatsapp' : 'instagram_dm',
            'message_id' => $result['message_id'] ?? NULL,
            'sender_id'  => $recipient,
            'text'       => $text,
        );
        $this->crm_lead_activity_model->add($id, $this->admin['id'], $activity_type, $text, $meta);
        $this->crm_lead_model->update($id, array('updated_at' => date('Y-m-d H:i:s')));

        $this->audit->log('admin', $this->admin['id'], 'crm.lead.chat_send', 'crm_lead', $id, array(
            'channel'    => $channel,
            'message_id' => $result['message_id'] ?? NULL,
        ));

        $this->flash('success', 'Message sent via '.($channel === 'whatsapp' ? 'WhatsApp' : 'Instagram').'.');
        redirect(admin_url('leads/'.$id));
    }

    public function update_stage($id)
    {
        $this->require_perm('leads.edit');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        $stage  = $this->input->post('stage');
        $status = $this->input->post('status');
        $valid_stages  = array_keys(crm_lead_stages());
        $valid_status  = array('open','converted','junk');

        if (!in_array($stage, $valid_stages, TRUE)) {
            $this->flash('error', 'Invalid stage.');
            redirect(admin_url('leads/'.$id));
        }

        $patch = array('stage' => $stage);
        if (in_array($stage, array('quote_sent', 'lost'), TRUE)) {
            $patch['stage_locked'] = 1;
        } elseif ($stage === 'warm_lead') {
            $patch['stage_locked'] = 0;
        } elseif ($this->input->post('next_follow_up_at') !== NULL) {
            $patch['next_follow_up_at'] = $this->input->post('next_follow_up_at') ?: NULL;
            if (!in_array($stage, crm_lead_manual_stages(), TRUE)) {
                $patch['stage_locked'] = 0;
            }
        }
        if ($this->input->post('next_follow_up_at') !== NULL && empty($patch['stage_locked'])) {
            $nf = trim(str_replace('T', ' ', (string) $this->input->post('next_follow_up_at')));
            if ($nf !== '') {
                $ts = strtotime($nf);
                $patch['next_follow_up_at'] = $ts ? date('Y-m-d H:i:s', $ts) : NULL;
            } else {
                $patch['next_follow_up_at'] = NULL;
            }
        }
        if ($status && in_array($status, $valid_status, TRUE)) {
            $patch['status'] = $status;
        }
        if ($this->input->post('priority') !== NULL && $this->input->post('priority') !== '') {
            $patch['priority'] = (int) $this->input->post('priority');
        }

        $this->crm_lead_model->update($id, $patch);
        if (empty($patch['stage_locked']) && $lead['stage'] !== 'warm_lead') {
            $this->crm_lead_model->recalculate_stage($id);
        }

        $msg = 'Stage updated to '.crm_lead_stage_label($stage);
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
        $this->_copy_lead_tags_to_contact($id, $contact_id);
        $this->crm_contact_model->link_user_if_exists($contact_id);
        $contact = $this->crm_contact_model->find($contact_id);
        $this->crm_lead_model->mark_converted($id, $contact_id, !empty($contact['user_id']) ? (int) $contact['user_id'] : NULL);
        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'system',
            'Lead converted to contact #'.$contact_id
        );
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.convert', 'crm_lead', $id, array(
            'contact_id' => $contact_id,
        ));

        $this->flash('success', 'Lead converted to customer.');
        redirect(admin_url('leads/'.$id));
    }

    protected function _copy_lead_tags_to_contact($lead_id, $contact_id)
    {
        $tag_ids = array();
        foreach ($this->crm_tag_model->for_lead($lead_id) as $t) {
            $tag_ids[] = (int) $t['id'];
        }
        if ($tag_ids) {
            $this->crm_tag_model->merge_contact_tags($contact_id, $tag_ids);
        }
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
        $this->form_validation->set_rules('company', 'Workshop', 'trim|max_length[120]');
        $this->form_validation->set_rules('address', 'Address', 'trim|max_length[255]');
        $this->form_validation->set_rules('car_type', 'Car type', 'trim|max_length[120]');
        $this->form_validation->set_rules('source_id', 'Source', 'required|integer');
        $stage_list = implode(',', array_keys(crm_lead_stages()));
        $this->form_validation->set_rules('stage', 'Stage', 'in_list['.$stage_list.']');
        $this->form_validation->set_rules('status', 'Status', 'in_list[open,converted,junk]');
        $this->form_validation->set_rules('next_follow_up_at', 'Next follow-up', 'trim');
        $this->form_validation->set_rules('priority', 'Priority', 'integer');
        if (crm_can('leads.assign')) {
            $this->form_validation->set_rules('assigned_to', 'Assignee', 'integer');
        }
    }

    protected function _payload_from_post()
    {
        $stage = $this->input->post('stage') ?: 'warm_lead';
        $payload = array(
            'source_id' => (int) $this->input->post('source_id'),
            'name'      => $this->input->post('name'),
            'mobile'    => preg_replace('/\D/', '', (string) $this->input->post('mobile')),
            'email'     => strtolower(trim((string) $this->input->post('email'))),
            'company'   => $this->input->post('company') ?: NULL,
            'address'   => trim((string) $this->input->post('address')) ?: NULL,
            'car_type'  => trim((string) $this->input->post('car_type')) ?: NULL,
            'message'   => $this->input->post('message') ?: NULL,
            'stage'     => $stage,
            'status'    => $this->input->post('status') ?: 'open',
            'priority'  => (int) $this->input->post('priority'),
            'next_follow_up_at' => $this->input->post('next_follow_up_at'),
        );
        if (in_array($stage, array('quote_sent', 'lost'), TRUE)) {
            $payload['stage_locked'] = 1;
        } elseif ($stage === 'warm_lead') {
            $payload['stage_locked'] = 0;
        } else {
            $payload['stage_locked'] = 0;
        }
        if (crm_can('leads.assign')) {
            $assignee = (int) $this->input->post('assigned_to');
            $payload['assigned_to'] = $assignee > 0 ? $assignee : NULL;
        }
        return $payload;
    }
}
