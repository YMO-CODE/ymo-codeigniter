<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google reviews (Business Profile) seen by the daily check, what the CRM did
 * with each one, and every "please review us" message the team sent.
 *
 * Tables are created on first use (same approach as YMOChat), so no manual
 * migration is needed on the server.
 */
class Gbp_review_model extends CI_Model
{
    const SCHEMA_SQL = "
CREATE TABLE IF NOT EXISTS gbp_reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  review_id VARCHAR(190) NOT NULL,
  workshop VARCHAR(20) NOT NULL DEFAULT '',
  location_name VARCHAR(190) NOT NULL DEFAULT '',
  author VARCHAR(190) NOT NULL DEFAULT '',
  stars TINYINT UNSIGNED NOT NULL DEFAULT 0,
  comment TEXT NULL,
  review_time DATETIME NULL,
  reply_text TEXT NULL,
  reply_time DATETIME NULL,
  action VARCHAR(20) NOT NULL DEFAULT 'new',
  reason TEXT NULL,
  error VARCHAR(255) NULL,
  alerted_at DATETIME NULL,
  handled_at DATETIME NULL,
  handled_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review (review_id),
  KEY idx_action (action),
  KEY idx_time (review_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS review_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NULL,
  mobile VARCHAR(20) NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT '',
  workshop VARCHAR(20) NOT NULL DEFAULT '',
  channel VARCHAR(20) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'sent',
  error VARCHAR(255) NULL,
  source VARCHAR(30) NOT NULL DEFAULT '',
  admin_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_mobile (mobile),
  KEY idx_booking (booking_id),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

    protected $ready = NULL;

    public function ensure_schema()
    {
        if ($this->ready !== NULL) {
            return $this->ready;
        }
        if ($this->db->table_exists('gbp_reviews') && $this->db->table_exists('review_requests')) {
            return $this->ready = TRUE;
        }
        foreach (array_filter(array_map('trim', explode(';', self::SCHEMA_SQL))) as $sql) {
            if (!$this->db->query($sql)) {
                log_message('error', '[gbp_reviews] schema failed: '.$this->db->error()['message']);
                return $this->ready = FALSE;
            }
        }
        return $this->ready = TRUE;
    }

    // --- settings (settings table, JSON values) ---------------------------------

    public function setting($key, $default = NULL)
    {
        $row = $this->db->select('setting_value')->get_where('settings', array('setting_key' => $key), 1)->row_array();
        if (!$row || $row['setting_value'] === NULL || $row['setting_value'] === '') {
            return $default;
        }
        $v = json_decode((string) $row['setting_value'], TRUE);
        return $v === NULL ? $row['setting_value'] : $v;
    }

    public function save_setting($key, $value)
    {
        $val = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->db->query('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', array($key, $val));
        if (function_exists('ymo_clear_settings_cache')) {
            ymo_clear_settings_cache();
        }
    }

    public function delete_setting($key)
    {
        $this->db->where('setting_key', $key)->delete('settings');
        if (function_exists('ymo_clear_settings_cache')) {
            ymo_clear_settings_cache();
        }
    }

    // --- reviews ------------------------------------------------------------------

    public function find($id)
    {
        $this->ensure_schema();
        return $this->db->get_where('gbp_reviews', array('id' => (int) $id), 1)->row_array();
    }

    public function find_by_review_id($review_id)
    {
        return $this->db->get_where('gbp_reviews', array('review_id' => (string) $review_id), 1)->row_array();
    }

    /** Insert or refresh a review from Google; returns the stored row. */
    public function upsert(array $r)
    {
        $this->ensure_schema();
        $now = date('Y-m-d H:i:s');
        $row = $this->find_by_review_id($r['review_id']);
        $data = array(
            'workshop'      => $r['workshop'],
            'location_name' => $r['location_name'],
            'author'        => mb_substr((string) $r['author'], 0, 190),
            'stars'         => (int) $r['stars'],
            'comment'       => $r['comment'],
            'review_time'   => $r['review_time'],
            'updated_at'    => $now,
        );
        if (!empty($r['reply_text'])) {
            $data['reply_text'] = $r['reply_text'];
            $data['reply_time'] = $r['reply_time'];
        }
        if ($row) {
            // A changed rating means the customer edited the review: handle it again.
            if ((int) $row['stars'] !== (int) $r['stars'] && empty($r['reply_text'])) {
                $data['action'] = 'new';
                $data['alerted_at'] = NULL;
            }
            $this->db->where('id', (int) $row['id'])->update('gbp_reviews', $data);
        } else {
            $data['review_id'] = (string) $r['review_id'];
            $data['action'] = !empty($r['reply_text']) ? 'had_reply' : 'new';
            $data['created_at'] = $now;
            $this->db->insert('gbp_reviews', $data);
        }
        return $this->find_by_review_id($r['review_id']);
    }

    public function mark($id, array $patch)
    {
        $patch['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update('gbp_reviews', $patch);
    }

    /** Recent reviews for the CRM page. */
    public function recent($limit = 60, $filter = '')
    {
        $this->ensure_schema();
        $this->db->from('gbp_reviews');
        if ($filter === 'attention') {
            $this->db->where_in('action', array('alerted', 'failed'))->where('handled_at IS NULL', NULL, FALSE);
        }
        return $this->db->order_by('review_time', 'DESC')->limit((int) $limit)->get()->result_array();
    }

    /**
     * One page of reviews. Filters: attention (bool), workshop (slug), stars ('5', '4', 'low' = 1–3).
     * @return array{rows:array, total:int}
     */
    public function page(array $f, $limit, $offset)
    {
        $this->ensure_schema();
        $apply = function () use ($f) {
            $this->db->from('gbp_reviews');
            if (!empty($f['attention'])) {
                $this->db->where_in('action', array('alerted', 'failed'))->where('handled_at IS NULL', NULL, FALSE);
            }
            if (!empty($f['workshop'])) {
                $this->db->where('workshop', (string) $f['workshop']);
            }
            if (isset($f['stars']) && $f['stars'] === 'low') {
                $this->db->where('stars <=', 3);
            } elseif (isset($f['stars']) && in_array($f['stars'], array('4', '5'), TRUE)) {
                $this->db->where('stars', (int) $f['stars']);
            }
        };
        $apply();
        $total = (int) $this->db->count_all_results();
        $apply();
        $rows = $this->db->order_by('review_time', 'DESC')->order_by('id', 'DESC')
            ->limit((int) $limit, (int) $offset)->get()->result_array();
        return array('rows' => $rows, 'total' => $total);
    }

    /** 0-based position of a review in the attention list (same order as page()), or NULL. */
    public function attention_position($id)
    {
        $r = $this->find($id);
        if (!$r || !in_array($r['action'], array('alerted', 'failed'), TRUE) || $r['handled_at'] !== NULL) {
            return NULL;
        }
        return (int) $this->db->where_in('action', array('alerted', 'failed'))->where('handled_at IS NULL', NULL, FALSE)
            ->group_start()
                ->where('review_time >', $r['review_time'])
                ->or_group_start()->where('review_time', $r['review_time'])->where('id >', (int) $r['id'])->group_end()
            ->group_end()
            ->count_all_results('gbp_reviews');
    }

    public function counts()
    {
        $this->ensure_schema();
        $out = array('attention' => 0, 'auto_30d' => 0, 'new_30d' => 0, 'avg_30d' => NULL);
        $out['attention'] = (int) $this->db->where_in('action', array('alerted', 'failed'))
            ->where('handled_at IS NULL', NULL, FALSE)->count_all_results('gbp_reviews');
        $since = date('Y-m-d H:i:s', strtotime('-30 days'));
        $row = $this->db->select('COUNT(*) n, AVG(stars) a, SUM(action = "auto_replied") auto', FALSE)
            ->where('review_time >=', $since)->get('gbp_reviews')->row_array();
        $out['new_30d'] = (int) $row['n'];
        $out['auto_30d'] = (int) $row['auto'];
        $out['avg_30d'] = $row['n'] ? round((float) $row['a'], 1) : NULL;
        return $out;
    }

    // --- review requests ----------------------------------------------------------

    public function log_request(array $d)
    {
        $this->ensure_schema();
        $d['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('review_requests', $d);
        return (int) $this->db->insert_id();
    }

    /** Last successful request to this number (to avoid asking twice). */
    public function last_request_for($mobile)
    {
        $this->ensure_schema();
        $m = preg_replace('/\D/', '', (string) $mobile);
        $m = substr($m, -10);
        if ($m === '') {
            return NULL;
        }
        return $this->db->like('mobile', $m, 'before')->where('status', 'sent')
            ->order_by('id', 'DESC')->limit(1)->get('review_requests')->row_array();
    }

    public function requested_for_booking($booking_id)
    {
        $this->ensure_schema();
        return $this->db->where('booking_id', (int) $booking_id)->where('status', 'sent')
            ->order_by('id', 'DESC')->limit(1)->get('review_requests')->row_array();
    }

    public function recent_requests($limit = 30)
    {
        $this->ensure_schema();
        return $this->db->order_by('id', 'DESC')->limit((int) $limit)->get('review_requests')->result_array();
    }

    /** @return array{rows:array, total:int} */
    public function page_requests($limit, $offset)
    {
        $this->ensure_schema();
        $total = (int) $this->db->count_all('review_requests');
        $rows = $this->db->order_by('id', 'DESC')->limit((int) $limit, (int) $offset)->get('review_requests')->result_array();
        return array('rows' => $rows, 'total' => $total);
    }

    public function request_counts()
    {
        $this->ensure_schema();
        $since = date('Y-m-d H:i:s', strtotime('-30 days'));
        return (int) $this->db->where('status', 'sent')->where('created_at >=', $since)->count_all_results('review_requests');
    }
}
