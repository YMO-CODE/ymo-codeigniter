<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Job card status changes, shared by the advisor screen and the technician phone app.
 * Keeps the linked booking in step: work started → booking In progress;
 * delivered → booking Completed (reminders, referral, customer notice, review request).
 */
class Work_order_flow
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('crm_work_order_model');
        $this->CI->load->library('audit');
    }

    /**
     * @param array    $wo       job card row (find())
     * @param int      $admin_id advisor making the change, or 0
     * @param int|null $tech_id  technician making the change (phone app), or NULL
     * @return array [bool ok, string message, string level success|warning|error]
     */
    public function change(array $wo, $to, $note, $admin_id, $tech_id = NULL)
    {
        $id = (int) $wo['id'];
        $S = Crm_work_order_model::STATUSES;
        if (!isset($S[$to])) {
            return array(FALSE, 'Unknown status.', 'error');
        }
        if ($tech_id && !in_array($to, Crm_work_order_model::TECH_MOVES[$wo['status']] ?? array(), TRUE)) {
            return array(FALSE, 'Not allowed from '.$S[$wo['status']].'.', 'error');
        }
        if ($to === 'delivered' && !$this->CI->crm_work_order_model->lines($id)) {
            return array(FALSE, 'Add at least one service or part before delivering.', 'error');
        }
        if (!$this->CI->crm_work_order_model->set_status($id, $to, (int) $admin_id, (string) $note, $tech_id)) {
            return array(FALSE, 'That status change is not allowed from '.$S[$wo['status']].'.', 'error');
        }
        $msg = $S[$to].'.';
        $level = 'success';
        $b_to = NULL;

        if (!empty($wo['booking_id'])) {
            if (in_array($to, array('in_progress', 'on_hold', 'ready'), TRUE) && in_array($wo['booking_status'], array('pending', 'confirmed'), TRUE)) {
                $b_to = 'in_progress';
            } elseif ($to === 'delivered' && !in_array($wo['booking_status'], array('completed', 'cancelled'), TRUE)) {
                $b_to = 'completed';
            }
            if ($b_to) {
                $this->CI->load->library('booking_status_service');
                list($ok, $notice) = $this->CI->booking_status_service->change(
                    (int) $wo['booking_id'], $b_to, NULL, (int) $admin_id, $tech_id ? 'technician:'.(int) $tech_id : 'job_card'
                );
                $label = str_replace('_', ' ', $b_to);
                $msg .= ' Booking '.$wo['booking_reference'].' marked '.$label.'.'.($notice !== '' ? ' '.$notice : '');
                $this->CI->crm_work_order_model->log($id, (int) $admin_id, 'booking', 'Booking '.$wo['booking_reference'].' → '.$label, $tech_id);
                if (!$ok) {
                    $level = 'warning';
                }
            }
        }
        if ($to === 'delivered') {
            $this->CI->load->model('crm_billing_model');
            $inv = $this->CI->crm_billing_model->ensure_schema() ? $this->CI->crm_billing_model->for_work_order($id) : NULL;
            if (!$inv) {
                $msg .= ' No invoice raised yet: raise it from the Invoice & payment box.';
                $level = 'warning';
            } elseif ((float) $inv['balance'] > 0) {
                $msg .= ' '.$inv['invoice_number'].' still has '.number_format((float) $inv['balance'], 2).' due.';
            }
        }
        // Short WhatsApp / SMS update for the customer (unless the booking just sent one).
        $this->CI->load->library('work_order_customer');
        $line = $this->CI->work_order_customer->notify_stage($wo, $to, $b_to !== NULL);
        if ($line !== '') {
            $msg .= ' '.$line.'.';
            if (strpos($line, 'NOT') !== FALSE) {
                $level = 'warning';
            }
        }

        $this->CI->audit->log($tech_id ? 'system' : 'admin', $tech_id ? 0 : (int) $admin_id, 'work_order.status', 'work_order', $id,
            array('from' => $wo['status'], 'to' => $to) + ($tech_id ? array('technician_id' => (int) $tech_id) : array()));
        return array(TRUE, $msg, $level);
    }
}
