<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Local cache of WhatsApp message templates (synced read-only from Meta).
 * Templates are created/approved in WhatsApp Manager; this app only lists
 * and uses them.
 */
class Crm_wa_template_model extends CI_Model
{
    const TABLE = 'crm_wa_templates';

    public function table_ready()
    {
        return $this->db->table_exists(self::TABLE);
    }

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_by_name($name, $lang = NULL)
    {
        $this->db->where('name', (string) $name);
        if ($lang) {
            $this->db->where('language', (string) $lang);
        }
        return $this->db->order_by('id', 'ASC')->limit(1)->get(self::TABLE)->row_array();
    }

    public function all()
    {
        if (!$this->table_ready()) {
            return array();
        }
        return $this->db->order_by('status', 'ASC')->order_by('name', 'ASC')->get(self::TABLE)->result_array();
    }

    /** Approved templates usable for campaigns (marketing/utility, no auth/OTP). */
    public function approved_for_campaigns()
    {
        if (!$this->table_ready()) {
            return array();
        }
        return $this->db
            ->where('status', 'APPROVED')
            ->where('category !=', 'AUTHENTICATION')
            ->order_by('name', 'ASC')
            ->get(self::TABLE)
            ->result_array();
    }

    /**
     * Replace local cache with the list returned by Meta.
     *
     * @param array[] $remote Raw template objects from Crm_messaging::fetch_whatsapp_templates()
     * @return array{added:int,updated:int,removed:int}
     */
    public function sync_from_meta(array $remote)
    {
        $now = date('Y-m-d H:i:s');
        $stats = array('added' => 0, 'updated' => 0, 'removed' => 0);
        $seen = array();

        foreach ($remote as $tpl) {
            $name = (string) ($tpl['name'] ?? '');
            $lang = (string) ($tpl['language'] ?? 'en');
            if ($name === '') {
                continue;
            }
            $parsed = $this->parse_components($tpl['components'] ?? array());
            $row = array(
                'name'             => $name,
                'language'         => $lang,
                'category'         => isset($tpl['category']) ? strtoupper((string) $tpl['category']) : NULL,
                'status'           => strtoupper((string) ($tpl['status'] ?? 'UNKNOWN')),
                'header_type'      => $parsed['header_type'],
                'body_text'        => $parsed['body_text'],
                'param_count'      => $parsed['param_count'],
                'components_json'  => json_encode($tpl['components'] ?? array()),
                'meta_template_id' => isset($tpl['id']) ? (string) $tpl['id'] : NULL,
                'synced_at'        => $now,
            );

            $existing = $this->db->get_where(self::TABLE, array('name' => $name, 'language' => $lang))->row_array();
            if ($existing) {
                $this->db->where('id', (int) $existing['id'])->update(self::TABLE, $row);
                $stats['updated']++;
                $seen[] = (int) $existing['id'];
            } else {
                $this->db->insert(self::TABLE, $row);
                $stats['added']++;
                $seen[] = (int) $this->db->insert_id();
            }
        }

        // Templates deleted in WhatsApp Manager: keep the row (campaigns may
        // reference it) but mark it so it can no longer be picked.
        $this->db->where('status !=', 'DELETED');
        if ($seen) {
            $this->db->where_not_in('id', $seen);
        }
        $this->db->update(self::TABLE, array('status' => 'DELETED', 'synced_at' => $now));
        $stats['removed'] = (int) $this->db->affected_rows();

        return $stats;
    }

    /**
     * @return array{header_type:?string,body_text:string,param_count:int}
     */
    public function parse_components(array $components)
    {
        $header = NULL;
        $body = '';
        foreach ($components as $c) {
            $type = strtoupper((string) ($c['type'] ?? ''));
            if ($type === 'HEADER') {
                $header = strtolower((string) ($c['format'] ?? 'text'));
            } elseif ($type === 'BODY') {
                $body = (string) ($c['text'] ?? '');
            }
        }
        $count = 0;
        if (preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $m)) {
            $count = (int) max(array_map('intval', $m[1]));
        }
        return array('header_type' => $header, 'body_text' => $body, 'param_count' => $count);
    }
}
