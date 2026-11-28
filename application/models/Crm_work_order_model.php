<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Job cards (work orders, "RO" numbers): the car's job from check-in to delivery.
 * Customer + car are the same users / vehicles records bookings use; a job card can
 * come from a booking (booking_id) or be opened for a walk-in.
 *
 * Line amounts: base = qty x rate x (1 - discount%).
 *   GST included: amount = base, taxable = base / (1 + gst%)
 *   GST on top:   taxable = base, amount = base x (1 + gst%)
 */
class Crm_work_order_model extends CI_Model
{
    const T = 'crm_work_orders';
    const T_LINES = 'crm_work_order_lines';
    const T_TECH = 'crm_work_order_technicians';
    const T_EVENTS = 'crm_work_order_events';

    /** Status => label. Order is the normal flow. */
    const STATUSES = array(
        'draft'       => 'Draft',
        'in_progress' => 'In progress',
        'on_hold'     => 'On hold',
        'ready'       => 'Ready',
        'delivered'   => 'Delivered',
        'returned'    => 'Returned',
    );

    /** Allowed moves: from => list of to. */
    const MOVES = array(
        'draft'       => array('in_progress', 'returned'),
        'in_progress' => array('on_hold', 'ready', 'returned'),
        'on_hold'     => array('in_progress', 'returned'),
        'ready'       => array('delivered', 'in_progress'),
        'delivered'   => array('in_progress'),   // reopen
        'returned'    => array('in_progress'),   // reopen
    );

    /** First RO number when the CRM takes over from OkMechanic (last there was RO2237). */
    const FIRST_RO = 2238;

    protected $ready = NULL;

    public function ensure_schema()
    {
        if ($this->ready !== NULL) {
            return $this->ready;
        }
        $sql = array(
            self::T => "CREATE TABLE IF NOT EXISTS `crm_work_orders` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ro_number`            VARCHAR(20) NOT NULL,
  `booking_id`           BIGINT UNSIGNED NULL,
  `user_id`              BIGINT UNSIGNED NOT NULL,
  `vehicle_id`           BIGINT UNSIGNED NOT NULL,
  `workshop`             VARCHAR(30) NOT NULL DEFAULT '',
  `status`               ENUM('draft','in_progress','on_hold','ready','delivered','returned') NOT NULL DEFAULT 'draft',
  `booking_type`         ENUM('workshop','pickup','doorstep') NOT NULL DEFAULT 'workshop',
  `odometer_km`          INT UNSIGNED NULL,
  `fuel_pct`             TINYINT UNSIGNED NULL,
  `advisor_id`           INT UNSIGNED NULL,
  `bay`                  VARCHAR(20) NOT NULL DEFAULT '',
  `expected_delivery_at` DATETIME NULL,
  `complaint`            TEXT NULL,
  `instructions`         TEXT NULL,
  `lpo_number`           VARCHAR(40) NOT NULL DEFAULT '',
  `lpo_date`             DATE NULL,
  `lpo_amount`           DECIMAL(12,2) NULL,
  `subtotal`             DECIMAL(12,2) NOT NULL DEFAULT 0,
  `tax_total`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `grand_total`          DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status_changed_at`    DATETIME NULL,
  `started_at`           DATETIME NULL,
  `ready_at`             DATETIME NULL,
  `delivered_at`         DATETIME NULL,
  `created_by`           INT UNSIGNED NULL,
  `created_at`           DATETIME NOT NULL,
  `updated_at`           DATETIME NULL,
  `deleted_at`           DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wo_ro` (`ro_number`),
  KEY `idx_wo_status` (`status`, `created_at`),
  KEY `idx_wo_booking` (`booking_id`),
  KEY `idx_wo_user` (`user_id`),
  KEY `idx_wo_vehicle` (`vehicle_id`),
  KEY `idx_wo_workshop` (`workshop`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::T_LINES => "CREATE TABLE IF NOT EXISTS `crm_work_order_lines` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_order_id`   BIGINT UNSIGNED NOT NULL,
  `type`            ENUM('service','part') NOT NULL,
  `catalog_item_id` INT UNSIGNED NULL,
  `name`            VARCHAR(255) NOT NULL,
  `description`     TEXT NULL,
  `qty`             DECIMAL(10,2) NOT NULL DEFAULT 1,
  `rate`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `discount_pct`    DECIMAL(5,2) NOT NULL DEFAULT 0,
  `tax_pct`         DECIMAL(5,2) NOT NULL DEFAULT 0,
  `tax_inclusive`   TINYINT(1) NOT NULL DEFAULT 1,
  `taxable`         DECIMAL(12,2) NOT NULL DEFAULT 0,
  `tax_amount`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `amount`          DECIMAL(12,2) NOT NULL DEFAULT 0,
  `technician_id`   INT UNSIGNED NULL,
  `done_at`         DATETIME NULL,
  `done_by`         INT UNSIGNED NULL,
  `created_by`      INT UNSIGNED NULL,
  `created_at`      DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wol_wo` (`work_order_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::T_TECH => "CREATE TABLE IF NOT EXISTS `crm_work_order_technicians` (
  `work_order_id` BIGINT UNSIGNED NOT NULL,
  `admin_id`      INT UNSIGNED NOT NULL,
  `created_at`    DATETIME NOT NULL,
  PRIMARY KEY (`work_order_id`, `admin_id`),
  KEY `idx_wot_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::T_EVENTS => "CREATE TABLE IF NOT EXISTS `crm_work_order_events` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_order_id` BIGINT UNSIGNED NOT NULL,
  `admin_id`      INT UNSIGNED NULL,
  `type`          VARCHAR(30) NOT NULL,
  `detail`        VARCHAR(500) NOT NULL DEFAULT '',
  `created_at`    DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_woe_wo` (`work_order_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
        foreach ($sql as $table => $q) {
            if (!$this->db->table_exists($table) && !$this->db->query($q)) {
                log_message('error', '[work_orders] schema failed for '.$table.': '.$this->db->error()['message']);
                return $this->ready = FALSE;
            }
        }
        return $this->ready = TRUE;
    }

    // ---- read ----------------------------------------------------------------

    protected function _base_select()
    {
        $this->db->select('w.*, u.name AS customer_name, u.mobile AS customer_mobile, u.email AS customer_email,
                           v.variant AS vehicle_variant, v.vehicle_number, m.name AS vehicle_make,
                           a.name AS advisor_name, b.reference AS booking_reference, b.status AS booking_status', FALSE)
            ->from(self::T.' w')
            ->join('users u', 'u.id = w.user_id', 'left')
            ->join('vehicles v', 'v.id = w.vehicle_id', 'left')
            ->join('vehicle_makes m', 'm.id = v.make_id', 'left')
            ->join('admin_users a', 'a.id = w.advisor_id', 'left')
            ->join('bookings b', 'b.id = w.booking_id', 'left')
            ->where('w.deleted_at IS NULL', NULL, FALSE);
    }

    public function find($id)
    {
        $this->ensure_schema();
        $this->_base_select();
        return $this->db->where('w.id', (int) $id)->get()->row_array();
    }

    public function find_open_for_booking($booking_id)
    {
        $this->ensure_schema();
        return $this->db->select('id, ro_number, status')->where('booking_id', (int) $booking_id)
            ->where('deleted_at IS NULL', NULL, FALSE)->order_by('id', 'DESC')->limit(1)
            ->get(self::T)->row_array();
    }

    /** Open job card for this car (anything not delivered / returned). */
    public function find_open_for_vehicle($vehicle_id)
    {
        $this->ensure_schema();
        return $this->db->select('id, ro_number, status')->where('vehicle_id', (int) $vehicle_id)
            ->where_not_in('status', array('delivered', 'returned'))
            ->where('deleted_at IS NULL', NULL, FALSE)->order_by('id', 'DESC')->limit(1)
            ->get(self::T)->row_array();
    }

    /**
     * @param string $tab status key or 'open' (not delivered/returned) or 'all'
     * @return array rows, total
     */
    public function page($tab, $workshop, $q, $limit, $offset)
    {
        $this->ensure_schema();
        $apply = function () use ($tab, $workshop, $q) {
            $this->_base_select();
            if ($tab === 'open') {
                $this->db->where_not_in('w.status', array('delivered', 'returned'));
            } elseif (isset(self::STATUSES[$tab])) {
                $this->db->where('w.status', $tab);
            }
            if ($workshop !== '') {
                $this->db->where('w.workshop', $workshop);
            }
            $q = trim((string) $q);
            if ($q !== '') {
                $digits = preg_replace('/\D/', '', $q);
                $plate = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $q));
                $this->db->group_start()
                    ->like('w.ro_number', $q)
                    ->or_like('u.name', $q)
                    ->or_like("REPLACE(v.vehicle_number, ' ', '')", $plate, 'both', FALSE);
                if (strlen($digits) >= 4) {
                    $this->db->or_like('u.mobile', $digits);
                }
                $this->db->group_end();
            }
        };
        $apply();
        $total = (int) $this->db->count_all_results();
        $apply();
        $this->db->order_by('w.id', 'DESC');
        $rows = $this->db->limit((int) $limit, (int) $offset)->get()->result_array();
        return array('rows' => $rows, 'total' => $total);
    }

    /** status => count (+ open, all), for one workshop or all. */
    public function counts($workshop = '')
    {
        $this->ensure_schema();
        $out = array_fill_keys(array_keys(self::STATUSES), 0) + array('open' => 0, 'all' => 0);
        $this->db->select('status, COUNT(*) n', FALSE)->where('deleted_at IS NULL', NULL, FALSE);
        if ($workshop !== '') {
            $this->db->where('workshop', $workshop);
        }
        foreach ($this->db->group_by('status')->get(self::T)->result_array() as $r) {
            $out[$r['status']] = (int) $r['n'];
            $out['all'] += (int) $r['n'];
            if (!in_array($r['status'], array('delivered', 'returned'), TRUE)) {
                $out['open'] += (int) $r['n'];
            }
        }
        return $out;
    }

    public function lines($work_order_id)
    {
        $this->ensure_schema();
        return $this->db->select('l.*, t.name AS technician_name', FALSE)
            ->from(self::T_LINES.' l')->join('admin_users t', 't.id = l.technician_id', 'left')
            ->where('l.work_order_id', (int) $work_order_id)
            ->order_by('l.type', 'DESC')->order_by('l.id', 'ASC')->get()->result_array();
    }

    public function find_line($work_order_id, $line_id)
    {
        $this->ensure_schema();
        return $this->db->where('id', (int) $line_id)->where('work_order_id', (int) $work_order_id)
            ->get(self::T_LINES)->row_array();
    }

    /** @return array of admin rows (id, name) */
    public function technicians($work_order_id)
    {
        $this->ensure_schema();
        return $this->db->select('a.id, a.name')->from(self::T_TECH.' t')->join('admin_users a', 'a.id = t.admin_id')
            ->where('t.work_order_id', (int) $work_order_id)->order_by('a.name', 'ASC')->get()->result_array();
    }

    public function events($work_order_id, $limit = 100)
    {
        $this->ensure_schema();
        return $this->db->select('e.*, a.name AS admin_name', FALSE)->from(self::T_EVENTS.' e')
            ->join('admin_users a', 'a.id = e.admin_id', 'left')
            ->where('e.work_order_id', (int) $work_order_id)->order_by('e.id', 'DESC')->limit((int) $limit)
            ->get()->result_array();
    }

    /** Past job cards of the same car (newest first), for the car's history panel. */
    public function history_for_vehicle($vehicle_id, $except_id = 0, $limit = 10)
    {
        $this->ensure_schema();
        return $this->db->select('id, ro_number, status, grand_total, created_at, delivered_at, odometer_km')
            ->where('vehicle_id', (int) $vehicle_id)->where('id !=', (int) $except_id)
            ->where('deleted_at IS NULL', NULL, FALSE)->order_by('id', 'DESC')->limit((int) $limit)
            ->get(self::T)->result_array();
    }

    // ---- write ---------------------------------------------------------------

    /** Next RO number: max used + 1, never below FIRST_RO. */
    public function next_ro_number()
    {
        $this->ensure_schema();
        $row = $this->db->query("SELECT MAX(CAST(SUBSTRING(ro_number, 3) AS UNSIGNED)) AS n FROM crm_work_orders WHERE ro_number LIKE 'RO%'")->row_array();
        $n = max(self::FIRST_RO, (int) ($row['n'] ?? 0) + 1);
        return 'RO'.$n;
    }

    public function create(array $d, $admin_id)
    {
        $this->ensure_schema();
        $now = date('Y-m-d H:i:s');
        $d += array('status' => 'draft');
        $d['created_by'] = (int) $admin_id ?: NULL;
        $d['created_at'] = $now;
        $d['status_changed_at'] = $now;
        for ($try = 0; $try < 5; $try++) {
            $d['ro_number'] = $this->next_ro_number();
            if ($this->db->insert(self::T, $d)) {
                $id = (int) $this->db->insert_id();
                $this->log($id, $admin_id, 'created', 'Job card '.$d['ro_number'].' opened');
                return $id;
            }
            // Duplicate RO from a parallel save: try the next number.
            if ((int) $this->db->error()['code'] !== 1062) {
                break;
            }
        }
        log_message('error', '[work_orders] create failed: '.$this->db->error()['message']);
        return 0;
    }

    public function update($id, array $d)
    {
        $this->ensure_schema();
        $d['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $id)->update(self::T, $d);
    }

    public function set_technicians($id, array $admin_ids)
    {
        $this->ensure_schema();
        $admin_ids = array_values(array_unique(array_filter(array_map('intval', $admin_ids))));
        $this->db->where('work_order_id', (int) $id)->delete(self::T_TECH);
        $now = date('Y-m-d H:i:s');
        foreach ($admin_ids as $aid) {
            $this->db->insert(self::T_TECH, array('work_order_id' => (int) $id, 'admin_id' => $aid, 'created_at' => $now));
        }
        return $admin_ids;
    }

    /** Fills taxable / tax_amount / amount from qty, rate, discount, tax. */
    public static function compute_line(array $l)
    {
        $qty = max(0, (float) ($l['qty'] ?? 1));
        $rate = max(0, (float) ($l['rate'] ?? 0));
        $disc = min(100, max(0, (float) ($l['discount_pct'] ?? 0)));
        $tax = max(0, (float) ($l['tax_pct'] ?? 0));
        $base = $qty * $rate * (1 - $disc / 100);
        if (!empty($l['tax_inclusive'])) {
            $taxable = $tax > 0 ? $base / (1 + $tax / 100) : $base;
            $amount = $base;
        } else {
            $taxable = $base;
            $amount = $base * (1 + $tax / 100);
        }
        $l['taxable'] = round($taxable, 2);
        $l['amount'] = round($amount, 2);
        $l['tax_amount'] = round($amount - $taxable, 2);
        return $l;
    }

    public function add_line($work_order_id, array $l, $admin_id)
    {
        $this->ensure_schema();
        $l = self::compute_line($l);
        $l['work_order_id'] = (int) $work_order_id;
        $l['created_by'] = (int) $admin_id ?: NULL;
        $l['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::T_LINES, $l);
        $id = (int) $this->db->insert_id();
        $this->recalc($work_order_id);
        return $id;
    }

    public function update_line($work_order_id, $line_id, array $l)
    {
        $this->ensure_schema();
        $cur = $this->find_line($work_order_id, $line_id);
        if (!$cur) {
            return FALSE;
        }
        $l = self::compute_line(array_merge($cur, $l));
        unset($l['id'], $l['work_order_id'], $l['created_at'], $l['created_by'], $l['technician_name']);
        $this->db->where('id', (int) $line_id)->update(self::T_LINES, $l);
        $this->recalc($work_order_id);
        return TRUE;
    }

    public function delete_line($work_order_id, $line_id)
    {
        $this->ensure_schema();
        $this->db->where('id', (int) $line_id)->where('work_order_id', (int) $work_order_id)->delete(self::T_LINES);
        $this->recalc($work_order_id);
        return $this->db->affected_rows() >= 0;
    }

    public function set_line_done($work_order_id, $line_id, $done, $admin_id)
    {
        $this->ensure_schema();
        return $this->db->where('id', (int) $line_id)->where('work_order_id', (int) $work_order_id)
            ->update(self::T_LINES, $done
                ? array('done_at' => date('Y-m-d H:i:s'), 'done_by' => (int) $admin_id ?: NULL)
                : array('done_at' => NULL, 'done_by' => NULL));
    }

    public function recalc($work_order_id)
    {
        $r = $this->db->select('COALESCE(SUM(taxable),0) sub, COALESCE(SUM(tax_amount),0) tax, COALESCE(SUM(amount),0) total', FALSE)
            ->where('work_order_id', (int) $work_order_id)->get(self::T_LINES)->row_array();
        $this->db->where('id', (int) $work_order_id)->update(self::T, array(
            'subtotal'    => round((float) $r['sub'], 2),
            'tax_total'   => round((float) $r['tax'], 2),
            'grand_total' => round((float) $r['total'], 2),
            'updated_at'  => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Move to a new status if the move is allowed. Stamps started / ready / delivered times.
     * @return bool
     */
    public function set_status($id, $to, $admin_id, $note = '')
    {
        $wo = $this->find($id);
        if (!$wo || !in_array($to, self::MOVES[$wo['status']] ?? array(), TRUE)) {
            return FALSE;
        }
        $now = date('Y-m-d H:i:s');
        $d = array('status' => $to, 'status_changed_at' => $now);
        if ($to === 'in_progress' && empty($wo['started_at'])) {
            $d['started_at'] = $now;
        }
        if ($to === 'ready') {
            $d['ready_at'] = $now;
        }
        if ($to === 'delivered') {
            $d['delivered_at'] = $now;
        }
        $this->update($id, $d);
        $reopen = in_array($wo['status'], array('delivered', 'returned'), TRUE);
        $this->log($id, $admin_id, 'status', ($reopen ? 'Reopened: ' : '').self::STATUSES[$wo['status']].' → '.self::STATUSES[$to].($note !== '' ? ' ('.$note.')' : ''));
        return TRUE;
    }

    public function soft_delete($id)
    {
        return $this->update($id, array('deleted_at' => date('Y-m-d H:i:s')));
    }

    public function log($work_order_id, $admin_id, $type, $detail)
    {
        $this->ensure_schema();
        $this->db->insert(self::T_EVENTS, array(
            'work_order_id' => (int) $work_order_id,
            'admin_id'      => (int) $admin_id ?: NULL,
            'type'          => mb_substr((string) $type, 0, 30),
            'detail'        => mb_substr((string) $detail, 0, 500),
            'created_at'    => date('Y-m-d H:i:s'),
        ));
    }
}
