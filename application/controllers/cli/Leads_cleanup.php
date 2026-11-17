<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-off: move every open lead to Customers and archive it, for a fresh Leads list.
 *
 *   php index.php cli/leads_cleanup preview          what would happen, changes nothing
 *   php index.php cli/leads_cleanup apply            do it (writes an undo file)
 *   php index.php cli/leads_cleanup undo <file-name> put the leads back, remove customers it created
 *
 * Open lead = stage New, Contacted or Quote sent, not archived. Leads with a valid
 * Indian mobile and no spam signs become (or are linked to) a customer tagged
 * "Former lead"; spam and invalid numbers are archived without becoming customers.
 * Every lead is archived (hidden, restorable) and its pending follow-ups are closed.
 */
class Leads_cleanup extends CI_Controller
{
    const TAG = 'Former lead';

    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        $this->load->model(array('crm_lead_model', 'crm_contact_model', 'crm_tag_model', 'crm_task_model', 'crm_lead_activity_model'));
    }

    public function index()
    {
        echo "Usage:\n  php index.php cli/leads_cleanup preview\n  php index.php cli/leads_cleanup apply\n  php index.php cli/leads_cleanup undo <undo-file>\n";
    }

    public function preview()
    {
        $plan = $this->_plan();
        $this->_print_plan($plan);
        echo "\nNothing was changed. Run `php index.php cli/leads_cleanup apply` to do it.\n";
    }

    public function apply()
    {
        $plan = $this->_plan();
        $this->_print_plan($plan);
        if (!$plan['rows']) {
            echo "\nNothing to do.\n";
            return;
        }

        $tag_id = $this->crm_tag_model->find_or_create(self::TAG);
        $undo = array('at' => date('c'), 'leads' => array(), 'contacts_created' => array(), 'tasks_closed' => array());
        $made = $linked = $archived = 0;
        $created = array(); // mobile => customer id made in this run

        $this->db->trans_start();
        foreach ($plan['rows'] as $r) {
            $lead = $r['lead'];
            $contact_id = 0;
            if ($r['action'] === 'new_customer') {
                $lead_for_contact = $lead;
                $lead_for_contact['mobile'] = $r['mobile'];
                $contact_id = (int) $this->crm_contact_model->create_from_lead($lead_for_contact);
                $undo['contacts_created'][] = $contact_id;
                $created[$r['mobile']] = $contact_id;
                $made++;
            } elseif ($r['action'] === 'same_as_new') {
                $contact_id = (int) ($created[$r['mobile']] ?? 0);
                $linked++;
            } elseif ($r['action'] === 'existing_customer') {
                $contact_id = (int) $r['contact_id'];
                $linked++;
            }
            if ($contact_id) {
                $this->crm_tag_model->merge_contact_tags($contact_id, array($tag_id));
                $this->db->where('id', (int) $lead['id'])->update('crm_leads', array('converted_contact_id' => $contact_id));
            }

            foreach ($this->db->select('id')->where('lead_id', (int) $lead['id'])->where('status', 'pending')->get('crm_tasks')->result_array() as $t) {
                $undo['tasks_closed'][] = (int) $t['id'];
            }
            $this->crm_task_model->close_lead_followups((int) $lead['id']);

            $this->crm_lead_activity_model->add((int) $lead['id'], NULL, 'system',
                'Archived in the leads clean-up. '.($contact_id ? 'Moved to customers (customer #'.$contact_id.').' : 'Not made a customer: '.$r['why'].'.'));
            $this->crm_lead_model->soft_delete((int) $lead['id']);
            $undo['leads'][] = array('id' => (int) $lead['id'], 'contact_id' => $contact_id ?: NULL,
                'converted_contact_id_before' => $lead['converted_contact_id'] ? (int) $lead['converted_contact_id'] : NULL);
            $archived++;
        }
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            echo "\nFAILED: database error, nothing was changed.\n";
            exit(1);
        }

        $dir = APPPATH.'../storage/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0775, TRUE); }
        $file = (realpath($dir) ?: $dir).'/leads_cleanup_'.date('Ymd_His').'.json';
        file_put_contents($file, json_encode($undo, JSON_PRETTY_PRINT));

        printf("\nDone: %d leads archived, %d new customers, %d linked to existing customers, all tagged \"%s\".\n",
            $archived, $made, $linked, self::TAG);
        echo 'Undo file saved: storage/logs/'.basename($file)."\n";
        echo 'To reverse: php index.php cli/leads_cleanup undo '.basename($file)."\n";
    }

    public function undo()
    {
        // CLI arguments are split on "/", so take the file name and look in storage/logs.
        $name = basename(implode('/', func_get_args()));
        $file = (realpath(APPPATH.'../storage/logs') ?: APPPATH.'../storage/logs').'/'.$name;
        if ($name === '' || !preg_match('/^leads_cleanup_\d{8}_\d{6}\.json$/', $name) || !is_file($file)) {
            echo "Give the undo file name printed by `apply`, e.g. leads_cleanup_20261117_110500.json\n";
            $found = glob((realpath(APPPATH.'../storage/logs') ?: '.').'/leads_cleanup_*.json');
            if ($found) { echo "Available: ".implode(', ', array_map('basename', $found))."\n"; }
            exit(1);
        }
        $undo = json_decode(file_get_contents($file), TRUE);
        if (!is_array($undo) || empty($undo['leads'])) {
            echo "That file has nothing to undo.\n";
            exit(1);
        }
        $this->db->trans_start();
        foreach ($undo['leads'] as $l) {
            $this->db->where('id', (int) $l['id'])->update('crm_leads', array(
                'deleted_at' => NULL,
                'converted_contact_id' => $l['converted_contact_id_before'],
                'updated_at' => date('Y-m-d H:i:s'),
            ));
            $this->crm_lead_activity_model->add((int) $l['id'], NULL, 'system', 'Restored from the leads clean-up.');
        }
        if (!empty($undo['tasks_closed'])) {
            $this->db->where_in('id', $undo['tasks_closed'])->update('crm_tasks', array('status' => 'pending', 'completed_at' => NULL));
            foreach ($undo['tasks_closed'] as $tid) {
                $this->crm_task_model->sync_lead_date((int) $tid);
            }
        }
        if (!empty($undo['contacts_created'])) {
            // Only remove customers that still have nothing else attached (no bookings/account).
            $ids = $this->db->select('id')->where_in('id', $undo['contacts_created'])->where('user_id IS NULL', NULL, FALSE)
                ->get('crm_contacts')->result_array();
            foreach ($ids as $c) {
                $this->crm_contact_model->soft_delete((int) $c['id']);
            }
        }
        $this->db->trans_complete();
        echo $this->db->trans_status()
            ? 'Restored '.count($undo['leads'])." leads.\n"
            : "FAILED: database error, nothing was changed.\n";
    }

    // --- internals ---------------------------------------------------------

    protected function _plan()
    {
        $leads = $this->db->select('l.*, s.label AS source_label', FALSE)
            ->from('crm_leads l')
            ->join('crm_lead_sources s', 's.id = l.source_id', 'left')
            ->where('l.deleted_at IS NULL', NULL, FALSE)
            ->where_in('l.stage', array('new', 'contacted', 'quote_sent'))
            ->order_by('l.id', 'ASC')
            ->get()->result_array();

        $rows = array();
        $seen = array(); // one customer per mobile even if they enquired twice
        foreach ($leads as $l) {
            $mobile = ymo_indian_mobile($l['mobile']);
            $spam = ymo_spam_reason($l['name'], $l['message']);
            $row = array('lead' => $l, 'mobile' => $mobile, 'contact_id' => 0, 'why' => '');
            if ($spam) {
                $row['action'] = 'archive_only';
                $row['why'] = 'looks like spam ('.$spam.')';
            } elseif ($mobile === '') {
                $row['action'] = 'archive_only';
                $row['why'] = 'no valid Indian mobile';
            } else {
                $existing = isset($seen[$mobile]) ? array('id' => $seen[$mobile]) : $this->crm_contact_model->find_by_mobile($mobile);
                if ($existing && empty($existing['deleted_at'])) {
                    $row['action'] = 'existing_customer';
                    $row['contact_id'] = (int) $existing['id'];
                } elseif (isset($seen[$mobile])) {
                    $row['action'] = 'existing_customer';
                    $row['contact_id'] = $seen[$mobile];
                } else {
                    $row['action'] = 'new_customer';
                    $seen[$mobile] = 0; // placeholder: created during apply
                }
            }
            $rows[] = $row;
        }
        // A second lead for a mobile created in this run links to the same new customer.
        $first = array();
        foreach ($rows as $i => $r) {
            if ($r['action'] === 'new_customer') {
                $first[$r['mobile']] = $i;
            }
        }
        foreach ($rows as $i => $r) {
            if ($r['action'] === 'existing_customer' && $r['contact_id'] === 0 && isset($first[$r['mobile']])) {
                $rows[$i]['action'] = 'same_as_new';
            }
        }
        return array('rows' => $rows);
    }

    protected function _print_plan(array $plan)
    {
        $count = array('new_customer' => 0, 'existing_customer' => 0, 'same_as_new' => 0, 'archive_only' => 0);
        foreach ($plan['rows'] as $r) {
            $count[$r['action']]++;
        }
        printf("Open leads found: %d\n", count($plan['rows']));
        printf("  become new customers:              %d\n", $count['new_customer']);
        printf("  already customers (linked):        %d\n", $count['existing_customer']);
        printf("  repeat enquiries of the above:     %d\n", $count['same_as_new']);
        printf("  archived only (spam / bad mobile): %d\n", $count['archive_only']);
        $shown = 0;
        foreach ($plan['rows'] as $r) {
            if ($r['action'] !== 'archive_only' || $shown >= 15) { continue; }
            if ($shown === 0) { echo "\nNot becoming customers (first 15):\n"; }
            printf("  #%-6d %-28s %-16s %s\n", $r['lead']['id'], mb_strimwidth($r['lead']['name'], 0, 27, '…'), $r['lead']['mobile'], $r['why']);
            $shown++;
        }
    }
}
