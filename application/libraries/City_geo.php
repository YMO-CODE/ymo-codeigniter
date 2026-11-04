<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Visitor city detection for the marketing site (Pune / Indore / Nashik).
 *
 * Order:
 *   1. The visitor's own choice (ymo_city cookie, set by the header city switcher).
 *   2. Cloudflare geo headers, if the site is ever put behind Cloudflare.
 *   3. Local IP lookup in the DB-IP "IP to City Lite" database
 *      (storage/geo/dbip-city-lite.mmdb, refreshed monthly by deploy/scripts/update-geoip.sh).
 *      IP geolocation by DB-IP (https://db-ip.com), licensed CC BY 4.0.
 *
 * Never redirects and never changes server-rendered HTML: the result is consumed by
 * /geo/city (JSON) and the browser script ymo-city.js, so pages stay cacheable and
 * crawlers always see the same content.
 */
class City_geo
{
    const COOKIE_DISMISSED = 'ymo_city_hint_dismissed';
    const COOKIE_CITY      = 'ymo_city';

    /**
     * City centres and catchment radius (km) used when the database returns a
     * suburb or village name we do not list in geo_match.
     *
     * @var array<string, array{0: float, 1: float, 2: float}>
     */
    protected $centres = array(
        'pune'   => array(18.5204, 73.8567, 60.0),
        'indore' => array(22.7196, 75.8577, 50.0),
        'nashik' => array(19.9975, 73.7898, 45.0),
    );

    /** @var CI_Controller */
    protected $CI;

    /** @var \MaxMind\Db\Reader|false|null  null = not opened yet, false = unavailable */
    protected $reader = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper('marketing');
    }

    /**
     * Visitor city, or NULL when unknown / outside our cities / crawler.
     *
     * @return array{slug:string, source:string}|null  source: choice|cdn|ip
     */
    public function detect()
    {
        $choice = $this->chosen_city();
        if ($choice !== NULL) {
            return array('slug' => $choice, 'source' => 'choice');
        }
        if ($this->is_bot()) {
            return NULL;
        }

        $override = strtolower(trim((string) $this->CI->input->get('city_hint')));
        if ($override !== '' && isset($this->centres[$override])) {
            return array('slug' => $override, 'source' => 'override');
        }

        $geo = $this->resolve_geo();
        if ($geo === NULL) {
            return NULL;
        }
        $slug = $this->match_city_slug($geo['city'], $geo['region'], $geo['country'], $geo['lat'], $geo['lon']);
        if ($slug === NULL) {
            return NULL;
        }
        return array('slug' => $slug, 'source' => $geo['source']);
    }

    /**
     * Kept for older callers: homepage banner payload (hidden when dismissed).
     *
     * @return array{slug:string,name:string,hub_path:string}|null
     */
    public function homepage_hint()
    {
        if ($this->is_dismissed()) {
            return NULL;
        }
        $hit = $this->detect();
        if ($hit === NULL) {
            return NULL;
        }
        $city = marketing_city_by_slug($hit['slug']);
        if ($city === NULL || empty($city['hub_path'])) {
            return NULL;
        }
        return array('slug' => $hit['slug'], 'name' => $city['name'], 'hub_path' => $city['hub_path']);
    }

    /** @return string|null */
    public function chosen_city()
    {
        $raw = isset($_COOKIE[self::COOKIE_CITY]) ? (string) $_COOKIE[self::COOKIE_CITY] : '';
        $raw = strtolower(trim($raw));
        return isset($this->centres[$raw]) ? $raw : NULL;
    }

    /** @return bool */
    public function is_dismissed()
    {
        return isset($_COOKIE[self::COOKIE_DISMISSED]) && (string) $_COOKIE[self::COOKIE_DISMISSED] === '1';
    }

    /** @return bool */
    public function is_bot()
    {
        $ua = strtolower((string) $this->CI->input->user_agent());
        if ($ua === '') {
            return FALSE;
        }
        $bots = array(
            'googlebot', 'google-inspectiontool', 'adsbot-google', 'mediapartners-google',
            'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot', 'facebot',
            'facebookexternalhit', 'ia_archiver', 'petalbot', 'semrushbot', 'ahrefsbot',
            'dotbot', 'rogerbot', 'screaming frog', 'gptbot', 'claudebot', 'bot/', 'spider', 'crawler',
        );
        foreach ($bots as $bot) {
            if (strpos($ua, $bot) !== FALSE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * @return array{city:string,region:string,country:string,lat:float|null,lon:float|null,source:string}|null
     */
    protected function resolve_geo()
    {
        $cf_city = $this->server_header('HTTP_CF_IPCITY');
        $cf_region = $this->server_header('HTTP_CF_REGION');
        if ($cf_region === '') {
            $cf_region = $this->server_header('HTTP_CF_IPREGION');
        }
        if ($cf_city !== '' || $cf_region !== '') {
            $lat = $this->server_header('HTTP_CF_IPLATITUDE');
            $lon = $this->server_header('HTTP_CF_IPLONGITUDE');
            $country = $this->server_header('HTTP_CF_IPCOUNTRY');
            return array(
                'city'    => $cf_city,
                'region'  => $cf_region,
                'country' => $country !== '' ? $country : 'IN',
                'lat'     => $lat !== '' ? (float) $lat : NULL,
                'lon'     => $lon !== '' ? (float) $lon : NULL,
                'source'  => 'cdn',
            );
        }

        $ip = (string) $this->CI->input->ip_address();
        if ($this->is_private_ip($ip)) {
            return NULL;
        }
        return $this->lookup_db($ip);
    }

    /** @return string */
    public static function database_path()
    {
        $env = getenv('YMO_GEOIP_DB');
        if ($env !== FALSE && trim((string) $env) !== '') {
            return trim((string) $env);
        }
        return rtrim(FCPATH, '/\\').'/../storage/geo/dbip-city-lite.mmdb';
    }

    /**
     * @param string $ip
     * @return array{city:string,region:string,country:string,lat:float|null,lon:float|null,source:string}|null
     */
    public function lookup_db($ip)
    {
        $reader = $this->reader();
        if (!$reader) {
            return NULL;
        }
        try {
            $rec = $reader->get($ip);
        } catch (\Throwable $e) {
            return NULL;
        }
        if (!is_array($rec)) {
            return NULL;
        }
        $name = function ($node) {
            if (!is_array($node) || empty($node['names']) || !is_array($node['names'])) {
                return '';
            }
            return isset($node['names']['en']) ? (string) $node['names']['en'] : (string) reset($node['names']);
        };
        return array(
            'city'    => $name(isset($rec['city']) ? $rec['city'] : NULL),
            'region'  => $name(isset($rec['subdivisions'][0]) ? $rec['subdivisions'][0] : NULL),
            'country' => isset($rec['country']['iso_code']) ? (string) $rec['country']['iso_code'] : '',
            'lat'     => isset($rec['location']['latitude']) ? (float) $rec['location']['latitude'] : NULL,
            'lon'     => isset($rec['location']['longitude']) ? (float) $rec['location']['longitude'] : NULL,
            'source'  => 'ip',
        );
    }

    /** @return \MaxMind\Db\Reader|false */
    protected function reader()
    {
        if ($this->reader !== NULL) {
            return $this->reader;
        }
        $this->reader = FALSE;
        $path = self::database_path();
        if (!is_file($path) || !is_readable($path)) {
            return FALSE;
        }
        $base = APPPATH.'third_party/MaxMind/Db/';
        foreach (array('Reader/InvalidDatabaseException.php', 'Reader/Util.php', 'Reader/Decoder.php', 'Reader/Metadata.php', 'Reader.php') as $file) {
            if (!is_file($base.$file)) {
                return FALSE;
            }
            require_once $base.$file;
        }
        try {
            $this->reader = new \MaxMind\Db\Reader($path);
        } catch (\Throwable $e) {
            log_message('error', 'City_geo: cannot open '.$path.': '.$e->getMessage());
            $this->reader = FALSE;
        }
        return $this->reader;
    }

    /**
     * @return string|null pune|indore|nashik
     */
    protected function match_city_slug($city, $region, $country, $lat, $lon)
    {
        if ($country !== '' && strtoupper($country) !== 'IN') {
            return NULL;
        }

        $city_l = strtolower(trim((string) $city));
        if ($city_l !== '') {
            $cfg = marketing_cities_config();
            foreach (array_keys($this->centres) as $slug) {
                $needles = isset($cfg[$slug]['geo_match']['cities']) && is_array($cfg[$slug]['geo_match']['cities'])
                    ? $cfg[$slug]['geo_match']['cities'] : array($slug);
                foreach ($needles as $needle) {
                    $needle_l = strtolower(trim((string) $needle));
                    if ($needle_l !== '' && ($city_l === $needle_l || strpos($city_l, $needle_l) !== FALSE)) {
                        return $slug;
                    }
                }
            }
        }

        // Suburbs and villages: nearest city centre within its catchment radius.
        if ($lat !== NULL && $lon !== NULL) {
            $best = NULL;
            $best_km = INF;
            foreach ($this->centres as $slug => $c) {
                $km = $this->distance_km((float) $lat, (float) $lon, $c[0], $c[1]);
                if ($km <= $c[2] && $km < $best_km) {
                    $best = $slug;
                    $best_km = $km;
                }
            }
            return $best;
        }

        return NULL;
    }

    /** @return float */
    protected function distance_km($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371.0;
        $d_lat = deg2rad($lat2 - $lat1);
        $d_lon = deg2rad($lon2 - $lon1);
        $a = sin($d_lat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($d_lon / 2) ** 2;
        return 2 * $r * asin(min(1.0, sqrt($a)));
    }

    /** @param string $ip @return bool */
    protected function is_private_ip($ip)
    {
        if ($ip === '' || $ip === '0.0.0.0' || $ip === '::1' || $ip === '127.0.0.1') {
            return TRUE;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return TRUE;
        }
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** @param string $key @return string */
    protected function server_header($key)
    {
        $val = $this->CI->input->server($key);
        return is_string($val) ? trim($val) : '';
    }
}
