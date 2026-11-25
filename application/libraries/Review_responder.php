<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Writes the public owner reply for a Google review and, for 4★ reviews,
 * works out why a star was lost.
 *
 * If an Anthropic API key is set (env ANTHROPIC_API_KEY or setting
 * anthropic_api_key) Claude writes the reply and the reason; otherwise a
 * built-in template + keyword rules are used, so it always works.
 */
class Review_responder
{
    const MODEL = 'claude-haiku-5-5';

    /** Staff names customers often mention; thanked by name in replies. */
    protected $staff = array('Vaibhav', 'Sunil', 'Shanu', 'Ashish', 'Rahul', 'Sachin');

    protected $services = array(
        'ac'         => array('/\b(a\.?c\.?|air ?con\w*|cooling)\b/i', 'AC service'),
        'denting'    => array('/\b(dent\w*|paint\w*|scratch\w*|body ?shop|bumper)\b/i', 'denting and painting work'),
        'polish'     => array('/\b(polish\w*|rubbing|ceramic|coating|detailing)\b/i', 'detailing work'),
        'brake'      => array('/\bbrake\w*\b/i', 'brake work'),
        'clutch'     => array('/\bclutch\w*\b/i', 'clutch work'),
        'suspension' => array('/\b(suspension|shock\w*|steering)\b/i', 'suspension work'),
        'battery'    => array('/\bbatter\w*\b/i', 'battery work'),
        'engine'     => array('/\b(engine|noise|vibration|leak\w*)\b/i', 'repair'),
        'electrical' => array('/\b(electric\w*|wiring|sensor\w*)\b/i', 'electrical repair'),
        'wheel'      => array('/\b(alignment|balancing|tyre\w*|tire\w*)\b/i', 'wheel alignment'),
        'service'    => array('/\b(servic\w*|oil change|maintenance)\b/i', 'car service'),
    );

    protected $CI;
    public $used_ai = FALSE;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * @param array  $review  author, stars, comment, review_id
     * @param string $area    e.g. "our Hinjewadi workshop"
     * @return array{reply:string, reason:string}
     */
    public function respond(array $review, $area)
    {
        $this->used_ai = FALSE;
        $ai = $this->_claude($review, $area);
        if ($ai) {
            $this->used_ai = TRUE;
            return $ai;
        }
        return array(
            'reply'  => $this->template_reply($review, $area),
            'reason' => (int) $review['stars'] < 5 ? $this->reason($review) : '',
        );
    }

    // --- built-in replies ---------------------------------------------------------------

    public function template_reply(array $review, $area)
    {
        $name = self::first_name($review['author']);
        $hi = $name !== '' ? ', '.$name : '';
        $text = (string) $review['comment'];
        $stars = (int) $review['stars'];
        $seed = crc32((string) (isset($review['review_id']) ? $review['review_id'] : $review['author']));

        $svc = '';
        foreach ($this->services as $s) {
            if ($text !== '' && preg_match($s[0], $text)) {
                $svc = $s[1];
                break;
            }
        }
        $staff = '';
        foreach ($this->staff as $p) {
            if (stripos($text, $p) !== FALSE) {
                $staff = $p;
                break;
            }
        }

        if ($stars >= 5) {
            $open = array('Thank you'.$hi.'!', 'Thanks a lot'.$hi.'!', 'Thank you so much'.$hi.'!');
            $mid = array();
            if ($svc !== '') {
                $mid[] = 'Glad the '.$svc.' at '.$area.' went well.';
            } elseif ($text !== '') {
                $mid[] = 'We are really glad you had a good experience at '.$area.'.';
            } else {
                $mid[] = 'Glad you were happy with the work at '.$area.'.';
            }
            if ($staff !== '') {
                $mid[] = 'We have passed your kind words on to '.$staff.'.';
            } elseif (preg_match('/whats ?app|update|photo|video|transparen/i', $text)) {
                $mid[] = 'Happy the WhatsApp photo and video updates helped.';
            }
            $close = array('See you at your next service.', 'Drive safe, and see you next time.', 'We look forward to looking after your car again.');
            return $open[$seed % 3].' '.implode(' ', $mid).' '.$close[($seed >> 3) % 3].' – Team YMO';
        }

        // 4 stars: thank them and invite the missing-star feedback.
        $parts = array('Thank you for the 4 stars'.$hi.'!');
        if ($svc !== '') {
            $parts[] = 'Glad the '.$svc.' at '.$area.' went well.';
        } else {
            $parts[] = 'Glad you chose '.$area.'.';
        }
        if ($staff !== '') {
            $parts[] = 'We will pass your note on to '.$staff.'.';
        }
        $parts[] = 'We would love to know what would have made it 5 stars – please call or WhatsApp us on 75587 83868.';
        return implode(' ', $parts).' – Team YMO';
    }

    /** Why a review is not 5★, in one or two plain sentences (keyword rules). */
    public function reason(array $review)
    {
        $text = trim((string) $review['comment']);
        if ($text === '') {
            return 'No comment left, so they did not say why. Worth a call to ask.';
        }
        $cues = '/\b(but|however|though|although|except|only thing|only issue|delay\w*|late|slow|took (too )?long\w*|longer|wait\w*|time|costly|expensive|pric\w*|charge\w*|bill\w*|improv\w*|could (have|be)|should|issue|problem|not|didn\'?t|did not|no |dirty|scratch\w*|rude|communicat\w*|call\w* back|pick ?up|drop)\b/i';
        $hits = array();
        foreach (preg_split('/(?<=[.!?])\s+|\n+/', $text) as $s) {
            $s = trim($s);
            if ($s !== '' && preg_match($cues, $s)) {
                $hits[] = $s;
            }
        }
        if (!$hits) {
            return 'The text is all positive and names no problem. Worth a call to ask what would make it 5★.';
        }
        $topic = array();
        $map = array(
            'Time / delay'      => '/\b(delay\w*|late|slow|took (too )?long\w*|longer|wait\w*|time)\b/i',
            'Price / bill'      => '/\b(costly|expensive|pric\w*|charge\w*|bill\w*)\b/i',
            'Communication'     => '/\b(communicat\w*|call\w* back|update\w*|inform\w*|rude)\b/i',
            'Pick-up / drop'    => '/\b(pick ?up|drop)\b/i',
            'Work quality'      => '/\b(issue|problem|again|not fixed|scratch\w*|dirty|noise)\b/i',
        );
        foreach ($map as $label => $re) {
            if (preg_match($re, implode(' ', $hits))) {
                $topic[] = $label;
            }
        }
        $quote = mb_substr(implode(' ', array_slice($hits, 0, 2)), 0, 260);
        return ($topic ? implode(', ', $topic).': ' : '').'"'.$quote.'"';
    }

    public static function first_name($author)
    {
        $a = trim(preg_replace('/[^\p{L}\s.\'-]/u', ' ', (string) $author));
        $first = strtok($a, ' ');
        if ($first === FALSE || mb_strlen($first) < 2 || preg_match('/^(a|google|user)$/i', $first)) {
            return '';
        }
        return mb_convert_case(mb_strtolower($first), MB_CASE_TITLE);
    }

    // --- optional Claude --------------------------------------------------------------

    protected function _api_key()
    {
        $k = trim((string) getenv('ANTHROPIC_API_KEY'));
        if ($k === '') {
            $k = trim((string) $this->CI->config->item('anthropic_api_key'));
        }
        return $k;
    }

    protected function _claude(array $review, $area)
    {
        $key = $this->_api_key();
        if ($key === '' || !function_exists('curl_init')) {
            return NULL;
        }
        $stars = (int) $review['stars'];
        $prompt = "You reply to Google reviews for Your Mechanic Online (YMO), a multi-brand car service workshop in India. "
            ."Customers get live WhatsApp photo/video updates during service. Write the owner's public reply.\n\n"
            ."Rules: 1–3 short sentences, warm and specific to what they wrote, no emojis, no hashtags, no discounts or promises, "
            ."do not repeat their whole review, use their first name if it looks like a real name, mention '".$area."' only if natural, "
            ."sign off with ' – Team YMO'. ".($stars === 4 ? "It is a 4-star review: thank them and invite them to tell us what would have made it 5 stars by calling or WhatsApping 75587 83868. " : '')
            ."\n\nAlso give 'reason': ".($stars < 5 ? "in one short sentence, why the customer did not give 5 stars, based only on the text (if they did not say, say so)." : "an empty string.")
            ."\n\nReturn only JSON: {\"reply\": \"...\", \"reason\": \"...\"}\n\n"
            ."Reviewer: ".$review['author']."\nStars: ".$stars."\nReview: ".($review['comment'] !== '' ? $review['comment'] : '(no text)');

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => array('content-type: application/json', 'x-api-key: '.$key, 'anthropic-version: 2023-06-01'),
            CURLOPT_POSTFIELDS     => json_encode(array(
                'model'      => self::MODEL,
                'max_tokens' => 400,
                'messages'   => array(array('role' => 'user', 'content' => $prompt)),
            )),
        ));
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode((string) $raw, TRUE);
        if ($http !== 200 || empty($data['content'][0]['text'])) {
            log_message('error', '[review_responder] Claude HTTP '.$http.': '.substr((string) $raw, 0, 200));
            return NULL;
        }
        $txt = $data['content'][0]['text'];
        if (!preg_match('/\{.*\}/s', $txt, $m)) {
            return NULL;
        }
        $j = json_decode($m[0], TRUE);
        if (!is_array($j) || empty($j['reply'])) {
            return NULL;
        }
        $reply = trim((string) $j['reply']);
        if (mb_strlen($reply) > 900) {
            return NULL;
        }
        return array('reply' => $reply, 'reason' => trim((string) (isset($j['reason']) ? $j['reason'] : '')));
    }
}
