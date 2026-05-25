<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Package_model extends CI_Model
{
    const TABLE = 'service_packages';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_by_slug($slug)
    {
        return $this->db->get_where(self::TABLE, array('slug' => $slug, 'is_active' => 1))->row_array();
    }

    public function list_active($with_features = TRUE)
    {
        $rows = $this->db->where('is_active', 1)
                         ->order_by('sort_order')
                         ->order_by('id')
                         ->get(self::TABLE)->result_array();
        if ($with_features) {
            foreach ($rows as &$r) {
                $r['features'] = $this->features_for($r['id']);
            }
        }
        return $rows;
    }

    public function list_active_with_features()
    {
        return $this->list_active(TRUE);
    }

    public function list_all()
    {
        $rows = $this->db->order_by('sort_order')->order_by('id')
                         ->get(self::TABLE)->result_array();
        foreach ($rows as &$r) {
            $r['features'] = $this->features_for($r['id']);
        }
        return $rows;
    }

    public function features_for($package_id)
    {
        $rows = $this->db->where('package_id', (int) $package_id)
                         ->order_by('sort_order')
                         ->get('service_package_features')
                         ->result_array();
        return array_column($rows, 'feature_text');
    }

    public function create(array $payload, array $features)
    {
        $payload['slug'] = $this->_unique_slug($payload['name']);
        $this->db->insert(self::TABLE, $payload);
        $id = (int) $this->db->insert_id();
        $this->_replace_features($id, $features);
        return $id;
    }

    public function update($id, array $patch, array $features)
    {
        if (isset($patch['name']) && empty($patch['slug'])) {
            $patch['slug'] = $this->_unique_slug($patch['name'], (int) $id);
        }
        $this->db->where('id', (int) $id)->update(self::TABLE, $patch);
        $this->_replace_features((int) $id, $features);
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete(self::TABLE);
    }

    /**
     * JSON snapshot stored on a booking — survives later edits to the package.
     */
    public function snapshot(array $package)
    {
        return json_encode(array(
            'name'     => $package['name'],
            'slug'     => $package['slug'],
            'price'    => (float) $package['price'],
            'features' => $this->features_for($package['id']),
            'taken_at' => date('c'),
        ));
    }

    // --- internals ---------------------------------------------------------

    protected function _unique_slug($name, $skip_id = 0)
    {
        $base = url_title(strtolower($name), 'dash', TRUE);
        if ($base === '') { $base = 'package'; }
        $slug = $base; $i = 1;
        while ($this->_slug_taken($slug, $skip_id)) {
            $slug = $base.'-'.(++$i);
        }
        return $slug;
    }

    protected function _slug_taken($slug, $skip_id = 0)
    {
        $this->db->where('slug', $slug);
        if ($skip_id) { $this->db->where('id !=', $skip_id); }
        return (bool) $this->db->count_all_results(self::TABLE);
    }

    protected function _replace_features($package_id, array $features)
    {
        $this->db->where('package_id', $package_id)->delete('service_package_features');
        $sort = 10;
        foreach ($features as $text) {
            $text = trim($text);
            if ($text === '') { continue; }
            $this->db->insert('service_package_features', array(
                'package_id'   => $package_id,
                'feature_text' => $text,
                'sort_order'   => $sort,
            ));
            $sort += 10;
        }
    }
}
