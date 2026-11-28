<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Money: invoices from job cards, payments, To collect, payment accounts and billing setup.
 * bookings.view to see, bookings.edit to raise invoices / record payments, settings.manage for setup.
 */
class Billing extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('crm_billing_model', 'crm_work_order_model'));
        $this->load->library(array('job_invoice_service', 'audit'));
        $this->load->helper('review');
    }

    protected function _post_only()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
    }

    protected function _inv_or_404($id)
    {
        $inv = $this->crm_billing_model->find($id);
        if (!$inv) {
            show_404();
        }
        return $inv;
    }

    public function index()
    {
        $this->require_perm('bookings.view');
        if (!$this->crm_billing_model->ensure_schema()) {
            show_error('Billing tables could not be created; check the database user can CREATE TABLE.', 500);
        }
        $tab = in_array($this->input->get('tab'), array('collect', 'paid', 'cancelled', 'all'), TRUE) ? $this->input->get('tab') : 'collect';
        $workshops = ymo_review_workshops();
        $ws = isset($workshops[(string) $this->input->get('ws')]) ? (string) $this->input->get('ws') : '';
        $q = trim((string) $this->input->get('q'));
        $per = 40;
        $page = max(1, (int) $this->input->get('p'));
        $total = 0;
        $rows = $this->crm_billing_model->page($tab, $ws, $q, $per, ($page - 1) * $per, $total);
        $this->render('admin/billing/index', array(
            'title'     => 'Invoices',
            'rows'      => $rows,
            'tab'       => $tab,
            'ws'        => $ws,
            'q'         => $q,
            'counts'    => $this->crm_billing_model->counts($ws),
            'workshops' => $workshops,
            'pager'     => array('page' => $page, 'pages' => max(1, (int) ceil($total / $per)), 'total' => $total, 'per' => $per, 'param' => 'p', 'anchor' => 'list', 'path' => 'invoices', 'noun' => 'invoices'),
            'can_edit'  => crm_can('bookings.edit'),
        ));
    }

    public function view($id)
    {
        $this->require_perm('bookings.view');
        $inv = $this->_inv_or_404($id);
        $wo = $inv['work_order_id'] ? $this->crm_work_order_model->find($inv['work_order_id']) : NULL;
        $s = $this->crm_billing_model->settings();
        $this->render('admin/billing/view', array(
            'title'    => 'Invoice '.$inv['invoice_number'],
            'inv'      => $inv,
            'wo'       => $wo,
            'lines'    => $this->crm_billing_model->lines($id),
            'payments' => $this->crm_billing_model->payments_for_invoice($id),
            'accounts' => $this->crm_billing_model->accounts(),
            'pay_url'  => $this->job_invoice_service->pay_url($inv),
            'upi_ok'   => $this->job_invoice_service->upi_link($inv) !== '' || (float) $inv['balance'] <= 0,
            'settings' => $s,
            'can_edit' => crm_can('bookings.edit'),
        ));
    }

    public function pdf($id)
    {
        $this->require_perm('bookings.view');
        $inv = $this->_inv_or_404($id);
        $bytes = $this->job_invoice_service->pdf($inv);
        if ($bytes === NULL) {
            show_error('Could not make the PDF: '.html_escape($this->job_invoice_service->last_error()), 500);
        }
        $this->output->set_header('Content-Type: application/pdf')
            ->set_header('Content-Disposition: inline; filename="'.$inv['invoice_number'].'.pdf"')
            ->set_header('Cache-Control: private, no-store')->set_output($bytes);
    }

    /** POST from the job card: raise its invoice. */
    public function raise($wo_id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $wo = $this->crm_work_order_model->find($wo_id);
        if (!$wo) {
            show_404();
        }
        $date = $this->input->post('invoice_date');
        $gstin = strtoupper(trim((string) $this->input->post('bill_gstin')));
        if ($gstin !== '' && !preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z\d]Z[A-Z\d]$/', $gstin)) {
            $this->flash('error', 'That customer GSTIN does not look right (15 characters, e.g. 27ABCDE1234F1Z5).');
            redirect(admin_url('job-cards/'.(int) $wo_id).'#invoice');
        }
        list($id, $msg) = $this->job_invoice_service->raise($wo, array(
            'invoice_date' => $date && strtotime($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d'),
            'gst_type'     => $this->input->post('gst_type') === 'inter' ? 'inter' : 'intra',
            'bill_name'    => (string) $this->input->post('bill_name'),
            'bill_gstin'   => $gstin,
            'bill_address' => (string) $this->input->post('bill_address'),
            'notes'        => (string) $this->input->post('notes'),
        ), (int) $this->admin['id']);
        if ($id) {
            $this->audit->log('admin', $this->admin['id'], 'invoice.create', 'invoice', $id, array('work_order_id' => (int) $wo_id));
        }
        $this->flash($id ? 'success' : 'error', $msg);
        redirect(admin_url('job-cards/'.(int) $wo_id).'#invoice');
    }

    public function payment($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $inv = $this->_inv_or_404($id);
        list($ok, $msg) = $this->job_invoice_service->record_payment($inv, array(
            'amount' => $this->input->post('amount'), 'account_id' => $this->input->post('account_id'), 'mode' => $this->input->post('mode'),
            'reference' => $this->input->post('reference'), 'paid_at' => $this->input->post('paid_at'), 'note' => $this->input->post('note'),
        ), (int) $this->admin['id']);
        if ($ok) {
            $this->audit->log('admin', $this->admin['id'], 'invoice.payment', 'invoice', (int) $id, array('amount' => (float) $this->input->post('amount')));
        }
        $this->flash($ok ? 'success' : 'error', $msg);
        redirect($this->_back($inv));
    }

    public function void_payment($id, $payment_id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $inv = $this->_inv_or_404($id);
        $p = $this->crm_billing_model->find_payment($payment_id);
        if ($p && (int) $p['invoice_id'] === (int) $id && $this->crm_billing_model->void_payment($payment_id, (int) $this->admin['id'])) {
            if ($inv['work_order_id']) {
                $this->crm_work_order_model->log($inv['work_order_id'], (int) $this->admin['id'], 'payment', 'Payment of '.Work_order_customer_money::fmt($p['amount']).' removed (entered by mistake)');
            }
            $this->audit->log('admin', $this->admin['id'], 'invoice.payment_void', 'invoice', (int) $id, array('payment_id' => (int) $payment_id, 'amount' => (float) $p['amount']));
            $this->flash('success', 'Payment removed.');
        }
        redirect(admin_url('invoices/'.(int) $id));
    }

    /** POST kind=invoice|reminder */
    public function send($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $inv = $this->_inv_or_404($id);
        list($ok, $msg) = $this->job_invoice_service->send_whatsapp($inv, $this->input->post('kind') === 'reminder' ? 'reminder' : 'invoice', (int) $this->admin['id']);
        $this->flash($ok ? 'success' : 'error', $msg);
        redirect($this->_back($inv));
    }

    public function cancel($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $inv = $this->_inv_or_404($id);
        $reason = trim((string) $this->input->post('reason'));
        if ($reason === '') {
            $this->flash('error', 'Give a reason for cancelling.');
            redirect(admin_url('invoices/'.(int) $id));
        }
        if ($this->crm_billing_model->cancel($id, $reason)) {
            if ($inv['work_order_id']) {
                $this->crm_work_order_model->log($inv['work_order_id'], (int) $this->admin['id'], 'invoice', 'Invoice '.$inv['invoice_number'].' cancelled: '.$reason);
            }
            $this->audit->log('admin', $this->admin['id'], 'invoice.cancel', 'invoice', (int) $id, array('reason' => $reason));
            $this->job_invoice_service->store_pdf($this->crm_billing_model->find($id));
            $this->flash('success', 'Invoice '.$inv['invoice_number'].' cancelled. Its number is not reused; payments stay with the job card for the next invoice.');
        }
        redirect(admin_url('invoices/'.(int) $id));
    }

    public function set_date($id)
    {
        $this->require_perm('bookings.edit');
        $this->_post_only();
        $inv = $this->_inv_or_404($id);
        $d = $this->input->post('invoice_date');
        if ($d && strtotime($d) && $inv['status'] !== 'cancelled') {
            $this->crm_billing_model->update_invoice($id, array('invoice_date' => date('Y-m-d', strtotime($d))));
            $this->audit->log('admin', $this->admin['id'], 'invoice.date', 'invoice', (int) $id, array('from' => $inv['invoice_date'], 'to' => date('Y-m-d', strtotime($d))));
            $this->flash('success', 'Invoice date changed.');
        }
        redirect(admin_url('invoices/'.(int) $id));
    }

    /** Payments received (daily book), by account. */
    public function payments()
    {
        $this->require_perm('bookings.view');
        $from = $this->input->get('from') && strtotime($this->input->get('from')) ? date('Y-m-d', strtotime($this->input->get('from'))) : date('Y-m-01');
        $to = $this->input->get('to') && strtotime($this->input->get('to')) ? date('Y-m-d', strtotime($this->input->get('to'))) : date('Y-m-d');
        $rows = $this->crm_billing_model->received($from, $to);
        $by_acc = array();
        foreach ($rows as $r) {
            $k = $r['account_name'] ?: 'No account';
            $by_acc[$k] = ($by_acc[$k] ?? 0) + (float) $r['amount'];
        }
        arsort($by_acc);
        $this->render('admin/billing/payments', array(
            'title' => 'Payments received', 'rows' => $rows, 'from' => $from, 'to' => $to, 'by_acc' => $by_acc,
            'total' => array_sum(array_column($rows, 'amount')),
        ));
    }

    /** Billing setup: GSTIN, UPI ID, bank text, terms, numbering; payment accounts. */
    public function setup()
    {
        $this->require_perm('settings.manage');
        $this->crm_billing_model->ensure_schema();
        if ($this->input->method() === 'post') {
            $in = array();
            foreach (array_keys(Crm_billing_model::SETTINGS) as $k) {
                if ($k === 'bill_ws_gst') {
                    continue;
                }
                $v = $this->input->post($k);
                if ($v !== NULL) {
                    $in[$k] = trim((string) $v);
                }
            }
            $in['bill_gstin'] = strtoupper($in['bill_gstin'] ?? '');
            $errors = array();
            if ($in['bill_gstin'] !== '' && !preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z\d]Z[A-Z\d]$/', $in['bill_gstin'])) {
                $errors[] = 'GSTIN should be 15 characters, e.g. 27ABCDE1234F1Z5.';
            }
            $this->load->library('upi_qr');
            if (($in['bill_upi_vpa'] ?? '') !== '' && !Upi_qr::valid_vpa($in['bill_upi_vpa'])) {
                $errors[] = 'UPI ID should look like name@bank (e.g. ymomobility@icici).';
            }
            if (isset($in['bill_inv_next']) && !ctype_digit($in['bill_inv_next'])) {
                $errors[] = 'Next invoice number should be a number, e.g. 2097.';
            }
            // Per-workshop GST registrations
            $ws_gst = array();
            foreach (ymo_review_workshops() as $slug => $w) {
                $g = strtoupper(trim((string) $this->input->post('ws_gstin_'.$slug)));
                if ($g === '') {
                    continue;
                }
                if (!preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z\d]Z[A-Z\d]$/', $g)) {
                    $errors[] = $w['label'].' GSTIN does not look right.';
                    continue;
                }
                $ws_gst[$slug] = array(
                    'gstin' => $g, 'state' => trim((string) $this->input->post('ws_state_'.$slug)),
                    'state_code' => substr($g, 0, 2), 'address' => trim((string) $this->input->post('ws_address_'.$slug)),
                );
            }
            if ($errors) {
                $this->flash('error', implode(' ', $errors));
                redirect(admin_url('billing-setup'));
            }
            if ($in['bill_gstin'] !== '') {
                $in['bill_state_code'] = substr($in['bill_gstin'], 0, 2);
            }
            $in['bill_ws_gst'] = $ws_gst ? json_encode($ws_gst, JSON_UNESCAPED_UNICODE) : '';
            $this->crm_billing_model->save_settings($in);
            $this->audit->log('admin', $this->admin['id'], 'billing.settings', 'settings', 0, array('keys' => array_keys($in)));
            $this->flash('success', 'Billing setup saved.');
            redirect(admin_url('billing-setup'));
        }
        $this->render('admin/billing/setup', array(
            'title'     => 'Billing setup',
            's'         => $this->crm_billing_model->settings(),
            'accounts'  => $this->crm_billing_model->accounts(FALSE),
            'acc_tot'   => $this->crm_billing_model->account_totals(date('Y-m-d', strtotime('-30 days'))),
            'next'      => $this->crm_billing_model->next_invoice_number(),
            'workshops' => ymo_review_workshops(),
        ));
    }

    /** POST id (0 = new), name, kind, details, is_active */
    public function account_save()
    {
        $this->require_perm('settings.manage');
        $this->_post_only();
        $name = trim((string) $this->input->post('name'));
        if ($name === '') {
            $this->flash('error', 'Give the account a name.');
            redirect(admin_url('billing-setup').'#accounts');
        }
        $kind = isset(Crm_billing_model::ACC_KINDS[$this->input->post('kind')]) ? $this->input->post('kind') : 'other';
        $this->crm_billing_model->save_account((int) $this->input->post('id'), array(
            'name' => mb_substr($name, 0, 80), 'kind' => $kind,
            'details' => mb_substr(trim((string) $this->input->post('details')), 0, 255),
            'is_active' => $this->input->post('is_active') === '0' ? 0 : 1,
        ));
        $this->flash('success', 'Saved '.$name.'.');
        redirect(admin_url('billing-setup').'#accounts');
    }

    protected function _back(array $inv)
    {
        $from = (string) $this->input->post('back');
        if ($from === 'job' && $inv['work_order_id']) {
            return admin_url('job-cards/'.(int) $inv['work_order_id']).'#invoice';
        }
        return admin_url('invoices/'.(int) $inv['id']);
    }
}
