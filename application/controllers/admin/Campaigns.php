<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Campaigns extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('crm_campaign_model', 'crm_tag_model', 'admin_model', 'crm_wa_template_model'));
        $this->load->library(array('mailer', 'sms_gateway', 'audit', 'crm_messaging'));
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

        $wa_tpl = ($camp['channel'] === 'whatsapp' && !empty($camp['wa_template_id']))
            ? $this->crm_wa_template_model->find($camp['wa_template_id'])
            : NULL;

        $this->render('admin/campaigns/view', array(
            'title'  => $camp['name'],
            'camp'   => $camp,
            'stats'  => $this->crm_campaign_model->recipient_stats($id),
            'delivery' => $camp['channel'] === 'whatsapp' ? $this->crm_campaign_model->delivery_stats($id) : NULL,
            'wa_tpl' => $wa_tpl,
            'wa_cfg' => $this->crm_campaign_model->wa_config($camp),
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
        $this->_assert_whatsapp_ready($camp);

        // A scheduled campaign already has its recipient list; don't add it twice.
        if (!$this->crm_campaign_model->has_recipients($id)) {
            $segment = json_decode($camp['segment_json'] ?: '{}', TRUE) ?: array();
            $recipients = $this->crm_campaign_model->build_recipients_from_segment($segment, $camp['channel']);
            if (empty($recipients)) {
                $this->flash('error', 'No recipients match this segment.');
                redirect(admin_url('campaigns/'.$id));
            }
            $this->crm_campaign_model->add_recipients($id, $recipients);
        }
        $this->crm_campaign_model->update($id, array('status' => 'scheduled', 'scheduled_at' => date('Y-m-d H:i:s')));
        $this->crm_campaign_model->process_send_batch($id, $this->mailer, $this->sms_gateway);
        $this->flash('success', 'Campaign dispatch started. The first batch went out now; the cron job sends the rest.');
        redirect(admin_url('campaigns/'.$id));
    }

    public function schedule($id)
    {
        $this->require_perm('campaigns.send');
        $camp = $this->crm_campaign_model->find($id);
        if (!$camp) { show_404(); }

        if (!in_array($camp['status'], array('draft', 'scheduled'), TRUE)) {
            $this->flash('error', 'Campaign cannot be scheduled in its current state.');
            redirect(admin_url('campaigns/'.$id));
        }

        $at = $this->input->post('scheduled_at');
        if (!$at || strtotime($at) === FALSE) {
            $this->flash('error', 'Pick a schedule date/time.');
            redirect(admin_url('campaigns/'.$id));
        }
        $this->_assert_whatsapp_ready($camp);

        if (!$this->crm_campaign_model->has_recipients($id)) {
            $segment = json_decode($camp['segment_json'] ?: '{}', TRUE) ?: array();
            $recipients = $this->crm_campaign_model->build_recipients_from_segment($segment, $camp['channel']);
            if (empty($recipients)) {
                $this->flash('error', 'No recipients match this segment.');
                redirect(admin_url('campaigns/'.$id));
            }
            $this->crm_campaign_model->add_recipients($id, $recipients);
        }
        $this->crm_campaign_model->update($id, array(
            'status'       => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime($at)),
        ));
        $this->flash('success', 'Campaign scheduled.');
        redirect(admin_url('campaigns/'.$id));
    }

    /** Stop before touching recipients if a WhatsApp campaign can't actually send. */
    protected function _assert_whatsapp_ready(array $camp)
    {
        if ($camp['channel'] !== 'whatsapp') {
            return;
        }
        $tpl = !empty($camp['wa_template_id']) ? $this->crm_wa_template_model->find($camp['wa_template_id']) : NULL;
        $error = NULL;
        if (!$this->crm_messaging->can_send_whatsapp()) {
            $error = 'WhatsApp Cloud API is not configured yet (CRM_WHATSAPP_ACCESS_TOKEN / CRM_WHATSAPP_PHONE_NUMBER_ID).';
        } elseif (!$tpl) {
            $error = 'Pick an approved WhatsApp template for this campaign.';
        } elseif ($tpl['status'] !== 'APPROVED') {
            $error = 'WhatsApp template "'.$tpl['name'].'" is '.strtolower($tpl['status']).' - sync templates or pick another.';
        } elseif (in_array($tpl['header_type'], array('image', 'document', 'video'), TRUE)
            && $this->crm_campaign_model->wa_config($camp)['header_link'] === '') {
            $error = 'Template "'.$tpl['name'].'" has a '.$tpl['header_type'].' header - add a public https link for it.';
        }
        if ($error) {
            $this->flash('error', $error);
            redirect(admin_url('campaigns/'.$camp['id']));
        }
    }

    protected function _form($camp)
    {
        $this->render('admin/campaigns/form', array(
            'title' => $camp ? 'Edit campaign' : 'New campaign',
            'camp'  => $camp,
            'tags'  => $this->crm_tag_model->all(),
            'wa_templates' => $this->crm_wa_template_model->approved_for_campaigns(),
            'wa_ready'     => $this->crm_messaging->can_send_whatsapp(),
            'wa_cfg'       => $camp ? $this->crm_campaign_model->wa_config($camp) : array('params' => array(), 'header_link' => ''),
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[160]');
        $channel = (string) $this->input->post('channel');
        $this->form_validation->set_rules('channel', 'Channel', 'required|in_list[email,sms,both,whatsapp]');
        if ($channel === 'whatsapp') {
            $this->form_validation->set_rules('wa_template_id', 'WhatsApp template', 'required|is_natural_no_zero');
            $this->form_validation->set_rules('wa_header_link', 'Header media link', 'trim|max_length[500]|regex_match[/^(https:\/\/\S+)?$/]');
            $this->form_validation->set_rules('body', 'Internal note', 'trim');
        } else {
            $this->form_validation->set_rules('body', 'Message', 'trim|required|min_length[5]');
        }
        if (!$this->form_validation->run()) {
            $this->_form($id ? $this->crm_campaign_model->find($id) : NULL);
            return;
        }

        $wa_template_id = NULL;
        $wa_params_json = NULL;
        $body = (string) $this->input->post('body');
        if ($channel === 'whatsapp') {
            $tpl = $this->crm_wa_template_model->find((int) $this->input->post('wa_template_id'));
            if (!$tpl || $tpl['status'] !== 'APPROVED') {
                $this->flash('error', 'Pick an approved WhatsApp template.');
                $this->_form($id ? $this->crm_campaign_model->find($id) : NULL);
                return;
            }
            $params = array();
            foreach ((array) $this->input->post('wa_params') as $i => $p) {
                if ($i >= (int) $tpl['param_count']) {
                    break;
                }
                $params[] = trim((string) $p);
            }
            $wa_template_id = (int) $tpl['id'];
            $wa_params_json = json_encode(array(
                'params'      => $params,
                'header_link' => trim((string) $this->input->post('wa_header_link')),
            ));
            if (trim($body) === '') {
                $body = (string) $tpl['body_text'];
            }
        }

        $segment_type = $this->input->post('segment_type') ?: 'all_contacts';
        $segment = array('type' => $segment_type);
        if ($segment_type === 'tag') {
            $segment['tag_id'] = (int) $this->input->post('tag_id');
        }

        $payload = array(
            'name'         => $this->input->post('name'),
            'channel'      => $channel,
            'subject'      => $this->input->post('subject') ?: NULL,
            'body'         => $body,
            'segment_json' => json_encode($segment),
        );
        if ($this->db->field_exists('wa_template_id', 'crm_campaigns')) {
            $payload['wa_template_id'] = $wa_template_id;
            $payload['wa_params_json'] = $wa_params_json;
        }

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
