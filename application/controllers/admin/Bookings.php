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
        $this->load->library(array('sms_gateway', 'mailer', 'audit', 'invoice_pdf'));
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
            'can_edit'  => function_exists('crm_can') ? crm_can('bookings.edit') : TRUE,
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
        $previous   = $booking['status'];

        $this->booking_model->update_status($id, $new_status, $reason);
        $this->audit->log('admin', $this->admin['id'], 'booking.status_change', 'booking', $id, array(
            'from' => $previous, 'to' => $new_status, 'reason' => $reason,
        ));

        // Auto-create reminders the first time a booking flips to completed
        if ($new_status === 'completed' && $previous !== 'completed') {
            $months = (int) $this->config->item('reminder_months');
            $review_days = (int) $this->config->item('reminder_review_days');

            // Next-service reminder
            $this->reminder_model->create(array(
                'booking_id'   => (int) $id,
                'type'         => 'next_service',
                'channel'      => 'both',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime("+$months months")),
                'status'       => 'pending',
            ));
            // Review reminder - earliest send date
            $this->reminder_model->create(array(
                'booking_id'   => (int) $id,
                'type'         => 'review',
                'channel'      => 'both',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime("+$review_days days")),
                'status'       => 'pending',
            ));

            $this->load->library('referral_service');
            $this->referral_service->complete_for_booking((int) $id);
        }

        if ($new_status === 'cancelled' && $previous !== 'cancelled') {
            $this->load->library('referral_service');
            $this->referral_service->cancel_for_booking((int) $id);
        }

        // Customer notification on every status change after `pending`
        if ($new_status !== $previous && in_array($new_status, array('confirmed', 'in_progress', 'completed', 'cancelled'))) {
            $detailed = $this->booking_model->find_detailed($id);
            $this->_notify_customer_status($detailed);
        }

        $this->flash('success', 'Booking marked '.str_replace('_', ' ', $new_status).'.');
        redirect(admin_url('bookings/'.$id));
    }

    public function send_review($id)
    {
        $this->require_perm('bookings.edit');
        $booking = $this->booking_model->find_detailed($id);
        if (!$booking) { show_404(); }

        if (!$this->_review_eligible($booking)) {
            $this->flash('warning', 'Review can only be sent for completed bookings older than '
                .(int) $this->config->item('reminder_review_days').' day(s).');
            redirect(admin_url('bookings/'.$id));
        }

        $sms_ok = $this->sms_gateway->send_template($booking['user_mobile'], 'review_request', array(
            'name' => strtok($booking['user_name'], ' '),
            'ref'  => $booking['reference'],
        ));
        ymo_sms_log('admin.review_request', 'review_request', $booking['user_mobile'], $sms_ok, $this->sms_gateway);
        $this->load->helper('marketing');
        $mail_ok = $this->mailer->send_view(
            $booking['user_email'],
            'How did we do? - '.$booking['reference'],
            'emails/review_request',
            array('booking' => $booking)
        );

        $review = $this->reminder_model->find_pending_review_for_booking($id);
        if ($review) {
            if ($sms_ok || $mail_ok) {
                $this->reminder_model->mark_sent($review['id']);
            } else {
                $this->reminder_model->mark_failed($review['id'],
                    'SMS: '.($this->sms_gateway->last_error() ?: 'fail').' / Mail: '.($this->mailer->last_error() ?: 'fail'));
            }
        }

        $this->audit->log('admin', $this->admin['id'], 'booking.send_review', 'booking', $id, array(
            'sms' => $sms_ok ? 'ok' : ($this->sms_gateway->last_error() ?: 'fail'),
            'mail'=> $mail_ok ? 'ok' : ($this->mailer->last_error() ?: 'fail'),
        ));
        $this->flash($sms_ok || $mail_ok ? 'success' : 'error',
            $sms_ok || $mail_ok ? 'Review request sent.' : 'Could not send review request - check gateway logs.');
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

    // --- helpers -----------------------------------------------------------

    protected function _review_eligible(array $booking)
    {
        if ($booking['status'] !== 'completed') { return FALSE; }
        $days = (int) $this->config->item('reminder_review_days');
        $earliest = strtotime($booking['completed_at']) + $days * 86400;
        return time() >= $earliest;
    }

    protected function _notify_customer_status(array $booking)
    {
        $sms_ok = $this->sms_gateway->send_template($booking['user_mobile'], 'booking_status', array(
            'ref'    => $booking['reference'],
            'status' => ymo_sms_status_label($booking['status']),
        ));
        ymo_sms_log('admin.booking_status', 'booking_status', $booking['user_mobile'], $sms_ok, $this->sms_gateway);
        $this->mailer->send_view(
            $booking['user_email'],
            'Booking update - '.$booking['reference'],
            'emails/booking_status',
            array('booking' => $booking)
        );
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
            $sms_ok = $this->sms_gateway->send_template($booking['user_mobile'], 'invoice_sent', array(
                'ref'   => $booking['reference'],
                'total' => $total_fmt,
            ));
            ymo_sms_log('admin.invoice_sent', 'invoice_sent', $booking['user_mobile'], $sms_ok, $this->sms_gateway);
            if ($sms_ok) {
                $this->booking_invoice_model->mark_sms_sent($invoice['id']);
            }

            if (!empty($booking['user_email'])) {
                $attachments = array();
                if (!empty($invoice['pdf_path']) && file_exists(FCPATH.$invoice['pdf_path'])) {
                    $attachments[] = array(
                        'path' => FCPATH.$invoice['pdf_path'],
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
            'sms'        => $notify ? ($sms_ok ? 'ok' : ($this->sms_gateway->last_error() ?: 'fail')) : 'skip',
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
        if (empty($invoice['pdf_path']) || !file_exists(FCPATH.$invoice['pdf_path'])) {
            show_error('Invoice PDF not found.', 404);
        }
        $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $invoice['invoice_number']).'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.$filename.'"');
        header('Content-Length: '.filesize(FCPATH.$invoice['pdf_path']));
        readfile(FCPATH.$invoice['pdf_path']);
        exit;
    }
}
