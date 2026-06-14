<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Registered booking app users (online accounts). */
class Online_accounts extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('user_model', 'booking_model', 'vehicle_model'));
    }

    public function index()
    {
        $this->require_perm('customers.view');
        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;
        $q       = $this->input->get('q');

        $result = $this->user_model->paginate($perPage, $offset, $q);

        $this->render('admin/online_accounts/index', array(
            'title' => 'Online accounts',
            'rows'  => $result['rows'],
            'total' => $result['total'],
            'page'  => $page,
            'pages' => max(1, (int) ceil($result['total'] / $perPage)),
            'q'     => $q,
        ));
    }

    public function view($id)
    {
        $this->require_perm('customers.view');
        $user = $this->user_model->find($id);
        if (!$user) { show_404(); }
        unset($user['password_hash']);

        $this->render('admin/online_accounts/view', array(
            'title'     => $user['name'],
            'user'      => $user,
            'bookings'  => $this->booking_model->for_user($id, 50, 0),
            'vehicles'  => $this->vehicle_model->for_user($id, TRUE),
        ));
    }
}
