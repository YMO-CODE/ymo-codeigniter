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
            'q'       => $this->input->get('q'),
            'tag_id'  => $this->input->get('tag_id'),
            'segment' => $this->input->get('segment'),
        );
        $result = $this->crm_contact_model->paginate($filters, $perPage, $offset);

        $segment = (string) ($filters['segment'] ?? '');
        $segment_titles = array(
            ''         => 'Customers',
            'active'   => 'Active customers',
            'due'      => 'Due for service',
            'inactive' => 'Inactive customers',
            'vip'      => 'VIP customers',
        );

        $this->render('admin/contacts/index', array(
            'title'   => $segment_titles[$segment] ?? 'Customers',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / $perPage)),
            'filters' => $filters,
            'tags'    => $this->crm_tag_model->all(),
            'can_edit'=> crm_can('contacts.edit'),
            'segments'=> array('' => 'All', 'active' => 'Active', 'due' => 'Due for service', 'inactive' => 'Inactive', 'vip' => 'VIP'),
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
            'link_users' => $this->crm_contact_model->search_users_for_link(
                $contact['mobile'] ?: $contact['email'] ?: $contact['name']
            ),
        ));
    }

    public function link_user($id)
    {
        $this->require_perm('contacts.edit');
        $contact = $this->crm_contact_model->find($id);
        if (!$contact) { show_404(); }

        $user_id = (int) $this->input->post('user_id');
        $this->crm_contact_model->link_to_user($id, $user_id);
        $this->audit->log('admin', $this->admin['id'], 'crm.customer.link_user', 'crm_contact', $id, array(
            'user_id' => $user_id ?: NULL,
        ));
        $this->flash('success', $user_id ? 'Linked to online account.' : 'Online account link removed.');
        redirect(admin_url('customers/'.$id));
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

    public function bulk_edit()
    {
        $this->require_perm('contacts.edit');
        if ($this->input->method() !== 'post') {
            redirect(admin_url('customers'));
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('contact_ids')))));
        if (empty($ids)) {
            $this->flash('error', 'Select at least one contact.');
            redirect(admin_url('customers'));
        }

        $found = $this->crm_contact_model->find_many($ids);
        if (count($found) !== count($ids)) {
            $this->flash('error', 'Some selected contacts were not found.');
            redirect(admin_url('customers'));
        }

        $apply_workshop = $this->input->post('apply_workshop') === '1';
        $tag_mode = $this->input->post('tag_mode');
        if (!in_array($tag_mode, array('none', 'add', 'replace'), TRUE)) {
            $tag_mode = 'none';
        }

        $tag_ids = array();
        foreach ((array) $this->input->post('tag_ids') as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $tag_ids[] = $tid;
            }
        }
        $new_tag = trim((string) $this->input->post('new_tag'));
        if ($new_tag !== '') {
            $tag_ids[] = $this->crm_tag_model->find_or_create($new_tag);
        }
        $tag_ids = array_values(array_unique($tag_ids));

        if (!$apply_workshop && $tag_mode === 'none') {
            $this->flash('error', 'Choose workshop and/or tags to update.');
            redirect(admin_url('customers'));
        }
        if ($tag_mode !== 'none' && empty($tag_ids)) {
            $this->flash('error', 'Pick at least one tag or enter a new tag name.');
            redirect(admin_url('customers'));
        }

        $this->db->trans_start();
        if ($apply_workshop) {
            $this->crm_contact_model->bulk_set_workshop($ids, $this->input->post('workshop'));
        }
        if ($tag_mode !== 'none') {
            foreach ($ids as $cid) {
                if ($tag_mode === 'replace') {
                    $this->crm_tag_model->sync_contact_tags($cid, $tag_ids);
                } else {
                    $this->crm_tag_model->merge_contact_tags($cid, $tag_ids);
                }
            }
        }
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            $this->flash('error', 'Bulk update failed.');
            redirect(admin_url('customers'));
        }

        $this->audit->log('admin', $this->admin['id'], 'crm.contacts.bulk_edit', 'crm_contact', 0, array(
            'count' => count($ids),
            'workshop' => $apply_workshop,
            'tag_mode' => $tag_mode,
        ));
        $this->flash('success', sprintf('Updated %d contact(s).', count($ids)));
        redirect(admin_url('customers'));
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
            array('Name', 'Mobile', 'Email', 'Workshop', 'Created'),
            $data
        );
    }

    public function import()
    {
        $this->require_perm('contacts.edit');
        $this->render('admin/contacts/import', array(
            'title'   => 'Import customers',
            'preview' => NULL,
        ));
    }

    public function import_template()
    {
        $this->require_perm('contacts.edit');
        crm_csv_download('crm-contacts-import-template.csv',
            array('name', 'mobile', 'email', 'workshop', 'notes', 'tags'),
            array(
                array('Rajesh Kumar', '9876543210', 'rajesh@example.com', 'G1 Pune', 'Sample visit note', 'exhibition-wakad-2026'),
            )
        );
    }

    public function import_preview()
    {
        $this->require_perm('contacts.edit');
        @set_time_limit(300);

        if ($this->input->method() !== 'post') {
            redirect(admin_url('customers/import'));
        }

        $upload_err = $this->_csv_upload_error();
        if ($upload_err !== NULL) {
            $this->flash('error', $upload_err);
            redirect(admin_url('customers/import'));
        }

        $rows = crm_parse_contacts_csv($_FILES['csv_file']['tmp_name']);
        if (empty($rows)) {
            $this->flash('error', 'No valid rows found. Expected columns: name, mobile, email, workshop, notes, tags');
            redirect(admin_url('customers/import'));
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
            $pending = $this->_import_pending_dir();
            $this->flash('error', 'Could not save upload. Ensure this folder is writable by the web server: '.$pending);
            redirect(admin_url('customers/import'));
        }

        $this->render('admin/contacts/import', array(
            'title'   => 'Import customers - preview',
            'preview' => $preview,
        ));
    }

    public function import_commit()
    {
        $this->require_perm('contacts.edit');
        @set_time_limit(600);

        $path = $this->_pending_import_path();
        if (!$path) {
            $this->flash('error', 'Import file expired or missing. Upload the CSV again.');
            redirect(admin_url('customers/import'));
        }

        $rows = crm_parse_contacts_csv($path);
        if (empty($rows)) {
            $this->_clear_pending_import();
            $this->flash('error', 'Import file could not be read. Upload the CSV again.');
            redirect(admin_url('customers/import'));
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
            $this->flash('error', 'Import failed - no changes were saved. Try again.');
            redirect(admin_url('customers/import'));
        }

        $this->_clear_pending_import();
        $this->session->unset_userdata('crm_import_rows');
        $this->audit->log('admin', $this->admin['id'], 'crm.contacts.import', 'crm_contact', 0, $stats);
        $this->flash('success', sprintf(
            'Import complete: %d created, %d merged, %d updated, %d skipped, %d errors.',
            $stats['created'], $stats['merged'], $stats['updated'], $stats['skipped'], $stats['errors']
        ));
        redirect(admin_url('customers'));
    }

    /** @return string Absolute path to pending import directory */
    protected function _import_pending_dir()
    {
        $base = realpath(FCPATH.'../storage/import');
        if ($base === FALSE) {
            $base = FCPATH.'../storage/import';
            if (!is_dir($base)) {
                @mkdir($base, 0775, TRUE);
            }
            $base = realpath($base) ?: rtrim($base, '/\\');
        }
        $pending = $base.'/pending';
        if (!is_dir($pending)) {
            @mkdir($pending, 0775, TRUE);
        }
        return $pending;
    }

    /** @return string|null User-facing upload error message */
    protected function _csv_upload_error()
    {
        if (empty($_FILES['csv_file']['name'])) {
            if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
                return 'Upload too large for server limits. Ask your host to raise post_max_size and upload_max_filesize, or use CLI import.';
            }
            return 'Please choose a CSV file to upload.';
        }
        $err = (int) ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_OK && !empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            return NULL;
        }
        $messages = array(
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form size limit.',
            UPLOAD_ERR_PARTIAL    => 'Upload interrupted - try again.',
            UPLOAD_ERR_NO_FILE    => 'Please choose a CSV file to upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder for uploads.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
        );
        return $messages[$err] ?? 'Upload failed (error code '.$err.').';
    }

    /** Copy uploaded CSV to disk; session stores filename only (avoids BLOB size limit). */
    protected function _store_pending_import($upload_tmp_path)
    {
        $this->_clear_pending_import();
        if (!is_uploaded_file($upload_tmp_path)) {
            return FALSE;
        }
        $name = 'admin_'.(int) $this->admin['id'].'_'.bin2hex(random_bytes(8)).'.csv';
        $dest = $this->_import_pending_dir().'/'.$name;
        if (!@move_uploaded_file($upload_tmp_path, $dest)) {
            if (!@copy($upload_tmp_path, $dest)) {
                return FALSE;
            }
        }
        @chmod($dest, 0660);
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
            'title'   => $contact ? 'Edit customer' : 'New customer',
            'contact' => $contact,
            'tags'    => $this->crm_tag_model->all(),
            'tag_ids' => $tag_ids,
        ));
    }

    protected function _save($id)
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email|max_length[180]');
        $this->form_validation->set_rules('company', 'Workshop', 'trim|max_length[120]');
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
        redirect(admin_url('customers/'.$id));
    }
}
