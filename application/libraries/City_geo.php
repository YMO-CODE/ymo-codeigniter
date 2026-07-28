<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lightweight IP / header geo lookup for marketing city-hint banner.
 * Never redirects — returns a city slug for optional UX only.
 */
class City_geo
{
    const COOKIE_DISMISSED = 'ymo_city_hint_dismissed';

    /** @var CI_Controller */
    protected $CI;

    /** @var array<string, array{city:string,region:string,country:string}|null> */
    protected $geo_cache = array();

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper('marketing');
    }

    /**
     * City hint payload for homepage banner, or NULL when hidden / unknown.
     *
     * @return array{slug:string,name:string,hub_path:string}|null
     */
    public function homepage_hint()
    {
        if ($this->is_bot()) {
            return NULL;
        }
        if ($this->is_dismissed()) {
            return NULL;
        }

        $slug = $this->detect_city_slug();
        if ($slug === NULL) {
            return NULL;
        }

        $city = marketing_city_by_slug($slug);
        if ($city === NULL || empty($city['hub_path'])) {
            return NULL;
        }

        return array(
            'slug'     => $slug,
            'name'     => $city['name'],
            'hub_path' => $city['hub_path'],
        );
    }

    /** @return bool */
    public function is_dismissed()
    {
        if (isset($_COOKIE[self::COOKIE_DISMISSED])) {
            return (string) $_COOKIE[self::COOKIE_DISMISSED] === '1';
        }

        $cookie = $this->CI->input->cookie(self::COOKIE_DISMISSED);
        return $cookie === '1' || $cookie === 1;
    }

    /** @return bool */
    public function is_bot()
    {
        $ua = strtolower((string) $this->CI->input->user_agent());
        if ($ua === '') {
            return FALSE;
        }

        $bots = array(
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'facebot', 'ia_archiver', 'petalbot', 'semrushbot',
            'ahrefsbot', 'dotbot', 'rogerbot', 'screaming frog',
        );
        foreach ($bots as $bot) {
            if (strpos($ua, $bot) !== FALSE) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /** @return string|null */
    protected function detect_city_slug()
    {
        $override = strtolower(trim((string) $this->CI->input->get('city_hint')));
        if ($override !== '' && marketing_city_by_slug($override) !== NULL) {
            return $override;
        }

        $geo = $this->resolve_geo();
        if ($geo === NULL) {
            return NULL;
        }

        return $this->match_city_slug($geo['city'], $geo['region'], $geo['country']);
    }

    /**
     * @return array{city:string,region:string,country:string}|null
     */
    protected function resolve_geo()
    {
        $cf_city = $this->server_header('HTTP_CF_IPCITY');
        $cf_region = $this->server_header('HTTP_CF_REGION');
        if ($cf_region === '') {
            $cf_region = $this->server_header('HTTP_CF_IPREGION');
        }
        $cf_country = $this->server_header('HTTP_CF_IPCOUNTRY');

        if ($cf_city !== '' || $cf_region !== '') {
            return array(
                'city'    => $cf_city,
                'region'  => $cf_region,
                'country' => $cf_country !== '' ? $cf_country : 'IN',
            );
        }

        $ip = $this->CI->input->ip_address();
        if ($this->is_private_ip($ip)) {
            return NULL;
        }

        return $this->lookup_ip_api($ip);
    }

    /**
     * @param string $ip
     * @return array{city:string,region:string,country:string}|null
     */
    protected function lookup_ip_api($ip)
    {
        if (isset($this->geo_cache[$ip])) {
            return $this->geo_cache[$ip];
        }

        $url = 'http://ip-api.com/json/'.rawurlencode($ip).'?fields=status,city,regionName,countryCode';
        $ctx = stream_context_create(array(
            'http' => array(
                'timeout' => 2,
                'header'  => "User-Agent: YMO-Marketing/1.0\r\n",
            ),
        ));

        $raw = @file_get_contents($url, FALSE, $ctx);
        if ($raw === FALSE) {
            $this->geo_cache[$ip] = NULL;
            return NULL;
        }

        $data = json_decode($raw, TRUE);
        if (!is_array($data) || empty($data['status']) || $data['status'] !== 'success') {
            $this->geo_cache[$ip] = NULL;
            return NULL;
        }

        $this->geo_cache[$ip] = array(
            'city'    => isset($data['city']) ? (string) $data['city'] : '',
            'region'  => isset($data['regionName']) ? (string) $data['regionName'] : '',
            'country' => isset($data['countryCode']) ? (string) $data['countryCode'] : '',
        );

        return $this->geo_cache[$ip];
    }

    /**
     * @param string $city
     * @param string $region
     * @param string $country
     * @return string|null
     */
    protected function match_city_slug($city, $region, $country)
    {
        if ($country !== '' && strtoupper($country) !== 'IN') {
            return NULL;
        }

        $city_l = strtolower(trim($city));
        $region_l = strtolower(trim($region));
        $cfg = marketing_cities_config();

        foreach (array('pune', 'indore', 'nashik') as $slug) {
            if (!isset($cfg[$slug]['geo_match']) || !is_array($cfg[$slug]['geo_match'])) {
                continue;
            }
            $match = $cfg[$slug]['geo_match'];

            if (!empty($match['cities']) && is_array($match['cities'])) {
                foreach ($match['cities'] as $needle) {
                    $needle_l = strtolower(trim((string) $needle));
                    if ($needle_l === '' || $city_l === '') {
                        continue;
                    }
                    if ($city_l === $needle_l || strpos($city_l, $needle_l) !== FALSE) {
                        return $slug;
                    }
                }
            }
        }

        if ($region_l !== '' && strpos($region_l, 'madhya pradesh') !== FALSE && $city_l === 'indore') {
            return 'indore';
        }

        return NULL;
    }

    /** @param string $ip @return bool */
    protected function is_private_ip($ip)
    {
        if ($ip === '' || $ip === '0.0.0.0') {
            return TRUE;
        }
        if ($ip === '::1' || $ip === '127.0.0.1') {
            return TRUE;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        return FALSE;
    }

    /** @param string $key @return string */
    protected function server_header($key)
    {
        $val = $this->CI->input->server($key);
        return is_string($val) ? trim($val) : '';
    }
}
