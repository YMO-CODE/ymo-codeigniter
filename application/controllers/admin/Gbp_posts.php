<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google posts: write once, publish to the chosen workshop listings now or on a date.
 */
class Gbp_posts extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('gbp_post_model', 'gbp_review_model'));
        $this->load->library(array('gbp_post_service', 'audit'));
        $this->load->helper('review');
    }

    public function index()
    {
        $this->require_perm('campaigns.view');
        $this->gbp_post_model->ensure_schema();
        $this->render('admin/gbp_posts/index', array(
            'title'     => 'Google posts',
            'posts'     => $this->gbp_post_model->recent(40),
            'workshops' => ymo_review_workshops(),
            'connected' => $this->gbp_post_service->connected_workshops(),
            'google_on' => (bool) $this->gbp_review_model->setting('gbp_oauth_token', NULL),
            'last'      => $this->gbp_post_model->last_published_on(),
            'can_edit'  => crm_can('campaigns.edit'),
            'max_text'  => Gbp_post_service::MAX_TEXT,
        ));
    }

    /** POST google-posts/save — new post (publish now or on a date). */
    public function save()
    {
        $this->require_perm('campaigns.edit');
        $this->_post_only();
        $summary = trim(str_replace("\r\n", "\n", (string) $this->input->post('summary')));
        $workshops = array_values(array_intersect((array) $this->input->post('workshops'), array_keys(ymo_review_workshops())));
        $cta = (string) $this->input->post('cta_type');
        $cta = in_array($cta, array('CALL', 'BOOK', 'LEARN_MORE', 'NONE'), TRUE) ? $cta : 'CALL';
        $when = (string) $this->input->post('when');
        $date = (string) $this->input->post('publish_on');

        $errors = array();
        if ($summary === '') {
            $errors[] = 'Write the post text.';
        } elseif (mb_strlen($summary) > Gbp_post_service::MAX_TEXT) {
            $errors[] = 'Text is longer than '.Gbp_post_service::MAX_TEXT.' characters.';
        }
        if (preg_match('/(?:\+?91[\s-]*)?[6-9](?:[\s-]*\d){9}/', $summary)) {
            $errors[] = 'Remove the phone number from the text: Google rejects posts with phone numbers. Use the "Call now" button instead.';
        }
        if (!$workshops) {
            $errors[] = 'Pick at least one workshop.';
        }
        if ($when === 'date' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d'))) {
            $errors[] = 'Pick today or a future date.';
        }

        $image = NULL;
        if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
            $image = $this->gbp_post_service->prepare_image($_FILES['photo']['tmp_name']);
            if ($image === NULL) {
                $errors[] = $this->gbp_post_service->error;
            }
        } elseif (!empty($_FILES['photo']['name']) && !empty($_FILES['photo']['error'])) {
            $errors[] = 'Photo upload failed (too big? max 8 MB).';
        }

        if ($errors) {
            $this->session->set_flashdata('gbp_post_draft', array('summary' => $summary, 'workshops' => $workshops, 'cta_type' => $cta, 'publish_on' => $date));
            $this->flash('error', implode(' ', $errors));
            redirect(admin_url('google-posts'));
        }

        // Photo goes to the R2 public bucket; the database copy is only the no-R2 fallback.
        $image_hash = $image !== NULL ? substr(sha1($image), 0, 12) : '';
        $image_path = NULL;
        if ($image !== NULL) {
            $image_path = ymo_media_put_bytes('public', 'gbp/'.date('Y/m').'/'.$image_hash.'-'.bin2hex(random_bytes(3)).'.jpg', $image, 'image/jpeg');
            if ($image_path !== NULL) {
                $image = NULL;
            }
        }
        $id = $this->gbp_post_model->create(array(
            'summary'    => $summary,
            'cta_type'   => $cta,
            'workshops'  => implode(',', $workshops),
            'image'      => $image,
            'image_hash' => $image_hash,
            'image_path' => $image_path,
            'status'     => 'scheduled',
            'publish_on' => $when === 'date' ? $date : date('Y-m-d'),
            'created_by' => (int) $this->admin['id'],
        ));
        $this->audit->log('admin', $this->admin['id'], 'gbp_post.create', 'gbp_post', $id, array('workshops' => $workshops, 'when' => $when === 'date' ? $date : 'now'));

        if ($when !== 'date') {
            $s = $this->gbp_post_service->publish($id);
            $this->flash($s['failed'] ? ($s['ok'] ? 'warning' : 'error') : 'success',
                $s['ok'] ? 'Posted to '.$s['ok'].' listing(s).'.($s['failed'] ? ' Failed: '.implode('; ', $s['messages']) : '')
                    : 'Not posted: '.implode('; ', $s['messages']));
        } else {
            $this->flash('success', 'Scheduled for '.date('D d M', strtotime($date)).'. It goes live with the 9 AM daily run.');
        }
        redirect(admin_url('google-posts'));
    }

    /** POST google-posts/{id}/publish — publish a scheduled post now, or retry a failed one. */
    public function publish($id)
    {
        $this->require_perm('campaigns.edit');
        $this->_post_only();
        $s = $this->gbp_post_service->publish((int) $id);
        $this->flash($s['failed'] ? ($s['ok'] ? 'warning' : 'error') : 'success',
            $s['ok'] ? 'Posted to '.$s['ok'].' listing(s).'.($s['failed'] ? ' Failed: '.implode('; ', $s['messages']) : '')
                : 'Not posted: '.implode('; ', $s['messages']));
        redirect(admin_url('google-posts'));
    }

    /** POST google-posts/{id}/delete — only posts that are not live anywhere. */
    public function delete($id)
    {
        $this->require_perm('campaigns.edit');
        $this->_post_only();
        $ok = $this->gbp_post_model->delete((int) $id);
        $this->flash($ok ? 'success' : 'error', $ok ? 'Post removed from the queue.' : 'Only scheduled or failed posts can be removed here. Live posts are removed on Google.');
        redirect(admin_url('google-posts'));
    }

    protected function _post_only()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
    }
}
