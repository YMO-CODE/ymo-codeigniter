<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Publishes CRM-written posts to the workshops' Google Business Profiles.
 * Listings come from the "Google reviews" connection (setting gbp_locations).
 */
class Gbp_post_service
{
    const MAX_TEXT = 1500;

    protected $CI;
    public $error = '';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper(array('review', 'marketing'));
        $this->CI->load->model(array('gbp_post_model', 'gbp_review_model'));
    }

    /** Workshops that have a connected listing: slug => location path. */
    public function connected_workshops()
    {
        $out = array();
        foreach ((array) $this->CI->gbp_review_model->setting('gbp_locations', array()) as $l) {
            if (!empty($l['workshop']) && !empty($l['path'])) {
                $out[$l['workshop']] = $l['path'];
            }
        }
        return $out;
    }

    /**
     * Uploaded photo → JPEG bytes (longest side ≤ 1600px). NULL + $this->error if unusable.
     */
    public function prepare_image($tmp_path)
    {
        $this->error = '';
        $info = @getimagesize($tmp_path);
        if (!$info || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), TRUE)) {
            $this->error = 'Photo must be a JPG, PNG or WebP image.';
            return NULL;
        }
        list($w, $h) = $info;
        if ($w < 400 || $h < 300) {
            $this->error = 'Photo is too small ('.$w.'×'.$h.'). Use at least 720×540 so it looks sharp on Google.';
            return NULL;
        }
        if (!function_exists('imagecreatefromstring')) {
            return (string) file_get_contents($tmp_path);
        }
        $src = @imagecreatefromstring((string) file_get_contents($tmp_path));
        if (!$src) {
            $this->error = 'Could not read that photo.';
            return NULL;
        }
        $scale = min(1, 1600 / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start();
        imagejpeg($dst, NULL, 85);
        $jpeg = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);
        return $jpeg;
    }

    /** Public URL Google fetches the photo from (R2 when stored there). */
    public function image_url(array $post)
    {
        if (!empty($post['image_path'])) {
            $this->CI->load->helper('media');
            return ymo_media_url($post['image_path']);
        }
        return rtrim(marketing_canonical_url(''), '/').'/media/gbp/'.(int) $post['id'].'/'.$post['image_hash'];
    }

    /** Link for the Book / Learn more button: the workshop's page, tagged for analytics. */
    public function button_url($workshop, $post_id)
    {
        $all = ymo_review_workshops();
        $page = isset($all[$workshop]['page']) ? $all[$workshop]['page'] : '';
        return rtrim(marketing_canonical_url(''), '/').'/'.ltrim($page, '/')
            .'?utm_source=google&utm_medium=organic&utm_campaign=gbp-post-'.$workshop.'&utm_content=post-'.(int) $post_id;
    }

    /**
     * Publish one post to all its workshops now.
     * @return array{ok:int, failed:int, messages:string[]}
     */
    public function publish($id)
    {
        $sum = array('ok' => 0, 'failed' => 0, 'messages' => array());
        $post = $this->CI->gbp_post_model->find($id);
        if (!$post || !in_array($post['status'], array('scheduled', 'failed', 'partial'), TRUE)) {
            $sum['messages'][] = 'Post not found or already published.';
            return $sum;
        }
        $this->CI->load->library('gbp_api');
        $connected = $this->connected_workshops();
        $all = ymo_review_workshops();
        $prev = json_decode((string) $post['results'], TRUE);
        $results = is_array($prev) ? $prev : array();

        foreach (array_filter(explode(',', (string) $post['workshops'])) as $w) {
            if (!empty($results[$w]['ok'])) {
                $sum['ok']++;
                continue; // already live there (retry of a partial post)
            }
            $label = isset($all[$w]['label']) ? $all[$w]['label'] : $w;
            if (empty($connected[$w])) {
                $results[$w] = array('ok' => FALSE, 'error' => 'Listing not connected on the Google reviews page.');
                $sum['failed']++;
                $sum['messages'][] = $label.': not connected';
                continue;
            }
            $payload = array(
                'languageCode' => 'en',
                'summary'      => $post['summary'],
                'topicType'    => 'STANDARD',
            );
            if ($post['cta_type'] === 'CALL') {
                $payload['callToAction'] = array('actionType' => 'CALL');
            } elseif (in_array($post['cta_type'], array('BOOK', 'LEARN_MORE'), TRUE)) {
                $payload['callToAction'] = array('actionType' => $post['cta_type'], 'url' => $this->button_url($w, $post['id']));
            }
            if (!empty($post['has_image'])) {
                $payload['media'] = array(array('mediaFormat' => 'PHOTO', 'sourceUrl' => $this->image_url($post)));
            }
            $res = $this->CI->gbp_api->create_post($connected[$w], $payload);
            if ($res !== NULL) {
                $results[$w] = array('ok' => TRUE, 'url' => isset($res['searchUrl']) ? $res['searchUrl'] : '', 'name' => isset($res['name']) ? $res['name'] : '', 'state' => isset($res['state']) ? $res['state'] : '');
                $sum['ok']++;
            } else {
                $results[$w] = array('ok' => FALSE, 'error' => mb_substr($this->CI->gbp_api->error, 0, 250));
                $sum['failed']++;
                $sum['messages'][] = $label.': '.$this->CI->gbp_api->error;
            }
        }
        $status = $sum['failed'] === 0 ? 'published' : ($sum['ok'] > 0 ? 'partial' : 'failed');
        $this->CI->gbp_post_model->update($id, array(
            'status'       => $status,
            'results'      => json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'published_at' => $sum['ok'] > 0 ? date('Y-m-d H:i:s') : NULL,
        ));
        return $sum;
    }

    /** Daily cron: publish everything scheduled for today or earlier. */
    public function publish_due()
    {
        $n = 0; $f = 0;
        foreach ($this->CI->gbp_post_model->due(10) as $p) {
            $s = $this->publish((int) $p['id']);
            $n += $s['ok'];
            $f += $s['failed'];
        }
        return array('published' => $n, 'failed' => $f);
    }
}
