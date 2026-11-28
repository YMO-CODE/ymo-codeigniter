<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Job cards (work orders). Advisor screen: open from a booking or for a walk-in,
 * services + parts from the Catalogue, technicians, status flow, printable job card.
 * Permissions: bookings.view to see, bookings.edit to change.
 */
class Work_orders extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('crm_work_order_model', 'crm_catalog_model', 'admin_model', 'booking_model'));
        $this->load->helper('review');
        $this->load->library('audit');
    }

    public function index()
    {
        $this->require_perm('bookings.view');
        $this->crm_work_order_model->ensure_schema();
        $workshops = ymo_review_workshops();
        $ws = (string) $this->input->get('ws');
        if (!isset($workshops[$ws])) {
            $ws = '';
        }
        $tab = (string) $this->input->get('tab');
        if ($tab !== 'open' && $tab !== 'all' && !isset(Crm_work_order_model::STATUSES[$tab])) {
            $tab = 'open';
        }
        $q = trim((string) $this->input->get('q'));
        $per = 25;
        $page = max(1, (int) $this->input->get('p'));
        $res = $this->crm_work_order_model->page($tab, $ws, $q, $per, ($page - 1) * $per);
        if ($res['total'] && ($page - 1) * $per >= $res['total']) {
            $page = (int) ceil($res['total'] / $per);
            $res = $this->crm_work_order_model->page($tab, $ws, $q, $per, ($page - 1) * $per);
        }
        $this->render('admin/work_orders/index', array(
            'title'     => 'Job cards',
            'rows'      => $res['rows'],
            'tab'       => $tab,
            'ws'        => $ws,
            'q'         => $q,
            'counts'    => $this->crm_work_order_model->counts($ws),
            'workshops' => $workshops,
            'pager'     => array('page' => $page, 'pages' => (int) ceil($res['total'] / $per), 'total' => $res['total'], 'per' => $per, 'param' => 'p', 'anchor' => 'list', 'path' => 'job-cards', 'noun' => 'job cards'),
            'can_edit'  => crm_can('bookings.edit'),
        ));
    }

    /** GET form / POST create. ?booking_id= opens it from a booking. */
    public function create()
    {
        $this->require_perm('bookings.edit');
        $this->load->model(array('vehicle_model', 'user_model'));
        $this->load->library('crm_booking_service');
        $this->crm_work_order_model->ensure_schema();

        $booking_id = (int) ($this->input->post('booking_id') ?: $this->input->get('booking_id'));
        $booking = $booking_id ? $this->booking_model->find_detailed($booking_id) : NULL;
        if ($booking_id && !$booking) {
            show_404();
        }
        if ($booking) {
            $open = $this->crm_work_order_model->find_open_for_booking($booking_id);
            if ($open) {
                $this->flash('success', 'This booking already has job card '.$open['ro_number'].'.');
                redirect(admin_url('job-cards/'.(int) $open['id']));
            }
        }

        if ($this->input->method() === 'post') {
            return $this->_store($booking);
        }
        $this->render('admin/work_orders/create', array(
            'title'     => 'New job card',
            'booking'   => $booking,
            'workshops' => ymo_review_workshops(),
            'admins'    => $this->admin_model->list_active(),
            'makes'     => $this->vehicle_model->makes(),
            'me'        => (int) $this->admin['id'],
        ));
    }

    protected function _store($booking)
    {
        $back = admin_url('job-cards/new'.($booking ? '?booking_id='.(int) $booking['id'] : ''));
        if (!$booking) {
            $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|regex_match[/^[6-9]\d{9}$/]');
            $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        }
        $this->form_validation->set_rules('odometer_km', 'Odometer', 'trim|is_natural');
        $this->form_validation->set_rules('fuel_pct', 'Fuel', 'trim|is_natural|less_than_equal_to[100]');
        $this->form_validation->set_rules('complaint', 'Customer complaint', 'trim|max_length[3000]');
        if (!$this->form_validation->run()) {
            $this->flash('error', strip_tags(validation_errors(' ', ' ')) ?: 'Check the form.');
            redirect($back);
        }

        if ($booking) {
            $user_id = (int) $booking['user_id'];
            $vehicle_id = (int) $booking['vehicle_id'];
        } else {
            $mobile = $this->input->post('mobile');
            $user = $this->crm_booking_service->find_user_by_mobile($mobile);
            if (!$user) {
                $uid = $this->crm_booking_service->create_customer(
                    $this->input->post('name'), $mobile, $this->input->post('email'), '', ''
                );
                if (!$uid) {
                    $this->flash('error', $this->crm_booking_service->last_error());
                    redirect($back);
                }
                $user = $this->user_model->find($uid);
            }
            $user_id = (int) $user['id'];
            $vehicle_id = (int) $this->input->post('vehicle_id');
            if ($vehicle_id > 0) {
                if (!$this->vehicle_model->find_for_user($vehicle_id, $user_id)) {
                    $this->flash('error', 'That car does not belong to this customer.');
                    redirect($back);
                }
            } else {
                $number = strtoupper(preg_replace('/\s+/', '', (string) $this->input->post('vehicle_number')));
                $make_id = (int) $this->input->post('make_id');
                $variant = trim((string) $this->input->post('variant'));
                if (!$make_id || $variant === '' || $number === '') {
                    $this->flash('error', 'Add the car: make, model and registration number.');
                    redirect($back);
                }
                $vehicle_id = $this->vehicle_model->create(array(
                    'user_id' => $user_id, 'make_id' => $make_id, 'variant' => $variant, 'vehicle_number' => $number,
                ));
            }
        }

        $open = $this->crm_work_order_model->find_open_for_vehicle($vehicle_id);
        if ($open) {
            $this->flash('warning', 'This car already has an open job card, '.$open['ro_number'].'.');
            redirect(admin_url('job-cards/'.(int) $open['id']));
        }

        $workshops = ymo_review_workshops();
        $ws = (string) $this->input->post('workshop');
        $advisor = (int) $this->input->post('advisor_id');
        $id = $this->crm_work_order_model->create(array(
            'booking_id'           => $booking ? (int) $booking['id'] : NULL,
            'user_id'              => $user_id,
            'vehicle_id'           => $vehicle_id,
            'workshop'             => isset($workshops[$ws]) ? $ws : '',
            'booking_type'         => in_array($this->input->post('booking_type'), array('workshop', 'pickup', 'doorstep'), TRUE) ? $this->input->post('booking_type') : 'workshop',
            'odometer_km'          => $this->_int_or_null('odometer_km'),
            'fuel_pct'             => $this->_int_or_null('fuel_pct'),
            'advisor_id'           => $advisor > 0 ? $advisor : (int) $this->admin['id'],
            'complaint'            => trim((string) $this->input->post('complaint')) ?: NULL,
            'expected_delivery_at' => $this->_datetime_or_null('expected_delivery_at'),
        ), (int) $this->admin['id']);
        if (!$id) {
            $this->flash('error', 'Could not create the job card. Please try again.');
            redirect($back);
        }

        // From a booking: its package becomes the first service line.
        if ($booking && !empty($booking['package_name'])) {
            $snap = json_decode((string) $booking['package_snapshot'], TRUE) ?: array();
            $features = isset($snap['features']) && is_array($snap['features']) ? implode('; ', $snap['features']) : '';
            $this->crm_work_order_model->add_line($id, array(
                'type'          => 'service',
                'name'          => $booking['package_name'],
                'description'   => $features !== '' ? $features : NULL,
                'qty'           => 1,
                'rate'          => (float) ($snap['price'] ?? $booking['package_price']),
                'tax_pct'       => 0,
                'tax_inclusive' => 1,
            ), (int) $this->admin['id']);
            $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'booking', 'Opened from booking '.$booking['reference']);
        }
        $this->audit->log('admin', $this->admin['id'], 'work_order.create', 'work_order', $id, array('booking_id' => $booking ? (int) $booking['id'] : NULL));
        $wo = $this->crm_work_order_model->find($id);
        $this->flash('success', 'Job card '.$wo['ro_number'].' opened.');
        redirect(admin_url('job-cards/'.$id));
    }

    public function view($id)
    {
        $this->require_perm('bookings.view');
        $wo = $this->crm_work_order_model->find($id);
        if (!$wo) {
            show_404();
        }
        $this->render('admin/work_orders/view', array(
            'title'       => $wo['ro_number'],
            'wo'          => $wo,
            'lines'       => $this->crm_work_order_model->lines($id),
            'techs'       => $this->crm_work_order_model->technicians($id),
            'events'      => $this->crm_work_order_model->events($id, 50),
            'history'     => $this->crm_work_order_model->history_for_vehicle($wo['vehicle_id'], $id, 8),
            'admins'      => $this->admin_model->list_active(),
            'workshops'   => ymo_review_workshops(),
            'can_edit'    => crm_can('bookings.edit'),
        ));
    }

    /** POST: job details (workshop, odometer, advisor, technicians, bay, delivery, complaint, instructions, LPO). */
    public function update($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $wo = $this->_wo_or_404($id);
        $workshops = ymo_review_workshops();
        $ws = (string) $this->input->post('workshop');
        $advisor = (int) $this->input->post('advisor_id');
        $d = array(
            'workshop'             => isset($workshops[$ws]) ? $ws : $wo['workshop'],
            'booking_type'         => in_array($this->input->post('booking_type'), array('workshop', 'pickup', 'doorstep'), TRUE) ? $this->input->post('booking_type') : $wo['booking_type'],
            'odometer_km'          => $this->_int_or_null('odometer_km'),
            'fuel_pct'             => min(100, (int) $this->_int_or_null('fuel_pct')) ?: NULL,
            'advisor_id'           => $advisor > 0 ? $advisor : NULL,
            'bay'                  => mb_substr(trim((string) $this->input->post('bay')), 0, 20),
            'expected_delivery_at' => $this->_datetime_or_null('expected_delivery_at'),
            'complaint'            => trim((string) $this->input->post('complaint')) ?: NULL,
            'instructions'         => trim((string) $this->input->post('instructions')) ?: NULL,
            'lpo_number'           => mb_substr(trim((string) $this->input->post('lpo_number')), 0, 40),
            'lpo_date'             => $this->input->post('lpo_date') ? date('Y-m-d', strtotime($this->input->post('lpo_date'))) : NULL,
            'lpo_amount'           => $this->input->post('lpo_amount') !== '' && $this->input->post('lpo_amount') !== NULL ? round((float) $this->input->post('lpo_amount'), 2) : NULL,
        );
        if ($d['fuel_pct'] === NULL && $this->input->post('fuel_pct') === '0') {
            $d['fuel_pct'] = 0;
        }
        $changes = array();
        $labels = array('workshop' => 'Workshop', 'odometer_km' => 'Odometer', 'fuel_pct' => 'Fuel', 'advisor_id' => 'Advisor', 'bay' => 'Bay', 'expected_delivery_at' => 'Expected delivery', 'complaint' => 'Complaint', 'instructions' => 'Workshop instructions', 'lpo_number' => 'LPO');
        foreach ($labels as $k => $label) {
            if ((string) $wo[$k] !== (string) $d[$k] && !($k === 'expected_delivery_at' && substr((string) $wo[$k], 0, 16) === substr((string) $d[$k], 0, 16))) {
                $changes[] = $label;
            }
        }
        $this->crm_work_order_model->update($id, $d);

        $before = array_map('intval', array_column($this->crm_work_order_model->technicians($id), 'id'));
        $after = $this->crm_work_order_model->set_technicians($id, (array) $this->input->post('technicians'));
        sort($before);
        sort($after);
        if ($before !== $after) {
            $changes[] = 'Technicians';
        }
        if ($changes) {
            $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'details', 'Updated: '.implode(', ', $changes));
        }
        $this->flash('success', $changes ? 'Saved.' : 'Nothing changed.');
        redirect(admin_url('job-cards/'.$id).'#details');
    }

    /** POST: add a service or part line (from the Catalogue or typed). */
    public function line_add($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $wo = $this->_wo_or_404($id);
        $type = $this->input->post('type') === 'part' ? 'part' : 'service';
        $item = NULL;
        if ((int) $this->input->post('catalog_item_id') > 0) {
            $item = $this->crm_catalog_model->find((int) $this->input->post('catalog_item_id'));
        }
        $name = trim((string) $this->input->post('name')) ?: ($item['name'] ?? '');
        if ($name === '') {
            $this->flash('error', 'Pick an item from the catalogue or type a name.');
            redirect(admin_url('job-cards/'.$id).'#lines');
        }
        $rate = $this->input->post('rate');
        $tax = $this->input->post('tax_pct');
        $line = array(
            'type'            => $item ? $item['type'] : $type,
            'catalog_item_id' => $item ? (int) $item['id'] : NULL,
            'name'            => mb_substr($name, 0, 255),
            'description'     => $item && $item['description'] !== NULL && trim((string) $this->input->post('name')) === '' ? $item['description'] : NULL,
            'qty'             => max(0.01, (float) ($this->input->post('qty') ?: 1)),
            'rate'            => $rate !== NULL && $rate !== '' ? max(0, (float) $rate) : (float) ($item['rate'] ?? 0),
            'discount_pct'    => min(100, max(0, (float) $this->input->post('discount_pct'))),
            'tax_pct'         => $tax !== NULL && $tax !== '' ? max(0, (float) $tax) : (float) ($item['tax_pct'] ?? 0),
            'tax_inclusive'   => $this->input->post('tax_inclusive') !== NULL ? ($this->input->post('tax_inclusive') === '0' ? 0 : 1) : (int) ($item['tax_inclusive'] ?? 1),
            'technician_id'   => (int) $this->input->post('technician_id') ?: NULL,
        );
        $this->crm_work_order_model->add_line($id, $line, (int) $this->admin['id']);
        $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'line', 'Added '.$line['type'].': '.$line['name']);
        $this->flash('success', 'Added '.$line['name'].'.');
        redirect(admin_url('job-cards/'.$id).'#lines');
    }

    /** POST: change qty / rate / discount / tax / technician of a line. */
    public function line_update($id, $line_id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $this->_wo_or_404($id);
        $cur = $this->crm_work_order_model->find_line($id, $line_id);
        if (!$cur) {
            show_404();
        }
        $patch = array(
            'name'          => mb_substr(trim((string) $this->input->post('name')) ?: $cur['name'], 0, 255),
            'qty'           => max(0.01, (float) ($this->input->post('qty') ?: $cur['qty'])),
            'rate'          => max(0, (float) $this->input->post('rate')),
            'discount_pct'  => min(100, max(0, (float) $this->input->post('discount_pct'))),
            'tax_pct'       => max(0, (float) $this->input->post('tax_pct')),
            'tax_inclusive' => $this->input->post('tax_inclusive') === '0' ? 0 : 1,
            'technician_id' => (int) $this->input->post('technician_id') ?: NULL,
        );
        $this->crm_work_order_model->update_line($id, $line_id, $patch);
        $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'line', 'Changed '.$patch['name']);
        $this->flash('success', 'Saved '.$patch['name'].'.');
        redirect(admin_url('job-cards/'.$id).'#lines');
    }

    public function line_delete($id, $line_id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $this->_wo_or_404($id);
        $cur = $this->crm_work_order_model->find_line($id, $line_id);
        if ($cur) {
            $this->crm_work_order_model->delete_line($id, $line_id);
            $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'line', 'Removed '.$cur['type'].': '.$cur['name']);
            $this->flash('success', 'Removed '.$cur['name'].'.');
        }
        redirect(admin_url('job-cards/'.$id).'#lines');
    }

    /** POST: tick / untick a line as done (the advisor can do what technicians do). */
    public function line_done($id, $line_id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $this->_wo_or_404($id);
        $cur = $this->crm_work_order_model->find_line($id, $line_id);
        if ($cur) {
            $done = empty($cur['done_at']);
            $this->crm_work_order_model->set_line_done($id, $line_id, $done, (int) $this->admin['id']);
            $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'line', ($done ? 'Done: ' : 'Not done: ').$cur['name']);
        }
        redirect(admin_url('job-cards/'.$id).'#lines');
    }

    /** POST to=status. Moves the linked booking along too (in progress / completed). */
    public function status($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $wo = $this->_wo_or_404($id);
        $to = (string) $this->input->post('to');
        $note = mb_substr(trim((string) $this->input->post('note')), 0, 200);
        if ($to === 'delivered' && !$this->crm_work_order_model->lines($id)) {
            $this->flash('error', 'Add at least one service or part before delivering.');
            redirect(admin_url('job-cards/'.$id));
        }
        if (!$this->crm_work_order_model->set_status($id, $to, (int) $this->admin['id'], $note)) {
            $this->flash('error', 'That status change is not allowed from '.Crm_work_order_model::STATUSES[$wo['status']].'.');
            redirect(admin_url('job-cards/'.$id));
        }
        $msg = Crm_work_order_model::STATUSES[$to].'.';

        // Keep the booking in step: work started → In progress; delivered → Completed
        // (reminders, referral, customer notice and review request run as on the booking page).
        if (!empty($wo['booking_id'])) {
            $b_to = NULL;
            if (in_array($to, array('in_progress', 'on_hold', 'ready'), TRUE) && in_array($wo['booking_status'], array('pending', 'confirmed'), TRUE)) {
                $b_to = 'in_progress';
            } elseif ($to === 'delivered' && $wo['booking_status'] !== 'completed' && $wo['booking_status'] !== 'cancelled') {
                $b_to = 'completed';
            }
            if ($b_to) {
                $this->load->library('booking_status_service');
                list($ok, $notice) = $this->booking_status_service->change((int) $wo['booking_id'], $b_to, NULL, (int) $this->admin['id'], 'job_card');
                $msg .= ' Booking '.$wo['booking_reference'].' marked '.str_replace('_', ' ', $b_to).'.'.($notice !== '' ? ' '.$notice : '');
                $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'booking', 'Booking '.$wo['booking_reference'].' → '.str_replace('_', ' ', $b_to));
                if (!$ok) {
                    $this->flash('warning', $msg);
                    redirect(admin_url('job-cards/'.$id));
                }
            }
        }
        $this->audit->log('admin', $this->admin['id'], 'work_order.status', 'work_order', (int) $id, array('from' => $wo['status'], 'to' => $to));
        $this->flash('success', $msg);
        redirect(admin_url('job-cards/'.$id));
    }

    /** POST: remove a draft job card opened by mistake. */
    public function delete($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $wo = $this->_wo_or_404($id);
        if ($wo['status'] !== 'draft') {
            $this->flash('error', 'Only a draft job card can be removed. Use Returned for a car that left without work.');
            redirect(admin_url('job-cards/'.$id));
        }
        $this->crm_work_order_model->soft_delete($id);
        $this->crm_work_order_model->log($id, (int) $this->admin['id'], 'deleted', 'Draft removed');
        $this->audit->log('admin', $this->admin['id'], 'work_order.delete', 'work_order', (int) $id, array('ro' => $wo['ro_number']));
        $this->flash('success', $wo['ro_number'].' removed.');
        redirect(admin_url('job-cards'));
    }

    /** Printable job card (A4) for the workshop file / customer acknowledgement. */
    public function print_card($id)
    {
        $this->require_perm('bookings.view');
        $wo = $this->_wo_or_404($id);
        $this->load->view('admin/work_orders/print', array(
            'wo'        => $wo,
            'lines'     => $this->crm_work_order_model->lines($id),
            'techs'     => $this->crm_work_order_model->technicians($id),
            'workshops' => ymo_review_workshops(),
        ));
    }

    /** JSON for the line picker: ?q=&type=service|part */
    public function catalog_search()
    {
        $this->require_perm('bookings.view');
        $type = $this->input->get('type') === 'part' ? 'part' : 'service';
        $res = $this->crm_catalog_model->page($type, (string) $this->input->get('q'), '', 15, 0);
        $out = array();
        foreach ($res['rows'] as $r) {
            $out[] = array(
                'id'            => (int) $r['id'],
                'name'          => $r['name'],
                'code'          => $r['code'],
                'rate'          => $r['rate'] === NULL ? NULL : (float) $r['rate'],
                'tax_pct'       => $r['tax_pct'] === NULL ? NULL : (float) $r['tax_pct'],
                'tax_inclusive' => (int) $r['tax_inclusive'],
            );
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(array('items' => $out)));
    }

    // --- helpers -----------------------------------------------------------

    protected function _wo_or_404($id)
    {
        $wo = $this->crm_work_order_model->find($id);
        if (!$wo) {
            show_404();
        }
        return $wo;
    }

    protected function _post_only()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
    }

    protected function _int_or_null($k)
    {
        $v = trim((string) $this->input->post($k));
        return $v === '' ? NULL : max(0, (int) $v);
    }

    protected function _datetime_or_null($k)
    {
        $v = trim((string) $this->input->post($k));
        $ts = $v === '' ? FALSE : strtotime($v);
        return $ts ? date('Y-m-d H:i:00', $ts) : NULL;
    }
}
