<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRM CLI utilities (server-side import, etc.)
 *
 *   php index.php cli/crm import_contacts <csv_path> [merge_notes|skip|update]
 *   php index.php cli/crm import_catalog [csv_path]
 *       Services and spare parts from the OkMechanic export into the Catalogue
 *       (default: application/data/okmechanic_catalogue_2026-11-28.csv). Safe to re-run:
 *       matched on the OkMechanic id; items edited in the CRM are left as they are.
 *   php index.php cli/crm merge_duplicate_leads [apply]
 *       Same mobile with more than one open lead: keep the oldest, move the others'
 *       notes, tasks, tags and chat links onto it, archive the others (restorable).
 *       Without "apply" it only lists what it would do.
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

    public function merge_duplicate_leads($mode = '')
    {
        $apply = $mode === 'apply';
        $this->load->model(array('crm_lead_model', 'crm_lead_activity_model'));
        $open = $this->db->select('l.*, s.label AS source_label', FALSE)
            ->from('crm_leads l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->where_in('l.stage', crm_lead_open_stages())
            ->order_by('l.id', 'ASC')
            ->get()->result_array();

        $groups = array();
        foreach ($open as $l) {
            $m = crm_mobile10($l['mobile']);
            if (strlen($m) === 10) {
                $groups[$m][] = $l;
            }
        }
        $rank = array('new' => 0, 'contacted' => 1, 'quote_sent' => 2);
        $merged = 0;
        foreach ($groups as $m => $leads) {
            if (count($leads) < 2) {
                continue;
            }
            $keep = array_shift($leads);
            echo 'Keep #'.$keep['id'].' '.$keep['name'].' ('.$m.', '.$keep['source_label'].', '.$keep['created_at'].")\n";
            foreach ($leads as $d) {
                echo '   merge #'.$d['id'].' '.$d['name'].' ('.$d['source_label'].', '.$d['created_at'].")\n";
                $merged++;
                if (!$apply) {
                    continue;
                }
                $this->db->trans_start();
                $kid = (int) $keep['id'];
                $did = (int) $d['id'];
                $this->db->where('lead_id', $did)->update('crm_lead_activities', array('lead_id' => $kid));
                $this->db->where('lead_id', $did)->update('crm_tasks', array('lead_id' => $kid));
                $this->db->query('INSERT IGNORE INTO crm_lead_tags (lead_id, tag_id) SELECT ?, tag_id FROM crm_lead_tags WHERE lead_id = ?', array($kid, $did));
                foreach (array('ymochat_visitors', 'crm_ad_wa_clicks') as $t) {
                    if ($this->db->table_exists($t)) {
                        $this->db->where('lead_id', $did)->update($t, array('lead_id' => $kid));
                    }
                }
                $patch = array();
                foreach (array('email', 'address', 'car_type', 'company') as $k) {
                    if (trim((string) ($keep[$k] ?? '')) === '' && trim((string) ($d[$k] ?? '')) !== '') {
                        $patch[$k] = $keep[$k] = $d[$k];
                    }
                }
                if (empty($keep['assigned_to']) && !empty($d['assigned_to'])) {
                    $patch['assigned_to'] = $keep['assigned_to'] = $d['assigned_to'];
                }
                if (empty($keep['external_lead_id']) && !empty($d['external_lead_id'])) {
                    $patch['external_provider'] = $keep['external_provider'] = $d['external_provider'];
                    $patch['external_lead_id'] = $keep['external_lead_id'] = $d['external_lead_id'];
                    $this->db->where('id', $did)->update('crm_leads', array('external_lead_id' => NULL));
                }
                if (array_key_exists('ad_click_id', $d) && empty($keep['ad_click_id']) && !empty($d['ad_click_id'])) {
                    foreach (array('ad_click_id', 'ad_click_type', 'ad_match') as $k) {
                        $patch[$k] = $keep[$k] = $d[$k];
                    }
                }
                if ($rank[$d['stage']] > $rank[$keep['stage']]) {
                    $patch['stage'] = $keep['stage'] = $d['stage'];
                }
                if ($patch) {
                    $this->crm_lead_model->update($kid, $patch);
                }
                $this->crm_lead_activity_model->add($kid, NULL, 'system',
                    'Merged duplicate lead #'.$did.' ('.$d['source_label'].', '.$d['created_at'].').'
                    .(trim((string) $d['message']) !== '' ? "\n".trim((string) $d['message']) : ''),
                    array('source' => 'merge_duplicate_leads', 'merged_lead_id' => $did));
                $this->crm_lead_model->soft_delete($did);
                $this->db->trans_complete();
                if (!$this->db->trans_status()) {
                    echo "   FAILED on #$did, nothing changed for it.\n";
                }
            }
        }
        echo $merged === 0 ? "No duplicate open leads.\n"
            : ($apply ? "Merged $merged duplicate lead(s). The merged ones are archived, not deleted.\n"
                      : "\n$merged duplicate lead(s) would be merged. Run with `apply` to do it.\n");
    }

    public function import_catalog($path = '')
    {
        $resolved = $path === '' ? APPPATH.'data/okmechanic_catalogue_2026-11-28.csv' : $this->_resolve_path($path);
        if (!is_readable($resolved)) {
            $this->_die("CSV not readable: $path (resolved: $resolved)\n");
        }
        $this->load->model('crm_catalog_model');
        if (!$this->crm_catalog_model->ensure_schema()) {
            $this->_die("Could not create the crm_catalog_items table (see the log).\n");
        }
        $fh = fopen($resolved, 'r');
        $head = fgetcsv($fh);
        if (!$head) {
            $this->_die("Empty CSV.\n");
        }
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]); // Excel BOM
        $head = array_map('trim', $head);
        foreach (array('okm_id', 'type', 'name') as $need) {
            if (!in_array($need, $head, TRUE)) {
                $this->_die("CSV needs a '$need' column.\n");
            }
        }
        $stats = array('created' => 0, 'updated' => 0, 'kept' => 0, 'skipped' => 0);
        $by_type = array('service' => 0, 'part' => 0);
        $this->db->trans_start();
        while (($row = fgetcsv($fh)) !== FALSE) {
            if (count($row) < count($head)) {
                $row = array_pad($row, count($head), '');
            }
            $r = array_combine($head, array_slice($row, 0, count($head)));
            $res = $this->crm_catalog_model->import_okm_row($r);
            $stats[$res]++;
            if ($res !== 'skipped' && isset($by_type[$r['type']])) {
                $by_type[$r['type']]++;
            }
        }
        fclose($fh);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            $this->_die("Import failed - nothing was saved (transaction rolled back).\n");
        }
        $c = $this->crm_catalog_model->counts();
        fwrite(STDOUT, sprintf(
            "Catalogue import: %d created, %d updated, %d kept (edited in CRM), %d skipped.\n"
            ."Rows read: %d services, %d parts.\n"
            ."Catalogue now: %d services (%d with price set on job), %d parts (%d with price set on job).\n",
            $stats['created'], $stats['updated'], $stats['kept'], $stats['skipped'],
            $by_type['service'], $by_type['part'],
            $c['service'], $c['service_no_price'], $c['part'], $c['part_no_price']
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
