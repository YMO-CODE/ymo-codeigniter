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

    public function import()
    {
        $this->require_perm('contacts.edit');
        $this->render('admin/contacts/import', array(
            'title'   => 'Import contacts',
            'preview' => NULL,
        ));
    }

    public function import_template()
    {
        $this->require_perm('contacts.edit');
        crm_csv_download('crm-contacts-import-template.csv',
            array('name', 'mobile', 'email', 'company', 'notes', 'tags'),
            array(
                array('Rajesh Kumar', '9876543210', 'rajesh@example.com', '', 'Sample visit note', 'exhibition-wakad-2026'),
            )
        );
    }

    public function import_preview()
    {
        $this->require_perm('contacts.edit');
        if (empty($_FILES['csv_file']['tmp_name'])) {
            $this->flash('danger', 'Please choose a CSV file to upload.');
            redirect(admin_url('contacts/import'));
        }

        $rows = crm_parse_contacts_csv($_FILES['csv_file']['tmp_name']);
        if (empty($rows)) {
            $this->flash('danger', 'No valid rows found. Expected columns: name, mobile, email, company, notes, tags');
            redirect(admin_url('contacts/import'));
        }

        $preview = array(
            'total'     => count($rows),
            'new'       => 0,
            'duplicate' => 0,
            'sample'    => array(),
        );
        foreach ($rows as $i => $row) {
            $existing = $this->crm_contact_model->find_existing($row);
            if ($existing) {
                $preview['duplicate']++;
            } else {
                $preview['new']++;
            }
            if ($i < 15) {
                $preview['sample'][] = array(
                    'row'      => $row,
                    'existing' => $existing,
                );
            }
        }

        if (!$this->_store_pending_import($_FILES['csv_file']['tmp_name'])) {
            $this->flash('danger', 'Could not save upload for import. Check storage/import/pending is writable.');
            redirect(admin_url('contacts/import'));
        }

        $this->render('admin/contacts/import', array(
            'title'   => 'Import contacts — preview',
            'preview' => $preview,
        ));
    }

    public function import_commit()
    {
        $this->require_perm('contacts.edit');
        $path = $this->_pending_import_path();
        if (!$path) {
            $this->flash('danger', 'Import file expired or missing. Upload the CSV again.');
            redirect(admin_url('contacts/import'));
        }

        $rows = crm_parse_contacts_csv($path);
        if (empty($rows)) {
            $this->_clear_pending_import();
            $this->flash('danger', 'Import file could not be read. Upload the CSV again.');
            redirect(admin_url('contacts/import'));
        }

        $policy = $this->input->post('duplicate_policy');
        if (!in_array($policy, array('skip', 'update', 'merge_notes'), TRUE)) {
            $policy = 'merge_notes';
        }

        $stats = array('created' => 0, 'updated' => 0, 'merged' => 0, 'skipped' => 0, 'errors' => 0);
        $this->db->trans_start();
        foreach ($rows as $row) {
            $result = $this->crm_contact_model->import_row($row, $policy);
            if (!empty($result['action']) && isset($stats[$result['action']])) {
                $stats[$result['action']]++;
            } elseif ($result['action'] === 'error') {
                $stats['errors']++;
            }
            if (!empty($row['tags']) && !empty($result['id']) && $result['action'] !== 'skipped') {
                $this->_apply_import_tags((int) $result['id'], (string) $row['tags'], $policy);
            }
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            $this->flash('danger', 'Import failed — no changes were saved. Try again.');
            redirect(admin_url('contacts/import'));
        }

        $this->_clear_pending_import();
        $this->session->unset_userdata('crm_import_rows');
        $this->audit->log('admin', $this->admin['id'], 'crm.contacts.import', 'crm_contact', 0, $stats);
        $this->flash('success', sprintf(
            'Import complete: %d created, %d merged, %d updated, %d skipped, %d errors.',
            $stats['created'], $stats['merged'], $stats['updated'], $stats['skipped'], $stats['errors']
        ));
        redirect(admin_url('contacts'));
    }

    /** @return string Absolute path to pending import directory */
    protected function _import_pending_dir()
    {
        $dir = realpath(APPPATH.'../storage/import');
        if ($dir === FALSE) {
            $dir = APPPATH.'../storage/import';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, TRUE);
            }
        }
        $pending = $dir.'/pending';
        if (!is_dir($pending)) {
            @mkdir($pending, 0755, TRUE);
        }
        return $pending;
    }

    /** Copy uploaded CSV to disk; session stores filename only (avoids BLOB size limit). */
    protected function _store_pending_import($upload_tmp_path)
    {
        $this->_clear_pending_import();
        $name = 'admin_'.(int) $this->admin['id'].'_'.bin2hex(random_bytes(8)).'.csv';
        $dest = $this->_import_pending_dir().'/'.$name;
        if (!@copy($upload_tmp_path, $dest)) {
            return FALSE;
        }
        $this->session->set_userdata('crm_import_file', $name);
        return $dest;
    }

    /** @return string|null Readable path to pending CSV for this admin session */
    protected function _pending_import_path()
    {
        $name = $this->session->userdata('crm_import_file');
        if (!$name) {
            return NULL;
        }
        $name = basename((string) $name);
        $path = $this->_import_pending_dir().'/'.$name;
        return is_readable($path) ? $path : NULL;
    }

    protected function _clear_pending_import()
    {
        $name = $this->session->userdata('crm_import_file');
        if ($name) {
            $path = $this->_import_pending_dir().'/'.basename((string) $name);
            if (is_file($path)) {
                @unlink($path);
            }
            $this->session->unset_userdata('crm_import_file');
        }
    }

    protected function _apply_import_tags($contact_id, $tags_csv, $policy)
    {
        $names = array_filter(array_map('trim', explode(',', $tags_csv)));
        if (empty($names)) {
            return;
        }
        $tag_ids = array();
        foreach ($names as $name) {
            $tag_ids[] = $this->crm_tag_model->find_or_create($name);
        }
        if ($policy === 'update') {
            $this->crm_tag_model->sync_contact_tags($contact_id, $tag_ids);
            return;
        }
        $existing = $this->crm_tag_model->for_contact($contact_id);
        foreach ($existing as $t) {
            $tag_ids[] = (int) $t['id'];
        }
        $this->crm_tag_model->sync_contact_tags($contact_id, array_values(array_unique($tag_ids)));
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
