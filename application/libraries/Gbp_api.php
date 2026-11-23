<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google Business Profile API (reviews) over plain cURL.
 *
 * Needs: an OAuth client (Web application) in the Google Cloud project that was
 * approved for Business Profile API access, with redirect URI
 *   {admin}/reviews/google/callback
 * Client id/secret: env YMO_GBP_CLIENT_ID / YMO_GBP_CLIENT_SECRET, or saved on
 * the CRM "Google reviews" page. The refresh token is kept in settings.
 */
class Gbp_api
{
    const SCOPE = 'https://www.googleapis.com/auth/business.manage openid email';

    public $error = '';

    protected $CI;
    protected $access_token = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('gbp_review_model');
    }

    // --- configuration --------------------------------------------------------------

    public function client()
    {
        $id = trim((string) getenv('YMO_GBP_CLIENT_ID'));
        $secret = trim((string) getenv('YMO_GBP_CLIENT_SECRET'));
        if ($id === '' || $secret === '') {
            $c = (array) $this->CI->gbp_review_model->setting('gbp_oauth_client', array());
            $id = isset($c['id']) ? trim((string) $c['id']) : '';
            $secret = isset($c['secret']) ? trim((string) $c['secret']) : '';
        }
        return ($id !== '' && $secret !== '') ? array('id' => $id, 'secret' => $secret) : NULL;
    }

    public function connection()
    {
        $t = $this->CI->gbp_review_model->setting('gbp_oauth_token', NULL);
        return is_array($t) && !empty($t['refresh_token']) ? $t : NULL;
    }

    public function redirect_uri()
    {
        return admin_url('reviews/google/callback');
    }

    public function auth_url($state)
    {
        $c = $this->client();
        if (!$c) {
            return '';
        }
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(array(
            'client_id'     => $c['id'],
            'redirect_uri'  => $this->redirect_uri(),
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'include_granted_scopes' => 'true',
            'state'         => $state,
        ));
    }

    /** Swap the code from Google's redirect for tokens and remember the refresh token. */
    public function finish_auth($code)
    {
        $c = $this->client();
        if (!$c) {
            $this->error = 'OAuth client id/secret missing.';
            return FALSE;
        }
        $res = $this->_request('POST', 'https://oauth2.googleapis.com/token', array(
            'code' => $code, 'client_id' => $c['id'], 'client_secret' => $c['secret'],
            'redirect_uri' => $this->redirect_uri(), 'grant_type' => 'authorization_code',
        ), FALSE, TRUE);
        if (!$res || empty($res['refresh_token'])) {
            $this->error = $this->error ?: 'Google did not return a refresh token. Remove the app at myaccount.google.com/permissions and connect again.';
            return FALSE;
        }
        $this->access_token = $res['access_token'];
        $email = '';
        $me = $this->_request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo');
        if (is_array($me) && !empty($me['email'])) {
            $email = (string) $me['email'];
        }
        $this->CI->gbp_review_model->save_setting('gbp_oauth_token', array(
            'refresh_token' => $res['refresh_token'],
            'email'         => $email,
            'connected_at'  => date('c'),
        ));
        return TRUE;
    }

    protected function token()
    {
        if ($this->access_token) {
            return $this->access_token;
        }
        $c = $this->client();
        $t = $this->connection();
        if (!$c || !$t) {
            $this->error = !$c ? 'OAuth client id/secret missing.' : 'Google account not connected.';
            return NULL;
        }
        $res = $this->_request('POST', 'https://oauth2.googleapis.com/token', array(
            'client_id' => $c['id'], 'client_secret' => $c['secret'],
            'refresh_token' => $t['refresh_token'], 'grant_type' => 'refresh_token',
        ), FALSE, TRUE);
        if (!$res || empty($res['access_token'])) {
            $this->error = 'Could not refresh Google access: '.($this->error ?: 'no token');
            return NULL;
        }
        return $this->access_token = (string) $res['access_token'];
    }

    // --- Business Profile -------------------------------------------------------------

    /** @return array[]|null accounts (name, accountName, type) */
    public function accounts()
    {
        $res = $this->_request('GET', 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts?pageSize=20');
        return $res === NULL ? NULL : (isset($res['accounts']) ? $res['accounts'] : array());
    }

    /**
     * All locations the connected account manages.
     * @return array[]|null each: account, name (locations/…), title, address, place_id
     */
    public function locations()
    {
        $accounts = $this->accounts();
        if ($accounts === NULL) {
            return NULL;
        }
        $out = array();
        foreach ($accounts as $a) {
            $next = '';
            $guard = 0;
            do {
                $url = 'https://mybusinessbusinessinformation.googleapis.com/v1/'.$a['name'].'/locations?'
                    .http_build_query(array('readMask' => 'name,title,storefrontAddress,metadata', 'pageSize' => 100, 'pageToken' => $next));
                $res = $this->_request('GET', $url);
                if ($res === NULL) {
                    return NULL;
                }
                foreach (isset($res['locations']) ? $res['locations'] : array() as $l) {
                    $addr = isset($l['storefrontAddress']['addressLines']) ? implode(', ', $l['storefrontAddress']['addressLines']) : '';
                    $out[] = array(
                        'account'  => $a['name'],
                        'name'     => $l['name'],
                        'title'    => isset($l['title']) ? $l['title'] : '',
                        'address'  => trim($addr.(isset($l['storefrontAddress']['locality']) ? ', '.$l['storefrontAddress']['locality'] : ''), ', '),
                        'place_id' => isset($l['metadata']['placeId']) ? $l['metadata']['placeId'] : '',
                    );
                }
                $next = isset($res['nextPageToken']) ? $res['nextPageToken'] : '';
            } while ($next !== '' && ++$guard < 10);
        }
        return $out;
    }

    /**
     * Newest reviews for one location ("accounts/1/locations/2").
     * @return array[]|null normalised: review_id, name, author, stars, comment, review_time, reply_text, reply_time
     */
    public function reviews($account_location, $max = 100)
    {
        $out = array();
        $next = '';
        $guard = 0;
        do {
            $url = 'https://mybusiness.googleapis.com/v4/'.$account_location.'/reviews?'
                .http_build_query(array('pageSize' => 50, 'orderBy' => 'updateTime desc', 'pageToken' => $next));
            $res = $this->_request('GET', $url);
            if ($res === NULL) {
                return NULL;
            }
            foreach (isset($res['reviews']) ? $res['reviews'] : array() as $r) {
                $out[] = array(
                    'review_id'   => isset($r['reviewId']) ? $r['reviewId'] : basename($r['name']),
                    'name'        => $r['name'],
                    'author'      => isset($r['reviewer']['displayName']) ? $r['reviewer']['displayName'] : 'A Google user',
                    'stars'       => self::stars(isset($r['starRating']) ? $r['starRating'] : ''),
                    'comment'     => isset($r['comment']) ? self::clean_comment($r['comment']) : '',
                    'review_time' => self::time(isset($r['createTime']) ? $r['createTime'] : ''),
                    'reply_text'  => isset($r['reviewReply']['comment']) ? $r['reviewReply']['comment'] : '',
                    'reply_time'  => self::time(isset($r['reviewReply']['updateTime']) ? $r['reviewReply']['updateTime'] : ''),
                );
            }
            $next = isset($res['nextPageToken']) ? $res['nextPageToken'] : '';
        } while ($next !== '' && count($out) < $max && ++$guard < 10);
        return $out;
    }

    /** Post (or replace) the owner reply on a review. $review_name = accounts/…/locations/…/reviews/… */
    public function reply($review_name, $text)
    {
        $res = $this->_request('PUT', 'https://mybusiness.googleapis.com/v4/'.$review_name.'/reply', array('comment' => (string) $text), TRUE);
        return $res !== NULL;
    }

    // --- helpers --------------------------------------------------------------------

    public static function stars($s)
    {
        $map = array('ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5);
        return isset($map[$s]) ? $map[$s] : 0;
    }

    /** Google appends "(Translated by Google) …" blocks; keep the original text. */
    public static function clean_comment($c)
    {
        $c = (string) $c;
        if (preg_match('/\(Original\)\s*(.+)$/s', $c, $m)) {
            return trim($m[1]);
        }
        return trim(preg_replace('/\s*\(Translated by Google\).*$/s', '', $c));
    }

    public static function time($iso)
    {
        if ($iso === '' || $iso === NULL) {
            return NULL;
        }
        $t = strtotime($iso);
        return $t ? date('Y-m-d H:i:s', $t) : NULL;
    }

    protected function _request($method, $url, $body = NULL, $json = FALSE, $no_auth = FALSE)
    {
        $this->error = '';
        $headers = array();
        if (!$no_auth) {
            $tok = $this->token();
            if (!$tok) {
                return NULL;
            }
            $headers[] = 'Authorization: Bearer '.$tok;
        }
        $ch = curl_init($url);
        $opts = array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        );
        if ($body !== NULL) {
            if ($json) {
                $opts[CURLOPT_POSTFIELDS] = json_encode($body);
                $headers[] = 'Content-Type: application/json';
            } else {
                $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            $this->error = 'Network: '.$err;
            log_message('error', '[gbp_api] '.$this->error);
            return NULL;
        }
        $data = json_decode((string) $raw, TRUE);
        if ($http >= 400) {
            $msg = is_array($data) && isset($data['error']['message']) ? $data['error']['message']
                : (is_array($data) && isset($data['error_description']) ? $data['error_description'] : substr((string) $raw, 0, 200));
            $this->error = 'Google HTTP '.$http.': '.$msg;
            log_message('error', '[gbp_api] '.$method.' '.preg_replace('/\?.*$/', '', $url).' → '.$this->error);
            return NULL;
        }
        return is_array($data) ? $data : array();
    }
}
