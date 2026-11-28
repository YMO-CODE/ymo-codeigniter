<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Services (labour) and spare parts price list, used by estimates and job cards.
 * First filled from the OkMechanic export (cli/crm import_catalog).
 *
 * rate NULL = "price set on the job" (OkMechanic used ₹101 as a placeholder for these).
 */
class Crm_catalog_model extends CI_Model
{
    const TABLE = 'crm_catalog_items';

    const SCHEMA_SQL = "
CREATE TABLE IF NOT EXISTS `crm_catalog_items` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`          ENUM('service','part') NOT NULL,
  `name`          VARCHAR(255) NOT NULL,
  `description`   TEXT NULL,
  `code`          VARCHAR(64) NOT NULL DEFAULT '',
  `category`      VARCHAR(80) NOT NULL DEFAULT '',
  `unit`          VARCHAR(16) NOT NULL DEFAULT 'UNT',
  `rate`          DECIMAL(10,2) NULL,
  `purchase_rate` DECIMAL(10,2) NULL,
  `tax_pct`       DECIMAL(5,2) NULL,
  `tax_inclusive` TINYINT(1) NOT NULL DEFAULT 1,
  `stock`         DECIMAL(10,2) NOT NULL DEFAULT 0,
  `low_stock`     DECIMAL(10,2) NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `okm_id`        INT UNSIGNED NULL,
  `okm_stock`     DECIMAL(10,2) NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_catalog_okm` (`okm_id`),
  KEY `idx_catalog_type_name` (`type`, `is_active`, `name`(80)),
  KEY `idx_catalog_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    /** Rates OkMechanic users typed as "no fixed price" (₹101, or a tax % typed into the rate box). */
    const PLACEHOLDER_RATES = array(0.0, 18.0, 101.0, 181.0);

    protected $ready = NULL;

    public function ensure_schema()
    {
        if ($this->ready !== NULL) {
            return $this->ready;
        }
        if ($this->db->table_exists(self::TABLE)) {
            return $this->ready = TRUE;
        }
        if (!$this->db->query(trim(self::SCHEMA_SQL))) {
            log_message('error', '[catalog] schema failed: '.$this->db->error()['message']);
            return $this->ready = FALSE;
        }
        return $this->ready = TRUE;
    }

    /**
     * One page of a list. $f: '' all active | 'no_price' active without a rate | 'hidden'.
     * @return array rows, total
     */
    public function page($type, $q, $f, $limit, $offset)
    {
        $this->ensure_schema();
        $apply = function () use ($type, $q, $f) {
            $this->db->from(self::TABLE)->where('type', $type === 'part' ? 'part' : 'service');
            if ($f === 'hidden') {
                $this->db->where('is_active', 0);
            } else {
                $this->db->where('is_active', 1);
                if ($f === 'no_price') {
                    $this->db->where('rate IS NULL', NULL, FALSE);
                }
            }
            $q = trim((string) $q);
            if ($q !== '') {
                $this->db->group_start()
                    ->like('name', $q)
                    ->or_like('description', $q)
                    ->or_like('code', $q)
                    ->or_like('category', $q)
                    ->group_end();
            }
        };
        $apply();
        $total = (int) $this->db->count_all_results();
        $apply();
        $rows = $this->db->order_by('name', 'ASC')->order_by('id', 'ASC')
            ->limit((int) $limit, (int) $offset)->get()->result_array();
        return array('rows' => $rows, 'total' => $total);
    }

    /** Counts for the tabs: service / part / no_price per type / hidden per type. */
    public function counts()
    {
        $this->ensure_schema();
        $out = array('service' => 0, 'part' => 0, 'service_no_price' => 0, 'part_no_price' => 0, 'service_hidden' => 0, 'part_hidden' => 0);
        $rows = $this->db->select('type, is_active, (rate IS NULL) AS no_price, COUNT(*) n', FALSE)
            ->group_by(array('type', 'is_active', 'no_price'))->get(self::TABLE)->result_array();
        foreach ($rows as $r) {
            $t = $r['type'];
            if ((int) $r['is_active'] === 1) {
                $out[$t] += (int) $r['n'];
                if ((int) $r['no_price'] === 1) {
                    $out[$t.'_no_price'] += (int) $r['n'];
                }
            } else {
                $out[$t.'_hidden'] += (int) $r['n'];
            }
        }
        return $out;
    }

    public function find($id)
    {
        $this->ensure_schema();
        return $this->db->where('id', (int) $id)->get(self::TABLE)->row_array();
    }

    public function create(array $d)
    {
        $this->ensure_schema();
        $d['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, $d);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $d)
    {
        $this->ensure_schema();
        $d['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $id)->update(self::TABLE, $d);
    }

    /**
     * Insert or refresh one row of the OkMechanic export (matched on okm_id).
     * An item already edited in the CRM (updated_at set) keeps its CRM values.
     * @return string created | updated | kept | skipped
     */
    public function import_okm_row(array $r)
    {
        $this->ensure_schema();
        $okm_id = (int) ($r['okm_id'] ?? 0);
        $type = ($r['type'] ?? '') === 'part' ? 'part' : (($r['type'] ?? '') === 'service' ? 'service' : '');
        $raw_name = $this->_clean((string) ($r['name'] ?? ''));
        if ($okm_id <= 0 || $type === '' || $raw_name === '') {
            return 'skipped';
        }
        list($name, $description) = $this->_split_name($raw_name);

        $rate = $this->_money($r['rate'] ?? '');
        if ($rate !== NULL && in_array((float) $rate, self::PLACEHOLDER_RATES, TRUE)) {
            $rate = NULL;
        }
        $purchase = $type === 'part' ? $this->_money($r['purchase_rate'] ?? '') : NULL;
        if ($purchase !== NULL && in_array((float) $purchase, self::PLACEHOLDER_RATES, TRUE)) {
            $purchase = NULL;
        }
        $tax = trim((string) ($r['tax_pct'] ?? ''));
        $tax = $tax === '' ? NULL : (float) ($tax === '181' ? '18' : $tax);

        $category = $this->_clean((string) ($r['category'] ?? ''));
        if (strcasecmp($category, 'Service 1.0') === 0 || strcasecmp($category, 'null') === 0) {
            $category = ''; // OkMechanic's default bucket, carries no meaning
        }
        $unit = strtoupper($this->_clean((string) ($r['unit'] ?? ''))) ?: 'UNT';

        $code = $type === 'part' ? $this->_clean((string) ($r['code'] ?? '')) : '';
        if (strlen($code) < 6) {
            $code = ''; // filler such as 101 or 1001; real barcodes are 12-13 digits
        }

        $row = array(
            'type'          => $type,
            'name'          => mb_substr($name, 0, 255),
            'description'   => $description !== '' ? $description : NULL,
            // Service codes in OkMechanic are filler (101, 100001); part codes are real barcodes.
            'code'          => mb_substr($code, 0, 64),
            'category'      => mb_substr($category, 0, 80),
            'unit'          => mb_substr($unit, 0, 16),
            'rate'          => $rate,
            'purchase_rate' => $purchase,
            'tax_pct'       => $tax,
            'tax_inclusive' => (string) ($r['tax_inclusive'] ?? '1') === '0' ? 0 : 1,
            'okm_stock'     => ($r['okm_stock'] ?? '') === '' ? NULL : (float) $r['okm_stock'],
            'low_stock'     => max(0, (float) ($r['low_stock'] ?? 0)),
        );

        $existing = $this->db->where('okm_id', $okm_id)->get(self::TABLE)->row_array();
        if ($existing) {
            if (!empty($existing['updated_at'])) {
                return 'kept';
            }
            $this->db->where('id', (int) $existing['id'])->update(self::TABLE, $row);
            return 'updated';
        }
        // Stock starts at 0: OkMechanic's counts were not maintained (mostly negative); kept in okm_stock for reference.
        $row += array('okm_id' => $okm_id, 'stock' => 0, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'));
        $this->db->insert(self::TABLE, $row);
        return 'created';
    }

    protected function _clean($s)
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    protected function _money($v)
    {
        $v = trim(str_replace(',', '', (string) $v));
        return ($v === '' || !is_numeric($v)) ? NULL : round((float) $v, 2);
    }

    /**
     * Long package names carry their checklist ("General Servicing Package 1) Engine oil … 2) …"):
     * short name for lists, the rest as the description.
     */
    protected function _split_name($name)
    {
        if (mb_strlen($name) <= 90) {
            return array($name, '');
        }
        $cut = NULL;
        foreach (array('/\s\(/u', '/\s*1\)\s*/u', '/:\s/u') as $re) {
            if (preg_match($re, $name, $m, PREG_OFFSET_CAPTURE) && $m[0][1] >= 8) {
                $pos = $m[0][1];
                $cut = ($cut === NULL) ? $pos : min($cut, $pos);
            }
        }
        if ($cut === NULL) {
            return array(rtrim(mb_strcut($name, 0, 90)).'…', $name);
        }
        $short = rtrim(substr($name, 0, $cut), " :-(");
        $rest = trim(substr($name, $cut), " :");
        $rest = preg_replace('/^\((.*)\)?$/us', '$1', $rest);
        return array($short, trim($rest, " ()"));
    }
}
