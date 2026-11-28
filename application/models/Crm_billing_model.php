<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Job card invoices (continuing OkMechanic's INV series), payments against them,
 * payment accounts (cash, bank / UPI, card…) and billing settings (GSTIN, UPI ID, terms).
 * Settings live in the `settings` table under bill_* keys; the owner fills them in.
 */
class Crm_billing_model extends CI_Model
{
    const T_INV = 'crm_invoices';
    const T_LINES = 'crm_invoice_lines';
    const T_PAY = 'crm_payments';
    const T_ACC = 'crm_pay_accounts';

    const MODES = array('cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'bank' => 'Bank transfer', 'cheque' => 'Cheque', 'online' => 'Online (gateway)');
    const ACC_KINDS = array('cash' => 'Cash', 'bank' => 'Bank / UPI', 'card' => 'Card machine', 'gateway' => 'Payment gateway', 'other' => 'Other');
    const STATUS = array('unpaid' => 'Unpaid', 'partly_paid' => 'Part paid', 'paid' => 'Paid', 'cancelled' => 'Cancelled');

    /** Billing settings and their defaults. */
    const SETTINGS = array(
        'bill_legal_name'  => '',
        'bill_gstin'       => '',
        'bill_address'     => '',
        'bill_state'       => 'Maharashtra',
        'bill_state_code'  => '27',
        'bill_upi_vpa'     => '',
        'bill_upi_name'    => '',
        'bill_bank_text'   => '',
        'bill_terms'       => "Goods once sold will not be taken back.\nWarranty on parts as per the manufacturer; on our labour as per our warranty policy.",
        'bill_inv_prefix'  => 'INV',
        'bill_inv_next'    => '2097',
        'bill_sac_service' => '998714',
        'bill_hsn_part'    => '8708',
        'bill_ws_gst'      => '',   // JSON: workshop => {gstin, state, state_code, address} when a workshop bills under another registration
    );

    protected $ready = NULL;

    public function ensure_schema()
    {
        if ($this->ready !== NULL) {
            return $this->ready;
        }
        $sql = array(
            self::T_ACC => "CREATE TABLE IF NOT EXISTS `crm_pay_accounts` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80) NOT NULL,
  `kind`        ENUM('cash','bank','card','gateway','other') NOT NULL DEFAULT 'bank',
  `details`     VARCHAR(255) NOT NULL DEFAULT '',
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `sort`        SMALLINT NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::T_INV => "CREATE TABLE IF NOT EXISTS `crm_invoices` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number`  VARCHAR(30) NOT NULL,
  `work_order_id`   BIGINT UNSIGNED NULL,
  `user_id`         BIGINT UNSIGNED NULL,
  `workshop`        VARCHAR(30) NOT NULL DEFAULT '',
  `invoice_date`    DATE NOT NULL,
  `gst_type`        ENUM('intra','inter') NOT NULL DEFAULT 'intra',
  `bill_name`       VARCHAR(160) NOT NULL DEFAULT '',
  `bill_mobile`     VARCHAR(20) NOT NULL DEFAULT '',
  `bill_gstin`      VARCHAR(20) NOT NULL DEFAULT '',
  `bill_address`    VARCHAR(400) NOT NULL DEFAULT '',
  `vehicle`         VARCHAR(160) NOT NULL DEFAULT '',
  `odometer_km`     INT UNSIGNED NULL,
  `seller_json`     TEXT NULL,
  `subtotal`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `cgst`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `sgst`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `igst`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `round_off`       DECIMAL(6,2) NOT NULL DEFAULT 0,
  `grand_total`     DECIMAL(12,2) NOT NULL DEFAULT 0,
  `paid`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `balance`         DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status`          ENUM('unpaid','partly_paid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  `notes`           VARCHAR(500) NOT NULL DEFAULT '',
  `pay_token`       CHAR(24) NOT NULL,
  `pdf_object`      VARCHAR(200) NOT NULL DEFAULT '',
  `sent_at`         DATETIME NULL,
  `last_reminder_at` DATETIME NULL,
  `reminders`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_by`      INT UNSIGNED NULL,
  `created_at`      DATETIME NOT NULL,
  `cancelled_at`    DATETIME NULL,
  `cancel_reason`   VARCHAR(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_number` (`invoice_number`),
  UNIQUE KEY `uq_inv_token` (`pay_token`),
  KEY `idx_inv_wo` (`work_order_id`),
  KEY `idx_inv_status` (`status`, `invoice_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::T_LINES => "CREATE TABLE IF NOT EXISTS `crm_invoice_lines` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id`  BIGINT UNSIGNED NOT NULL,
  `sort`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `type`        ENUM('service','part') NOT NULL DEFAULT 'service',
  `name`        VARCHAR(255) NOT NULL,
  `hsn_sac`     VARCHAR(12) NOT NULL DEFAULT '',
  `qty`         DECIMAL(10,2) NOT NULL DEFAULT 1,
  `rate`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `discount_pct` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `taxable`     DECIMAL(12,2) NOT NULL DEFAULT 0,
  `tax_pct`     DECIMAL(5,2) NOT NULL DEFAULT 0,
  `cgst`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `sgst`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `igst`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `amount`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_invl_inv` (`invoice_id`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::T_PAY => "CREATE TABLE IF NOT EXISTS `crm_payments` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id`    BIGINT UNSIGNED NULL,
  `work_order_id` BIGINT UNSIGNED NULL,
  `user_id`       BIGINT UNSIGNED NULL,
  `amount`        DECIMAL(12,2) NOT NULL,
  `account_id`    INT UNSIGNED NULL,
  `mode`          VARCHAR(12) NOT NULL DEFAULT 'upi',
  `reference`     VARCHAR(80) NOT NULL DEFAULT '',
  `paid_at`       DATETIME NOT NULL,
  `note`          VARCHAR(255) NOT NULL DEFAULT '',
  `source`        VARCHAR(20) NOT NULL DEFAULT 'manual',
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL,
  `voided_at`     DATETIME NULL,
  `voided_by`     INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pay_inv` (`invoice_id`),
  KEY `idx_pay_wo` (`work_order_id`),
  KEY `idx_pay_date` (`paid_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
        foreach ($sql as $table => $q) {
            if (!$this->db->table_exists($table) && !$this->db->query($q)) {
                log_message('error', '[billing] schema failed for '.$table.': '.$this->db->error()['message']);
                return $this->ready = FALSE;
            }
        }
        if (!$this->db->count_all(self::T_ACC)) {
            $now = date('Y-m-d H:i:s');
            foreach (array(array('Cash collection', 'cash'), array('Bank UPI', 'bank'), array('Card swipe', 'card')) as $i => $a) {
                $this->db->insert(self::T_ACC, array('name' => $a[0], 'kind' => $a[1], 'sort' => $i, 'created_at' => $now));
            }
        }
        return $this->ready = TRUE;
    }

    // ---- settings -----------------------------------------------------------------

    public function settings()
    {
        $out = self::SETTINGS;
        if ($this->db->table_exists('settings')) {
            $rows = $this->db->where_in('setting_key', array_keys(self::SETTINGS))->get('settings')->result_array();
            foreach ($rows as $r) {
                if ($r['setting_value'] !== NULL && $r['setting_value'] !== '') {
                    $out[$r['setting_key']] = (string) $r['setting_value'];
                }
            }
        }
        $out['ws_gst'] = json_decode((string) $out['bill_ws_gst'], TRUE) ?: array();
        return $out;
    }

    public function save_settings(array $vals)
    {
        foreach ($vals as $k => $v) {
            if (!array_key_exists($k, self::SETTINGS)) {
                continue;
            }
            $v = (string) $v;
            if ($this->db->where('setting_key', $k)->count_all_results('settings')) {
                $this->db->where('setting_key', $k)->update('settings', array('setting_value' => $v));
            } else {
                $this->db->insert('settings', array('setting_key' => $k, 'setting_value' => $v));
            }
        }
        if (function_exists('ymo_clear_settings_cache')) {
            ymo_clear_settings_cache();
        }
    }

    /** Seller details for a workshop (its own GST registration if one is set). */
    public function seller_for($workshop)
    {
        $s = $this->settings();
        $biz_file = APPPATH.'config/marketing_business.php';
        $biz = is_file($biz_file) ? (require $biz_file) : array();
        $seller = array(
            'name'       => $s['bill_legal_name'] !== '' ? $s['bill_legal_name'] : ($biz['legal_entity'] ?? 'YMO Mobility Solutions Private Limited'),
            'brand'      => $biz['brand_name'] ?? 'Your Mechanic Online',
            'gstin'      => strtoupper(trim($s['bill_gstin'])),
            'address'    => $s['bill_address'],
            'state'      => $s['bill_state'],
            'state_code' => $s['bill_state_code'],
            'phone'      => $biz['phone_display'] ?? '',
            'whatsapp'   => $biz['whatsapp_display'] ?? '',
            'email'      => $biz['email'] ?? '',
        );
        if ($workshop !== '' && !empty($s['ws_gst'][$workshop])) {
            foreach (array('gstin', 'address', 'state', 'state_code') as $k) {
                if (!empty($s['ws_gst'][$workshop][$k])) {
                    $seller[$k] = $k === 'gstin' ? strtoupper(trim($s['ws_gst'][$workshop][$k])) : $s['ws_gst'][$workshop][$k];
                }
            }
        }
        return $seller;
    }

    // ---- payment accounts -----------------------------------------------------------

    public function accounts($active_only = TRUE)
    {
        $this->ensure_schema();
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        return $this->db->order_by('is_active', 'DESC')->order_by('sort', 'ASC')->order_by('name', 'ASC')->get(self::T_ACC)->result_array();
    }

    public function save_account($id, array $d)
    {
        $this->ensure_schema();
        $d = array_intersect_key($d, array_flip(array('name', 'kind', 'details', 'is_active', 'sort')));
        if ($id) {
            return $this->db->where('id', (int) $id)->update(self::T_ACC, $d);
        }
        $d['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert(self::T_ACC, $d);
    }

    /** id => received in the last N days / all time, for the accounts page. */
    public function account_totals($since = NULL)
    {
        $this->ensure_schema();
        $this->db->select('account_id, COALESCE(SUM(amount),0) total, COUNT(*) n', FALSE)->where('voided_at IS NULL', NULL, FALSE);
        if ($since) {
            $this->db->where('paid_at >=', $since);
        }
        $out = array();
        foreach ($this->db->group_by('account_id')->get(self::T_PAY)->result_array() as $r) {
            $out[(int) $r['account_id']] = $r;
        }
        return $out;
    }

    // ---- invoices -------------------------------------------------------------------

    public function next_invoice_number()
    {
        $s = $this->settings();
        $prefix = preg_replace('/[^A-Za-z0-9\/-]/', '', $s['bill_inv_prefix']) ?: 'INV';
        $next = max(1, (int) $s['bill_inv_next']);
        $rows = $this->db->select('invoice_number')->like('invoice_number', $prefix, 'after')->get(self::T_INV)->result_array();
        foreach ($rows as $r) {
            $tail = substr($r['invoice_number'], strlen($prefix));
            if (ctype_digit($tail)) {
                $next = max($next, (int) $tail + 1);
            }
        }
        return $prefix.$next;
    }

    /**
     * Snapshot a job card's approved lines into a new invoice.
     * @param array $opts invoice_date, gst_type, bill_name, bill_gstin, bill_address, notes, admin_id
     * @return int invoice id (0 on failure)
     */
    public function create_from_work_order(array $wo, array $lines, array $opts)
    {
        $this->ensure_schema();
        $s = $this->settings();
        $inter = ($opts['gst_type'] ?? 'intra') === 'inter';
        $rows = array();
        $sub = $cg = $sg = $ig = $tot = 0.0;
        foreach (array_values($lines) as $i => $l) {
            $tax = (float) $l['tax_amount'];
            $c = $inter ? 0.0 : round($tax / 2, 2);
            $g = $inter ? 0.0 : round($tax - $c, 2);
            $x = $inter ? round($tax, 2) : 0.0;
            $rows[] = array(
                'sort'         => $i,
                'type'         => $l['type'] === 'part' ? 'part' : 'service',
                'name'         => mb_substr($l['name'], 0, 255),
                'hsn_sac'      => $l['type'] === 'part' ? $s['bill_hsn_part'] : $s['bill_sac_service'],
                'qty'          => (float) $l['qty'],
                'rate'         => (float) $l['rate'],
                'discount_pct' => (float) $l['discount_pct'],
                'taxable'      => round((float) $l['taxable'], 2),
                'tax_pct'      => (float) $l['tax_pct'],
                'cgst'         => $c,
                'sgst'         => $g,
                'igst'         => $x,
                'amount'       => round((float) $l['amount'], 2),
            );
            $sub += (float) $l['taxable'];
            $cg += $c;
            $sg += $g;
            $ig += $x;
            $tot += (float) $l['amount'];
        }
        $tot = round($tot, 2);
        $grand = round($tot);
        $seller = $this->seller_for((string) $wo['workshop']);

        for ($try = 0; $try < 4; $try++) {
            $number = $this->next_invoice_number();
            $this->db->trans_start();
            $this->db->insert(self::T_INV, array(
                'invoice_number' => $number,
                'work_order_id'  => (int) $wo['id'],
                'user_id'        => (int) $wo['user_id'] ?: NULL,
                'workshop'       => (string) $wo['workshop'],
                'invoice_date'   => $opts['invoice_date'] ?? date('Y-m-d'),
                'gst_type'       => $inter ? 'inter' : 'intra',
                'bill_name'      => mb_substr(trim((string) ($opts['bill_name'] ?? '')) ?: (string) $wo['customer_name'], 0, 160),
                'bill_mobile'    => (string) $wo['customer_mobile'],
                'bill_gstin'     => strtoupper(mb_substr(trim((string) ($opts['bill_gstin'] ?? '')), 0, 20)),
                'bill_address'   => mb_substr(trim((string) ($opts['bill_address'] ?? '')), 0, 400),
                'vehicle'        => mb_substr(trim($wo['vehicle_number'].' · '.trim($wo['vehicle_make'].' '.$wo['vehicle_variant'])), 0, 160),
                'odometer_km'    => $wo['odometer_km'] !== NULL ? (int) $wo['odometer_km'] : NULL,
                'seller_json'    => json_encode($seller, JSON_UNESCAPED_UNICODE),
                'subtotal'       => round($sub, 2),
                'cgst'           => round($cg, 2),
                'sgst'           => round($sg, 2),
                'igst'           => round($ig, 2),
                'round_off'      => round($grand - $tot, 2),
                'grand_total'    => $grand,
                'paid'           => 0,
                'balance'        => $grand,
                'status'         => $grand > 0 ? 'unpaid' : 'paid',
                'notes'          => mb_substr(trim((string) ($opts['notes'] ?? '')), 0, 500),
                'pay_token'      => substr(strtr(base64_encode(random_bytes(18)), '+/', 'Az'), 0, 24),
                'created_by'     => (int) ($opts['admin_id'] ?? 0) ?: NULL,
                'created_at'     => date('Y-m-d H:i:s'),
            ));
            $id = (int) $this->db->insert_id();
            if ($id) {
                foreach ($rows as $r) {
                    $r['invoice_id'] = $id;
                    $this->db->insert(self::T_LINES, $r);
                }
            }
            $this->db->trans_complete();
            if ($id && $this->db->trans_status()) {
                // Payments taken on the job before the invoice (advance) count against it.
                $this->db->where('work_order_id', (int) $wo['id'])->where('invoice_id IS NULL', NULL, FALSE)
                    ->where('voided_at IS NULL', NULL, FALSE)->update(self::T_PAY, array('invoice_id' => $id));
                $this->recalc($id);
                return $id;
            }
        }
        return 0;
    }

    public function find($id)
    {
        $this->ensure_schema();
        return $this->db->where('id', (int) $id)->get(self::T_INV)->row_array();
    }

    public function find_by_token($token)
    {
        if (!preg_match('/^[A-Za-z0-9]{24}$/', (string) $token) || !$this->ensure_schema()) {
            return NULL;
        }
        return $this->db->where('pay_token', $token)->get(self::T_INV)->row_array();
    }

    public function lines($invoice_id)
    {
        return $this->db->where('invoice_id', (int) $invoice_id)->order_by('sort', 'ASC')->get(self::T_LINES)->result_array();
    }

    /** Live (not cancelled) invoice of a job card, if any. */
    public function for_work_order($work_order_id)
    {
        $this->ensure_schema();
        return $this->db->where('work_order_id', (int) $work_order_id)->where('status !=', 'cancelled')
            ->order_by('id', 'DESC')->limit(1)->get(self::T_INV)->row_array();
    }

    public function all_for_work_order($work_order_id)
    {
        $this->ensure_schema();
        return $this->db->where('work_order_id', (int) $work_order_id)->order_by('id', 'DESC')->get(self::T_INV)->result_array();
    }

    public function update_invoice($id, array $d)
    {
        return $this->db->where('id', (int) $id)->update(self::T_INV, $d);
    }

    public function cancel($id, $reason)
    {
        $inv = $this->find($id);
        if (!$inv || $inv['status'] === 'cancelled') {
            return FALSE;
        }
        // Payments stay with the job card and move to the next invoice raised for it.
        $this->db->where('invoice_id', (int) $id)->update(self::T_PAY, array('invoice_id' => NULL));
        return $this->update_invoice($id, array('status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s'),
            'cancel_reason' => mb_substr((string) $reason, 0, 200), 'paid' => 0, 'balance' => 0));
    }

    /** Recomputes paid / balance / status from payments. */
    public function recalc($id)
    {
        $inv = $this->find($id);
        if (!$inv || $inv['status'] === 'cancelled') {
            return;
        }
        $paid = (float) $this->db->select('COALESCE(SUM(amount),0) s', FALSE)->where('invoice_id', (int) $id)
            ->where('voided_at IS NULL', NULL, FALSE)->get(self::T_PAY)->row()->s;
        $bal = round((float) $inv['grand_total'] - $paid, 2);
        $status = $bal <= 0.009 ? 'paid' : ($paid > 0 ? 'partly_paid' : 'unpaid');
        $this->update_invoice($id, array('paid' => round($paid, 2), 'balance' => max(0, $bal), 'status' => $status));
    }

    /**
     * @param string $tab collect | paid | cancelled | all
     */
    public function page($tab, $workshop, $q, $limit, $offset, &$total = NULL)
    {
        $this->ensure_schema();
        $apply = function () use ($tab, $workshop, $q) {
            $this->db->from(self::T_INV.' i')->join('crm_work_orders w', 'w.id = i.work_order_id', 'left');
            if ($tab === 'collect') {
                $this->db->where_in('i.status', array('unpaid', 'partly_paid'));
            } elseif ($tab === 'paid') {
                $this->db->where('i.status', 'paid');
            } elseif ($tab === 'cancelled') {
                $this->db->where('i.status', 'cancelled');
            }
            if ($workshop !== '') {
                $this->db->where('i.workshop', $workshop);
            }
            if ($q !== '') {
                $this->db->group_start()->like('i.invoice_number', $q)->or_like('i.bill_name', $q)->or_like('i.bill_mobile', $q)
                    ->or_like('i.vehicle', $q)->or_like('w.ro_number', $q)->group_end();
            }
        };
        $apply();
        $total = $this->db->count_all_results();
        $apply();
        return $this->db->select('i.*, w.ro_number', FALSE)->order_by($tab === 'collect' ? 'i.invoice_date' : 'i.id', $tab === 'collect' ? 'ASC' : 'DESC')
            ->limit($limit, $offset)->get()->result_array();
    }

    public function counts($workshop = '')
    {
        $this->ensure_schema();
        if ($workshop !== '') {
            $this->db->where('workshop', $workshop);
        }
        $rows = $this->db->select('status, COUNT(*) n, COALESCE(SUM(balance),0) bal, COALESCE(SUM(grand_total),0) tot', FALSE)
            ->group_by('status')->get(self::T_INV)->result_array();
        $out = array('collect' => 0, 'paid' => 0, 'cancelled' => 0, 'all' => 0, 'to_collect' => 0.0);
        foreach ($rows as $r) {
            $n = (int) $r['n'];
            $out['all'] += $n;
            if (in_array($r['status'], array('unpaid', 'partly_paid'), TRUE)) {
                $out['collect'] += $n;
                $out['to_collect'] += (float) $r['bal'];
            } else {
                $out[$r['status']] += $n;
            }
        }
        return $out;
    }

    // ---- payments ---------------------------------------------------------------------

    public function payments_for_invoice($invoice_id)
    {
        return $this->_payments()->where('p.invoice_id', (int) $invoice_id)->order_by('p.paid_at', 'ASC')->get()->result_array();
    }

    public function payments_for_work_order($work_order_id)
    {
        return $this->_payments()->where('p.work_order_id', (int) $work_order_id)->order_by('p.paid_at', 'ASC')->get()->result_array();
    }

    protected function _payments()
    {
        $this->ensure_schema();
        return $this->db->select('p.*, a.name AS account_name, u.name AS created_by_name', FALSE)->from(self::T_PAY.' p')
            ->join(self::T_ACC.' a', 'a.id = p.account_id', 'left')->join('admin_users u', 'u.id = p.created_by', 'left');
    }

    public function find_payment($id)
    {
        $this->ensure_schema();
        return $this->db->where('id', (int) $id)->get(self::T_PAY)->row_array();
    }

    /** @return int payment id */
    public function add_payment(array $d)
    {
        $this->ensure_schema();
        $d['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::T_PAY, $d);
        $id = (int) $this->db->insert_id();
        if (!empty($d['invoice_id'])) {
            $this->recalc($d['invoice_id']);
        }
        return $id;
    }

    public function void_payment($id, $admin_id)
    {
        $p = $this->find_payment($id);
        if (!$p || $p['voided_at']) {
            return FALSE;
        }
        $this->db->where('id', (int) $id)->update(self::T_PAY, array('voided_at' => date('Y-m-d H:i:s'), 'voided_by' => (int) $admin_id ?: NULL));
        if ($p['invoice_id']) {
            $this->recalc($p['invoice_id']);
        }
        return TRUE;
    }

    /** Payments received in a period, by mode and account (daily book). */
    public function received($from, $to)
    {
        $this->ensure_schema();
        return $this->_payments()->where('p.voided_at IS NULL', NULL, FALSE)->where('p.paid_at >=', $from.' 00:00:00')
            ->where('p.paid_at <=', $to.' 23:59:59')->order_by('p.paid_at', 'DESC')->get()->result_array();
    }
}
