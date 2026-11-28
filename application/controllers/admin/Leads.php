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
            'crm_task_model',
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

        $stage = $this->input->get('stage');
        if ($stage === NULL) {
            $stage = 'open'; // default tab: leads that still need work
        }
        $filters = array(
            'q'           => $this->input->get('q'),
            'source_id'   => $this->input->get('source_id'),
            'source_slug' => $this->input->get('source'),
            'stage'       => $stage === 'all' ? '' : $stage,
            'stage_tab'   => $stage,
            'due'         => $this->input->get('due'),
            'status'      => $this->input->get('status'),
            'assigned_to' => $this->input->get('assigned_to'),
            'priority'    => $this->input->get('priority'),
            'mine'        => $this->input->get('mine'),
            'admin_id'    => $this->admin['id'],
            'sort'        => 'call_first',
        );
        // Default view: my open leads plus unassigned ones. ?who=all shows the whole team.
        $who = $this->input->get('who') === 'all' ? 'all' : 'me';
        if ($who === 'me' && empty($filters['assigned_to'])) {
            $filters['mine'] = 'with_unassigned';
        }
        $filters['who'] = $who;

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

        $tab_filters = $filters;
        unset($tab_filters['stage']);
        $tab_filters['board'] = 1;

        $this->render('admin/leads/index', array(
            'title'        => $source_label ? $source_label.' leads' : 'Leads',
            'counts'       => $this->crm_lead_model->stage_counts(array_diff_key($tab_filters, array('board' => 1))),
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
            'q'           => $this->input->get('q'),
            'source_slug' => $this->input->get('source'),
            'assigned_to' => $this->input->get('assigned_to'),
            'mine'        => $this->input->get('mine'),
            'admin_id'    => $this->admin['id'],
            'board'       => 1,
        );

        $this->render('admin/leads/pipeline', array(
            'title'        => 'Leads board',
            'columns'      => $this->crm_lead_model->for_pipeline($filters),
            'counts'       => $this->crm_lead_model->stage_counts($filters),
            'filters'      => $filters,
            'sources'      => $this->crm_lead_model->list_sources(),
            'admins'       => $this->admin_model->list_active(),
            'stage_labels' => crm_lead_stages(),
            'can_edit'     => crm_can('leads.edit'),
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

        $contact = !empty($lead['converted_contact_id'])
            ? $this->crm_contact_model->find((int) $lead['converted_contact_id'])
            : $this->crm_contact_model->find_by_lead($id);
        if (!$contact && $lead['mobile'] !== '') {
            $contact = $this->crm_contact_model->find_by_mobile($lead['mobile']);
        }
        $booking = NULL;
        if (!empty($lead['booking_id'])) {
            $this->load->model('booking_model');
            $booking = $this->booking_model->find_detailed((int) $lead['booking_id']);
        }
        $other_leads = array_values(array_filter(
            $this->crm_lead_model->for_mobile($lead['mobile'], 10),
            function ($l) use ($id) { return (int) $l['id'] !== (int) $id; }
        ));

        $activities = $this->crm_lead_activity_model->for_lead($id);

        $this->render('admin/leads/view', array(
            'title'      => $lead['name'],
            'lead'       => $lead,
            'booking'    => $booking,
            'other_leads'=> $other_leads,
            'followup'   => $this->crm_task_model->pending_for_lead($id),
            'can_book'   => crm_can('bookings.edit'),
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
        $pending = $this->crm_task_model->pending_for_lead($id);
        if ($pending && $assignee) {
            $this->crm_task_model->update((int) $pending['id'], array('assignee_admin_id' => $assignee));
        }

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
            'provider'   => $channel === 'whatsapp' ? 'whatsapp' : ($channel === 'messenger' ? 'messenger_dm' : 'instagram_dm'),
            'message_id' => $result['message_id'] ?? NULL,
            'sender_id'  => $recipient,
            'text'       => $text,
        );
        $this->crm_lead_activity_model->add($id, $this->admin['id'], $activity_type, $text, $meta);
        if ($channel === 'whatsapp') {
            // Message log: delivery receipts (and Meta's "outside 24h window"
            // failures) arrive later via the WhatsApp webhook.
            $this->load->model('crm_wa_message_model');
            $this->crm_wa_message_model->log(array(
                'wa_message_id' => $result['message_id'] ?? NULL,
                'phone'         => $recipient,
                'direction'     => 'out',
                'kind'          => 'text',
                'body'          => $text,
                'context_type'  => 'lead',
                'context_id'    => (int) $id,
                'status'        => 'sent',
                'admin_id'      => (int) $this->admin['id'],
            ));
        }
        $this->crm_lead_model->update($id, array('updated_at' => date('Y-m-d H:i:s')));

        $this->audit->log('admin', $this->admin['id'], 'crm.lead.chat_send', 'crm_lead', $id, array(
            'channel'    => $channel,
            'message_id' => $result['message_id'] ?? NULL,
        ));

        $this->flash('success', 'Message sent via '.($channel === 'whatsapp' ? 'WhatsApp' : 'Instagram').'.');
        redirect(admin_url('leads/'.$id));
    }

    /**
     * One form for everything that happens on a call / chat / visit (CRM v6):
     * outcome → stage, a note, and the next follow-up (one task per lead).
     */
    public function log_call($id)
    {
        $this->require_perm('leads.edit');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        $outcomes = crm_lead_call_outcomes();
        $outcome  = (string) $this->input->post('outcome');
        if (!isset($outcomes[$outcome])) {
            $this->flash('error', 'Pick what happened.');
            redirect(admin_url('leads/'.$id));
        }
        $channel = in_array($this->input->post('channel'), array('call', 'whatsapp', 'sms', 'email', 'note'), TRUE)
            ? $this->input->post('channel') : 'call';
        $note = trim((string) $this->input->post('note'));
        $lost_reason = (string) $this->input->post('lost_reason');
        if ($outcome === 'lost' && !isset(crm_lead_lost_reasons()[$lost_reason])) {
            $this->flash('error', 'Pick why the lead is lost.');
            redirect(admin_url('leads/'.$id));
        }

        if ($outcome === 'booked') {
            if ($note !== '') {
                $this->crm_lead_activity_model->add($id, $this->admin['id'], $channel, $note, array('outcome' => 'booked'));
            }
            if (empty($lead['assigned_to'])) {
                $this->crm_lead_model->assign($id, (int) $this->admin['id']);
            }
            redirect(admin_url('bookings/new?lead_id='.$id)); // stage flips when the booking is saved
        }

        // 1. Timeline entry
        $label = $outcomes[$outcome]['label'];
        if ($outcome === 'lost') {
            $label = 'Lost - '.crm_lead_lost_reasons()[$lost_reason];
        }
        $body = $outcome === 'note' ? $note : $label.($note !== '' ? "\n".$note : '');
        if ($body === '') {
            $this->flash('error', 'Write a note.');
            redirect(admin_url('leads/'.$id));
        }
        $this->crm_lead_activity_model->add($id, $this->admin['id'], $outcome === 'note' ? 'note' : $channel, $body,
            array('outcome' => $outcome));

        // 2. Stage
        $to = $outcomes[$outcome]['stage'];
        if ($to === NULL && $lead['stage'] === 'new' && $outcome !== 'note') {
            $to = 'contacted';
        }
        if ($to && $to !== $lead['stage']) {
            $this->crm_lead_model->set_stage($id, $to, $outcome === 'lost' ? $lost_reason : NULL);
        } else {
            $this->crm_lead_model->update($id, array());
        }
        if ($outcome === 'interested') {
            $this->crm_lead_model->mark_qualified($id); // Google Ads "CRM - Qualified lead"
        }
        if (empty($lead['assigned_to'])) {
            $this->crm_lead_model->assign($id, (int) $this->admin['id']);
        }

        // 3. Follow-up (lost → none)
        if ($outcome === 'lost') {
            $this->crm_task_model->close_lead_followups($id);
        } else {
            $when = trim((string) $this->input->post('follow_up'));
            $due = $this->_followup_datetime($when, (string) $this->input->post('follow_up_date'));
            if ($when === 'none') {
                $this->crm_task_model->set_lead_followup($id, NULL, NULL);
            } elseif ($due) {
                $this->crm_task_model->set_lead_followup($id, $due, $lead['assigned_to'] ?: $this->admin['id'], $this->admin['id'],
                    $note !== '' ? mb_substr($note, 0, 500) : NULL);
            }
        }

        $this->audit->log('admin', $this->admin['id'], 'crm.lead.log', 'crm_lead', $id, array('outcome' => $outcome));
        $msg = 'Saved.';
        if ($to && $to !== $lead['stage']) {
            $msg = 'Saved - lead is now '.crm_lead_stage_label($to).'.';
        }
        $this->flash('success', $msg);
        redirect(admin_url('leads/'.$id));
    }

    /** Quick stage change (board / dropdown). */
    public function update_stage($id)
    {
        $this->require_perm('leads.edit');
        $lead = $this->crm_lead_model->find($id);
        if (!$lead) { show_404(); }

        $stage = (string) $this->input->post('stage');
        if (!isset(crm_lead_stages()[$stage])) {
            $this->flash('error', 'Invalid stage.');
            redirect(admin_url('leads/'.$id));
        }
        if ($stage === 'booked') {
            redirect(admin_url('bookings/new?lead_id='.$id));
        }
        $reason = (string) $this->input->post('lost_reason');
        if ($stage === 'lost' && !isset(crm_lead_lost_reasons()[$reason])) {
            $reason = 'other';
        }
        $this->crm_lead_model->set_stage($id, $stage, $stage === 'lost' ? $reason : NULL);
        if ($stage === 'lost') {
            $this->crm_task_model->close_lead_followups($id);
        }
        if ($this->input->post('priority') !== NULL && $this->input->post('priority') !== '') {
            $this->crm_lead_model->update($id, array('priority' => (int) $this->input->post('priority')));
        }
        $this->crm_lead_activity_model->add($id, $this->admin['id'], 'status_change',
            'Stage: '.crm_lead_stage_label($lead['stage']).' → '.crm_lead_stage_label($stage)
            .($stage === 'lost' ? ' ('.crm_lead_lost_reasons()[$reason].')' : ''),
            array('stage' => $stage));
        $this->audit->log('admin', $this->admin['id'], 'crm.lead.stage', 'crm_lead', $id, array('stage' => $stage));

        $this->flash('success', 'Lead moved to '.crm_lead_stage_label($stage).'.');
        $back = $this->input->post('back');
        redirect($back === 'board' ? admin_url('leads/pipeline') : admin_url('leads/'.$id));
    }

    /**
     * @param string $when today|tomorrow|3d|1w|2w|1m|custom|none
     * @return string|null Y-m-d H:i:s
     */
    protected function _followup_datetime($when, $custom)
    {
        $at10 = function ($ts) { return date('Y-m-d 10:00:00', $ts); };
        switch ($when) {
            case 'today':    return date('Y-m-d H:i:s', max(time() + 3600, strtotime('today 17:00')));
            case 'tomorrow': return $at10(strtotime('+1 day'));
            case '2d':       return $at10(strtotime('+2 days'));
            case '3d':       return $at10(strtotime('+3 days'));
            case '1w':       return $at10(strtotime('+7 days'));
            case '2w':       return $at10(strtotime('+14 days'));
            case '1m':       return $at10(strtotime('+1 month'));
            case 'custom':
                $ts = strtotime(str_replace('T', ' ', $custom));
                return $ts ? date('Y-m-d H:i:s', $ts) : NULL;
        }
        return NULL;
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

    /** Legacy "Convert to customer": customers now come from bookings. */
    public function convert($id)
    {
        redirect(admin_url('bookings/new?lead_id='.(int) $id));
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

        $payload = $this->_payload_from_post();
        $follow = $payload['next_follow_up_at'] ?? NULL;
        unset($payload['next_follow_up_at']);
        $id = $this->crm_lead_model->create($payload);
        if ($follow) {
            $this->crm_task_model->set_lead_followup($id, $follow, $payload['assigned_to'] ?? NULL, $this->admin['id']);
        }
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

        $payload = $this->_payload_from_post();
        $follow = array_key_exists('next_follow_up_at', $payload) ? $payload['next_follow_up_at'] : FALSE;
        unset($payload['next_follow_up_at']);
        if (isset($payload['stage']) && $payload['stage'] === 'booked' && $lead['stage'] !== 'booked') {
            unset($payload['stage']); // booking is created from the Book service screen
        }
        $this->crm_lead_model->update($id, $payload);
        if (isset($payload['stage']) && in_array($payload['stage'], array('booked', 'lost'), TRUE)) {
            $this->crm_task_model->close_lead_followups($id);
        } elseif ($follow !== FALSE && (string) $follow !== (string) $lead['next_follow_up_at']) {
            $this->crm_task_model->set_lead_followup($id, $follow ?: NULL, $payload['assigned_to'] ?? $lead['assigned_to'], $this->admin['id']);
        }
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
        $this->form_validation->set_rules('next_follow_up_at', 'Next follow-up', 'trim');
        $this->form_validation->set_rules('priority', 'Priority', 'integer');
        if (crm_can('leads.assign')) {
            $this->form_validation->set_rules('assigned_to', 'Assignee', 'integer');
        }
    }

    protected function _payload_from_post()
    {
        $stage = $this->input->post('stage') ?: 'new';
        $follow = trim(str_replace('T', ' ', (string) $this->input->post('next_follow_up_at')));
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
            'lost_reason' => $stage === 'lost' ? ($this->input->post('lost_reason') ?: 'other') : NULL,
            'priority'  => (int) $this->input->post('priority'),
            'next_follow_up_at' => $follow !== '' && strtotime($follow) ? date('Y-m-d H:i:s', strtotime($follow)) : NULL,
        );
        if (crm_can('leads.assign')) {
            $assignee = (int) $this->input->post('assigned_to');
            $payload['assigned_to'] = $assignee > 0 ? $assignee : NULL;
        }
        return $payload;
    }
}
