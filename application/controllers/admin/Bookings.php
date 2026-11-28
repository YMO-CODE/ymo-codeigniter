<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin booking management. Handles status transitions, manual review
 * dispatch, and creation of automatic next-service reminders when a booking
 * flips to `completed`.
 */
class Bookings extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('booking_model', 'reminder_model', 'package_model', 'booking_invoice_model'));
        $this->load->library(array('sms_gateway', 'mailer', 'audit', 'invoice_pdf', 'customer_notifier'));
    }

    public function index()
    {
        $this->require_perm('bookings.view');
        $perPage = (int) $this->config->item('admin_per_page');
        $page    = max(1, (int) $this->input->get('page'));
        $offset  = ($page - 1) * $perPage;

        $filters = array(
            'status'     => $this->input->get('status'),
            'package_id' => $this->input->get('package_id'),
            'q'          => $this->input->get('q'),
            'from'       => $this->input->get('from'),
            'to'         => $this->input->get('to'),
        );

        $result = $this->booking_model->paginate($filters, $perPage, $offset);

        $this->render('admin/bookings/index', array(
            'title'    => 'Bookings',
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'page'     => $page,
            'pages'    => max(1, (int) ceil($result['total'] / $perPage)),
            'filters'  => $filters,
            'packages' => $this->package_model->list_all(),
        ));
    }

    /**
     * "Book service" for a phone / WhatsApp / walk-in customer (CRM v6).
     *   bookings/new?lead_id=12      prefilled from a lead (lead → Booked)
     *   bookings/new?contact_id=34   prefilled from a customer record
     */
    public function create()
    {
        $this->require_perm('bookings.edit');
        $this->load->model(array('vehicle_model', 'user_model'));
        $this->load->library('crm_booking_service');

        $lead_id    = (int) ($this->input->post('lead_id') ?: $this->input->get('lead_id'));
        $contact_id = (int) ($this->input->post('contact_id') ?: $this->input->get('contact_id'));
        $lead = $contact = NULL;
        if ($lead_id && $this->db->table_exists('crm_leads')) {
            $this->load->model('crm_lead_model');
            $lead = $this->crm_lead_model->find($lead_id);
        }
        if ($contact_id && $this->db->table_exists('crm_contacts')) {
            $this->load->model('crm_contact_model');
            $contact = $this->crm_contact_model->find($contact_id);
        }

        if ($this->input->method() === 'post') {
            return $this->_store_admin_booking($lead, $contact);
        }

        $prefill = array(
            'name'    => $lead['name'] ?? ($contact['name'] ?? ''),
            'mobile'  => crm_mobile10($lead['mobile'] ?? ($contact['mobile'] ?? '')),
            'email'   => $lead['email'] ?? ($contact['email'] ?? ''),
            'area'    => $lead['address'] ?? '',
            'remarks' => $lead ? trim(($lead['car_type'] ? 'Car: '.$lead['car_type']."\n" : '').($lead['message'] ?? '')) : '',
        );
        $user = NULL;
        if (!empty($contact['user_id'])) {
            $user = $this->user_model->find((int) $contact['user_id']);
        }
        if (!$user && $prefill['mobile'] !== '') {
            $user = $this->crm_booking_service->find_user_by_mobile($prefill['mobile']);
        }

        $this->render('admin/bookings/create', array(
            'title'    => 'Book service',
            'lead'     => $lead,
            'contact'  => $contact,
            'prefill'  => $prefill,
            'user'     => $user,
            'vehicles' => $user ? $this->vehicle_model->for_user((int) $user['id']) : array(),
            'makes'    => $this->vehicle_model->makes(),
            'packages' => $this->package_model->list_active(FALSE),
        ));
    }

    /** JSON: existing account + cars for a mobile (used by the Book service form). */
    public function lookup()
    {
        $this->require_perm('bookings.edit');
        $this->load->model('vehicle_model');
        $this->load->library('crm_booking_service');
        $user = $this->crm_booking_service->find_user_by_mobile((string) $this->input->get('mobile'));
        $out = array('found' => FALSE);
        if ($user) {
            $cars = array();
            foreach ($this->vehicle_model->for_user((int) $user['id']) as $v) {
                $cars[] = array(
                    'id'    => (int) $v['id'],
                    'label' => trim($v['make_name'].' '.$v['variant']).' · '.$v['vehicle_number'],
                );
            }
            $out = array(
                'found'    => TRUE,
                'name'     => $user['name'],
                'email'    => ymo_is_placeholder_email($user['email']) ? '' : $user['email'],
                'vehicles' => $cars,
            );
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    protected function _store_admin_booking($lead, $contact)
    {
        $back = admin_url('bookings/new'.($lead ? '?lead_id='.$lead['id'] : ($contact ? '?contact_id='.$contact['id'] : '')));
        $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|regex_match[/^[6-9]\d{9}$/]');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('package_id', 'Package', 'required|is_natural_no_zero');
        $this->form_validation->set_rules('preferred_date', 'Date', 'trim');
        $this->form_validation->set_rules('remarks', 'Notes', 'trim|max_length[2000]');
        if (!$this->form_validation->run()) {
            $this->flash('error', strip_tags(validation_errors(' ', ' ')) ?: 'Check the form.');
            redirect($back);
        }

        $mobile = $this->input->post('mobile');
        $user = $this->crm_booking_service->find_user_by_mobile($mobile);
        $new_account = FALSE;
        if (!$user) {
            $uid = $this->crm_booking_service->create_customer(
                $this->input->post('name'), $mobile, $this->input->post('email'),
                $this->input->post('area'), $this->input->post('city')
            );
            if (!$uid) {
                $this->flash('error', $this->crm_booking_service->last_error());
                redirect($back);
            }
            $user = $this->user_model->find($uid);
            $new_account = TRUE;
        }

        $vehicle_id = (int) $this->input->post('vehicle_id');
        $booking_id = $this->crm_booking_service->create_booking(array(
            'user_id'        => (int) $user['id'],
            'vehicle_id'     => $vehicle_id > 0 ? $vehicle_id : 0,
            'make_id'        => (int) $this->input->post('make_id'),
            'variant'        => $this->input->post('variant'),
            'vehicle_number' => $this->input->post('vehicle_number'),
            'package_id'     => (int) $this->input->post('package_id'),
            'preferred_date' => $this->input->post('preferred_date'),
            'remarks'        => $this->input->post('remarks'),
            'status'         => $this->input->post('status') ?: 'confirmed',
            'lead_id'        => $lead ? (int) $lead['id'] : NULL,
            'admin_id'       => (int) $this->admin['id'],
        ));
        if (!$booking_id) {
            $this->flash('error', $this->crm_booking_service->last_error());
            redirect($back);
        }

        $booking = $this->booking_model->find_detailed($booking_id);
        $notified = '';
        if ($this->input->post('notify')) {
            $ok = $this->customer_notifier->notify($booking['user_mobile'], 'booking_confirmed', array(
                'name'    => strtok($booking['user_name'], ' '),
                'ref'     => $booking['reference'],
                'package' => $booking['package_name'],
            ), array('context' => 'admin.booking_create', 'booking_id' => (int) $booking_id));
            if (!ymo_is_placeholder_email($booking['user_email'])) {
                $this->mailer->send_view($booking['user_email'], 'Your booking is confirmed - '.$booking['reference'],
                    'emails/booking_confirmed', array('booking' => $booking));
            }
            $notified = $ok ? ' Confirmation sent by '.($this->customer_notifier->last_channel() === 'whatsapp' ? 'WhatsApp' : 'SMS').'.' : ' (Confirmation message failed.)';
        }

        $this->audit->log('admin', $this->admin['id'], 'booking.create_admin', 'booking', $booking_id, array(
            'lead_id' => $lead ? (int) $lead['id'] : NULL,
            'new_account' => $new_account,
        ));
        $this->flash('success', 'Booking '.$booking['reference'].' created'.($lead ? ' and the lead marked Booked' : '').'.'.$notified);
        redirect(admin_url('bookings/'.$booking_id));
    }

    public function view($id)
    {
        $this->require_perm('bookings.view');
        $booking = $this->booking_model->find_detailed($id);
        if (!$booking) { show_404(); }

        $reminders = $this->reminder_model->for_booking($id);
        $review    = $this->reminder_model->find_pending_review_for_booking($id);
        $invoices  = $this->booking_invoice_model->for_booking($id);

        $this->render('admin/bookings/view', array(
            'title'     => 'Booking '.$booking['reference'],
            'booking'   => $booking,
            'reminders' => $reminders,
            'invoices'  => $invoices,
            'review_pending' => !empty($review),
            'review_eligible' => $this->_review_eligible($booking),
            'review_workshops' => $this->_review_workshops(),
            'review_workshop'  => $this->_review_guess($booking),
            'review_sent'      => $this->_review_sent($booking),
            'can_edit'  => function_exists('crm_can') ? crm_can('bookings.edit') : TRUE,
            'wa'        => $this->_wa_panel_data($booking),
            'work_order' => $this->_work_order_for($id),
        ));
    }

    public function update_status($id)
    {
        $this->require_perm('bookings.edit');
        $booking = $this->booking_model->find($id);
        if (!$booking) { show_404(); }

        $this->form_validation->set_rules('status', 'Status', 'required|in_list[pending,confirmed,in_progress,completed,cancelled]');
        if (!$this->form_validation->run()) {
            $this->flash('error', 'Pick a valid status.');
            redirect(admin_url('bookings/'.$id));
        }

        $new_status = $this->input->post('status');
        $reason     = $this->input->post('cancel_reason');

        // Reminders, referral, customer notice and review request live in Booking_status_service
        // so job cards trigger exactly the same steps.
        $this->load->library('booking_status_service');
        list($notice_ok, $notice) = $this->booking_status_service->change($id, $new_status, $reason, (int) $this->admin['id'], 'booking');

        $this->flash($notice_ok ? 'success' : 'warning', 'Booking marked '.str_replace('_', ' ', $new_status).'.'.($notice !== '' ? ' '.$notice : ''));
        redirect(admin_url('bookings/'.$id));
    }

    public function send_review($id)
    {
        $this->require_perm('bookings.edit');
        $booking = $this->booking_model->find_detailed($id);
        if (!$booking) { show_404(); }

        if (!$this->_review_eligible($booking)) {
            $this->flash('warning', 'A review can be asked for once the booking is completed.');
            redirect(admin_url('bookings/'.$id));
        }

        $this->load->library('review_service');
        $res = $this->review_service->ask_for_booking($booking, (string) $this->input->post('workshop'), array(
            'source'   => 'manual',
            'admin_id' => (int) $this->admin['id'],
            'force'    => TRUE,
        ));
        $this->audit->log('admin', $this->admin['id'], 'booking.send_review', 'booking', $id, array(
            'ok' => $res['ok'], 'channel' => $res['channel'], 'message' => $res['message'],
        ));
        $this->flash($res['ok'] ? 'success' : 'error', $res['message']);
        redirect(admin_url('bookings/'.$id));
    }

    public function create_invoice($id)
    {
        $this->require_perm('bookings.edit');
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }

        $booking = $this->_booking_for_invoice($id);
        if (!$booking) { return; }

        $input = $this->_parse_invoice_input(admin_url('bookings/'.$id));
        if (!$input) { return; }

        $invoice = $this->booking_invoice_model->create_for_booking(
            $id,
            $this->admin,
            $input['lines'],
            $input['gst_type'],
            $input['gst_rate'],
            $input['notes']
        );

        if (!$invoice) {
            $this->flash('error', 'Could not save invoice.');
            redirect(admin_url('bookings/'.$id));
        }

        $this->_finalize_invoice($booking, $invoice, TRUE, 'booking.invoice_create');
        redirect(admin_url('bookings/'.$id));
    }

    public function edit_invoice($booking_id, $invoice_id)
    {
        $this->require_perm('bookings.edit');
        $booking = $this->_booking_for_invoice($booking_id);
        if (!$booking) { return; }

        $invoice = $this->booking_invoice_model->find_detailed($invoice_id);
        if (!$invoice || (int) $invoice['booking_id'] !== (int) $booking_id) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            return $this->_save_invoice_edit($booking, $invoice);
        }

        $this->render('admin/bookings/invoice_edit', array(
            'title'   => 'Edit invoice '.$invoice['invoice_number'],
            'booking' => $booking,
            'invoice' => $invoice,
        ));
    }

    public function download_invoice($booking_id, $invoice_id)
    {
        $this->require_perm('bookings.view');
        $invoice = $this->booking_invoice_model->find_detailed($invoice_id);
        if (!$invoice || (int) $invoice['booking_id'] !== (int) $booking_id) {
            show_404();
        }
        $this->_stream_invoice_pdf($invoice);
    }

    /**
     * WhatsApp update to the customer from the booking page: a message plus
     * up to 5 photos and/or one PDF estimate.
     *
     * Inside the 24h customer-service window (customer messaged us recently)
     * everything goes as free-form messages. Outside it, Meta only allows
     * approved templates: photos go via `service_update` (image header) and
     * a PDF via `estimate_sent` (document header).
     */
    public function whatsapp_update($id)
    {
        $this->require_perm('bookings.edit');
        if ($this->input->method() !== 'post') { show_error('Method not allowed', 405); }
        $back = admin_url('bookings/'.$id).'#wa-updates';

        $booking = $this->booking_model->find_detailed($id);
        if (!$booking) { show_404(); }

        $this->load->library('crm_messaging');
        $this->load->model('crm_wa_message_model');
        if (!$this->crm_messaging->can_send_whatsapp()) {
            $this->flash('error', 'WhatsApp Cloud API is not configured yet.');
            redirect($back);
        }
        if (!$this->crm_wa_message_model->table_ready()) {
            $this->flash('error', 'Run database/crm_migration_v5_whatsapp.sql first.');
            redirect($back);
        }
        $phone = crm_whatsapp_e164($booking['user_mobile']);
        if (strlen($phone) < 11) {
            $this->flash('error', 'Customer has no valid mobile number.');
            redirect($back);
        }

        $text = trim((string) $this->input->post('wa_message'));
        if (mb_strlen($text) > 900) {
            $this->flash('error', 'Keep the message under 900 characters.');
            redirect($back);
        }

        $files = $this->_collect_wa_uploads((int) $booking['id']);
        if ($files === NULL) {
            redirect($back); // flash already set
        }
        if ($text === '' && !$files) {
            $this->flash('error', 'Write a message or attach a photo / PDF.');
            redirect($back);
        }

        $in_window = $this->crm_wa_message_model->in_service_window($phone);
        $sent = 0;
        $errors = array();

        if ($in_window) {
            $caption_used = FALSE;
            $has_images = (bool) array_filter($files, function ($f) { return $f['type'] === 'image'; });
            if ($text !== '' && !$has_images) {
                $res = $this->crm_messaging->send_whatsapp_text($phone, $text);
                $this->_log_wa_out($booking, $res, 'text', $text, NULL);
                if ($res['ok']) {
                    $sent++;
                } else {
                    $errors[] = $this->crm_messaging->last_error();
                }
                $caption_used = TRUE;
            }
            foreach ($files as $f) {
                $media_id = $this->crm_messaging->upload_whatsapp_media($f['path'], $f['mime']);
                if (!$media_id) {
                    $errors[] = $f['name'].': '.$this->crm_messaging->last_error();
                    continue;
                }
                $caption = '';
                if (!$caption_used && $text !== '' && $f['type'] === 'image') {
                    $caption = $text;
                    $caption_used = TRUE;
                }
                $res = $this->crm_messaging->send_whatsapp_media($phone, $f['type'], $media_id, $caption,
                    $f['type'] === 'document' ? $booking['reference'].'-estimate.pdf' : '');
                $this->_log_wa_out($booking, $res, $f['type'], $caption !== '' ? $caption : $f['name'], $f['path']);
                if ($res['ok']) {
                    $sent++;
                } else {
                    $errors[] = $f['name'].': '.$this->crm_messaging->last_error();
                }
            }
        } else {
            $images = array_values(array_filter($files, function ($f) { return $f['type'] === 'image'; }));
            $docs   = array_values(array_filter($files, function ($f) { return $f['type'] === 'document'; }));
            if (!$images && !$docs) {
                $this->flash('error', 'The customer has not messaged in the last 24 hours, so WhatsApp only allows approved templates. Attach at least one photo or the PDF estimate.');
                redirect($back);
            }
            $n = count($images);
            foreach ($images as $i => $f) {
                $msg = $i === 0 ? ($text !== '' ? $text : 'Update on your car') : 'Photo '.($i + 1).' of '.$n;
                $ok = $this->customer_notifier->notify($phone, 'service_update',
                    array('ref' => $booking['reference'], 'msg' => $msg),
                    array('image' => $f['path'], 'booking_id' => (int) $booking['id'], 'admin_id' => (int) $this->admin['id'],
                          'force_whatsapp' => TRUE, 'sms_fallback' => FALSE, 'context' => 'admin.wa_service_update'));
                if ($ok) {
                    $sent++;
                } else {
                    $errors[] = $f['name'].': '.$this->customer_notifier->last_error();
                }
            }
            foreach ($docs as $f) {
                $ok = $this->customer_notifier->notify($phone, 'estimate_sent',
                    array('ref' => $booking['reference'], 'msg' => ($text !== '' && !$images) ? $text : 'Estimate attached'),
                    array('document' => $f['path'], 'document_name' => $booking['reference'].'-estimate.pdf',
                          'booking_id' => (int) $booking['id'], 'admin_id' => (int) $this->admin['id'],
                          'force_whatsapp' => TRUE, 'sms_fallback' => FALSE, 'context' => 'admin.wa_estimate'));
                if ($ok) {
                    $sent++;
                } else {
                    $errors[] = $f['name'].': '.$this->customer_notifier->last_error();
                }
            }
        }

        $this->audit->log('admin', $this->admin['id'], 'booking.whatsapp_update', 'booking', $id, array(
            'files'     => count($files),
            'in_window' => $in_window ? 'yes' : 'no',
            'sent'      => $sent,
            'errors'    => $errors,
        ));

        if ($sent && !$errors) {
            $this->flash('success', 'WhatsApp update sent ('.$sent.' message'.($sent > 1 ? 's' : '').').');
        } elseif ($sent) {
            $this->flash('warning', 'Sent '.$sent.' message(s), but some failed: '.implode('; ', array_slice(array_filter($errors), 0, 3)));
        } else {
            $this->flash('error', 'WhatsApp update failed: '.implode('; ', array_slice(array_filter($errors), 0, 3)));
        }
        redirect($back);
    }

    /** Stream a photo/PDF that was sent from the booking page (admin only). */
    public function whatsapp_media($booking_id, $message_id)
    {
        $this->require_perm('bookings.view');
        $this->load->model('crm_wa_message_model');
        $msg = $this->crm_wa_message_model->find($message_id);
        if (!$msg || $msg['context_type'] !== 'booking' || (int) $msg['context_id'] !== (int) $booking_id || empty($msg['media_path'])) {
            show_404();
        }
        $types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf');
        $ext = strtolower(pathinfo($msg['media_path'], PATHINFO_EXTENSION));
        if (!isset($types[$ext])) {
            show_404();
        }
        if (strpos($msg['media_path'], 'r2p/whatsapp/') === 0) {
            ymo_media_send($msg['media_path'], $types[$ext], basename($msg['media_path']), 'inline', 'private, max-age=86400');
        }
        $base = realpath((string) $this->config->item('crm_whatsapp_media_path'));
        $file = realpath(FCPATH.'../'.$msg['media_path']);
        if (!$base || !$file || strpos($file, $base.DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
            show_404();
        }
        header('Content-Type: '.$types[$ext]);
        header('Content-Disposition: inline; filename="'.basename($file).'"');
        header('Content-Length: '.filesize($file));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=86400');
        readfile($file);
        exit;
    }

    // --- helpers -----------------------------------------------------------

    /**
     * Validate and store uploaded photos (wa_photos[]) + PDF (wa_pdf) under
     * storage/whatsapp/booking_{id}/ (outside the public web root).
     *
     * @return array[]|null list of {path,mime,type,name}; NULL on validation error.
     */
    protected function _collect_wa_uploads($booking_id)
    {
        $max_bytes = (int) $this->config->item('crm_whatsapp_media_max_kb') * 1024;
        $dir = rtrim((string) $this->config->item('crm_whatsapp_media_path'), '/\\').'/booking_'.(int) $booking_id.'/';
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $out = array();

        $incoming = array();
        if (!empty($_FILES['wa_photos']['name']) && is_array($_FILES['wa_photos']['name'])) {
            foreach ($_FILES['wa_photos']['name'] as $i => $name) {
                if ((int) $_FILES['wa_photos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $incoming[] = array('name' => $name, 'tmp' => $_FILES['wa_photos']['tmp_name'][$i],
                    'error' => (int) $_FILES['wa_photos']['error'][$i], 'size' => (int) $_FILES['wa_photos']['size'][$i],
                    'allowed' => array('image/jpeg' => 'jpg', 'image/png' => 'png'), 'type' => 'image');
            }
        }
        if (!empty($_FILES['wa_pdf']['name']) && (int) $_FILES['wa_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
            $incoming[] = array('name' => $_FILES['wa_pdf']['name'], 'tmp' => $_FILES['wa_pdf']['tmp_name'],
                'error' => (int) $_FILES['wa_pdf']['error'], 'size' => (int) $_FILES['wa_pdf']['size'],
                'allowed' => array('application/pdf' => 'pdf'), 'type' => 'document');
        }

        $photo_count = count(array_filter($incoming, function ($f) { return $f['type'] === 'image'; }));
        if ($photo_count > 5) {
            $this->flash('error', 'Attach at most 5 photos at a time.');
            return NULL;
        }

        foreach ($incoming as $f) {
            $label = basename((string) $f['name']);
            if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp'])) {
                $this->flash('error', 'Upload failed for '.$label.' (error '.$f['error'].').');
                return NULL;
            }
            if ($f['size'] > $max_bytes) {
                $this->flash('error', $label.' is larger than '.(int) ($max_bytes / 1048576).' MB.');
                return NULL;
            }
            $mime = $finfo->file($f['tmp']);
            if (!isset($f['allowed'][$mime])) {
                $this->flash('error', $label.': only JPG/PNG photos and PDF estimates are allowed.');
                return NULL;
            }
            if (!is_dir($dir) && !@mkdir($dir, 0775, TRUE)) {
                $this->flash('error', 'Cannot create storage folder for WhatsApp media (storage/whatsapp must be writable).');
                return NULL;
            }
            $dest = $dir.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.'.$f['allowed'][$mime];
            if (!move_uploaded_file($f['tmp'], $dest)) {
                $this->flash('error', 'Could not save '.$label.'.');
                return NULL;
            }
            $out[] = array('path' => $dest, 'mime' => $mime, 'type' => $f['type'], 'name' => basename((string) $f['name']));
        }
        return $out;
    }

    protected function _log_wa_out(array $booking, array $res, $kind, $body, $path)
    {
        $rel = NULL;
        if ($path) {
            $root = realpath(FCPATH.'..');
            $real = realpath($path);
            $rel = ($root && $real && strpos($real, $root) === 0)
                ? ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/')
                : NULL;
        }
        $this->crm_wa_message_model->log(array(
            'wa_message_id' => $res['message_id'] ?? NULL,
            'phone'         => $booking['user_mobile'],
            'direction'     => 'out',
            'kind'          => $kind,
            'body'          => $body,
            'media_path'    => $rel,
            'context_type'  => 'booking',
            'context_id'    => (int) $booking['id'],
            'status'        => !empty($res['ok']) ? 'sent' : 'failed',
            'error'         => !empty($res['ok']) ? NULL : $this->crm_messaging->last_error(),
            'admin_id'      => (int) $this->admin['id'],
        ));
    }

    /** Data for the "WhatsApp update" card on the booking page. */
    protected function _wa_panel_data(array $booking)
    {
        $this->load->library('crm_messaging');
        $this->load->model('crm_wa_message_model');
        $ready = $this->crm_messaging->can_send_whatsapp() && $this->crm_wa_message_model->table_ready();
        $phone = crm_whatsapp_e164($booking['user_mobile']);
        $this->config->load('whatsapp_template_library', TRUE);
        return array(
            'quick'     => (array) $this->config->item('wa_booking_quick_messages', 'whatsapp_template_library'),
            'ready'     => $ready,
            'migrated'  => $this->crm_wa_message_model->table_ready(),
            'in_window' => $ready ? $this->crm_wa_message_model->in_service_window($phone) : FALSE,
            'last_in'   => $ready ? $this->crm_wa_message_model->last_inbound_at($phone) : NULL,
            'thread'    => $this->crm_wa_message_model->thread_for_booking($booking['id'], $phone, $booking['created_at'] ?? NULL, 30),
        );
    }

    protected function _review_workshops()
    {
        $this->load->helper('review');
        return ymo_review_workshops();
    }

    protected function _review_guess(array $booking)
    {
        $this->load->library('review_service');
        return $this->review_service->workshop_for_booking($booking);
    }

    protected function _review_sent(array $booking)
    {
        $this->load->model('gbp_review_model');
        return $this->gbp_review_model->requested_for_booking((int) $booking['id']);
    }

    protected function _review_eligible(array $booking)
    {
        return $booking['status'] === 'completed';
    }

    /**
     * Tell the customer about a status change and describe what happened.
     *
     * @return array{0: bool, 1: string} [anything delivered, admin-facing summary]
     */
    /** The booking's job card (id, ro_number, status), if one was opened. */
    protected function _work_order_for($booking_id)
    {
        $this->load->model('crm_work_order_model');
        if (!$this->crm_work_order_model->ensure_schema()) {
            return NULL;
        }
        return $this->crm_work_order_model->find_open_for_booking($booking_id);
    }

    protected function _notify_customer_status(array $booking)
    {
        $msg_ok = $this->customer_notifier->notify($booking['user_mobile'], 'booking_status', array(
            'ref'    => $booking['reference'],
            'status' => ymo_sms_status_label($booking['status']),
        ), array('context' => 'admin.booking_status', 'booking_id' => (int) $booking['id']));
        $msg_error = $msg_ok ? '' : (string) $this->customer_notifier->last_error();
        $channel   = $this->customer_notifier->last_channel() === 'whatsapp' ? 'WhatsApp' : 'SMS';

        $has_email = !ymo_is_placeholder_email($booking['user_email']);
        $mail_ok = $has_email && $this->mailer->send_view(
            $booking['user_email'],
            'Booking update - '.$booking['reference'],
            'emails/booking_status',
            array('booking' => $booking)
        );

        $sent = array();
        if ($msg_ok) {
            $sent[] = $channel;
        }
        if ($mail_ok) {
            $sent[] = 'email';
        }
        $text = $sent ? 'Customer notified by '.implode(' and ', $sent).'.' : 'Customer was NOT notified.';
        if (!$msg_ok) {
            $text .= ' Message failed: '.$this->_friendly_send_error($msg_error).'.';
        }
        if ($has_email && !$mail_ok) {
            $text .= ' Email failed'.($this->mailer->last_error() ? ': '.$this->mailer->last_error() : '').'.';
        }
        return array($msg_ok, $text);
    }

    /** Turn raw WhatsApp / SMS errors into something an admin can act on. */
    protected function _friendly_send_error($error)
    {
        $error = trim((string) $error);
        if ($error === '') {
            return 'unknown error';
        }
        if (strpos($error, '131042') !== FALSE) {
            $error = preg_replace('/WhatsApp:[^\/]*/', 'WhatsApp: no balance or payment method on the WhatsApp account (add funds in Meta Billing & payments) ', $error);
        }
        return rtrim(mb_strimwidth($error, 0, 300, '…'), '. ');
    }

    protected function _parse_invoice_lines()
    {
        $descs   = (array) $this->input->post('line_description');
        $amounts = (array) $this->input->post('line_amount');
        $lines   = array();

        foreach ($descs as $i => $desc) {
            $desc = trim((string) $desc);
            $amt  = isset($amounts[$i]) ? (float) $amounts[$i] : 0;
            if ($desc === '' && $amt <= 0) {
                continue;
            }
            if ($desc === '' || $amt < 0.01) {
                return array();
            }
            $lines[] = array(
                'description' => $desc,
                'amount'      => $amt,
            );
        }

        return $lines;
    }

    /** @return array|null */
    protected function _parse_invoice_input($redirect_url)
    {
        $lines = $this->_parse_invoice_lines();
        if (empty($lines)) {
            $this->flash('error', 'Add at least one line item with description and amount.');
            redirect($redirect_url);
            return NULL;
        }

        $gst_type = $this->input->post('gst_type') === 'inter' ? 'inter' : 'intra';
        $gst_rate = (float) $this->input->post('gst_rate');
        $allowed_rates = array(0, 5, 12, 18, 28);
        if (!in_array($gst_rate, $allowed_rates)) {
            $this->flash('error', 'Pick a valid GST rate.');
            redirect($redirect_url);
            return NULL;
        }

        $notes = trim((string) $this->input->post('invoice_notes'));

        return array(
            'lines'    => $lines,
            'gst_type' => $gst_type,
            'gst_rate' => $gst_rate,
            'notes'    => $notes !== '' ? $notes : NULL,
        );
    }

    /** @return array|null Booking row or NULL after redirect */
    protected function _booking_for_invoice($booking_id)
    {
        $booking = $this->booking_model->find_detailed($booking_id);
        if (!$booking) {
            show_404();
            return NULL;
        }
        if ($booking['status'] === 'cancelled') {
            $this->flash('error', 'Cannot modify invoices for a cancelled booking.');
            redirect(admin_url('bookings/'.$booking_id));
            return NULL;
        }
        return $booking;
    }

    protected function _save_invoice_edit(array $booking, array $invoice)
    {
        $url = admin_url('bookings/'.$booking['id'].'/invoice/'.$invoice['id'].'/edit');
        $input = $this->_parse_invoice_input($url);
        if (!$input) { return; }

        $updated = $this->booking_invoice_model->update(
            $invoice['id'],
            $input['lines'],
            $input['gst_type'],
            $input['gst_rate'],
            $input['notes']
        );

        if (!$updated) {
            $this->flash('error', 'Could not update invoice.');
            redirect($url);
        }

        $resend = (bool) $this->input->post('resend_notify');
        $this->_finalize_invoice($booking, $updated, $resend, 'booking.invoice_update');
        redirect(admin_url('bookings/'.$booking['id']));
    }

    protected function _finalize_invoice(array $booking, array $invoice, $notify, $audit_action)
    {
        $pdf_rel = $this->invoice_pdf->generate($invoice);
        if ($pdf_rel) {
            $this->booking_invoice_model->update_pdf_path($invoice['id'], $pdf_rel);
            $invoice['pdf_path'] = $pdf_rel;
        } else {
            log_message('error', '[invoice] PDF failed: '.$this->invoice_pdf->last_error());
        }

        $sms_ok = FALSE;
        $mail_ok = FALSE;

        if ($notify) {
            $total_fmt = ymo_sms_dlt_number($invoice['grand_total']);
            $pdf_abs = !empty($invoice['pdf_path']) ? ymo_media_local_copy($invoice['pdf_path']) : '';
            $sms_ok = $this->customer_notifier->notify($booking['user_mobile'], 'invoice_sent', array(
                'ref'   => $booking['reference'],
                'total' => $total_fmt,
            ), array(
                'context'       => 'admin.invoice_sent',
                'booking_id'    => (int) $booking['id'],
                'document'      => $pdf_abs,
                'document_name' => $invoice['invoice_number'].'.pdf',
            ));
            if ($sms_ok) {
                $this->booking_invoice_model->mark_sms_sent($invoice['id']);
            }

            if (!empty($booking['user_email'])) {
                $attachments = array();
                if ($pdf_abs !== '') {
                    $attachments[] = array(
                        'path' => $pdf_abs,
                        'name' => $invoice['invoice_number'].'.pdf',
                    );
                }
                $mail_ok = $this->mailer->send_view_with_attachment(
                    $booking['user_email'],
                    'Service invoice '.$invoice['invoice_number'].' - '.$booking['reference'],
                    'emails/invoice_sent',
                    array('invoice' => $invoice),
                    $attachments
                );
                if ($mail_ok) {
                    $this->booking_invoice_model->mark_email_sent($invoice['id']);
                }
            }
        }

        $this->audit->log('admin', $this->admin['id'], $audit_action, 'booking_invoice', $invoice['id'], array(
            'booking_id' => $booking['id'],
            'invoice_no' => $invoice['invoice_number'],
            'total'      => $invoice['grand_total'],
            'notify'     => $notify ? 'yes' : 'no',
            'sms'        => $notify ? ($sms_ok ? 'ok:'.$this->customer_notifier->last_channel() : ($this->customer_notifier->last_error() ?: 'fail')) : 'skip',
            'mail'       => $notify ? ($mail_ok ? 'ok' : ($this->mailer->last_error() ?: 'skip')) : 'skip',
        ));

        $is_create = ($audit_action === 'booking.invoice_create');
        if ($notify && ($sms_ok || $mail_ok)) {
            $msg = $is_create
                ? 'Invoice '.$invoice['invoice_number'].' created and sent to customer.'
                : 'Invoice '.$invoice['invoice_number'].' updated'.($pdf_rel ? ' and PDF regenerated' : '').'. Customer notified.';
            if (!$sms_ok || !$mail_ok) {
                $msg .= ' Some notifications failed - check logs.';
                $this->flash('warning', $msg);
            } else {
                $this->flash('success', $msg);
            }
        } elseif ($notify && $pdf_rel) {
            $this->flash('warning', 'Invoice saved and PDF updated, but SMS/email could not be sent.');
        } elseif ($pdf_rel) {
            $this->flash('success', 'Invoice '.$invoice['invoice_number'].' updated and PDF regenerated.');
        } else {
            $this->flash('warning', 'Invoice saved but PDF generation failed.');
        }
    }

    protected function _stream_invoice_pdf(array $invoice)
    {
        if (empty($invoice['pdf_path'])) {
            show_error('Invoice PDF not found.', 404);
        }
        $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $invoice['invoice_number']).'.pdf';
        ymo_media_send($invoice['pdf_path'], 'application/pdf', $filename);
    }
}
