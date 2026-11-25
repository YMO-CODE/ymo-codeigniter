<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google Business Profile posts written in the CRM: text, optional photo
 * (R2 public bucket in image_path; older posts or no-R2 setups keep the bytes
 * here, served at /media/gbp/{id}/{hash} so Google can fetch it), button, which workshop listings, when to publish, and the result per
 * listing. Table is created on first use.
 */
class Gbp_post_model extends CI_Model
{
    const SCHEMA_SQL = "
CREATE TABLE IF NOT EXISTS gbp_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  summary TEXT NOT NULL,
  cta_type VARCHAR(20) NOT NULL DEFAULT 'CALL',
  workshops VARCHAR(120) NOT NULL DEFAULT '',
  image MEDIUMBLOB NULL,
  image_hash VARCHAR(16) NOT NULL DEFAULT '',
  image_path VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
  publish_on DATE NOT NULL,
  results TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  published_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_status_date (status, publish_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

    protected $ready = NULL;

    public function ensure_schema()
    {
        if ($this->ready !== NULL) {
            return $this->ready;
        }
        if ($this->db->table_exists('gbp_posts')) {
            if (!$this->db->field_exists('image_path', 'gbp_posts')) {
                $this->db->query("ALTER TABLE gbp_posts ADD COLUMN image_path VARCHAR(255) NULL AFTER image_hash");
            }
            return $this->ready = TRUE;
        }
        if (!$this->db->query(trim(self::SCHEMA_SQL))) {
            log_message('error', '[gbp_posts] schema failed: '.$this->db->error()['message']);
            return $this->ready = FALSE;
        }
        return $this->ready = TRUE;
    }

    public function create(array $d)
    {
        $this->ensure_schema();
        $d['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('gbp_posts', $d);
        return (int) $this->db->insert_id();
    }

    public function find($id, $with_image = FALSE)
    {
        $this->ensure_schema();
        if (!$with_image) {
            $this->db->select('id, summary, cta_type, workshops, image_hash, image_path, status, publish_on, results, created_by, created_at, published_at, (image IS NOT NULL OR image_path IS NOT NULL) AS has_image', FALSE);
        }
        return $this->db->get_where('gbp_posts', array('id' => (int) $id), 1)->row_array();
    }

    public function image($id, $hash)
    {
        $this->ensure_schema();
        return $this->db->select('image')->get_where('gbp_posts', array('id' => (int) $id, 'image_hash' => (string) $hash), 1)->row_array();
    }

    public function update($id, array $d)
    {
        $this->db->where('id', (int) $id)->update('gbp_posts', $d);
    }

    public function delete($id)
    {
        $row = $this->find($id);
        // Only R2 copies are removed; site assets (assets/img/...) are shared and stay.
        if ($row && !empty($row['image_path']) && strpos($row['image_path'], 'r2/') === 0
            && in_array($row['status'], array('draft', 'scheduled', 'failed'), TRUE)) {
            $this->load->helper('media');
            ymo_media_delete($row['image_path']);
        }
        $this->db->where('id', (int) $id)->where_in('status', array('draft', 'scheduled', 'failed'))->delete('gbp_posts');
        return $this->db->affected_rows() > 0;
    }

    /** Scheduled posts whose day has come. */
    public function due($limit = 10)
    {
        $this->ensure_schema();
        return $this->db->select('id')->where('status', 'scheduled')->where('publish_on <=', date('Y-m-d'))
            ->order_by('publish_on', 'ASC')->order_by('id', 'ASC')->limit((int) $limit)->get('gbp_posts')->result_array();
    }

    public function recent($limit = 40)
    {
        $this->ensure_schema();
        return $this->db->select('id, summary, cta_type, workshops, image_hash, image_path, status, publish_on, results, created_at, published_at, (image IS NOT NULL OR image_path IS NOT NULL) AS has_image', FALSE)
            // Drafts first (oldest first, so they read in order), then scheduled, then the rest by date.
            ->order_by("FIELD(status, 'scheduled', 'draft') DESC", '', FALSE)
            ->order_by("(status = 'draft') * id", 'ASC', FALSE)
            ->order_by('publish_on', 'DESC')->order_by('id', 'DESC')
            ->limit((int) $limit)->get('gbp_posts')->result_array();
    }

    /** Post with this exact text already saved? (keeps the draft seeder idempotent) */
    public function exists_with_summary($summary)
    {
        $this->ensure_schema();
        return $this->db->where('summary', (string) $summary)->count_all_results('gbp_posts') > 0;
    }

    public function count_by_status($status)
    {
        $this->ensure_schema();
        return $this->db->where('status', (string) $status)->count_all_results('gbp_posts');
    }

    /** Count per status tab: draft, scheduled, live (published + partial), failed, all. */
    public function tab_counts()
    {
        $this->ensure_schema();
        $out = array('draft' => 0, 'scheduled' => 0, 'live' => 0, 'failed' => 0, 'all' => 0);
        foreach ($this->db->select('status, COUNT(*) n', FALSE)->group_by('status')->get('gbp_posts')->result_array() as $r) {
            $n = (int) $r['n'];
            $out['all'] += $n;
            $k = in_array($r['status'], array('published', 'partial'), TRUE) ? 'live' : $r['status'];
            if (isset($out[$k])) {
                $out[$k] += $n;
            }
        }
        return $out;
    }

    /**
     * One page of posts for a tab.
     * @return array{rows:array, total:int}
     */
    public function page($tab, $limit, $offset)
    {
        $this->ensure_schema();
        $map = array('draft' => array('draft'), 'scheduled' => array('scheduled'), 'live' => array('published', 'partial'), 'failed' => array('failed'));
        $apply = function () use ($tab, $map) {
            $this->db->from('gbp_posts');
            if (isset($map[$tab])) {
                $this->db->where_in('status', $map[$tab]);
            }
        };
        $apply();
        $total = (int) $this->db->count_all_results();
        $apply();
        $this->db->select('id, summary, cta_type, workshops, image_hash, image_path, status, publish_on, results, created_at, published_at, (image IS NOT NULL OR image_path IS NOT NULL) AS has_image', FALSE);
        if ($tab === 'draft') {
            $this->db->order_by('id', 'ASC');
        } elseif ($tab === 'scheduled') {
            $this->db->order_by('publish_on', 'ASC')->order_by('id', 'ASC');
        } elseif ($tab === 'live') {
            $this->db->order_by('published_at', 'DESC')->order_by('id', 'DESC');
        } else {
            $this->db->order_by("FIELD(status, 'scheduled', 'draft') DESC", '', FALSE)
                ->order_by("(status = 'draft') * id", 'ASC', FALSE)
                ->order_by('publish_on', 'DESC')->order_by('id', 'DESC');
        }
        $rows = $this->db->limit((int) $limit, (int) $offset)->get()->result_array();
        return array('rows' => $rows, 'total' => $total);
    }

    public function last_published_on()
    {
        $this->ensure_schema();
        $r = $this->db->select_max('published_at')->where_not_in('status', array('scheduled', 'draft'))->get('gbp_posts')->row_array();
        return $r ? $r['published_at'] : NULL;
    }
}
