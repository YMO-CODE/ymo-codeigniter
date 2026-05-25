<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crm_tag_model extends CI_Model
{
    const TABLE = 'crm_tags';

    public function all()
    {
        return $this->db->order_by('name', 'ASC')->get(self::TABLE)->result_array();
    }

    public function find_or_create($name)
    {
        $slug = crm_slug($name);
        $row = $this->db->get_where(self::TABLE, array('slug' => $slug))->row_array();
        if ($row) {
            return (int) $row['id'];
        }
        $this->db->insert(self::TABLE, array(
            'name'       => $name,
            'slug'       => $slug,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function for_contact($contact_id)
    {
        return $this->db
            ->select('t.*')
            ->from('crm_contact_tags ct')
            ->join(self::TABLE.' t', 't.id = ct.tag_id')
            ->where('ct.contact_id', (int) $contact_id)
            ->get()
            ->result_array();
    }

    public function for_lead($lead_id)
    {
        return $this->db
            ->select('t.*')
            ->from('crm_lead_tags lt')
            ->join(self::TABLE.' t', 't.id = lt.tag_id')
            ->where('lt.lead_id', (int) $lead_id)
            ->get()
            ->result_array();
    }

    public function sync_contact_tags($contact_id, array $tag_ids)
    {
        $this->db->delete('crm_contact_tags', array('contact_id' => (int) $contact_id));
        foreach ($tag_ids as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $this->db->insert('crm_contact_tags', array(
                    'contact_id' => (int) $contact_id,
                    'tag_id'     => $tid,
                ));
            }
        }
    }
}
