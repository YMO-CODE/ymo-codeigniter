<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loads the ready-made Google post drafts from config/gbp_post_drafts.php.
 *
 *   php index.php cli/gbp_drafts load
 *
 * Saved as drafts: nothing is posted until you press "Publish now" on the
 * Google posts page. Safe to run again: a draft with the same text is skipped.
 */
class Gbp_drafts extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        $this->load->model('gbp_post_model');
        $this->load->helper('review');
        $this->config->load('gbp_post_drafts', TRUE);
    }

    public function index()
    {
        $this->load();
    }

    public function load()
    {
        $this->gbp_post_model->ensure_schema();
        $drafts = (array) $this->config->item('gbp_post_drafts', 'gbp_post_drafts');
        $known = array_keys(ymo_review_workshops());
        $added = 0;
        $skipped = 0;
        foreach ($drafts as $i => $d) {
            $summary = trim((string) $d['summary']);
            $ws = array_values(array_intersect(array_map('trim', explode(',', (string) $d['workshops'])), $known));
            if ($summary === '' || !$ws) {
                echo 'Draft '.($i + 1).": missing text or unknown workshop, skipped\n";
                $skipped++;
                continue;
            }
            if (preg_match('/(?:\+?91[\s-]*)?[6-9](?:[\s-]*\d){9}/', $summary)) {
                echo 'Draft '.($i + 1).": contains a phone number, skipped\n";
                $skipped++;
                continue;
            }
            if ($this->gbp_post_model->exists_with_summary($summary)) {
                $skipped++;
                continue;
            }
            $photo = isset($d['photo']) ? ltrim((string) $d['photo'], '/') : '';
            if ($photo !== '' && !is_file(rtrim(FCPATH, '/\\').'/'.$photo)) {
                echo 'Draft '.($i + 1).': photo '.$photo." not found, saved without it\n";
                $photo = '';
            }
            $this->gbp_post_model->create(array(
                'summary'    => $summary,
                'cta_type'   => in_array($d['cta'], array('CALL', 'BOOK', 'LEARN_MORE', 'NONE'), TRUE) ? $d['cta'] : 'CALL',
                'workshops'  => implode(',', $ws),
                'image'      => NULL,
                'image_hash' => $photo !== '' ? substr(sha1($photo), 0, 12) : '',
                'image_path' => $photo !== '' ? $photo : NULL,
                'status'     => 'draft',
                'publish_on' => date('Y-m-d'),
                'created_by' => NULL,
            ));
            $added++;
        }
        echo 'Added '.$added.' draft(s)'.($skipped ? ', skipped '.$skipped.' (already there or invalid)' : '').".\n";
        echo 'Drafts waiting: '.$this->gbp_post_model->count_by_status('draft').". Publish them from Google posts in the CRM.\n";
    }
}
