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
        // ?edit={id}: load a post that is not live yet into the form.
        $editing = NULL;
        $edit_id = (int) $this->input->get('edit');
        if ($edit_id > 0 && crm_can('campaigns.edit')) {
            $row = $this->gbp_post_model->find($edit_id);
            if ($row && in_array($row['status'], array('draft', 'scheduled', 'failed'), TRUE)) {
                $editing = $row;
            }
        }
        // Status tabs + 15 per page. Default tab: drafts while there are any.
        $tabs = $this->gbp_post_model->tab_counts();
        $tab = (string) $this->input->get('tab');
        if (!in_array($tab, array('draft', 'scheduled', 'live', 'failed', 'all'), TRUE)) {
            $tab = $tabs['draft'] ? 'draft' : 'all';
        }
        $per = 15;
        $page = max(1, (int) $this->input->get('p'));
        $res = $this->gbp_post_model->page($tab, $per, ($page - 1) * $per);
        if ($res['total'] && ($page - 1) * $per >= $res['total']) {
            $page = (int) ceil($res['total'] / $per);
            $res = $this->gbp_post_model->page($tab, $per, ($page - 1) * $per);
        }
        $this->render('admin/gbp_posts/index', array(
            'title'     => 'Google posts',
            'posts'     => $res['rows'],
            'tab'       => $tab,
            'tabs'      => $tabs,
            'pager'     => array('page' => $page, 'pages' => (int) ceil($res['total'] / $per), 'total' => $res['total'], 'per' => $per, 'param' => 'p', 'anchor' => 'posts', 'path' => 'google-posts', 'noun' => 'posts'),
            'workshops' => ymo_review_workshops(),
            'connected' => $this->gbp_post_service->connected_workshops(),
            'google_on' => (bool) $this->gbp_review_model->setting('gbp_oauth_token', NULL),
            'last'      => $this->gbp_post_model->last_published_on(),
            'drafts'    => $tabs['draft'],
            'editing'   => $editing,
            'can_edit'  => crm_can('campaigns.edit'),
            'max_text'  => Gbp_post_service::MAX_TEXT,
        ));
    }

    /**
     * POST google-posts/save — new post, or changes to a draft/scheduled/failed one (post_id).
     * when: draft (keep, never auto-posts) | now | date (auto-posts with the 9 AM run).
     */
    public function save()
    {
        $this->require_perm('campaigns.edit');
        $this->_post_only();
        $post_id = (int) $this->input->post('post_id');
        $existing = NULL;
        if ($post_id > 0) {
            $existing = $this->gbp_post_model->find($post_id);
            if (!$existing || !in_array($existing['status'], array('draft', 'scheduled', 'failed'), TRUE)) {
                $this->flash('error', 'That post is already live, so it can no longer be edited here.');
                redirect(admin_url('google-posts'));
            }
        }
        $back = admin_url('google-posts'.($existing ? '?edit='.$post_id : ''));
        $summary = trim(str_replace("\r\n", "\n", (string) $this->input->post('summary')));
        $workshops = array_values(array_intersect((array) $this->input->post('workshops'), array_keys(ymo_review_workshops())));
        $cta = (string) $this->input->post('cta_type');
        $cta = in_array($cta, array('CALL', 'BOOK', 'LEARN_MORE', 'NONE'), TRUE) ? $cta : 'CALL';
        $when = (string) $this->input->post('when');
        $when = in_array($when, array('draft', 'now', 'date'), TRUE) ? $when : 'now';
        $date = (string) $this->input->post('publish_on');
        $remove_photo = (bool) $this->input->post('remove_photo');

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
            $this->session->set_flashdata('gbp_post_draft', array('summary' => $summary, 'workshops' => $workshops, 'cta_type' => $cta, 'publish_on' => $date, 'when' => $when));
            $this->flash('error', implode(' ', $errors));
            redirect($back);
        }

        $fields = array(
            'summary'    => $summary,
            'cta_type'   => $cta,
            'workshops'  => implode(',', $workshops),
            'status'     => $when === 'draft' ? 'draft' : 'scheduled',
            'publish_on' => $when === 'date' ? $date : date('Y-m-d'),
        );
        // New photo: R2 public bucket; the database copy is only the no-R2 fallback.
        if ($image !== NULL) {
            $hash = substr(sha1($image), 0, 12);
            $stored = ymo_media_put_bytes('public', 'gbp/'.date('Y/m').'/'.$hash.'-'.bin2hex(random_bytes(3)).'.jpg', $image, 'image/jpeg');
            $fields['image_hash'] = $hash;
            $fields['image_path'] = $stored;
            $fields['image'] = $stored !== NULL ? NULL : $image;
        } elseif ($remove_photo && $existing) {
            $fields['image_hash'] = '';
            $fields['image_path'] = NULL;
            $fields['image'] = NULL;
        }
        if ($existing && (isset($fields['image_path']) || $remove_photo) && !empty($existing['image_path']) && strpos($existing['image_path'], 'r2/') === 0) {
            ymo_media_delete($existing['image_path']);
        }

        if ($existing) {
            $fields['results'] = NULL;
            $this->gbp_post_model->update($post_id, $fields);
            $id = $post_id;
            $this->audit->log('admin', $this->admin['id'], 'gbp_post.update', 'gbp_post', $id, array('workshops' => $workshops, 'when' => $when === 'date' ? $date : $when));
        } else {
            $fields += array('image' => NULL, 'image_hash' => '', 'image_path' => NULL);
            $fields['created_by'] = (int) $this->admin['id'];
            $id = $this->gbp_post_model->create($fields);
            $this->audit->log('admin', $this->admin['id'], 'gbp_post.create', 'gbp_post', $id, array('workshops' => $workshops, 'when' => $when === 'date' ? $date : $when));
        }

        if ($when === 'now') {
            $s = $this->gbp_post_service->publish($id);
            $this->flash($s['failed'] ? ($s['ok'] ? 'warning' : 'error') : 'success',
                $s['ok'] ? 'Posted to '.$s['ok'].' listing(s).'.($s['failed'] ? ' Failed: '.implode('; ', $s['messages']) : '')
                    : 'Not posted: '.implode('; ', $s['messages']));
        } elseif ($when === 'date') {
            $this->flash('success', 'Scheduled for '.date('D d M', strtotime($date)).'. It goes live with the 9 AM daily run.');
        } else {
            $this->flash('success', 'Saved as a draft. It stays here until you publish it.');
        }
        redirect(admin_url('google-posts'));
    }

    /** POST google-posts/{id}/publish — publish a draft or scheduled post now, or retry a failed one. */
    public function publish($id)
    {
        $this->require_perm('campaigns.edit');
        $this->_post_only();
        $s = $this->gbp_post_service->publish((int) $id);
        $this->flash($s['failed'] ? ($s['ok'] ? 'warning' : 'error') : 'success',
            $s['ok'] ? 'Posted to '.$s['ok'].' listing(s).'.($s['failed'] ? ' Failed: '.implode('; ', $s['messages']) : '')
                : 'Not posted: '.implode('; ', $s['messages']));
        $this->_back('google-posts', 'posts');
    }

    /** POST google-posts/{id}/delete — only posts that are not live anywhere. */
    public function delete($id)
    {
        $this->require_perm('campaigns.edit');
        $this->_post_only();
        $ok = $this->gbp_post_model->delete((int) $id);
        $this->flash($ok ? 'success' : 'error', $ok ? 'Post removed.' : 'Only drafts, scheduled or failed posts can be removed here. Live posts are removed on Google.');
        $this->_back('google-posts', 'posts');
    }

    protected function _post_only()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
    }

    /**
     * Back to the list the action came from (same tab, page and filters), else the plain page.
     */
    protected function _back($path, $anchor = '')
    {
        $ref = (string) $this->input->server('HTTP_REFERER');
        $base = admin_url($path);
        $url = $base;
        if ($ref !== '' && strpos($ref, $base) === 0) {
            $url = preg_replace('/#.*$/', '', $ref);
            $parts = parse_url($url);
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $qs);
                unset($qs['edit'], $qs['r']);
                $url = $base.($qs ? '?'.http_build_query($qs) : '');
            }
        }
        redirect($url.($anchor !== '' ? '#'.$anchor : ''));
    }
}
