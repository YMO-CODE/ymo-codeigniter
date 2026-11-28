<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Invoices from job cards: raise (GST split per rate), PDF with UPI QR, pay page link,
 * WhatsApp send / payment reminders, and recording payments.
 */
class Job_invoice_service
{
    protected $CI;
    protected $last_error = '';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('crm_billing_model', 'crm_work_order_model'));
        $this->CI->load->helper(array('crm', 'marketing'));
    }

    public function last_error()
    {
        return $this->last_error;
    }

    public function pay_url(array $inv)
    {
        return function_exists('ymo_marketing_url') ? ymo_marketing_url('pay/'.$inv['pay_token']) : base_url('pay/'.$inv['pay_token']);
    }

    /** upi:// link for the invoice balance, or '' when no UPI ID is set. */
    public function upi_link(array $inv)
    {
        $s = $this->CI->crm_billing_model->settings();
        $this->CI->load->library('upi_qr');
        if (!Upi_qr::valid_vpa($s['bill_upi_vpa']) || (float) $inv['balance'] <= 0) {
            return '';
        }
        $seller = json_decode((string) $inv['seller_json'], TRUE) ?: array();
        $payee = $s['bill_upi_name'] !== '' ? $s['bill_upi_name'] : ($seller['name'] ?? 'Your Mechanic Online');
        return Upi_qr::link($s['bill_upi_vpa'], $payee, $inv['balance'], $inv['invoice_number'].' '.preg_replace('/\s.*$/', '', (string) $inv['vehicle']), $inv['invoice_number']);
    }

    /**
     * @return array [invoice id or 0, message]
     */
    public function raise(array $wo, array $opts, $admin_id)
    {
        $m = $this->CI->crm_work_order_model;
        $b = $this->CI->crm_billing_model;
        if ($b->for_work_order($wo['id'])) {
            return array(0, 'This job card already has an invoice. Cancel it first to raise a new one.');
        }
        $all = $m->lines($wo['id']);
        $waiting = array_filter($all, function ($l) { return $l['approval'] === 'pending'; });
        if ($waiting) {
            return array(0, count($waiting).' item(s) are still waiting for the customer\'s OK. Mark them approved or declined first.');
        }
        $lines = array_values(array_filter($all, function ($l) { return $l['approval'] === 'approved'; }));
        if (!$lines) {
            return array(0, 'Add services or parts before raising the invoice.');
        }
        $s = $b->settings();
        if (trim($s['bill_gstin']) === '' && empty($s['ws_gst'][$wo['workshop']]['gstin'])) {
            return array(0, 'Add the GSTIN in Money → Billing setup first, so the invoice is a valid tax invoice.');
        }
        $opts['admin_id'] = (int) $admin_id;
        $id = $b->create_from_work_order($wo, $lines, $opts);
        if (!$id) {
            return array(0, 'Could not save the invoice. Try again.');
        }
        $inv = $b->find($id);
        $m->log($wo['id'], (int) $admin_id, 'invoice', 'Invoice '.$inv['invoice_number'].' raised: '.Work_order_customer_money::fmt($inv['grand_total']));
        $this->store_pdf($inv);
        return array($id, 'Invoice '.$inv['invoice_number'].' raised for '.Work_order_customer_money::fmt($inv['grand_total']).'.');
    }

    /** Renders the invoice PDF. @return string|null bytes */
    public function pdf(array $inv)
    {
        foreach (array(dirname(FCPATH).'/vendor/autoload.php', FCPATH.'vendor/autoload.php', FCPATH.'../vendor/autoload.php') as $a) {
            if (is_file($a)) {
                require_once $a;
                break;
            }
        }
        if (!class_exists('Dompdf\\Dompdf')) {
            $this->last_error = 'dompdf is not installed (composer install).';
            return NULL;
        }
        $this->CI->load->library('upi_qr');
        $link = $this->upi_link($inv);
        $wo = $inv['work_order_id'] ? $this->CI->crm_work_order_model->find($inv['work_order_id']) : NULL;
        $html = $this->CI->load->view('pdf/job_invoice', array(
            'inv'      => $inv,
            'lines'    => $this->CI->crm_billing_model->lines($inv['id']),
            'payments' => array_values(array_filter($this->CI->crm_billing_model->payments_for_invoice($inv['id']), function ($p) { return !$p['voided_at']; })),
            'seller'   => json_decode((string) $inv['seller_json'], TRUE) ?: array(),
            'settings' => $this->CI->crm_billing_model->settings(),
            'ro'       => $wo ? $wo['ro_number'] : '',
            'qr_png'   => $link !== '' ? 'data:image/png;base64,'.base64_encode($this->CI->upi_qr->png($link, 5)) : '',
            'pay_url'  => $this->pay_url($inv),
            'words'    => self::rupees_in_words($inv['grand_total']),
        ), TRUE);
        try {
            $d = new \Dompdf\Dompdf(array('isRemoteEnabled' => FALSE, 'isHtml5ParserEnabled' => TRUE));
            $d->loadHtml($html);
            $d->setPaper('A4', 'portrait');
            $d->render();
            return $d->output();
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            log_message('error', '[job_invoice] '.$e->getMessage());
            return NULL;
        }
    }

    /** Keeps a copy of the PDF in Cloud Storage (not on the CRM server). */
    public function store_pdf(array $inv)
    {
        $bytes = $this->pdf($inv);
        if ($bytes === NULL) {
            return FALSE;
        }
        $this->CI->load->library('gcs_client');
        $obj = 'invoices/'.date('Y', strtotime($inv['invoice_date'])).'/'.preg_replace('/[^A-Za-z0-9-]/', '', $inv['invoice_number']).'-'.substr(sha1($inv['pay_token']), 0, 8).'.pdf';
        if ($this->CI->gcs_client->put($obj, $bytes, 'application/pdf')) {
            $this->CI->crm_billing_model->update_invoice($inv['id'], array('pdf_object' => $obj));
            return TRUE;
        }
        return FALSE;
    }

    /**
     * WhatsApp: invoice PDF (+ Pay now link when the invoice_pay template is approved), or a payment reminder.
     * @param string $kind invoice | reminder
     * @return array [ok, message]
     */
    public function send_whatsapp(array $inv, $kind, $admin_id)
    {
        if ($inv['status'] === 'cancelled') {
            return array(FALSE, 'This invoice is cancelled.');
        }
        $phone = crm_whatsapp_e164($inv['bill_mobile']);
        if (strlen($phone) < 12) {
            return array(FALSE, 'No valid mobile number on the invoice.');
        }
        $this->CI->load->library(array('crm_messaging', 'customer_notifier', 'booking_status_service'));
        if (!$this->CI->crm_messaging->can_send_whatsapp()) {
            return array(FALSE, 'WhatsApp is not set up in the CRM.');
        }
        $bytes = $this->pdf($inv);
        if ($bytes === NULL) {
            return array(FALSE, 'Could not make the PDF: '.$this->last_error);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'inv');
        file_put_contents($tmp, $bytes);
        $car = preg_replace('/\s.*$/', '', (string) $inv['vehicle']);
        $vars = array(
            'ref' => $inv['invoice_number'], 'vehicle' => $car !== '' ? $car : 'your car',
            'amount' => number_format((float) ($inv['balance'] > 0 ? $inv['balance'] : $inv['grand_total']), 2),
            'total' => number_format((float) $inv['grand_total'], 2), 'pay_token' => $inv['pay_token'],
        );
        $wo = $inv['work_order_id'] ? $this->CI->crm_work_order_model->find($inv['work_order_id']) : NULL;
        $opts = array('document' => $tmp, 'document_name' => $inv['invoice_number'].'.pdf', 'admin_id' => (int) $admin_id,
            'booking_id' => $wo && $wo['booking_id'] ? (int) $wo['booking_id'] : NULL,
            'force_whatsapp' => TRUE, 'sms_fallback' => FALSE, 'context' => 'admin.job_invoice_'.$kind);
        $n = $this->CI->customer_notifier;
        $used = '';
        if ($kind === 'reminder' && (float) $inv['balance'] > 0) {
            unset($opts['document']);
            if ($n->notify($phone, 'payment_reminder', $vars, $opts)) {
                $used = 'payment_reminder';
            }
            $opts['document'] = $tmp;
        }
        if ($used === '' && (float) $inv['balance'] > 0 && $n->notify($phone, 'invoice_pay', $vars, $opts)) {
            $used = 'invoice_pay';
        }
        if ($used === '' && $n->notify($phone, 'invoice_sent', $vars, $opts)) {
            $used = 'invoice_sent';
        }
        @unlink($tmp);
        if ($used === '') {
            return array(FALSE, 'WhatsApp did not accept it: '.$this->CI->booking_status_service->friendly_send_error($n->last_error()));
        }
        $now = date('Y-m-d H:i:s');
        $upd = $kind === 'reminder'
            ? array('last_reminder_at' => $now, 'reminders' => (int) $inv['reminders'] + 1)
            : array('sent_at' => $now);
        $this->CI->crm_billing_model->update_invoice($inv['id'], $upd);
        if ($wo) {
            $this->CI->crm_work_order_model->log($wo['id'], (int) $admin_id, 'wa',
                ($kind === 'reminder' ? 'Payment reminder' : 'Invoice '.$inv['invoice_number']).' sent on WhatsApp'.($used === 'invoice_sent' ? ' (PDF with UPI QR)' : ' with Pay now link'));
        }
        $msg = ($kind === 'reminder' ? 'Payment reminder' : 'Invoice').' sent on WhatsApp';
        if ($used === 'invoice_sent') {
            $msg .= '. The PDF carries the UPI QR and pay link; the "Pay now" button starts working once the new templates are approved (WhatsApp templates page).';
        } else {
            $msg .= ' with a Pay now button.';
        }
        return array(TRUE, $msg);
    }

    /**
     * @param array $d amount, account_id, mode, reference, paid_at, note
     * @return array [ok, message]
     */
    public function record_payment(array $inv, array $d, $admin_id, $source = 'manual')
    {
        $amt = round((float) $d['amount'], 2);
        if ($inv['status'] === 'cancelled') {
            return array(FALSE, 'This invoice is cancelled.');
        }
        if ($amt <= 0) {
            return array(FALSE, 'Enter the amount received.');
        }
        if ($amt > (float) $inv['balance'] + 0.5) {
            return array(FALSE, 'That is more than the '.Work_order_customer_money::fmt($inv['balance']).' due.');
        }
        $mode = isset(Crm_billing_model::MODES[$d['mode'] ?? '']) ? $d['mode'] : 'upi';
        $this->CI->crm_billing_model->add_payment(array(
            'invoice_id'    => (int) $inv['id'],
            'work_order_id' => $inv['work_order_id'] ? (int) $inv['work_order_id'] : NULL,
            'user_id'       => $inv['user_id'] ? (int) $inv['user_id'] : NULL,
            'amount'        => $amt,
            'account_id'    => (int) ($d['account_id'] ?? 0) ?: NULL,
            'mode'          => $mode,
            'reference'     => mb_substr(trim((string) ($d['reference'] ?? '')), 0, 80),
            'paid_at'       => self::paid_at($d['paid_at'] ?? ''),
            'note'          => mb_substr(trim((string) ($d['note'] ?? '')), 0, 255),
            'source'        => $source,
            'created_by'    => (int) $admin_id ?: NULL,
        ));
        $inv2 = $this->CI->crm_billing_model->find($inv['id']);
        if ($inv['work_order_id']) {
            $this->CI->crm_work_order_model->log($inv['work_order_id'], (int) $admin_id, 'payment',
                'Payment '.Work_order_customer_money::fmt($amt).' ('.Crm_billing_model::MODES[$mode].') on '.$inv['invoice_number']
                .($inv2['status'] === 'paid' ? ' · fully paid' : ' · '.Work_order_customer_money::fmt($inv2['balance']).' still due'));
        }
        return array(TRUE, 'Recorded '.Work_order_customer_money::fmt($amt).'. '.($inv2['status'] === 'paid' ? 'Invoice fully paid.' : Work_order_customer_money::fmt($inv2['balance']).' still due.'));
    }

    /** Payment time: now for today (or a date-only value of today), noon for another date-only day. */
    public static function paid_at($v)
    {
        $v = trim((string) $v);
        if ($v === '' || !strtotime($v)) {
            return date('Y-m-d H:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v === date('Y-m-d') ? date('Y-m-d H:i:s') : $v.' 12:00:00';
        }
        return date('Y-m-d H:i:s', min(time(), strtotime($v)));
    }

    public static function rupees_in_words($amount)
    {
        $n = (int) round((float) $amount);
        if ($n === 0) {
            return 'Rupees zero only';
        }
        $ones = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve',
            'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen');
        $tens = array('', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety');
        $two = function ($x) use ($ones, $tens) {
            return $x < 20 ? $ones[$x] : trim($tens[intdiv($x, 10)].' '.$ones[$x % 10]);
        };
        $three = function ($x) use ($two, $ones) {
            $h = intdiv($x, 100);
            $r = $x % 100;
            return trim(($h ? $ones[$h].' hundred' : '').($h && $r ? ' ' : '').($r ? $two($r) : ''));
        };
        $parts = array();
        foreach (array(array(10000000, 'crore'), array(100000, 'lakh'), array(1000, 'thousand')) as $u) {
            if ($n >= $u[0]) {
                $k = intdiv($n, $u[0]);
                $parts[] = ($k >= 100 ? $three($k) : $two($k)).' '.$u[1];
                $n %= $u[0];
            }
        }
        if ($n) {
            $parts[] = $three($n);
        }
        return 'Rupees '.implode(' ', $parts).' only';
    }
}

/** Small money formatter shared by the billing pieces (₹1,23,456). */
class Work_order_customer_money
{
    public static function fmt($n)
    {
        $neg = (float) $n < 0;
        list($int, $dec) = explode('.', number_format(abs((float) $n), 2, '.', ''));
        if (strlen($int) > 3) {
            $int = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($int, 0, -3)).','.substr($int, -3);
        }
        return ($neg ? '-' : '').'₹'.$int.($dec !== '00' ? '.'.$dec : '');
    }
}
