<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Offer_model extends CI_Model
{
    const TABLE = 'site_offers';

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function list_all()
    {
        return $this->db->order_by('sort_order')->order_by('id', 'DESC')
            ->get(self::TABLE)->result_array();
    }

    /**
     * Active offers within schedule window, ordered by priority.
     *
     * @return array<int, array>
     */
    public function list_active()
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where('is_active', 1);
        $this->db->group_start();
        $this->db->where('starts_at IS NULL', NULL, FALSE);
        $this->db->or_where('starts_at <=', $now);
        $this->db->group_end();
        $this->db->group_start();
        $this->db->where('ends_at IS NULL', NULL, FALSE);
        $this->db->or_where('ends_at >=', $now);
        $this->db->group_end();
        return $this->db->order_by('sort_order')->order_by('id')
            ->get(self::TABLE)->result_array();
    }

    /**
     * Single highest-priority offer formatted for public API / widget.
     *
     * @return array<int, array>
     */
    public function list_active_public()
    {
        $rows = $this->list_active();
        if (empty($rows)) {
            return array();
        }
        return array($this->to_public($rows[0]));
    }

    public function create(array $payload)
    {
        $this->db->insert(self::TABLE, $payload);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $patch)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, $patch);
    }

    public function delete($id)
    {
        $row = $this->find($id);
        if ($row && !empty($row['image_path'])) {
            $this->_unlink_image($row['image_path']);
        }
        $this->db->where('id', (int) $id)->delete(self::TABLE);
    }

    /**
     * @param array $row DB row
     * @return array{id:int,title:string,body:string,cta_label:?string,cta_url:?string,image_url:?string,updated_at:string}
     */
    public function to_public(array $row)
    {
        $image_url = NULL;
        if (!empty($row['image_path'])) {
            $image_url = base_url(ltrim($row['image_path'], '/'));
        }

        $updated = !empty($row['updated_at']) ? $row['updated_at'] : date('Y-m-d H:i:s');

        return array(
            'id'         => (int) $row['id'],
            'title'      => (string) $row['title'],
            'body'       => (string) $row['body'],
            'cta_label'  => $row['cta_label'] !== NULL && $row['cta_label'] !== '' ? (string) $row['cta_label'] : NULL,
            'cta_url'    => $row['cta_url'] !== NULL && $row['cta_url'] !== '' ? (string) $row['cta_url'] : NULL,
            'image_url'  => $image_url,
            'updated_at' => date('c', strtotime($updated)),
        );
    }

    protected function _unlink_image($relative_path)
    {
        $path = FCPATH.ltrim((string) $relative_path, '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
