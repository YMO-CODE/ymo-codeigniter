<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-time move of OkMechanic data into the CRM (run from cli/okm_import).
 *
 * Reads the export built from the OkMechanic web app (customers, vehicles, work orders
 * with lines and payments, estimates, purchases, expenses, suppliers, staff, accounts)
 * and writes it with plain database inserts. Nothing here calls the notifier, the
 * booking service, reminders or campaigns, so no WhatsApp / SMS / email goes out.
 *
 *  - Customers are matched to existing CRM accounts by 10-digit mobile; new ones are added.
 *  - Cars are matched per customer by registration number; new ones are added.
 *  - Work orders become job cards (same RO numbers) with their services and parts,
 *    invoices keep their OkMechanic numbers (INV…) and payments keep their accounts.
 *  - Estimates, purchases, expenses, suppliers, staff and the full raw work-order
 *    records are kept in crm_okm_archive (Setup → OkMechanic archive).
 *  - Every imported row is recorded in crm_okm_map, so running it again skips what
 *    is already in and only adds what is new.
 */
class Okm_importer
{
    const MAP = 'crm_okm_map';
    const ARC = 'crm_okm_archive';

    /** @var CI_Controller */
    protected $CI;
    protected $db;
    protected $stats = array();
    protected $notes = array();
    protected $makes = NULL;
    protected $accounts = NULL;
    protected $billing = NULL;
    protected $now;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
        $this->CI->load->model(array('user_model', 'crm_work_order_model', 'crm_billing_model'));
        $this->CI->load->helper(array('crm', 'ymo'));
        $this->now = date('Y-m-d H:i:s');
    }

    // ---- schema --------------------------------------------------------------------

    public function ensure_schema()
    {
        if (!$this->CI->crm_work_order_model->ensure_schema() || !$this->CI->crm_billing_model->ensure_schema()) {
            return FALSE;
        }
        $q = array(
            self::MAP => "CREATE TABLE IF NOT EXISTS `crm_okm_map` (
  `entity`     VARCHAR(20) NOT NULL,
  `okm_id`     VARCHAR(40) NOT NULL,
  `crm_id`     BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`entity`, `okm_id`),
  KEY `idx_okm_crm` (`entity`, `crm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::ARC => "CREATE TABLE IF NOT EXISTS `crm_okm_archive` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity`        VARCHAR(20) NOT NULL,
  `okm_id`        VARCHAR(40) NOT NULL,
  `number`        VARCHAR(40) NOT NULL DEFAULT '',
  `title`         VARCHAR(255) NOT NULL DEFAULT '',
  `status`        VARCHAR(40) NOT NULL DEFAULT '',
  `amount`        DECIMAL(12,2) NULL,
  `doc_date`      DATE NULL,
  `work_order_id` BIGINT UNSIGNED NULL,
  `user_id`       BIGINT UNSIGNED NULL,
  `data`          MEDIUMTEXT NOT NULL,
  `created_at`    DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_okma` (`entity`, `okm_id`),
  KEY `idx_okma_wo` (`work_order_id`),
  KEY `idx_okma_user` (`user_id`),
  KEY `idx_okma_date` (`entity`, `doc_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
        foreach ($q as $t => $sql) {
            if (!$this->db->table_exists($t) && !$this->db->query($sql)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    // ---- entry point ---------------------------------------------------------------

    /**
     * @param array $main      okm-export-main.json decoded
     * @param array $details   okm-export-workorder-details.json decoded
     * @param bool  $apply     FALSE = dry run (everything rolled back at the end)
     * @return array [stats, notes]
     */
    public function run(array $main, array $details, $apply)
    {
        $this->stats = array();
        $this->notes = array();
        if (!$this->ensure_schema()) {
            return array(array(), array('Could not create the import tables.'));
        }
        $this->billing = $this->CI->crm_billing_model->settings();

        $this->db->trans_begin();
        try {
            $this->_customers($main['customers'] ?? array(), $main['customer_vehicles'] ?? array());
            $list = array();
            foreach ($main['workorders'] ?? array() as $w) {
                $list[(string) $w['repairorder_id']] = $w;
            }
            // Oldest first so RO / INV order follows OkMechanic.
            usort($details, function ($a, $b) { return (int) $a['id'] - (int) $b['id']; });
            foreach ($details as $d) {
                if (($d['status'] ?? 0) != 200 || empty($d['result'])) {
                    $this->_inc('work_orders_skipped_no_data');
                    continue;
                }
                $this->_work_order($d['result'], $list[(string) $d['id']] ?? array());
            }
            $this->_archive_rest($main);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return array($this->stats, array_merge($this->notes, array('Stopped: '.$e->getMessage().' — nothing was saved.')));
        }

        if ($this->db->trans_status() === FALSE) {
            $err = $this->db->error();
            $this->db->trans_rollback();
            return array($this->stats, array_merge($this->notes, array('Database error: '.$err['message'].' — nothing was saved.')));
        }
        if ($apply) {
            $this->db->trans_commit();
            $this->notes[] = 'Saved.';
        } else {
            $this->db->trans_rollback();
            $this->notes[] = 'Dry run: nothing was saved. Run again with "apply" to save.';
        }
        return array($this->stats, $this->notes);
    }

    // ---- helpers -------------------------------------------------------------------

    protected function _inc($k, $n = 1)
    {
        $this->stats[$k] = ($this->stats[$k] ?? 0) + $n;
    }

    protected function _note($s)
    {
        if (count($this->notes) < 400) {
            $this->notes[] = $s;
        }
    }

    protected function _mapped($entity, $okm_id)
    {
        $r = $this->db->select('crm_id')->where('entity', $entity)->where('okm_id', (string) $okm_id)->get(self::MAP)->row_array();
        return $r ? (int) $r['crm_id'] : 0;
    }

    protected function _map($entity, $okm_id, $crm_id)
    {
        $this->db->replace(self::MAP, array('entity' => $entity, 'okm_id' => (string) $okm_id, 'crm_id' => (int) $crm_id, 'created_at' => $this->now));
    }

    /** OkMechanic dates: "Sat - 28 Nov 2026 01:00:45 PM", "Sat - 28 Nov 2026 AT 03:40 PM", "28 Nov 2026", "28-11-2026". */
    public static function parse_date($s, $with_time = TRUE)
    {
        $s = trim((string) $s);
        if ($s === '' || $s === '0') {
            return NULL;
        }
        $s = preg_replace('/^[A-Za-z]{3}\s*-\s*/', '', $s);
        $s = str_ireplace(' AT ', ' ', $s);
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})(.*)$/', $s, $m)) {
            $s = $m[3].'-'.$m[2].'-'.$m[1].$m[4];
        }
        $t = strtotime($s);
        if (!$t) {
            return NULL;
        }
        return date($with_time ? 'Y-m-d H:i:s' : 'Y-m-d', $t);
    }

    protected function _money($v)
    {
        return round((float) preg_replace('/[^0-9.\-]/', '', (string) $v), 2);
    }

    protected function _mobile($raw)
    {
        $d = preg_replace('/\D+/', '', (string) $raw);
        if (strlen($d) === 12 && substr($d, 0, 2) === '91') {
            $d = substr($d, 2);
        } elseif (strlen($d) === 11 && $d[0] === '0') {
            $d = substr($d, 1);
        }
        return $d;
    }

    protected function _plate($s)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $s));
    }

    // ---- customers & cars ----------------------------------------------------------

    protected function _customers(array $customers, array $vehicle_sets)
    {
        foreach ($customers as $c) {
            $this->_customer_id($c);
        }
        foreach ($vehicle_sets as $set) {
            if (($set['status'] ?? 0) != 200 || !is_array($set['result'] ?? NULL)) {
                continue;
            }
            $uid = $this->_mapped('customer', $set['id']);
            foreach ($set['result'] as $v) {
                if ($uid) {
                    $this->_vehicle_id($uid, $v);
                }
            }
        }
    }

    /** CRM user id for an OkMechanic customer (customers list row or work order customer_info). */
    protected function _customer_id(array $c)
    {
        $okm = (string) ($c['customer_id'] ?? '');
        if ($okm !== '' && ($id = $this->_mapped('customer', $okm))) {
            return $id;
        }
        $mobile = $this->_mobile($c['phone_number'] ?? '');
        $valid = (bool) preg_match('/^[6-9]\d{9}$/', $mobile);
        if ($mobile === '') {
            $mobile = 'okm'.$okm;
        }
        $mobile = substr($mobile, 0, 15);
        $name = trim(preg_replace('/\s+/', ' ', (string) ($c['name'] ?? '')));

        $user = $this->db->where('mobile', $mobile)->get('users')->row_array();
        if ($user) {
            $id = (int) $user['id'];
            $this->_inc($this->_is_new_user($id) ? 'customers_merged_same_mobile' : 'customers_matched_existing');
            if (!$this->_is_new_user($id) && $okm !== '') {
                $this->_note('Matched existing customer #'.$id.' '.$user['name'].' ('.$mobile.') ← OkMechanic '.$name);
            }
        } else {
            $email = strtolower(trim((string) ($c['email'] ?? '')));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $this->db->where('email', $email)->count_all_results('users')) {
                $email = ymo_placeholder_email($mobile);
            }
            $created = self::parse_date($c['created'] ?? '') ?: $this->now;
            $this->db->insert('users', array(
                'name'          => mb_substr($name !== '' ? $name : 'Customer '.$mobile, 0, 120),
                'mobile'        => $mobile,
                'email'         => mb_substr($email, 0, 180),
                'area'          => mb_substr(trim((string) ($c['area_name'] ?? '')), 0, 120),
                'city'          => mb_substr(trim((string) ($c['city_name'] ?? '')) ?: 'Pune', 0, 80),
                'password_hash' => password_hash(bin2hex(random_bytes(12)), PASSWORD_BCRYPT, array('cost' => 8)),
                'is_active'     => 1,
                'created_at'    => $created,
                'updated_at'    => $this->now,
            ));
            $id = (int) $this->db->insert_id();
            if (!$id) {
                throw new Exception('could not add customer '.$name.' ('.$mobile.'): '.$this->db->error()['message']);
            }
            $this->new_users[$id] = TRUE;
            $this->_inc('customers_added');
            if (!$valid) {
                $this->_note('Customer '.$name.' added with a non-Indian/short mobile "'.$mobile.'" (check it).');
            }
        }
        $this->_contact($id, $c);
        if ($okm !== '') {
            $this->_map('customer', $okm, $id);
        }
        return $id;
    }

    protected $new_users = array();

    protected function _is_new_user($id)
    {
        return isset($this->new_users[$id]);
    }

    /** Make sure the customer has a CRM contact; add OkMechanic GSTIN / address / company to it. */
    protected function _contact($user_id, array $c)
    {
        if (!$this->db->table_exists('crm_contacts')) {
            return;
        }
        $this->CI->load->model('crm_contact_model');
        $cid = $this->CI->crm_contact_model->ensure_for_user($user_id);
        if (!$cid) {
            return;
        }
        $row = $this->db->select('notes, company')->where('id', $cid)->get('crm_contacts')->row_array();
        $bits = array();
        foreach (array('tax_no' => 'GSTIN', 'address' => 'Address', 'postal_code' => 'PIN') as $k => $label) {
            $v = trim((string) ($c[$k] ?? ''));
            if ($v !== '') {
                $bits[] = $label.': '.$v;
            }
        }
        $patch = array();
        if ($bits) {
            $line = 'OkMechanic: '.implode(' · ', $bits);
            if (strpos((string) $row['notes'], $line) === FALSE) {
                $patch['notes'] = trim(((string) $row['notes'])."\n".$line);
            }
        }
        $company = trim((string) ($c['company_name'] ?? ''));
        if ($company !== '' && trim((string) $row['company']) === '') {
            $patch['company'] = mb_substr($company, 0, 120);
        }
        if ($patch) {
            $this->db->where('id', $cid)->update('crm_contacts', $patch);
        }
    }

    protected function _make_id($name)
    {
        if ($this->makes === NULL) {
            $this->makes = array();
            foreach ($this->db->get('vehicle_makes')->result_array() as $m) {
                $this->makes[$this->_mk($m['name'])] = (int) $m['id'];
            }
        }
        $name = trim((string) $name);
        if ($name === '') {
            $name = 'Other';
        }
        $k = $this->_mk($name);
        if (isset($this->makes[$k])) {
            return $this->makes[$k];
        }
        foreach ($this->makes as $mk => $id) {
            if (strlen($mk) >= 3 && strlen($k) >= 3 && (strpos($mk, $k) === 0 || strpos($k, $mk) === 0)) {
                return $this->makes[$k] = $id; // MERCEDES ~ Mercedes-Benz
            }
        }
        $pretty = strlen($name) <= 3 ? strtoupper($name) : ucwords(strtolower($name));
        $this->db->insert('vehicle_makes', array('name' => mb_substr($pretty, 0, 80), 'sort_order' => 100, 'is_active' => 1, 'created_at' => $this->now));
        $id = (int) $this->db->insert_id();
        $this->_inc('car_makes_added');
        return $this->makes[$k] = $id;
    }

    protected function _mk($s)
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $s));
    }

    protected function _vehicle_id($user_id, array $v)
    {
        $okm = (string) ($v['customer_vehicle_id'] ?? '');
        if ($okm !== '' && $okm !== '0' && ($id = $this->_mapped('vehicle', $okm))) {
            return $id;
        }
        $plate = $this->_plate($v['vehicle_plate_number'] ?? ($v['vehicle_number'] ?? ''));
        $id = 0;
        if ($plate !== '') {
            foreach ($this->db->select('id, vehicle_number')->where('user_id', (int) $user_id)->get('vehicles')->result_array() as $r) {
                if ($this->_plate($r['vehicle_number']) === $plate) {
                    $id = (int) $r['id'];
                    break;
                }
            }
        }
        if ($id) {
            $this->_inc('cars_matched_existing');
        } else {
            $variant = trim(preg_replace('/\s+/', ' ', ($v['model_name'] ?? '').' '.($v['variant'] ?? '')));
            $fuel = trim((string) ($v['fuel_type'] ?? ''));
            if ($fuel !== '' && stripos($variant, $fuel) === FALSE) {
                $variant .= ' · '.$fuel;
            }
            $this->db->insert('vehicles', array(
                'user_id'        => (int) $user_id,
                'make_id'        => $this->_make_id($v['make_name'] ?? ''),
                'variant'        => mb_substr(trim($variant, ' ·') ?: 'Car', 0, 120),
                'vehicle_number' => mb_substr($plate, 0, 20),
                'created_at'     => $this->now,
                'updated_at'     => $this->now,
            ));
            $id = (int) $this->db->insert_id();
            if (!$id) {
                throw new Exception('could not add car '.$plate.': '.$this->db->error()['message']);
            }
            $this->_inc('cars_added');
        }
        if ($okm !== '' && $okm !== '0') {
            $this->_map('vehicle', $okm, $id);
        }
        return $id;
    }

    // ---- work orders ---------------------------------------------------------------

    const STATUS = array('1' => 'draft', '2' => 'in_progress', '3' => 'on_hold', '4' => 'ready', '5' => 'delivered', '6' => 'returned');

    protected function _workshop(array $tags)
    {
        foreach ($tags as $t) {
            $t = strtolower(trim((string) $t));
            if (strpos($t, 'indore') !== FALSE) {
                return 'indore';
            }
            if (strpos($t, 'nashik') !== FALSE) {
                return 'nashik';
            }
            if (strpos($t, 'warje') !== FALSE || $t === 'pune g2') {
                return 'warje';
            }
            if ($t === 'pune g1') {
                return 'hinjewadi';
            }
        }
        return '';
    }

    protected function _work_order(array $d, array $row)
    {
        $okm_id = (string) $d['repairorder_id'];
        if ($this->_mapped('work_order', $okm_id)) {
            $this->_inc('work_orders_already_imported');
            return;
        }
        $ci = (array) ($d['customer_info'] ?? array());
        if (empty($ci['customer_id']) && !empty($row['phone_number'])) {
            $ci += array('name' => $row['customer_name'] ?? '', 'phone_number' => $row['phone_number']);
        }
        $user_id = $this->_customer_id($ci);
        $vi = (array) ($d['vehicle_info'] ?? array());
        if (empty($vi['vehicle_plate_number']) && !empty($row['vehicle_number'])) {
            $vi += array('vehicle_plate_number' => $row['vehicle_number'], 'make_name' => $row['make_name'] ?? '', 'model_name' => $row['model_name'] ?? '', 'fuel_type' => $row['fuel_type'] ?? '');
        }
        $vehicle_id = $this->_vehicle_id($user_id, $vi);

        $ro = trim((string) $d['repairorder_number']) ?: 'RO-OKM'.$okm_id;
        if ($this->db->where('ro_number', $ro)->count_all_results('crm_work_orders')) {
            $this->_note($ro.' already exists in the CRM (a different job card): imported as '.$ro.'-OKM.');
            $ro .= '-OKM';
        }

        $status = self::STATUS[(string) $d['repairorder_status']] ?? 'draft';
        $created = self::parse_date($row['created'] ?? '') ?: (self::parse_date($d['invoice_datetime'] ?? '') ?: $this->now);
        $status_at = self::parse_date($d['status_datetime'] ?? '') ?: $created;
        $inv_at = self::parse_date($d['invoice_datetime'] ?? '');
        if ($status_at < $created) {
            $status_at = $created;
        }
        $tags = (array) ($row['repairtag_title'] ?? array());

        // Lines
        $lines = array();
        foreach ((array) ($d['labour_list'] ?? array()) as $l) {
            $lines[] = $this->_line('service', $l['labour_name'] ?? '', $l['remark'] ?? '', $l['qty'] ?? 1, $l['rate'] ?? 0,
                $l['discount'] ?? 0, $l['tax'] ?? 0, $l['tax_type'] ?? '0', $l['subtotal'] ?? NULL, $l['total_price'] ?? NULL,
                $l['work_status'] ?? '', $l['edit_date'] ?? '', $created);
        }
        foreach ((array) ($d['spare_part_list'] ?? array()) as $l) {
            $lines[] = $this->_line('part', $l['item_name'] ?? '', $l['remark'] ?? '', $l['qty'] ?? 1, $l['sale_price'] ?? 0,
                $l['discount'] ?? 0, $l['item_tax'] ?? 0, $l['tax_type'] ?? '0', $l['subtotal'] ?? NULL, $l['total_price'] ?? NULL,
                $l['work_status'] ?? '', $l['edit_date'] ?? '', $created);
        }
        $sub = $tax = $tot = 0.0;
        foreach ($lines as $l) {
            $sub += $l['taxable'];
            $tax += $l['tax_amount'];
            $tot += $l['amount'];
        }
        $okm_total = $this->_money($d['total_amount'] ?? 0);
        if (abs($okm_total - $tot) > 1) {
            $this->_inc('work_orders_total_differs');
            $this->_note($ro.': lines add up to '.number_format($tot, 2).' but OkMechanic total is '.number_format($okm_total, 2).'.');
        }

        $complaint = array();
        if (trim((string) ($d['customer_remark'] ?? '')) !== '') {
            $complaint[] = trim($d['customer_remark']);
        }
        foreach ((array) ($d['problem_list'] ?? array()) as $p) {
            if (trim((string) ($p['title'] ?? '')) !== '') {
                $complaint[] = '• '.trim($p['title']).(trim((string) ($p['remark'] ?? '')) !== '' ? ' — '.trim($p['remark']) : '');
            }
        }
        $fuel = (string) ($d['fuel_indicator'] ?? '');
        $odo = preg_replace('/\D/', '', (string) ($d['current_odometer'] ?? ''));

        $this->db->insert('crm_work_orders', array(
            'ro_number'            => mb_substr($ro, 0, 20),
            'booking_id'           => NULL,
            'user_id'              => $user_id,
            'vehicle_id'           => $vehicle_id,
            'workshop'             => $this->_workshop($tags),
            'status'               => $status,
            'booking_type'         => 'workshop',
            'odometer_km'          => $odo !== '' ? min(4294967295, (int) $odo) : NULL,
            'fuel_pct'             => $fuel !== '' && is_numeric($fuel) ? max(0, min(100, (int) $fuel)) : NULL,
            'advisor_id'           => NULL,
            'expected_delivery_at' => self::parse_date($d['estimate_delivery_date_time'] ?? ''),
            'complaint'            => $complaint ? implode("\n", $complaint) : NULL,
            'instructions'         => trim((string) ($d['workshop_instruction'] ?? '')) ?: NULL,
            'notify_customer'      => 0,
            'lpo_number'           => mb_substr(trim((string) ($ci['lpo_number'] ?? '')), 0, 40),
            'lpo_date'             => self::parse_date($ci['lpo_date'] ?? '', FALSE),
            'lpo_amount'           => trim((string) ($ci['lpo_amount'] ?? '')) !== '' ? $this->_money($ci['lpo_amount']) : NULL,
            'subtotal'             => round($sub, 2),
            'tax_total'            => round($tax, 2),
            'grand_total'          => round($tot, 2),
            'status_changed_at'    => $status_at,
            'started_at'           => $status !== 'draft' ? $created : NULL,
            'ready_at'             => in_array($status, array('ready', 'delivered'), TRUE) ? ($inv_at ?: $status_at) : NULL,
            'delivered_at'         => $status === 'delivered' ? $status_at : NULL,
            'created_by'           => NULL,
            'created_at'           => $created,
            'updated_at'           => $this->now,
        ));
        $wo_id = (int) $this->db->insert_id();
        if (!$wo_id) {
            throw new Exception('could not add '.$ro.': '.$this->db->error()['message']);
        }
        foreach ($lines as $l) {
            unset($l['_hsn']);
            $l['work_order_id'] = $wo_id;
            $this->db->insert('crm_work_order_lines', $l);
        }
        $who = array();
        foreach (array('service_advisor' => 'Advisor', 'primary_employee_name' => 'Technician') as $k => $label) {
            if (trim((string) ($d[$k] ?? '')) !== '') {
                $who[] = $label.': '.trim($d[$k]);
            }
        }
        if (!empty($row['created_by'])) {
            $who[] = 'Opened by: '.trim($row['created_by']);
        }
        if ($tags) {
            $who[] = 'Tags: '.implode(', ', $tags);
        }
        $this->db->insert('crm_work_order_events', array(
            'work_order_id' => $wo_id,
            'type'          => 'imported',
            'detail'        => mb_substr('Imported from OkMechanic'.($who ? ' · '.implode(' · ', $who) : ''), 0, 500),
            'created_at'    => $created,
        ));
        $this->_map('work_order', $okm_id, $wo_id);
        $this->_inc('work_orders_added');
        $this->_inc('work_orders_'.$status);
        $this->_inc('lines_added', count($lines));

        // Invoice
        $inv_id = NULL;
        if ((string) ($d['generate_invoice'] ?? '0') === '1') {
            $inv_id = $this->_invoice($d, $wo_id, $user_id, $ro, $vi, $ci, $lines, $tags, $inv_at ?: $status_at, $odo);
        }
        // Payments
        foreach ((array) ($d['payment_transaction_list'] ?? array()) as $p) {
            $this->_payment($p, $wo_id, $inv_id, $user_id, $created);
        }
        if ($inv_id) {
            // OkMechanic bills to the paisa; payments are in whole rupees. Leftovers under Rs 1 go to round-off.
            $inv = $this->db->select('grand_total, round_off')->where('id', $inv_id)->get('crm_invoices')->row_array();
            $paid = (float) $this->db->select('COALESCE(SUM(amount),0) s', FALSE)->where('invoice_id', $inv_id)->get('crm_payments')->row()->s;
            $diff = round((float) $inv['grand_total'] - $paid, 2);
            if ($paid > 0 && $diff != 0 && abs($diff) < 1) {
                $this->db->where('id', $inv_id)->update('crm_invoices', array(
                    'grand_total' => round($paid, 2),
                    'round_off'   => round((float) $inv['round_off'] - $diff, 2),
                ));
                $this->_inc('invoices_paisa_rounded');
            }
            $this->CI->crm_billing_model->recalc($inv_id);
        }

        // Keep the full OkMechanic record (technicians, images, insurance, feedback, ...).
        $this->_arc('work_order', $okm_id, $ro, trim(($ci['name'] ?? '').' · '.($vi['vehicle_plate_number'] ?? '')),
            self::STATUS[(string) $d['repairorder_status']] ?? (string) $d['repairorder_status'], $okm_total,
            substr($created, 0, 10), $wo_id, $user_id, $d);
    }

    protected function _line($type, $name, $remark, $qty, $rate, $disc, $tax_pct, $tax_type, $subtotal, $total, $work_status, $date, $fallback)
    {
        $name = trim(str_replace("\r", '', (string) $name));
        $first = trim(strtok($name, "\n"));
        $desc = array();
        if ($first !== $name) {
            $desc[] = $name;
        }
        if (trim((string) $remark) !== '') {
            $desc[] = trim($remark);
        }
        $qty = (float) $qty ?: 1;
        $rate = $this->_money($rate);
        $disc = max(0, min(100, (float) $disc));
        $tax_pct = (float) $tax_pct;
        $inclusive = (string) $tax_type !== '1';
        if ($subtotal !== NULL && $total !== NULL && $subtotal !== '' && $total !== '') {
            $taxable = $this->_money($subtotal);
            $amount = $this->_money($total);
        } else {
            $gross = $qty * $rate * (1 - $disc / 100);
            $taxable = $inclusive && $tax_pct > 0 ? $gross / (1 + $tax_pct / 100) : $gross;
            $amount = $inclusive ? $gross : $gross * (1 + $tax_pct / 100);
        }
        $at = self::parse_date($date) ?: $fallback;
        return array(
            'type'          => $type,
            'name'          => mb_substr($first !== '' ? $first : ($type === 'part' ? 'Part' : 'Service'), 0, 255),
            'description'   => $desc ? implode("\n", $desc) : NULL,
            'qty'           => $qty,
            'rate'          => $rate,
            'discount_pct'  => $disc,
            'tax_pct'       => $tax_pct,
            'tax_inclusive' => $inclusive ? 1 : 0,
            'taxable'       => round($taxable, 2),
            'tax_amount'    => round($amount - $taxable, 2),
            'amount'        => round($amount, 2),
            'done_at'       => (string) $work_status === '2' ? $at : NULL,
            'approval'      => 'approved',
            'approved_at'   => $at,
            'created_at'    => $at,
        );
    }

    protected function _invoice(array $d, $wo_id, $user_id, $ro, array $vi, array $ci, array $lines, array $tags, $when, $odo)
    {
        $prefix = trim((string) ($d['show_invoice_prefix'] ?? '')) ?: 'INV';
        $num = trim((string) ($d['show_invoice_number'] ?? ''));
        $number = trim((string) ($d['invoice_number'] ?? '')) ?: ($num !== '' ? $prefix.$num : 'INV-'.$ro);
        if ($this->db->where('invoice_number', $number)->count_all_results('crm_invoices')) {
            $this->_note('Invoice '.$number.' already used in the CRM: '.$ro.'\'s invoice imported as '.$number.'-B.');
            $number .= '-B';
        }
        $s = $this->billing;
        $sub = $cg = $sg = $tot = 0.0;
        $rows = array();
        foreach (array_values($lines) as $i => $l) {
            $c = round($l['tax_amount'] / 2, 2);
            $g = round($l['tax_amount'] - $c, 2);
            $rows[] = array(
                'sort' => $i, 'type' => $l['type'], 'name' => $l['name'],
                'hsn_sac' => $l['type'] === 'part' ? (string) ($s['bill_hsn_part'] ?? '') : (string) ($s['bill_sac_service'] ?? ''),
                'qty' => $l['qty'], 'rate' => $l['rate'], 'discount_pct' => $l['discount_pct'], 'taxable' => $l['taxable'],
                'tax_pct' => $l['tax_pct'], 'cgst' => $c, 'sgst' => $g, 'igst' => 0, 'amount' => $l['amount'],
            );
            $sub += $l['taxable'];
            $cg += $c;
            $sg += $g;
            $tot += $l['amount'];
        }
        $tot = round($tot, 2);
        $grand = round($tot);
        $workshop = $this->_workshop($tags);
        $plate = $this->_plate($vi['vehicle_plate_number'] ?? '');
        $address = trim(implode(', ', array_filter(array_map('trim', array($ci['address'] ?? '', $ci['area_name'] ?? '', $ci['city_name'] ?? '', $ci['postal_code'] ?? '')))));
        $this->db->insert('crm_invoices', array(
            'invoice_number' => mb_substr($number, 0, 30),
            'work_order_id'  => $wo_id,
            'user_id'        => $user_id,
            'workshop'       => $workshop,
            'invoice_date'   => substr($when, 0, 10),
            'gst_type'       => 'intra',
            'bill_name'      => mb_substr(trim((string) ($ci['company_name'] ?? '')) ?: trim((string) ($ci['name'] ?? '')), 0, 160),
            'bill_mobile'    => mb_substr($this->_mobile($ci['phone_number'] ?? ''), 0, 20),
            'bill_gstin'     => strtoupper(mb_substr(trim((string) ($ci['tax_no'] ?? '')), 0, 20)),
            'bill_address'   => mb_substr($address, 0, 400),
            'vehicle'        => mb_substr(trim($plate.' · '.trim(($vi['make_name'] ?? '').' '.($vi['model_name'] ?? ''))), 0, 160),
            'odometer_km'    => $odo !== '' ? (int) $odo : NULL,
            'seller_json'    => json_encode($this->CI->crm_billing_model->seller_for($workshop), JSON_UNESCAPED_UNICODE),
            'subtotal'       => round($sub, 2),
            'cgst'           => round($cg, 2),
            'sgst'           => round($sg, 2),
            'igst'           => 0,
            'round_off'      => round($grand - $tot, 2),
            'grand_total'    => $grand,
            'paid'           => 0,
            'balance'        => $grand,
            'status'         => $grand > 0 ? 'unpaid' : 'paid',
            'notes'          => 'Imported from OkMechanic',
            'pay_token'      => substr(strtr(base64_encode(random_bytes(18)), '+/', 'Az'), 0, 24),
            'sent_at'        => NULL,
            'created_by'     => NULL,
            'created_at'     => $when,
        ));
        $id = (int) $this->db->insert_id();
        if (!$id) {
            throw new Exception('could not add invoice '.$number.': '.$this->db->error()['message']);
        }
        foreach ($rows as $r) {
            $r['invoice_id'] = $id;
            $this->db->insert('crm_invoice_lines', $r);
        }
        $this->_map('invoice', $d['repairorder_id'], $id);
        $this->_inc('invoices_added');
        return $id;
    }

    protected function _account_id($okm_id, $name)
    {
        if ($this->accounts === NULL) {
            $this->accounts = array();
            foreach ($this->db->get('crm_pay_accounts')->result_array() as $a) {
                $this->accounts[strtolower(trim($a['name']))] = (int) $a['id'];
            }
        }
        $name = trim((string) $name) ?: 'OkMechanic account '.$okm_id;
        $k = strtolower($name);
        if (isset($this->accounts[$k])) {
            return $this->accounts[$k];
        }
        $kind = stripos($name, 'cash') !== FALSE ? 'cash' : (stripos($name, 'discount') !== FALSE ? 'other' : (stripos($name, 'card') !== FALSE ? 'card' : 'bank'));
        // Added switched off so they don't crowd the payment form; turn on in Billing setup if still used.
        $this->db->insert('crm_pay_accounts', array(
            'name' => mb_substr($name, 0, 80), 'kind' => $kind, 'details' => 'From OkMechanic',
            'is_active' => 0, 'sort' => 90, 'created_at' => $this->now,
        ));
        $id = (int) $this->db->insert_id();
        $this->_inc('payment_accounts_added');
        if ($okm_id !== '') {
            $this->_map('pay_account', $okm_id, $id);
        }
        return $this->accounts[$k] = $id;
    }

    protected function _payment(array $p, $wo_id, $inv_id, $user_id, $fallback)
    {
        $okm = (string) ($p['repairorder_transaction_id'] ?? '');
        if ($okm !== '' && $this->_mapped('payment', $okm)) {
            return;
        }
        $amount = $this->_money($p['amount'] ?? 0);
        if ($amount == 0) {
            return;
        }
        $mode_raw = strtolower(trim((string) ($p['payment_mode'] ?? '')));
        $modes = array('cash' => 'cash', 'upi' => 'upi', 'google pay' => 'upi', 'phonepe' => 'upi', 'paytm' => 'upi', 'bhim' => 'upi',
            'cheque' => 'cheque', 'credit card' => 'card', 'debit card' => 'card', 'net banking' => 'bank', 'neft' => 'bank',
            'imps' => 'bank', 'rtgs' => 'bank', 'wallet' => 'online', 'benefitpay' => 'online', 'm-pesa' => 'online');
        $mode = $modes[$mode_raw] ?? 'bank';
        $account = $this->_account_id((string) ($p['account_id'] ?? ''), $p['account_name'] ?? '');
        $when = self::parse_date($p['edit_date'] ?? '') ?: (self::parse_date($p['created'] ?? '') ?: $fallback);
        if (substr($when, 11) === '00:00:00') {
            $when = substr($when, 0, 10).' 12:00:00';
        }
        $note = 'OkMechanic: '.trim(($p['payment_mode'] ?? '').' · '.($p['account_name'] ?? ''), ' ·');
        if (trim((string) ($p['payment_remark'] ?? '')) !== '') {
            $note .= ' · '.trim($p['payment_remark']);
        }
        $this->db->insert('crm_payments', array(
            'invoice_id'    => $inv_id,
            'work_order_id' => $wo_id,
            'user_id'       => $user_id,
            'amount'        => $amount,
            'account_id'    => $account ?: NULL,
            'mode'          => $mode,
            'reference'     => '',
            'paid_at'       => $when,
            'note'          => mb_substr($note, 0, 255),
            'source'        => 'okmechanic',
            'created_by'    => NULL,
            'created_at'    => $when,
        ));
        $id = (int) $this->db->insert_id();
        if (!$id) {
            throw new Exception('could not add payment '.$okm.': '.$this->db->error()['message']);
        }
        if ($okm !== '') {
            $this->_map('payment', $okm, $id);
        }
        $this->_inc('payments_added');
        $this->_inc('payments_total_rs', (int) round($amount));
        if (stripos((string) ($p['account_name'] ?? ''), 'discount') !== FALSE) {
            $this->_inc('payments_marked_as_discount_in_okm');
        }
    }

    // ---- everything else (archive) -------------------------------------------------

    protected function _arc($entity, $okm_id, $number, $title, $status, $amount, $date, $wo_id, $user_id, $data)
    {
        $okm_id = (string) $okm_id;
        if ($okm_id === '') {
            return;
        }
        $row = array(
            'number'        => mb_substr((string) $number, 0, 40),
            'title'         => mb_substr(trim((string) $title, " ·"), 0, 255),
            'status'        => mb_substr((string) $status, 0, 40),
            'amount'        => $amount !== NULL && $amount !== '' ? $this->_money($amount) : NULL,
            'doc_date'      => $date ?: NULL,
            'work_order_id' => $wo_id ?: NULL,
            'user_id'       => $user_id ?: NULL,
            'data'          => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
        $have = $this->db->select('id')->where('entity', $entity)->where('okm_id', $okm_id)->get(self::ARC)->row_array();
        if ($have) {
            $this->db->where('id', $have['id'])->update(self::ARC, $row);
            $this->_inc('archive_updated_'.$entity);
        } else {
            $row += array('entity' => $entity, 'okm_id' => $okm_id, 'created_at' => $this->now);
            $this->db->insert(self::ARC, $row);
            $this->_inc('archive_'.$entity);
        }
    }

    protected function _archive_rest(array $m)
    {
        $est_status = array(1 => 'Draft', 2 => 'Pending', 3 => 'Approved', 4 => 'Refused', 5 => 'Converted');
        $details = array();
        foreach ($m['estimate_details'] ?? array() as $x) {
            $details[(string) $x['id']] = $x['result'] ?? NULL;
        }
        foreach ($m['estimates'] ?? array() as $e) {
            $id = (string) $e['estimate_id'];
            $wo = !empty($e['repairorder_id']) ? $this->_mapped('work_order', $e['repairorder_id']) : 0;
            $uid = $this->_user_by_mobile($e['phone_number'] ?? '');
            $data = array('list' => $e, 'detail' => $details[$id] ?? NULL);
            $this->_arc('estimate', $id, $e['estimate_number'] ?? '', ($e['customer_name'] ?? '').' · '.($e['vehicle_number'] ?? ''),
                $est_status[(int) ($e['__status'] ?? 0)] ?? '', $e['total_amount'] ?? NULL,
                self::parse_date(($details[$id]['created'] ?? '') ?: '', FALSE), $wo, $uid, $data);
        }

        $pdet = array();
        foreach ($m['purchase_details'] ?? array() as $x) {
            $pdet[(string) $x['id']] = $x['result'] ?? NULL;
        }
        foreach ($m['purchases'] ?? array() as $p) {
            $id = (string) $p['purchase_order_id'];
            $wo = !empty($p['repairorder_id']) ? $this->_mapped('work_order', $p['repairorder_id']) : 0;
            $det = $pdet[$id] ?? NULL;
            $this->_arc('purchase', $id, $p['purchase_number'] ?? '', $p['supplier_name'] ?? '',
                !empty($p['mark_as_closed']) && $p['mark_as_closed'] !== '0' ? 'Done' : 'Draft', $p['total_amount'] ?? NULL,
                self::parse_date($det['purchase_date'] ?? ($p['purchase_date'] ?? ''), FALSE), $wo, NULL, array('list' => $p, 'detail' => $det));
        }

        $epay = array();
        foreach ($m['expense_payments'] ?? array() as $x) {
            $epay[(string) $x['id']] = $x['result'] ?? NULL;
        }
        foreach ($m['expenses'] ?? array() as $e) {
            $id = (string) $e['expense_id'];
            $wo = !empty($e['repairorder_id']) ? $this->_mapped('work_order', $e['repairorder_id']) : 0;
            $this->_arc('expense', $id, $e['expense_number'] ?? '', trim(($e['category_name'] ?? '').' · '.($e['supplier_name'] ?? '')),
                ($e['generate_receipt'] ?? '0') === '1' ? 'Receipt' : 'Open', $e['total_amount'] ?? NULL,
                self::parse_date($e['expense_date'] ?? '', FALSE), $wo, NULL, array('list' => $e, 'payment' => $epay[$id] ?? NULL));
        }

        foreach ($m['suppliers'] ?? array() as $s) {
            $this->_arc('supplier', $s['supplier_id'], '', ($s['supplier_name'] ?? '').(!empty($s['phone_number']) ? ' · '.$s['phone_number'] : ''),
                $s['industry_name'] ?? '', NULL, self::parse_date($s['created'] ?? '', FALSE), NULL, NULL, $s);
        }
        foreach ($m['company_users'] ?? array() as $u) {
            $this->_arc('staff', $u['employee_id'], '', ($u['name'] ?? '').' · '.($u['designation_name'] ?? ''),
                $u['employee_login_type'] ?? '', NULL, self::parse_date($u['register_date'] ?? '', FALSE), NULL, NULL, $u);
        }
        foreach ($m['payment_accounts'] ?? array() as $a) {
            $crm = $this->_account_id((string) $a['account_id'], $a['account_title'] ?? '');
            $this->_map('pay_account', $a['account_id'], $crm);
            $this->_arc('pay_account', $a['account_id'], '', $a['account_title'] ?? '', $a['accounts_type'] ?? '',
                $a['balance'] ?? NULL, NULL, NULL, NULL, $a);
        }
    }

    protected function _user_by_mobile($raw)
    {
        $m = $this->_mobile($raw);
        if ($m === '') {
            return 0;
        }
        $r = $this->db->select('id')->where('mobile', $m)->get('users')->row_array();
        return $r ? (int) $r['id'] : 0;
    }
}
