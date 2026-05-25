<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Campaigns extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('crm_campaign_model', 'crm_tag_model', 'admin_model'));
        $this->load->library(array('mailer', 'sms_gateway', 'audit'));
    }

    public function index()
    {
        $this->require_perm('campaigns.view');
        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;
        $result  = $this->crm_campaign_model->paginate($perPage, $offset);

        $this->render('admin/campaigns/index', array(
            'title'    => 'Campaigns',
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'page'     => $page,
            'pages'    => max(1, (int) ceil($result['total'] / $perPage)),
            'can_edit' => crm_can('campaigns.edit'),
            'can_send' => crm_can('campaigns.send'),
        ));
    }

    public function create()
    {
        $this->require_perm('campaigns.edit');
        if ($this->input->method() === 'post') {
            return $this->_save(NULL);
        }
        $this->_form(NULL);
    }

    public function edit($id)
    {
        $this->require_perm('campaigns.edit');
        $camp = $this->crm_campaign_model->find($id);
        if (!$camp) { show_404(); }
        if ($this->input->method() === 'post') {
            return $this->_save($id);
        }
        $this->_form($camp);
    }

    public function view($id)
    {
        $this->require_perm('campaigns.view');
        $camp = $this->crm_campaign_model->find($id);
        if (!$camp) { show_404(); }

        $this->render('admin/campaigns/view', array(
            'title'  => $camp['name'],
            'camp'   => $camp,
            'stats'  => $this->crm_campaign_model->recipient_stats($id),
            'can_send' => crm_can('campaigns.send'),
        ));
    }

    public function send($id)
    {
        $this->require_perm('campaigns.send');
        $camp = $this->crm_campaign_model->find($id);
        if (!$camp) { show_404(); }

        if (!in_array($camp['status'], array('draft', 'scheduled'), TRUE)) {
            $this->flash('error', 'Campaign cannot be sent in its current state.');
            redirect(admin_url('campaigns/'.$id));
        }

        $segment = json_decode($camp['segment_json'] ?: '{}', TRUE) ?: array();
        $recipients = $this->crm_campaign_model->build_recipients_from_segment($segment, $camp['channel']);
        if (empty($recipients)) {
            $this->flash('error', 'No recipients match this segment.');
            redirect(admin_url('campaigns/'.$id));
        }

        $this->crm_campaign_model->add_recipients($id, $recipients);
        $this->crm_campaign_model->update($id, array('status' => 'scheduled', 'scheduled_at' => date('Y-m-d H:i:s')));
        $this->crm_campaign_model->process_send_batch($id, $this->mailer, $this->sms_gateway);
        $this->flash('success', 'Campaign dispatch started.');
        redirect(admin_url('campaigns/'.$id));
    }

    public function schedule($id)
    {
        $this->require_perm('campaigns.send');
        $camp = $this->crm_campaign_model->find($id);
        if (!$camp) { show_404(); }

        $at = $this->input->post('scheduled_at');
        if (!$at) {
            $this->flash('error', 'Pick a schedule date/time.');
            redirect(admin_url('campaigns/'.$id));
        }

        $segment = json_decode($camp['segment_json'] ?: '{}', TRUE) ?: array();
        $recipients = $this->crm_campaign_model->build_recipients_from_segment($segment, $camp['channel']);
        $this->crm_campaign_model->add_recipients($id, $recipients);
        $this->crm_campaign_model->update($id, array(
            'status'       => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime($at)),
        ));
        $this->flash('success', 'Campaign scheduled.');
        redirect(admin_url('campaigns/'.$id));
    }

    protected function _form($camp)
    {
        $this->render('admin/campaigns/form', array(
            'title' => $camp ? 'Edit campaign' : 'New campaign',
            'camp'  => $camp,
            'tags'  => $this->crm_tag_model->all(),
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[160]');
        $this->form_validation->set_rules('channel', 'Channel', 'required|in_list[email,sms,both]');
        $this->form_validation->set_rules('body', 'Message', 'trim|required|min_length[5]');
        if (!$this->form_validation->run()) {
            $this->_form($id ? $this->crm_campaign_model->find($id) : NULL);
            return;
        }

        $segment_type = $this->input->post('segment_type') ?: 'all_contacts';
        $segment = array('type' => $segment_type);
        if ($segment_type === 'tag') {
            $segment['tag_id'] = (int) $this->input->post('tag_id');
        }

        $payload = array(
            'name'         => $this->input->post('name'),
            'channel'      => $this->input->post('channel'),
            'subject'      => $this->input->post('subject') ?: NULL,
            'body'         => $this->input->post('body'),
            'segment_json' => json_encode($segment),
        );

        if ($id) {
            $this->crm_campaign_model->update($id, $payload);
        } else {
            $payload['status']     = 'draft';
            $payload['created_by'] = $this->admin['id'];
            $id = $this->crm_campaign_model->create($payload);
            $this->audit->log('admin', $this->admin['id'], 'crm.campaign.create', 'crm_campaign', $id, array());
        }

        $this->flash('success', 'Campaign saved.');
        redirect(admin_url('campaigns/'.$id));
    }
}
