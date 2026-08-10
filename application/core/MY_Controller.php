<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for the booking app. Provides:
 *   - render($view, $data, $layout) — wraps the view in a chrome layout
 *   - flash($type, $msg)            — short helper for session flash messages
 *   - $this->user                   — currently signed-in customer (or NULL)
 *
 * Authenticated areas extend Customer_Controller; admin areas extend
 * Admin_Controller — both enforce auth in their constructors.
 */
class MY_Controller extends CI_Controller
{
    /** @var array|null Cached customer session row, or NULL if signed out. */
    public $user;

    public function __construct()
    {
        parent::__construct();
        ymo_load_db_settings();
        $this->user = $this->session->userdata('user') ?: NULL;
    }

    /**
     * Render a view inside the public layout.
     *
     * @param string $view   View name (e.g. 'auth/login')
     * @param array  $data   Data passed to the view
     * @param string $layout Layout to wrap with (defaults to 'layout/main')
     */
    protected function render($view, array $data = array(), $layout = 'layout/main')
    {
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view($layout, $data);
    }

    /**
     * Set a flash message that survives one redirect.
     *
     * @param string $type One of: success, error, warning, info
     * @param string $msg
     */
    protected function flash($type, $msg)
    {
        $this->session->set_flashdata($type, $msg);
    }
}

/**
 * Base controller for routes that REQUIRE a signed-in customer.
 */
class Customer_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (empty($this->user) || empty($this->user['id'])) {
            $this->flash('error', 'Please sign in to continue.');
            redirect(site_url('login?next='.urlencode(uri_string())));
        }
        if (!ymo_user_is_verified($this->user)) {
            $this->flash('warning', 'Please verify your mobile to continue.');
            redirect(site_url('signup/verify'));
        }
    }
}

/**
 * Base controller for /admin routes. Requires an active admin session.
 */
class Admin_Controller extends CI_Controller
{
    /** @var array|null */
    public $admin;

    public function __construct()
    {
        parent::__construct();
        ymo_load_db_settings();
        $this->admin = $this->session->userdata('admin') ?: NULL;
        if (empty($this->admin) || empty($this->admin['id'])) {
            redirect(admin_url('login'));
        }
        if (function_exists('crm_refresh_permissions')) {
            crm_refresh_permissions();
            $this->admin = $this->session->userdata('admin') ?: $this->admin;
        }
    }

    protected function render($view, array $data = array(), $layout = 'layout/admin')
    {
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view($layout, $data);
    }

    protected function flash($type, $msg)
    {
        $this->session->set_flashdata($type, $msg);
    }

    /** @param string $perm_key e.g. bookings.view */
    protected function require_perm($perm_key)
    {
        if (!$this->db->table_exists('crm_permissions')) {
            return;
        }
        if (!function_exists('crm_can') || !crm_can($perm_key)) {
            show_error('You do not have permission to access this area.', 403, 'Forbidden');
        }
    }
}

/**
 * Public marketing site (www host). No customer auth; links out to booking subdomain.
 */
class Marketing_Controller extends MY_Controller
{
    /** @var array SEO + content defaults for the active page */
    protected $page_meta = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('marketing');
    }

    protected function render_marketing($view, array $data = array())
    {
        if ($this->input->method(TRUE) === 'GET' && empty($data['city_hint'])) {
            if ($this->output->cache(15)) {
                return;
            }
        }
        $this->output->set_header('Cache-Control: public, max-age=0, s-maxage=300, stale-while-revalidate=60');
        $data = array_merge($this->page_meta, $data);
        if (empty($data['canonical_path'])) {
            $data['canonical_path'] = trim($this->uri->uri_string(), '/');
        }
        $page_for_og = (isset($data['page']) && is_array($data['page'])) ? $data['page'] : $data;
        $resolved_og = marketing_resolve_og_image($data['canonical_path'], $page_for_og);
        $data['og_image'] = $resolved_og;
        if (isset($data['page']) && is_array($data['page'])) {
            $data['page']['og_image'] = $resolved_og;
        }
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('layout/marketing', $data);
    }
}

/**
 * Base controller for CRM admin modules. Ensures CRM tables exist.
 */
class Crm_Controller extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->db->table_exists('crm_leads')) {
            show_error(
                'CRM tables are not installed. Run database/crm_migration_v1.sql on your database.',
                503,
                'CRM not installed'
            );
        }
    }
}
