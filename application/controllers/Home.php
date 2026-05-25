<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{
    public function index()
    {
        $this->load->model('package_model');
        $packages = $this->package_model->list_active_with_features();

        $this->render('home/index', array(
            'title'     => 'Book your car service',
            'packages'  => $packages,
        ));
    }
}
