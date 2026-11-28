<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * What the customer sees of a job card, on WhatsApp:
 *  - estimates (PDF + optional photos) with Approve / Call me buttons, and handling those replies;
 *  - short stage updates (work started, ready for pickup, delivered).
 * Uses templates that already exist: estimate_sent (PDF), service_update (photo), booking_status (stage).
 */
class Work_order_customer
{
    const MAX_PHOTOS = 3;
    const APPROVE_WORDS = array('approve', 'approved', 'approve estimate');
    const CALL_WORDS = array('call me', 'call');

    protected $CI;
    protected $last_error = '';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('crm_work_order_model');
        $this->CI->load->helper('crm');
    }

    public function last_error()
    {
        return $this->last_error;
    }

    public static function ref(array $wo)
    {
        return $wo['ro_number'].($wo['vehicle_number'] !== '' && $wo['vehicle_number'] !== NULL ? ' ('.$wo['vehicle_number'].')' : '');
    }

    public static function rupees($n)
    {
        $n = round((float) $n);
        // Indian grouping: 1,23,456
        $s = (string) (int) $n;
        if (strlen($s) > 3) {
            $last = substr($s, -3);
            $rest = substr($s, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $s = $rest.','.$last;
        }
        return '₹'.$s;
    }

    /** WhatsApp template variables can't hold new lines, tabs or long runs of spaces. */
    public static function one_line($s, $max = 900)
    {
        $s = preg_replace('/[\r\n\t]+/', ' · ', (string) $s);
        $s = preg_replace('/ {2,}/', ' ', $s);
        return mb_strimwidth(trim($s, " ·"), 0, $max, '…');
    }

    // ---- estimates ---------------------------------------------------------------

    /**
     * Sends an estimate on WhatsApp.
     * @param string $scope pending (work waiting for OK) | all (whole job)
     * @param int[]  $photo_ids job photos to send first (max 3)
     * @return array [bool ok, string message, int estimate id]
     */
    public function send_estimate(array $wo, $scope, $note, array $photo_ids, $admin_id)
    {
        $m = $this->CI->crm_work_order_model;
        $id = (int) $wo['id'];
        $all = array_values(array_filter($m->lines($id), function ($l) { return $l['approval'] !== 'declined'; }));
        $lines = $scope === 'all' ? $all : array_values(array_filter($all, function ($l) { return $l['approval'] === 'pending'; }));
        if (!$lines) {
            return array(FALSE, $scope === 'all' ? 'Add services or parts first.' : 'Nothing is waiting for the customer\'s OK. Tick "Ask customer first" when adding work, or send the whole job.', 0);
        }
        $phone = crm_whatsapp_e164($wo['customer_mobile']);
        if (strlen($phone) < 12) {
            return array(FALSE, 'The customer has no valid mobile number.', 0);
        }
        $this->CI->load->library(array('crm_messaging', 'customer_notifier'));
        if (!$this->CI->crm_messaging->can_send_whatsapp()) {
            return array(FALSE, 'WhatsApp is not set up in the CRM.', 0);
        }

        $amount = array_sum(array_map(function ($l) { return (float) $l['amount']; }, $lines));
        $job_total = array_sum(array_map(function ($l) { return (float) $l['amount']; }, $all));
        $summary = $this->_summary($lines, $amount, $job_total, $scope, (string) $note);

        // Photos first, so the PDF (with the buttons) is the last thing they see.
        $photo_ids = array_slice(array_values(array_unique(array_map('intval', $photo_ids))), 0, self::MAX_PHOTOS);
        $photo_errors = array();
        $sent_photos = 0;
        foreach ($photo_ids as $i => $pid) {
            $p = $m->find_photo($pid);
            if (!$p || (int) $p['work_order_id'] !== $id) {
                continue;
            }
            list($ok, $err) = $this->_send_photo($wo, $p, $phone, $i === 0 ? ($note !== '' ? $note : 'Photo from the workshop') : 'Photo '.($i + 1), $admin_id);
            if ($ok) {
                $sent_photos++;
            } else {
                $photo_errors[] = $err;
            }
        }

        // PDF
        $est_id = $m->add_estimate(array(
            'work_order_id' => $id,
            'scope'         => $scope === 'all' ? 'all' : 'pending',
            'line_ids'      => implode(',', array_map(function ($l) { return (int) $l['id']; }, $lines)),
            'lines_json'    => json_encode(array_map(function ($l) {
                return array('id' => (int) $l['id'], 'type' => $l['type'], 'name' => $l['name'], 'qty' => (float) $l['qty'], 'amount' => (float) $l['amount']);
            }, $lines), JSON_UNESCAPED_UNICODE),
            'amount'        => round($amount, 2),
            'job_total'     => round($job_total, 2),
            'summary'       => mb_substr($summary, 0, 1000),
            'phone'         => $phone,
            'status'        => 'sent',
            'sent_by'       => (int) $admin_id ?: NULL,
        ));
        $est = $m->find_estimate($est_id);
        $pdf = $this->_render_pdf($wo, $est, $lines, $all, (string) $note);
        if ($pdf === NULL) {
            $m->update_estimate($est_id, array('status' => 'failed', 'error' => mb_substr($this->last_error, 0, 500)));
            return array(FALSE, 'Could not make the estimate PDF: '.$this->last_error, $est_id);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'est');
        file_put_contents($tmp, $pdf);
        $file_name = $wo['ro_number'].'-estimate-'.$est['number'].'.pdf';

        $n = $this->CI->customer_notifier;
        $ok = $n->notify($phone, 'estimate_sent', array('ref' => self::ref($wo), 'msg' => self::one_line($summary)), array(
            'document' => $tmp, 'document_name' => $file_name, 'booking_id' => (int) $wo['booking_id'] ?: NULL,
            'admin_id' => (int) $admin_id, 'force_whatsapp' => TRUE, 'sms_fallback' => FALSE, 'context' => 'admin.job_estimate',
        ));
        @unlink($tmp);

        // Keep a copy of the PDF with the job's photos (not on the CRM server).
        $this->CI->load->library('gcs_client');
        $obj = 'estimates/'.preg_replace('/[^A-Za-z0-9]/', '', $wo['ro_number']).'/estimate-'.$est['number'].'-'.bin2hex(random_bytes(3)).'.pdf';
        $stored = $this->CI->gcs_client->put($obj, $pdf, 'application/pdf');

        $m->update_estimate($est_id, array(
            'status'        => $ok ? 'sent' : 'failed',
            'wa_message_id' => $ok ? $n->last_message_id() : NULL,
            'error'         => $ok ? '' : mb_substr((string) $n->last_error(), 0, 500),
            'pdf_object'    => $stored ? $obj : '',
        ));
        foreach ($lines as $l) {
            $this->CI->db->where('id', (int) $l['id'])->update('crm_work_order_lines', array('estimate_id' => $est_id));
        }

        $m->log($id, (int) $admin_id, 'estimate', $ok
            ? 'Estimate #'.$est['number'].' sent on WhatsApp: '.self::rupees($amount).($sent_photos ? ' + '.$sent_photos.' photo'.($sent_photos > 1 ? 's' : '') : '')
            : 'Estimate #'.$est['number'].' NOT sent: '.$n->last_error());
        if (!$ok) {
            $this->CI->load->library('booking_status_service');
            return array(FALSE, 'WhatsApp did not accept the estimate: '.$this->CI->booking_status_service->friendly_send_error($n->last_error()), $est_id);
        }
        $msg = 'Estimate #'.$est['number'].' ('.self::rupees($amount).') sent on WhatsApp'.($sent_photos ? ' with '.$sent_photos.' photo'.($sent_photos > 1 ? 's' : '') : '')
             .'. When the customer taps Approve, the work is approved here automatically.';
        if ($photo_errors) {
            $msg .= ' Some photos failed: '.implode('; ', array_slice($photo_errors, 0, 2));
        }
        return array(TRUE, $msg, $est_id);
    }

    /** Send one job photo to the customer (service_update template: photo + Approve / Call me). */
    public function send_photo(array $wo, array $photo, $text, $admin_id)
    {
        $phone = crm_whatsapp_e164($wo['customer_mobile']);
        if (strlen($phone) < 12) {
            return array(FALSE, 'The customer has no valid mobile number.');
        }
        $this->CI->load->library(array('crm_messaging', 'customer_notifier'));
        if (!$this->CI->crm_messaging->can_send_whatsapp()) {
            return array(FALSE, 'WhatsApp is not set up in the CRM.');
        }
        list($ok, $err) = $this->_send_photo($wo, $photo, $phone, $text !== '' ? $text : 'Photo from the workshop', $admin_id);
        $this->CI->crm_work_order_model->log($wo['id'], (int) $admin_id, 'wa', $ok ? 'Photo sent to customer on WhatsApp' : 'Photo NOT sent: '.$err);
        return array($ok, $ok ? 'Photo sent to the customer on WhatsApp.' : 'Photo not sent: '.$err);
    }

    /**
     * Customer approved / declined / asked for a call (WhatsApp button, or the advisor after a call).
     * @param string $action approve | decline | call
     * @param string $via whatsapp | advisor
     */
    public function respond(array $est, $action, $via, $admin_id = 0)
    {
        $m = $this->CI->crm_work_order_model;
        $wo = $m->find($est['work_order_id']);
        if (!$wo) {
            return FALSE;
        }
        $ids = array_filter(array_map('intval', explode(',', (string) $est['line_ids'])));
        $by = $via === 'whatsapp' ? 'on WhatsApp' : 'to the advisor';
        $amount = self::rupees($est['amount']);
        $now = date('Y-m-d H:i:s');
        if ($action === 'approve' || $action === 'decline') {
            // Only lines still waiting change; anything the advisor already decided stays.
            $still = array_values(array_filter($m->lines($wo['id']), function ($l) use ($ids) {
                return in_array((int) $l['id'], $ids, TRUE) && $l['approval'] === 'pending';
            }));
            $m->set_approval($wo['id'], array_column($still, 'id'), $action === 'approve' ? 'approved' : 'declined');
            $m->update_estimate($est['id'], array('status' => $action === 'approve' ? 'approved' : 'declined',
                'responded_at' => $now, 'response_via' => $via, 'responded_by' => (int) $admin_id ?: NULL));
            $names = implode(', ', array_slice(array_column($still ?: array(), 'name'), 0, 6));
            $m->log($wo['id'], (int) $admin_id, 'estimate', 'Customer '.($action === 'approve' ? 'APPROVED' : 'declined').' estimate #'.$est['number'].' ('.$amount.') '.$by);
            if ($still) {
                // A note the technicians see on their phones.
                $m->log($wo['id'], (int) $admin_id, 'note', ($action === 'approve' ? '✅ Customer approved: ' : '❌ Customer said no to: ').$names);
            }
            return TRUE;
        }
        if ($action === 'call') {
            $m->update_estimate($est['id'], array('status' => 'call_requested', 'responded_at' => $now, 'response_via' => $via));
            $m->log($wo['id'], 0, 'estimate', 'Customer asked for a call about estimate #'.$est['number'].' ('.$amount.')');
            $this->_call_task($wo, $est);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Inbound WhatsApp message from the webhook. Handles Approve / Call me taps on a job estimate.
     * @return bool TRUE when the message was a reply to an estimate (so it is not a new lead)
     */
    public function handle_reply(array $msg)
    {
        $raw = (array) ($msg['raw'] ?? array());
        $type = (string) ($raw['type'] ?? 'text');
        $text = strtolower(trim(preg_replace('/[^a-zA-Z ]+/', ' ', (string) ($msg['text'] ?? ''))));
        $text = preg_replace('/\s+/', ' ', $text);
        $is_button = in_array($type, array('button', 'interactive'), TRUE);
        $action = in_array($text, self::APPROVE_WORDS, TRUE) ? 'approve' : (in_array($text, self::CALL_WORDS, TRUE) && $is_button ? 'call' : '');
        if ($action === '' || !$this->CI->crm_work_order_model->ensure_schema()) {
            return FALSE;
        }
        $ctx = (string) ($raw['context']['id'] ?? '');
        $est = $this->CI->crm_work_order_model->estimate_for_reply(crm_whatsapp_e164($msg['from'] ?? ''), $ctx);
        if (!$est) {
            return FALSE;
        }
        $wo = $this->CI->crm_work_order_model->find($est['work_order_id']);
        if (!$wo) {
            return FALSE;
        }
        $phone = crm_whatsapp_e164($msg['from']);
        if ($est['status'] === 'superseded') {
            $this->_reply($phone, 'Thanks! We have sent you a newer estimate for '.$wo['vehicle_number'].' — please use the buttons on the latest one, or reply here and our advisor will help.', $wo);
            return TRUE;
        }
        if (in_array($est['status'], array('approved', 'declined'), TRUE)) {
            return TRUE;   // already handled (double tap)
        }
        $this->respond($est, $action, 'whatsapp');
        if ($action === 'approve') {
            $this->_reply($phone, 'Thank you! We are going ahead with the work on your car '.$wo['vehicle_number'].' ('.self::rupees($est['amount']).'). We will message you when it is ready.', $wo);
        } else {
            $this->_reply($phone, 'Sure — our service advisor will call you shortly about your car '.$wo['vehicle_number'].'.', $wo);
        }
        return TRUE;
    }

    // ---- stage updates ---------------------------------------------------------------

    /**
     * Short WhatsApp/SMS update after a job card status change. Skipped where the booking
     * already told the customer (booking moved to in progress / completed).
     * @return string activity line ('' when nothing was sent)
     */
    public function notify_stage(array $wo_before, $to, $booking_notified)
    {
        if (!(int) ($wo_before['notify_customer'] ?? 1)) {
            return '';
        }
        $status = NULL;
        if ($to === 'in_progress' && $wo_before['status'] === 'draft' && !$booking_notified) {
            $status = 'Work started';
        } elseif ($to === 'ready') {
            $status = 'Ready for pickup';
        } elseif ($to === 'delivered' && !$booking_notified) {
            $status = 'Delivered - thank you';
        }
        if ($status === NULL || trim((string) $wo_before['customer_mobile']) === '') {
            return '';
        }
        $this->CI->load->library('customer_notifier');
        $n = $this->CI->customer_notifier;
        $ok = $n->notify($wo_before['customer_mobile'], 'booking_status', array('ref' => self::ref($wo_before), 'status' => $status), array(
            'context' => 'admin.job_stage', 'booking_id' => (int) $wo_before['booking_id'] ?: NULL,
        ));
        $channel = $n->last_channel() === 'whatsapp' ? 'WhatsApp' : 'SMS';
        $line = $ok ? 'Customer told "'.$status.'" on '.$channel : 'Customer NOT told "'.$status.'": '.$n->last_error();
        $this->CI->crm_work_order_model->log($wo_before['id'], 0, 'wa', mb_substr($line, 0, 480));
        return $line;
    }

    // ---- internals -------------------------------------------------------------------

    protected function _summary(array $lines, $amount, $job_total, $scope, $note)
    {
        $parts = array();
        foreach (array_slice($lines, 0, 6) as $l) {
            $q = (float) $l['qty'] != 1 ? ' x'.rtrim(rtrim(number_format((float) $l['qty'], 2, '.', ''), '0'), '.') : '';
            $parts[] = $l['name'].$q.' '.self::rupees($l['amount']);
        }
        if (count($lines) > 6) {
            $parts[] = '+'.(count($lines) - 6).' more';
        }
        $s = implode(', ', $parts).'. '.($scope === 'all' ? 'Total' : 'This work').': '.self::rupees($amount).' incl. GST.';
        if ($scope !== 'all' && round($job_total) > round($amount)) {
            $s .= ' Job total with this: '.self::rupees($job_total).'.';
        }
        if (trim($note) !== '') {
            $s = trim($note).' '.$s;
        }
        return $s;
    }

    protected function _render_pdf(array $wo, array $est, array $lines, array $all, $note)
    {
        $candidates = array(dirname(FCPATH).'/vendor/autoload.php', FCPATH.'vendor/autoload.php', FCPATH.'../vendor/autoload.php');
        foreach ($candidates as $a) {
            if (is_file($a)) {
                require_once $a;
                break;
            }
        }
        if (!class_exists('Dompdf\\Dompdf')) {
            $this->last_error = 'dompdf is not installed (composer install).';
            return NULL;
        }
        $biz_file = APPPATH.'config/marketing_business.php';
        $biz = is_file($biz_file) ? (require $biz_file) : array();
        $ws = function_exists('ymo_review_workshops') ? ymo_review_workshops() : array();
        $html = $this->CI->load->view('pdf/job_estimate', array(
            'wo' => $wo, 'est' => $est, 'lines' => $lines, 'all' => $all, 'note' => $note,
            'biz' => is_array($biz) ? $biz : array(),
            'workshop' => $wo['workshop'] !== '' && isset($ws[$wo['workshop']]) ? $ws[$wo['workshop']]['label'] : '',
        ), TRUE);
        try {
            $d = new \Dompdf\Dompdf(array('isRemoteEnabled' => FALSE, 'isHtml5ParserEnabled' => TRUE));
            $d->loadHtml($html);
            $d->setPaper('A4', 'portrait');
            $d->render();
            return $d->output();
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            log_message('error', '[job_estimate] '.$e->getMessage());
            return NULL;
        }
    }

    /** @return array [ok, error] */
    protected function _send_photo(array $wo, array $p, $phone, $text, $admin_id)
    {
        $this->CI->load->library(array('gcs_client', 'customer_notifier'));
        $bytes = $this->CI->gcs_client->get($p['object_name']);
        if ($bytes === NULL) {
            return array(FALSE, 'photo not found in storage');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'wop').'.jpg';
        file_put_contents($tmp, $bytes);
        $n = $this->CI->customer_notifier;
        $ok = $n->notify($phone, 'service_update', array('ref' => self::ref($wo), 'msg' => self::one_line($text, 600)), array(
            'image' => $tmp, 'booking_id' => (int) $wo['booking_id'] ?: NULL, 'admin_id' => (int) $admin_id,
            'force_whatsapp' => TRUE, 'sms_fallback' => FALSE, 'context' => 'admin.job_photo',
        ));
        @unlink($tmp);
        return array($ok, $ok ? '' : (string) $n->last_error());
    }

    protected function _reply($phone, $text, array $wo)
    {
        $this->CI->load->library('crm_messaging');
        $this->CI->load->model('crm_wa_message_model');
        $res = $this->CI->crm_messaging->send_whatsapp_text($phone, $text);
        $this->CI->crm_wa_message_model->log(array(
            'wa_message_id' => $res['message_id'] ?? NULL,
            'phone'         => $phone,
            'direction'     => 'out',
            'kind'          => 'text',
            'body'          => $text,
            'context_type'  => !empty($wo['booking_id']) ? 'booking' : NULL,
            'context_id'    => !empty($wo['booking_id']) ? (int) $wo['booking_id'] : NULL,
            'status'        => !empty($res['ok']) ? 'sent' : 'failed',
            'error'         => !empty($res['ok']) ? NULL : $this->CI->crm_messaging->last_error(),
        ));
    }

    protected function _call_task(array $wo, array $est)
    {
        $assignee = (int) ($wo['advisor_id'] ?: $wo['created_by'] ?: $est['sent_by']);
        if (!$assignee || !$this->CI->db->table_exists('crm_tasks')) {
            return;
        }
        $this->CI->load->model('crm_task_model');
        $this->CI->crm_task_model->create(array(
            'title'               => mb_substr('Call '.$wo['customer_name'].' ('.$wo['customer_mobile'].') about estimate for '.$wo['vehicle_number'].' · '.$wo['ro_number'], 0, 200),
            'due_at'              => date('Y-m-d H:i:s', time() + 30 * 60),
            'status'              => 'pending',
            'priority'            => 1,
            'assignee_admin_id'   => $assignee,
            'created_by_admin_id' => NULL,
            'notes'               => 'Tapped "Call me" on WhatsApp estimate #'.$est['number'].' ('.self::rupees($est['amount']).'). Job card: '.admin_url('job-cards/'.(int) $wo['id']),
        ));
    }
}
