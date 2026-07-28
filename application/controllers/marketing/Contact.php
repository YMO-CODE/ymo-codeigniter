<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contact extends Marketing_Controller
{
    public function index()
    {
        if ($this->input->method() === 'post') {
            return $this->_submit();
        }

        $this->_show_form();
    }

    protected function _show_form()
    {
        $this->page_meta = array(
            'title'            => 'Contact Us',
            'meta_description' => 'Contact Your Mechanic Online - call, email, or send an enquiry. Mon–Sat 9 AM to 8 PM.',
            'h1'               => 'Contact us',
            'canonical_path'   => 'contact-us',
        );
        $this->render_marketing('marketing/contact', array(
            'phone'       => $this->config->item('ymo_support_phone'),
            'email'       => $this->config->item('ymo_support_email'),
            'booking_url' => ymo_booking_url('packages'),
        ));
    }

    protected function _submit()
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|max_length[20]');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email|max_length[160]');
        $this->form_validation->set_rules('message', 'Message', 'trim|required|max_length[2000]');

        if (!$this->form_validation->run()) {
            $this->flash('error', validation_errors());
            return $this->_show_form();
        }

        $payload = array(
            'name'    => trim($this->input->post('name')),
            'mobile'  => trim($this->input->post('mobile')),
            'email'   => trim($this->input->post('email')),
            'message' => trim($this->input->post('message')),
        );

        $lead_id = $this->_store_enquiry($payload);

        if ($lead_id) {
            $this->flash('success', 'Thanks - we received your message and will call you shortly.');
        } else {
            log_message('error', 'Contact form enquiry could not be saved to crm_leads.');
            $this->flash('error', 'We could not save your enquiry right now. Please call us directly and we will help you.');
        }

        redirect('contact-us');
    }

    /**
     * Persist a marketing-site contact enquiry as a CRM lead.
     *
     * @return int Lead id, or 0 when CRM is unavailable.
     */
    protected function _store_enquiry(array $payload)
    {
        if (!$this->db->table_exists('crm_leads')) {
            return 0;
        }

        $this->load->model('crm_lead_model');
        $lead_id = $this->crm_lead_model->ingest('website', array(
            'name'    => $payload['name'],
            'mobile'  => $payload['mobile'],
            'email'   => $payload['email'],
            'message' => $payload['message'],
            'raw'     => $payload,
        ), NULL, 'marketing_contact');

        if (!$lead_id) {
            return 0;
        }

        $this->load->model('crm_lead_activity_model');
        $preview = function_exists('mb_substr')
            ? mb_substr($payload['message'], 0, 240)
            : substr($payload['message'], 0, 240);
        $this->crm_lead_activity_model->add(
            $lead_id,
            NULL,
            'note',
            'Enquiry from contact form: '.$preview,
            array('source' => 'marketing_contact')
        );

        return $lead_id;
    }
}
