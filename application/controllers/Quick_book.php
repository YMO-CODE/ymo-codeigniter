<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Guest quick-book form — no signup/login required.
 * Submissions become CRM leads (source: landing) for team follow-up.
 *
 * Routes:
 *   GET/POST /quick-book        Quick_book::index
 *   GET      /quick-book/thanks Quick_book::thanks
 */
class Quick_book extends MY_Controller
{
    /** Sentinel value for the customised-package dropdown option. */
    const PACKAGE_CUSTOM = 'custom';

    /** @var string[] UTM query params persisted through form POST */
    protected static $UTM_KEYS = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('package_model', 'vehicle_model'));
    }

    public function index()
    {
        if ($this->input->method() === 'post') {
            return $this->_submit();
        }

        return $this->_show_form();
    }

    public function thanks()
    {
        $this->render('bookings/quick_book_thanks', array(
            'title' => 'Request received',
            'phone' => $this->config->item('ymo_support_phone'),
        ));
    }

    protected function _show_form()
    {
        $prefill_package_id = 0;
        $package_slug = trim((string) $this->input->get('package'));
        if ($package_slug !== '') {
            $pkg = $this->package_model->find_by_slug($package_slug);
            if ($pkg) {
                $prefill_package_id = (int) $pkg['id'];
            }
        }

        $this->render('bookings/quick_book', array(
            'title'              => 'Quick book',
            'packages'           => $this->package_model->list_active_with_features(),
            'makes'              => $this->vehicle_model->makes(),
            'cities'             => (array) $this->config->item('ymo_service_cities'),
            'prefill_package_id' => $prefill_package_id,
            'utm'                => $this->_capture_utm(),
            'min_date'           => date('Y-m-d'),
        ));
    }

    protected function _submit()
    {
        $this->_set_validation_rules();

        if (!$this->form_validation->run()) {
            $prefill_package_id = (int) $this->input->post('package_id');
            $this->render('bookings/quick_book', array(
                'title'              => 'Quick book',
                'packages'           => $this->package_model->list_active_with_features(),
                'makes'              => $this->vehicle_model->makes(),
                'cities'             => (array) $this->config->item('ymo_service_cities'),
                'prefill_package_id' => $prefill_package_id,
                'utm'                => $this->_capture_utm_from_post(),
                'min_date'           => date('Y-m-d'),
            ));
            return;
        }

        $payload = $this->_collect_payload();
        $lead_id = $this->_store_lead($payload);

        if ($lead_id) {
            $this->flash('success', 'Thanks - we received your request and will call you shortly.');
        } else {
            log_message('error', 'Quick book form could not be saved to crm_leads.');
            $this->flash('error', 'We could not save your request right now. Please call us directly and we will help you.');
        }

        redirect(site_url('quick-book/thanks'));
    }

    protected function _set_validation_rules()
    {
        $this->form_validation->set_rules('name',     'Name',     'trim|required|min_length[2]|max_length[120]');
        $this->form_validation->set_rules('mobile',   'Mobile',   'trim|required|regex_match[/^[6-9]\d{9}$/]');
        $this->form_validation->set_rules('email',    'Email',    'trim|valid_email|max_length[180]');
        $this->form_validation->set_rules('area',     'Area',     'trim|required|max_length[120]');
        $allowed_cities = (array) $this->config->item('ymo_service_cities');
        $this->form_validation->set_rules('city', 'City',
            'trim|required|max_length[80]|in_list['.implode(',', $allowed_cities).']',
            array('in_list' => 'Please pick one of our serviceable cities.')
        );
        $this->form_validation->set_rules('package_id', 'Service package', 'trim|required|callback__valid_package');
        $this->form_validation->set_rules('custom_package', 'Custom service details', 'trim|max_length[500]|callback__valid_custom_package');
        $this->form_validation->set_rules('make_id',      'Car make',        'trim|required|integer|callback__valid_make');
        $this->form_validation->set_rules('variant',      'Car model',       'trim|required|min_length[2]|max_length[120]');
        $this->form_validation->set_rules('vehicle_number', 'Vehicle number', 'trim|max_length[20]|callback__valid_plate_optional');
        $this->form_validation->set_rules('preferred_date', 'Preferred date', 'trim|callback__valid_future_date');
        $this->form_validation->set_rules('remarks',        'Remarks',        'trim|max_length[2000]');
        $this->form_validation->set_rules('terms',          'Terms',          'required');
    }

    public function _valid_package($value)
    {
        if ($value === self::PACKAGE_CUSTOM) {
            return TRUE;
        }
        $pkg = $this->package_model->find((int) $value);
        if (!$pkg || empty($pkg['is_active'])) {
            $this->form_validation->set_message('_valid_package', 'Pick a valid service package.');
            return FALSE;
        }
        return TRUE;
    }

    public function _valid_custom_package($value)
    {
        if ($this->input->post('package_id') !== self::PACKAGE_CUSTOM) {
            return TRUE;
        }
        if (trim((string) $value) === '') {
            $this->form_validation->set_message('_valid_custom_package', 'Describe the customised service you need.');
            return FALSE;
        }
        return TRUE;
    }

    public function _valid_make($value)
    {
        $make_id = (int) $value;
        foreach ($this->vehicle_model->makes() as $m) {
            if ((int) $m['id'] === $make_id) {
                return TRUE;
            }
        }
        $this->form_validation->set_message('_valid_make', 'Pick a valid car make.');
        return FALSE;
    }

    public function _valid_plate_optional($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return TRUE;
        }
        $clean = strtoupper(preg_replace('/[\s\-]/', '', $value));
        if (!preg_match('/^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{4}$/', $clean)) {
            $this->form_validation->set_message('_valid_plate_optional', 'Enter a valid Indian vehicle number (e.g. MH12AB1234).');
            return FALSE;
        }
        $_POST['vehicle_number'] = $clean;
        return TRUE;
    }

    public function _valid_future_date($value)
    {
        if (empty($value)) {
            return TRUE;
        }
        $ts = strtotime($value);
        if (!$ts || date('Y-m-d', $ts) < date('Y-m-d')) {
            $this->form_validation->set_message('_valid_future_date', 'Pick a date today or later.');
            return FALSE;
        }
        return TRUE;
    }

    protected function _collect_payload()
    {
        $package_id_raw = trim((string) $this->input->post('package_id'));
        $is_custom      = ($package_id_raw === self::PACKAGE_CUSTOM);
        $package_id     = $is_custom ? 0 : (int) $package_id_raw;
        $make_id        = (int) $this->input->post('make_id');
        $package        = $is_custom ? NULL : $this->package_model->find($package_id);
        $custom_package = trim((string) $this->input->post('custom_package'));
        $make_name  = '';
        foreach ($this->vehicle_model->makes() as $m) {
            if ((int) $m['id'] === $make_id) {
                $make_name = $m['name'];
                break;
            }
        }

        $vehicle_number = trim((string) $this->input->post('vehicle_number'));
        $preferred_date = trim((string) $this->input->post('preferred_date'));
        $remarks        = trim((string) $this->input->post('remarks'));
        $email          = strtolower(trim((string) $this->input->post('email')));

        return array(
            'name'             => trim($this->input->post('name')),
            'mobile'           => trim($this->input->post('mobile')),
            'email'            => $email,
            'city'             => trim($this->input->post('city')),
            'area'             => trim($this->input->post('area')),
            'package_id'       => $is_custom ? NULL : $package_id,
            'package_name'     => $is_custom ? 'Customised package' : ($package ? $package['name'] : ''),
            'package_slug'     => $is_custom ? self::PACKAGE_CUSTOM : ($package ? $package['slug'] : ''),
            'custom_package'   => $is_custom ? $custom_package : '',
            'make_id'          => $make_id,
            'make_name'        => $make_name,
            'variant'          => trim($this->input->post('variant')),
            'vehicle_number'   => $vehicle_number,
            'preferred_date'   => $preferred_date !== '' ? $preferred_date : NULL,
            'remarks'          => $remarks,
            'utm'              => $this->_capture_utm_from_post(),
        );
    }

    protected function _build_message(array $payload)
    {
        $lines = array(
            'City: '.$payload['city'],
            'Area: '.$payload['area'],
            'Package: '.$payload['package_name'],
            'Vehicle: '.$payload['make_name'].' '.$payload['variant'],
        );
        if (!empty($payload['custom_package'])) {
            $lines[] = 'Custom service: '.$payload['custom_package'];
        }
        if (!empty($payload['vehicle_number'])) {
            $lines[] = 'Vehicle number: '.$payload['vehicle_number'];
        }
        if (!empty($payload['preferred_date'])) {
            $lines[] = 'Preferred date: '.$payload['preferred_date'];
        }
        if (!empty($payload['remarks'])) {
            $lines[] = 'Remarks: '.$payload['remarks'];
        }

        return implode("\n", $lines);
    }

    /**
     * @return int Lead id, or 0 when CRM is unavailable.
     */
    protected function _store_lead(array $payload)
    {
        if (!$this->db->table_exists('crm_leads')) {
            return 0;
        }

        $raw = $payload;
        if (!empty($payload['utm']) && is_array($payload['utm'])) {
            $raw['utm'] = $payload['utm'];
        }

        $this->load->model('crm_lead_model');
        $lead_id = $this->crm_lead_model->ingest('landing', array(
            'name'    => $payload['name'],
            'mobile'  => $payload['mobile'],
            'email'   => $payload['email'],
            'message' => $this->_build_message($payload),
            'raw'     => $raw,
        ), NULL, 'quick_book');

        if (!$lead_id) {
            return 0;
        }

        $this->load->model('crm_lead_activity_model');
        $this->crm_lead_activity_model->add(
            $lead_id,
            NULL,
            'note',
            'Quick book form submission',
            array('source' => 'quick_book')
        );

        crm_notify_admin_new_lead($lead_id, 'Quick book', array(
            'name'    => $payload['name'],
            'mobile'  => $payload['mobile'],
            'email'   => $payload['email'],
            'message' => $this->_build_message($payload),
        ));

        return $lead_id;
    }

    protected function _capture_utm()
    {
        $utm = array();
        foreach (self::$UTM_KEYS as $key) {
            $val = trim((string) $this->input->get($key));
            if ($val !== '') {
                $utm[$key] = $val;
            }
        }
        return $utm;
    }

    protected function _capture_utm_from_post()
    {
        $utm = array();
        foreach (self::$UTM_KEYS as $key) {
            $val = trim((string) $this->input->post($key));
            if ($val !== '') {
                $utm[$key] = $val;
            }
        }
        return $utm;
    }
}
