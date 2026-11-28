<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Workshop technicians (mechanics). They are not CRM admins: they sign in on their
 * phone at /tech with mobile + 4-digit PIN and stay signed in for 30 days on that phone.
 */
class Crm_technician_model extends CI_Model
{
    const T = 'crm_technicians';
    const T_SESS = 'crm_tech_sessions';

    const LANGS = array('en' => 'English', 'hi' => 'हिंदी', 'mr' => 'मराठी');

    /** Session length; renewed on use, so a phone in daily use stays signed in. */
    const SESSION_DAYS = 30;
    const MAX_FAILS = 5;
    const LOCK_MINUTES = 15;

    protected $ready = NULL;

    public function ensure_schema()
    {
        if ($this->ready !== NULL) {
            return $this->ready;
        }
        $sql = array(
            self::T => "CREATE TABLE IF NOT EXISTS `crm_technicians` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120) NOT NULL,
  `mobile`        VARCHAR(15) NOT NULL,
  `workshop`      VARCHAR(30) NOT NULL DEFAULT '',
  `lang`          ENUM('en','hi','mr') NOT NULL DEFAULT 'hi',
  `pin_hash`      VARCHAR(255) NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `failed_count`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until`  DATETIME NULL,
  `last_seen_at`  DATETIME NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tech_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::T_SESS => "CREATE TABLE IF NOT EXISTS `crm_tech_sessions` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `technician_id` INT UNSIGNED NOT NULL,
  `token_hash`    CHAR(64) NOT NULL,
  `user_agent`    VARCHAR(255) NOT NULL DEFAULT '',
  `created_at`    DATETIME NOT NULL,
  `last_seen_at`  DATETIME NOT NULL,
  `expires_at`    DATETIME NOT NULL,
  `revoked_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tech_token` (`token_hash`),
  KEY `idx_tech_sess` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
        foreach ($sql as $table => $q) {
            if (!$this->db->table_exists($table) && !$this->db->query($q)) {
                log_message('error', '[technicians] schema failed for '.$table.': '.$this->db->error()['message']);
                return $this->ready = FALSE;
            }
        }
        return $this->ready = TRUE;
    }

    public static function normalize_mobile($m)
    {
        $d = preg_replace('/\D/', '', (string) $m);
        if (strlen($d) === 12 && strpos($d, '91') === 0) {
            $d = substr($d, 2);
        }
        return $d;
    }

    // ---- read ----------------------------------------------------------------

    /** @param bool $active_only */
    public function all($active_only = FALSE, $workshop = '')
    {
        $this->ensure_schema();
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        if ($workshop !== '') {
            $this->db->group_start()->where('workshop', $workshop)->or_where('workshop', '')->group_end();
        }
        return $this->db->order_by('is_active', 'DESC')->order_by('name', 'ASC')->get(self::T)->result_array();
    }

    public function find($id)
    {
        $this->ensure_schema();
        return $this->db->where('id', (int) $id)->get(self::T)->row_array();
    }

    public function find_by_mobile($mobile)
    {
        $this->ensure_schema();
        return $this->db->where('mobile', self::normalize_mobile($mobile))->get(self::T)->row_array();
    }

    /** id => number of phones signed in now. */
    public function active_session_counts()
    {
        $this->ensure_schema();
        $out = array();
        $rows = $this->db->select('technician_id, COUNT(*) n', FALSE)->where('revoked_at IS NULL', NULL, FALSE)
            ->where('expires_at >', date('Y-m-d H:i:s'))->group_by('technician_id')->get(self::T_SESS)->result_array();
        foreach ($rows as $r) {
            $out[(int) $r['technician_id']] = (int) $r['n'];
        }
        return $out;
    }

    // ---- write ---------------------------------------------------------------

    /** @return int id, or 0 (mobile already used) */
    public function create(array $d, $admin_id)
    {
        $this->ensure_schema();
        $d['mobile'] = self::normalize_mobile($d['mobile']);
        if ($this->find_by_mobile($d['mobile'])) {
            return 0;
        }
        $d['created_by'] = (int) $admin_id ?: NULL;
        $d['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert(self::T, $d) ? (int) $this->db->insert_id() : 0;
    }

    public function update($id, array $d)
    {
        $this->ensure_schema();
        if (isset($d['mobile'])) {
            $d['mobile'] = self::normalize_mobile($d['mobile']);
            $other = $this->find_by_mobile($d['mobile']);
            if ($other && (int) $other['id'] !== (int) $id) {
                return FALSE;
            }
        }
        $d['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $id)->update(self::T, $d);
    }

    public function set_pin($id, $pin)
    {
        return $this->update($id, array(
            'pin_hash' => password_hash((string) $pin, PASSWORD_DEFAULT), 'failed_count' => 0, 'locked_until' => NULL,
        ));
    }

    /** Signs out every phone of this technician. */
    public function revoke_sessions($id)
    {
        $this->ensure_schema();
        $this->db->where('technician_id', (int) $id)->where('revoked_at IS NULL', NULL, FALSE)
            ->update(self::T_SESS, array('revoked_at' => date('Y-m-d H:i:s')));
        return $this->db->affected_rows();
    }

    // ---- sign in -------------------------------------------------------------

    /**
     * @return array [technician row or NULL, error code: '' | bad | locked | inactive | no_pin]
     */
    public function check_login($mobile, $pin)
    {
        $t = $this->find_by_mobile($mobile);
        if (!$t) {
            password_verify((string) $pin, '$2y$10$M7rC1Z9Rt/D4fRhqPbetg.dKYX4DEe07L/yGVMXLDB3p7aQ5vPS5C'); // same time either way
            return array(NULL, 'bad');
        }
        if (!(int) $t['is_active']) {
            return array(NULL, 'inactive');
        }
        if (!empty($t['locked_until']) && strtotime($t['locked_until']) > time()) {
            return array(NULL, 'locked');
        }
        if (empty($t['pin_hash'])) {
            return array(NULL, 'no_pin');
        }
        if (!password_verify((string) $pin, $t['pin_hash'])) {
            $fails = (int) $t['failed_count'] + 1;
            $d = array('failed_count' => $fails);
            if ($fails >= self::MAX_FAILS) {
                $d = array('failed_count' => 0, 'locked_until' => date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60));
            }
            $this->db->where('id', (int) $t['id'])->update(self::T, $d);
            return array(NULL, $fails >= self::MAX_FAILS ? 'locked' : 'bad');
        }
        $this->db->where('id', (int) $t['id'])->update(self::T, array('failed_count' => 0, 'locked_until' => NULL));
        return array($t, '');
    }

    /** @return string raw token for the cookie (only its hash is stored). */
    public function start_session($technician_id, $user_agent)
    {
        $this->ensure_schema();
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $this->db->insert(self::T_SESS, array(
            'technician_id' => (int) $technician_id,
            'token_hash'    => hash('sha256', $token),
            'user_agent'    => mb_substr((string) $user_agent, 0, 255),
            'created_at'    => $now,
            'last_seen_at'  => $now,
            'expires_at'    => date('Y-m-d H:i:s', time() + self::SESSION_DAYS * 86400),
        ));
        return $token;
    }

    /** Technician for a cookie token, renewing the 30 days; NULL if signed out / expired / inactive. */
    public function from_token($token)
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token) || !$this->ensure_schema()) {
            return NULL;
        }
        $s = $this->db->where('token_hash', hash('sha256', $token))->where('revoked_at IS NULL', NULL, FALSE)
            ->where('expires_at >', date('Y-m-d H:i:s'))->get(self::T_SESS)->row_array();
        if (!$s) {
            return NULL;
        }
        $t = $this->find($s['technician_id']);
        if (!$t || !(int) $t['is_active']) {
            return NULL;
        }
        // Renew at most every 10 minutes to keep writes low.
        if (strtotime($s['last_seen_at']) < time() - 600) {
            $now = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $s['id'])->update(self::T_SESS, array(
                'last_seen_at' => $now, 'expires_at' => date('Y-m-d H:i:s', time() + self::SESSION_DAYS * 86400),
            ));
            $this->db->where('id', (int) $t['id'])->update(self::T, array('last_seen_at' => $now));
        }
        $t['session_id'] = (int) $s['id'];
        return $t;
    }

    public function end_session($token)
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return;
        }
        $this->ensure_schema();
        $this->db->where('token_hash', hash('sha256', $token))->update(self::T_SESS, array('revoked_at' => date('Y-m-d H:i:s')));
    }
}
