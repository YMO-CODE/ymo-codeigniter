<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRM CLI utilities (server-side import, etc.)
 *
 *   php index.php cli/crm import_contacts <csv_path> [merge_notes|skip|update]
 *
 * Example (CSV in mounted storage folder):
 *   php index.php cli/crm import_contacts storage/import/contacts_master.csv merge_notes
 */
class Crm extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        $this->load->helper('crm');
        $this->load->model(array('crm_contact_model', 'crm_tag_model'));
    }

    public function import_contacts($path = '', $policy = 'merge_notes')
    {
        if ($path === '') {
            $this->_die("Usage: cli/crm import_contacts <csv_path> [merge_notes|skip|update]\n");
        }

        if (!in_array($policy, array('skip', 'update', 'merge_notes'), TRUE)) {
            $this->_die("Invalid policy: $policy\n");
        }

        $resolved = $this->_resolve_path($path);
        if (!is_readable($resolved)) {
            $this->_die("CSV not readable: $path (resolved: $resolved)\n");
        }

        $rows = crm_parse_contacts_csv($resolved);
        if (empty($rows)) {
            $this->_die("No valid rows in CSV. Need a 'name' column.\n");
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
                $this->_apply_tags((int) $result['id'], (string) $row['tags'], $policy);
            }
        }
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            $this->_die("Import failed — transaction rolled back.\n");
        }

        fwrite(STDOUT, sprintf(
            "Import complete: %d created, %d merged, %d updated, %d skipped, %d errors (%d rows).\n",
            $stats['created'],
            $stats['merged'],
            $stats['updated'],
            $stats['skipped'],
            $stats['errors'],
            count($rows)
        ));
    }

    protected function _resolve_path($path)
    {
        if ($path[0] === '/' && is_readable($path)) {
            return $path;
        }
        $candidates = array(
            $path,
            FCPATH.$path,
            FCPATH.'../'.$path,
            APPPATH.'../'.$path,
        );
        foreach ($candidates as $c) {
            $real = realpath($c);
            if ($real && is_readable($real)) {
                return $real;
            }
        }
        return $path;
    }

    protected function _apply_tags($contact_id, $tags_csv, $policy)
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

    protected function _die($msg)
    {
        fwrite(STDERR, $msg);
        exit(1);
    }
}
