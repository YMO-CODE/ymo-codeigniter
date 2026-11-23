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
        // Bots: hidden field filled, forged stamp or submitted in under 3 seconds.
        // Act as if it worked so they don't adapt, but save and send nothing.
        $trap = ymo_form_trap_tripped();
        if ($trap) {
            log_message('info', '[spam] contact form dropped: '.$trap.' ip='.$this->input->ip_address());
            $this->flash('success', 'Thanks - we received your message and will call you shortly.');
            redirect('contact-us');
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|max_length[20]|callback__valid_indian_mobile');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email|max_length[160]');
        $this->form_validation->set_rules('message', 'Message', 'trim|required|max_length[2000]');

        if (!$this->form_validation->run()) {
            $this->flash('error', trim(strip_tags(validation_errors())));
            return $this->_show_form();
        }

        $payload = array(
            'name'    => trim($this->input->post('name')),
            'mobile'  => ymo_indian_mobile($this->input->post('mobile')),
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

    public function _valid_indian_mobile($value)
    {
        if (ymo_indian_mobile($value) === '') {
            $this->form_validation->set_message('_valid_indian_mobile', 'Please enter a valid 10-digit Indian mobile number.');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Short "call me back" form on area and service pages (name, mobile, car).
     * POST /request-callback → CRM lead (google_ads when the visit came from an ad)
     * → /request-callback/thanks, which fires the lead conversion.
     */
    public function callback()
    {
        $from = $this->_safe_return_path($this->input->post('from'));
        if ($this->input->method() !== 'post') {
            redirect($from !== '' ? $from : '/');
        }

        $trap = ymo_form_trap_tripped(2);
        if ($trap) {
            log_message('info', '[spam] callback form dropped: '.$trap.' ip='.$this->input->ip_address());
            redirect('request-callback/thanks');
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|min_length[2]|max_length[120]');
        $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|max_length[20]|callback__valid_indian_mobile');
        $this->form_validation->set_rules('car', 'Car', 'trim|max_length[80]');

        if (!$this->form_validation->run()) {
            $this->flash('error', trim(strip_tags(validation_errors())));
            redirect(($from !== '' ? $from : '/').'#callback');
        }

        $car = trim((string) $this->input->post('car'));
        $page = $from !== '' ? $from : '/';
        $payload = array(
            'name'    => trim($this->input->post('name')),
            'mobile'  => ymo_indian_mobile($this->input->post('mobile')),
            'email'   => '',
            'message' => 'Please call me back.'.($car !== '' ? ' Car: '.$car.'.' : '').' Page: '.$page,
        );

        $lead_id = $this->_store_enquiry($payload, 'Call-back request', 'Call-back request from '.$page);
        if (!$lead_id) {
            log_message('error', 'Call-back request could not be saved to crm_leads.');
            $this->flash('error', 'We could not save your request right now. Please call us on '.$this->config->item('ymo_support_phone').'.');
            redirect($page.'#callback');
        }

        redirect('request-callback/thanks');
    }

    public function callback_thanks()
    {
        $this->page_meta = array(
            'title'            => 'Thanks - we will call you back',
            'meta_description' => 'Your call-back request reached Your Mechanic Online.',
            'h1'               => 'Thanks - we will call you shortly',
            'canonical_path'   => 'request-callback/thanks',
            'meta_robots'      => 'noindex, nofollow',
        );
        $this->render_marketing('marketing/callback_thanks', array(
            'phone' => $this->config->item('ymo_support_phone'),
        ));
    }

    /** Only same-site paths like "/car-servicing-in-warje-pune"; anything else → ''. */
    protected function _safe_return_path($raw)
    {
        $p = '/'.ltrim(trim((string) $raw), '/');
        return preg_match('#^/[a-z0-9][a-z0-9\-/]{0,150}$#', $p) ? $p : '';
    }

    /**
     * Persist a marketing-site contact enquiry as a CRM lead.
     *
     * @return int Lead id, or 0 when CRM is unavailable.
     */
    protected function _store_enquiry(array $payload, $label = 'Contact form', $note_prefix = 'Enquiry from contact form')
    {
        if (!$this->db->table_exists('crm_leads')) {
            return 0;
        }

        $this->load->model('crm_lead_model');
        $this->load->helper('marketing');
        $attr = ymo_ad_attribution();
        $raw = $payload;
        if ($attr) {
            $raw['utm'] = $attr;
        }
        $source = (ymo_is_google_ads_visit($attr) && $this->crm_lead_model->source_id_by_slug('google_ads') > 0) ? 'google_ads' : 'website';
        $lead_id = $this->crm_lead_model->ingest($source, array(
            'name'    => $payload['name'],
            'mobile'  => $payload['mobile'],
            'email'   => $payload['email'],
            'message' => $payload['message'],
            'raw'     => $raw,
        ), NULL, 'marketing_contact');

        if (!$lead_id) {
            return 0;
        }

        $this->load->model('crm_lead_activity_model');

        // Links, random strings, sales pitches: keep a record but file it as spam, no email.
        $spam = ymo_spam_reason($payload['name'], $payload['message']);
        if ($spam) {
            $this->crm_lead_model->set_stage($lead_id, 'lost', 'spam');
            $this->crm_lead_activity_model->add($lead_id, NULL, 'system',
                'Filed as spam automatically ('.$spam.'). No notification sent.', array('source' => 'marketing_contact'));
            log_message('info', '[spam] contact form lead #'.$lead_id.' filed as spam: '.$spam);
            return $lead_id;
        }

        $preview = function_exists('mb_substr')
            ? mb_substr($payload['message'], 0, 240)
            : substr($payload['message'], 0, 240);
        $this->crm_lead_activity_model->add(
            $lead_id,
            NULL,
            'note',
            $note_prefix.': '.$preview,
            array('source' => 'marketing_contact')
        );

        crm_notify_admin_new_lead($lead_id, $source === 'google_ads' ? $label.' (Google Ads)' : $label, array(
            'name'    => $payload['name'],
            'mobile'  => $payload['mobile'],
            'email'   => $payload['email'],
            'message' => $payload['message'],
        ));

        return $lead_id;
    }
}
