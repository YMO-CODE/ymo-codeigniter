<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Estimated WhatsApp prepaid balance for the Today page.
 *
 * Meta doesn't let apps read the prepaid balance, so an admin enters the
 * balance shown in Meta Business Settings → Billing & payments (and each
 * top-up), and the CRM subtracts the spend Meta reports since then
 * (pricing_analytics). Spend is cached for 30 minutes.
 *
 * Stored in `settings` as crm_wa_balance = {"amount": 500.0, "at": 1794900000, "by": 1}.
 */
class Crm_wa_balance
{
    const KEY = 'crm_wa_balance';
    const CACHE_TTL = 1800;
    const LOW = 100; // ₹

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('crm_messaging');
        $this->CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
    }

    public function checkpoint()
    {
        if (!$this->CI->db->table_exists('settings')) {
            return NULL;
        }
        $row = $this->CI->db->get_where('settings', array('setting_key' => self::KEY))->row_array();
        $v = $row ? json_decode((string) $row['setting_value'], TRUE) : NULL;
        return is_array($v) && isset($v['amount'], $v['at']) ? $v : NULL;
    }

    /** Admin typed the balance Meta shows right now. */
    public function set_balance($amount, $admin_id)
    {
        $value = json_encode(array('amount' => round((float) $amount, 2), 'at' => time(), 'by' => (int) $admin_id));
        $exists = $this->CI->db->where('setting_key', self::KEY)->count_all_results('settings');
        if ($exists) {
            $this->CI->db->where('setting_key', self::KEY)->update('settings', array('setting_value' => $value));
        } else {
            $this->CI->db->insert('settings', array('setting_key' => self::KEY, 'setting_value' => $value));
        }
        $this->_forget_cache();
        if (function_exists('ymo_clear_settings_cache')) {
            ymo_clear_settings_cache();
        }
    }

    /**
     * Admin added funds: new starting point = today's estimate + the top-up.
     * Returns FALSE (and changes nothing) when spend can't be read from Meta right now,
     * so an unknown balance is never silently treated as zero.
     */
    public function add_topup($amount, $admin_id)
    {
        $s = $this->summary();
        if (!$s || !$s['tracked']) {
            $this->set_balance((float) $amount, $admin_id);
            return TRUE;
        }
        if ($s['remaining'] === NULL) {
            return FALSE;
        }
        $this->set_balance(max(0, $s['remaining']) + (float) $amount, $admin_id);
        return TRUE;
    }

    /**
     * @return array|null NULL when WhatsApp isn't set up. Keys: tracked (bool),
     *   remaining (?float), base, base_at, spent_since (?float), today (?array),
     *   month (?array), msgs_left (?int), avg_cost (?float), low (bool), error (?string)
     */
    public function summary()
    {
        if (!$this->CI->crm_messaging->can_send_whatsapp()) {
            return NULL;
        }
        $cp = $this->checkpoint();
        $now = time();
        $out = array(
            'tracked' => (bool) $cp, 'remaining' => NULL, 'base' => $cp['amount'] ?? NULL, 'base_at' => $cp['at'] ?? NULL,
            'spent_since' => NULL, 'today' => NULL, 'month' => NULL, 'msgs_left' => NULL, 'avg_cost' => NULL,
            'low' => FALSE, 'error' => NULL,
        );

        $month = $this->_spend(strtotime(date('Y-m-01 00:00:00')), $now, 'DAILY');
        $today = $this->_spend(strtotime('today'), $now, 'HALF_HOUR');
        $out['month'] = $month;
        $out['today'] = $today;
        if ($month === NULL) {
            $out['error'] = $this->CI->crm_messaging->last_error() ?: 'Could not read spend from Meta.';
        }

        if ($cp) {
            // Half-hour buckets for the last week, daily beyond that (Meta's granularity limits).
            $gran = ($now - (int) $cp['at']) <= 7 * 86400 ? 'HALF_HOUR' : 'DAILY';
            $since = $this->_spend((int) $cp['at'], $now, $gran);
            if ($since !== NULL) {
                $out['spent_since'] = $since['cost'];
                $out['remaining'] = round((float) $cp['amount'] - $since['cost'], 2);
            } else {
                $out['error'] = $out['error'] ?: ($this->CI->crm_messaging->last_error() ?: 'Could not read spend from Meta.');
            }
        }

        // Average price of a paid message this month (marketing first) → roughly how many are left.
        if ($month) {
            $cat = $month['by_category']['marketing'] ?? NULL;
            if ($cat && $cat['volume'] > 0 && $cat['cost'] > 0) {
                $out['avg_cost'] = $cat['cost'] / $cat['volume'];
            } elseif ($month['volume'] > 0 && $month['cost'] > 0) {
                $out['avg_cost'] = $month['cost'] / $month['volume'];
            }
        }
        if ($out['remaining'] !== NULL) {
            if ($out['avg_cost']) {
                $out['msgs_left'] = max(0, (int) floor($out['remaining'] / $out['avg_cost']));
            }
            $out['low'] = $out['remaining'] < self::LOW;
        }
        return $out;
    }

    protected function _spend($start, $end, $granularity)
    {
        // Meta buckets by half hour / day, so align the start to the bucket it falls in.
        $step = $granularity === 'HALF_HOUR' ? 1800 : 86400;
        $start = (int) (floor($start / $step) * $step);
        if ($end - $start < 60) {
            return array('cost' => 0.0, 'volume' => 0, 'by_category' => array());
        }
        $key = 'wa_spend_'.$granularity.'_'.$start.'_'.(int) floor($end / self::CACHE_TTL);
        $hit = $this->CI->cache->get($key);
        if (is_array($hit)) {
            return $hit;
        }
        $res = $this->CI->crm_messaging->fetch_whatsapp_spend($start, $end, $granularity);
        if ($res !== NULL) {
            $this->CI->cache->save($key, $res, self::CACHE_TTL);
            $keys = (array) $this->CI->cache->get('wa_spend_keys');
            $keys[] = $key;
            $this->CI->cache->save('wa_spend_keys', array_slice(array_unique($keys), -50), 86400);
        }
        return $res;
    }

    protected function _forget_cache()
    {
        foreach ((array) $this->CI->cache->get('wa_spend_keys') as $k) {
            $this->CI->cache->delete($k);
        }
        $this->CI->cache->delete('wa_spend_keys');
    }
}
