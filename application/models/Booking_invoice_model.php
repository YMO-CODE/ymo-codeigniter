<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_invoice_model extends CI_Model
{
    const TABLE      = 'booking_invoices';
    const LINE_TABLE = 'booking_invoice_lines';

    /** @return array{subtotal:float,cgst_amount:float,sgst_amount:float,igst_amount:float,grand_total:float} */
    public function compute_gst($subtotal, $gst_type, $gst_rate)
    {
        $subtotal = round((float) $subtotal, 2);
        $rate     = max(0, min(28, (float) $gst_rate));
        $tax      = round($subtotal * $rate / 100, 2);

        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;

        if ($rate > 0) {
            if ($gst_type === 'inter') {
                $igst = $tax;
            } else {
                $half = round($tax / 2, 2);
                $cgst = $half;
                $sgst = round($tax - $half, 2);
            }
        }

        return array(
            'subtotal'     => $subtotal,
            'cgst_amount'  => $cgst,
            'sgst_amount'  => $sgst,
            'igst_amount'  => $igst,
            'grand_total'  => round($subtotal + $cgst + $sgst + $igst, 2),
        );
    }

    /**
     * @param array[] $lines Each: description, amount
     * @param array   $admin Session admin row (id, name)
     */
    public function create_for_booking($booking_id, array $admin, array $lines, $gst_type, $gst_rate, $notes = NULL)
    {
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['amount'];
        }
        $totals = $this->compute_gst($subtotal, $gst_type, $gst_rate);

        $this->db->trans_start();

        $invoice_number = $this->_make_invoice_number();
        $this->db->insert(self::TABLE, array(
            'booking_id'          => (int) $booking_id,
            'invoice_number'      => $invoice_number,
            'created_by_admin_id' => (int) $admin['id'],
            'created_by_name'     => $admin['name'],
            'subtotal'            => $totals['subtotal'],
            'gst_type'            => $gst_type === 'inter' ? 'inter' : 'intra',
            'gst_rate'            => round((float) $gst_rate, 2),
            'cgst_amount'         => $totals['cgst_amount'],
            'sgst_amount'         => $totals['sgst_amount'],
            'igst_amount'         => $totals['igst_amount'],
            'grand_total'         => $totals['grand_total'],
            'notes'               => $notes !== '' && $notes !== NULL ? $notes : NULL,
            'created_at'          => date('Y-m-d H:i:s'),
        ));
        $invoice_id = (int) $this->db->insert_id();

        $sort = 0;
        foreach ($lines as $line) {
            $this->db->insert(self::LINE_TABLE, array(
                'invoice_id'  => $invoice_id,
                'sort_order'  => $sort++,
                'description' => $line['description'],
                'amount'      => round((float) $line['amount'], 2),
            ));
        }

        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return NULL;
        }

        return $this->find_detailed($invoice_id);
    }

    /**
     * Update an existing invoice (lines, GST, notes). Invoice number and creator unchanged.
     *
     * @param array[] $lines Each: description, amount
     */
    public function update($invoice_id, array $lines, $gst_type, $gst_rate, $notes = NULL)
    {
        $existing = $this->find((int) $invoice_id);
        if (!$existing) {
            return NULL;
        }

        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['amount'];
        }
        $totals = $this->compute_gst($subtotal, $gst_type, $gst_rate);

        $this->db->trans_start();

        $this->db->where('id', (int) $invoice_id)->update(self::TABLE, array(
            'subtotal'    => $totals['subtotal'],
            'gst_type'    => $gst_type === 'inter' ? 'inter' : 'intra',
            'gst_rate'    => round((float) $gst_rate, 2),
            'cgst_amount' => $totals['cgst_amount'],
            'sgst_amount' => $totals['sgst_amount'],
            'igst_amount' => $totals['igst_amount'],
            'grand_total' => $totals['grand_total'],
            'notes'       => $notes !== '' && $notes !== NULL ? $notes : NULL,
        ));

        $this->db->where('invoice_id', (int) $invoice_id)->delete(self::LINE_TABLE);
        $sort = 0;
        foreach ($lines as $line) {
            $this->db->insert(self::LINE_TABLE, array(
                'invoice_id'  => (int) $invoice_id,
                'sort_order'  => $sort++,
                'description' => $line['description'],
                'amount'      => round((float) $line['amount'], 2),
            ));
        }

        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return NULL;
        }

        return $this->find_detailed($invoice_id);
    }

    public function for_booking($booking_id)
    {
        return $this->db
            ->where('booking_id', (int) $booking_id)
            ->order_by('created_at', 'DESC')
            ->get(self::TABLE)
            ->result_array();
    }

    public function find($id)
    {
        return $this->db->get_where(self::TABLE, array('id' => (int) $id))->row_array();
    }

    public function find_detailed($id)
    {
        $invoice = $this->db
            ->select('i.*, b.reference AS booking_reference, b.status AS booking_status,
                      u.name AS user_name, u.mobile AS user_mobile, u.email AS user_email,
                      v.variant AS vehicle_variant, v.vehicle_number, m.name AS vehicle_make,
                      p.name AS package_name', FALSE)
            ->from(self::TABLE.' i')
            ->join('bookings b', 'b.id = i.booking_id', 'inner')
            ->join('users u', 'u.id = b.user_id', 'left')
            ->join('vehicles v', 'v.id = b.vehicle_id', 'left')
            ->join('vehicle_makes m', 'm.id = v.make_id', 'left')
            ->join('service_packages p', 'p.id = b.package_id', 'left')
            ->where('i.id', (int) $id)
            ->get()
            ->row_array();

        if (!$invoice) {
            return NULL;
        }

        $invoice['lines'] = $this->db
            ->where('invoice_id', (int) $id)
            ->order_by('sort_order', 'ASC')
            ->get(self::LINE_TABLE)
            ->result_array();

        return $invoice;
    }

    public function find_for_booking_user($invoice_id, $booking_id, $user_id)
    {
        $invoice = $this->db
            ->select('i.*', FALSE)
            ->from(self::TABLE.' i')
            ->join('bookings b', 'b.id = i.booking_id', 'inner')
            ->where('i.id', (int) $invoice_id)
            ->where('i.booking_id', (int) $booking_id)
            ->where('b.user_id', (int) $user_id)
            ->get()
            ->row_array();

        if (!$invoice) {
            return NULL;
        }

        $invoice['lines'] = $this->db
            ->where('invoice_id', (int) $invoice_id)
            ->order_by('sort_order', 'ASC')
            ->get(self::LINE_TABLE)
            ->result_array();

        return $invoice;
    }

    public function update_pdf_path($id, $pdf_path)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'pdf_path' => $pdf_path,
        ));
    }

    public function mark_sms_sent($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'sms_sent_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function mark_email_sent($id)
    {
        $this->db->where('id', (int) $id)->update(self::TABLE, array(
            'email_sent_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected function _make_invoice_number()
    {
        $prefix = 'INV-'.date('Y').'-';
        $row = $this->db->select('invoice_number')
            ->like('invoice_number', $prefix, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get(self::TABLE)
            ->row_array();
        $next = $row ? ((int) substr($row['invoice_number'], strlen($prefix)) + 1) : 1;
        return $prefix.str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
