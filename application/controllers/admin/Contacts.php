<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contacts extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'crm_contact_model', 'crm_tag_model', 'crm_task_model', 'booking_model', 'user_model',
        ));
        $this->load->library('audit');
    }

    public function index()
    {
        $this->require_perm('contacts.view');
        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;
        $filters = array(
            'q'      => $this->input->get('q'),
            'tag_id' => $this->input->get('tag_id'),
        );
        $result = $this->crm_contact_model->paginate($filters, $perPage, $offset);

        $this->render('admin/contacts/index', array(
            'title'   => 'CRM Contacts',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / $perPage)),
            'filters' => $filters,
            'tags'    => $this->crm_tag_model->all(),
            'can_edit'=> crm_can('contacts.edit'),
        ));
    }

    public function create()
    {
        $this->require_perm('contacts.edit');
        if ($this->input->method() === 'post') {
            return $this->_save(NULL);
        }
        $this->_form(NULL);
    }

    public function view($id)
    {
        $this->require_perm('contacts.view');
        $contact = $this->crm_contact_model->find_detailed($id);
        if (!$contact) { show_404(); }

        $bookings = array();
        if (!empty($contact['user_id'])) {
            $bookings = $this->booking_model->for_user((int) $contact['user_id'], 20, 0);
        }

        $this->render('admin/contacts/view', array(
            'title'    => $contact['name'],
            'contact'  => $contact,
            'tags'     => $this->crm_tag_model->for_contact($id),
            'bookings' => $bookings,
            'tasks'    => $this->crm_task_model->paginate(array('contact_id' => $id), 10, 0)['rows'],
            'can_edit' => crm_can('contacts.edit'),
        ));
    }

    public function edit($id)
    {
        $this->require_perm('contacts.edit');
        $contact = $this->crm_contact_model->find($id);
        if (!$contact) { show_404(); }
        if ($this->input->method() === 'post') {
            return $this->_save($id);
        }
        $this->_form($contact);
    }

    public function export()
    {
        $this->require_perm('contacts.view');
        $rows = $this->crm_contact_model->export_all(array('q' => $this->input->get('q')));
        $data = array();
        foreach ($rows as $r) {
            $data[] = array($r['name'], $r['mobile'], $r['email'], $r['company'], $r['created_at']);
        }
        crm_csv_download('crm-contacts-'.date('Y-m-d').'.csv',
            array('Name', 'Mobile', 'Email', 'Company', 'Created'),
            $data
        );
    }

    protected function _form($contact)
    {
        $tag_ids = array();
        if ($contact) {
            foreach ($this->crm_tag_model->for_contact($contact['id']) as $t) {
                $tag_ids[] = (int) $t['id'];
            }
        }
        $this->render('admin/contacts/form', array(
            'title'   => $contact ? 'Edit contact' : 'New contact',
            'contact' => $contact,
            'tags'    => $this->crm_tag_model->all(),
            'tag_ids' => $tag_ids,
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email|max_length[180]');
        if (!$this->form_validation->run()) {
            $this->_form($id ? $this->crm_contact_model->find($id) : NULL);
            return;
        }

        $payload = array(
            'name'          => $this->input->post('name'),
            'mobile'        => preg_replace('/\D/', '', (string) $this->input->post('mobile')),
            'email'         => strtolower(trim((string) $this->input->post('email'))),
            'company'       => $this->input->post('company') ?: NULL,
            'notes'         => $this->input->post('notes') ?: NULL,
            'email_opt_out' => $this->input->post('email_opt_out') ? 1 : 0,
            'sms_opt_out'   => $this->input->post('sms_opt_out') ? 1 : 0,
        );

        if ($id) {
            $this->crm_contact_model->update($id, $payload);
        } else {
            $id = $this->crm_contact_model->create($payload);
            $this->audit->log('admin', $this->admin['id'], 'crm.contact.create', 'crm_contact', $id, array());
        }

        $new_tag = trim((string) $this->input->post('new_tag'));
        $tag_ids = (array) $this->input->post('tag_ids');
        if ($new_tag !== '') {
            $tag_ids[] = $this->crm_tag_model->find_or_create($new_tag);
        }
        $this->crm_tag_model->sync_contact_tags($id, $tag_ids);

        $this->flash('success', 'Contact saved.');
        redirect(admin_url('contacts/'.$id));
    }
}
