<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Renders booking service invoices to PDF via dompdf.
 */
class Invoice_pdf
{
    /** @var CI_Controller */
    protected $CI;
    /** @var string|null */
    protected $last_error = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function last_error()
    {
        return $this->last_error;
    }

    /**
     * Generate PDF file for an invoice (find_detailed row with lines).
     *
     * @return string|null Relative path under FCPATH, e.g. uploads/invoices/2026/INV-2026-000001.pdf
     */
    public function generate(array $invoice)
    {
        if (!$this->_load_dompdf()) {
            return NULL;
        }

        $brand_name = $this->CI->config->item('ymo_brand_name') ?: 'Your Mechanic Online';
        $logo_path  = FCPATH.'assets/img/logo.png';
        $logo_src   = file_exists($logo_path) ? $logo_path : NULL;

        $html = $this->CI->load->view('pdf/invoice', array(
            'invoice'    => $invoice,
            'brand_name' => $brand_name,
            'logo_src'   => $logo_src,
        ), TRUE);

        try {
            $dompdf = new \Dompdf\Dompdf(array(
                'isRemoteEnabled' => FALSE,
                'isHtml5ParserEnabled' => TRUE,
            ));
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            log_message('error', '[invoice_pdf] '.$this->last_error);
            return NULL;
        }

        $year     = date('Y', strtotime($invoice['created_at']));
        $dir      = FCPATH.'uploads/invoices/'.$year.'/';
        if (!is_dir($dir) && !mkdir($dir, 0755, TRUE)) {
            $this->last_error = 'Could not create invoice upload directory.';
            log_message('error', '[invoice_pdf] '.$this->last_error);
            return NULL;
        }

        $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $invoice['invoice_number']).'.pdf';
        $full     = $dir.$filename;
        if (file_put_contents($full, $output) === FALSE) {
            $this->last_error = 'Could not write PDF file.';
            log_message('error', '[invoice_pdf] '.$this->last_error);
            return NULL;
        }

        return 'uploads/invoices/'.$year.'/'.$filename;
    }

    protected function _load_dompdf()
    {
        $candidates = array(
            dirname(FCPATH).'vendor/autoload.php',
            FCPATH.'vendor/autoload.php',
        );
        foreach ($candidates as $autoload) {
            if (file_exists($autoload)) {
                require_once $autoload;
                break;
            }
        }
        if (!class_exists('Dompdf\\Dompdf')) {
            $this->last_error = 'dompdf is not installed. Run composer install.';
            log_message('error', '[invoice_pdf] '.$this->last_error);
            return FALSE;
        }
        return TRUE;
    }
}
