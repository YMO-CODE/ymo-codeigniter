<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp spend for the Today page: today and this month, from Meta's
 * pricing_analytics. Meta doesn't let apps read the prepaid balance, so the
 * card links straight to this WhatsApp account's page in Meta Billing.
 * Spend is cached for 30 minutes.
 */
class Crm_wa_balance
{
    const CACHE_TTL = 1800;

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->config('crm', TRUE, TRUE);
        $this->CI->load->library('crm_messaging');
        $this->CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
    }

    /** Meta Billing page for this WhatsApp account, where the prepaid balance is shown. */
    public function billing_url()
    {
        $waba = (string) ($this->CI->config->item('crm_whatsapp_waba_id', 'crm') ?: $this->CI->config->item('crm_whatsapp_waba_id'));
        $biz  = (string) ($this->CI->config->item('crm_meta_business_id', 'crm') ?: $this->CI->config->item('crm_meta_business_id'));
        if ($waba === '') {
            return 'https://business.facebook.com/billing_hub/accounts'.($biz !== '' ? '?business_id='.rawurlencode($biz) : '');
        }
        return 'https://business.facebook.com/billing_hub/accounts/details/?'.http_build_query(array_filter(array(
            'business_id'  => $biz,
            'asset_id'     => $waba,
            'account_type' => 'whatsapp-business-account',
        )));
    }

    /**
     * @return array|null NULL when WhatsApp isn't set up. Keys: today (?array),
     *   month (?array) each {cost, volume, by_category}, avg_cost (?float),
     *   error (?string), billing_url (string)
     */
    public function summary()
    {
        if (!$this->CI->crm_messaging->can_send_whatsapp()) {
            return NULL;
        }
        $now = time();
        $out = array('today' => NULL, 'month' => NULL, 'avg_cost' => NULL, 'error' => NULL, 'billing_url' => $this->billing_url());

        $out['month'] = $this->_spend(strtotime(date('Y-m-01 00:00:00')), $now, 'DAILY');
        $out['today'] = $this->_spend(strtotime('today'), $now, 'HALF_HOUR');
        if ($out['month'] === NULL) {
            $out['error'] = $this->CI->crm_messaging->last_error() ?: 'Could not read spend from Meta.';
            return $out;
        }

        // Average price of a paid message this month (marketing first).
        $m = $out['month'];
        $cat = $m['by_category']['marketing'] ?? NULL;
        if ($cat && $cat['volume'] > 0 && $cat['cost'] > 0) {
            $out['avg_cost'] = $cat['cost'] / $cat['volume'];
        } elseif ($m['volume'] > 0 && $m['cost'] > 0) {
            $out['avg_cost'] = $m['cost'] / $m['volume'];
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
        }
        return $res;
    }
}
