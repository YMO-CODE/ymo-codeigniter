<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer pay page for a job card invoice: www.yourmechaniconline.com/pay/<token>.
 * The 24-character token is the only key (sent to the customer on WhatsApp / printed on the invoice).
 */
class Pay extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (function_exists('ymo_load_db_settings')) {
            ymo_load_db_settings();
        }
        $this->load->model('crm_billing_model');
        $this->load->library('job_invoice_service');
    }

    public function show($token = '')
    {
        $inv = $this->crm_billing_model->find_by_token($token);
        if (!$inv) {
            show_404();
        }
        $this->load->library('upi_qr');
        $link = $this->job_invoice_service->upi_link($inv);
        $s = $this->crm_billing_model->settings();
        $biz_file = APPPATH.'config/marketing_business.php';
        $biz = is_file($biz_file) ? (require $biz_file) : array();
        $wa = preg_replace('/\D/', '', (string) ($biz['whatsapp_display'] ?? ''));
        $this->output->set_header('X-Robots-Tag: noindex, nofollow')->set_header('Cache-Control: no-store')
            ->set_header('Referrer-Policy: no-referrer');
        $this->load->view('pay/show', array(
            'inv'      => $inv,
            'seller'   => json_decode((string) $inv['seller_json'], TRUE) ?: array(),
            'upi_link' => $link,
            'qr_svg'   => $link !== '' ? $this->upi_qr->svg($link, 230) : '',
            'vpa'      => $s['bill_upi_vpa'],
            'bank'     => $s['bill_bank_text'],
            'pdf_url'  => current_url().'/invoice.pdf',
            'wa_link'  => $wa !== '' ? 'https://wa.me/'.$wa.'?text='.rawurlencode('I have paid invoice '.$inv['invoice_number'].'. Screenshot attached.') : '',
        ));
    }

    public function pdf($token = '')
    {
        $inv = $this->crm_billing_model->find_by_token($token);
        if (!$inv) {
            show_404();
        }
        $bytes = $this->job_invoice_service->pdf($inv);
        if ($bytes === NULL) {
            show_error('The invoice PDF is not available right now.', 503);
        }
        $this->output->set_header('Content-Type: application/pdf')
            ->set_header('Content-Disposition: inline; filename="'.$inv['invoice_number'].'.pdf"')
            ->set_header('X-Robots-Tag: noindex, nofollow')->set_header('Cache-Control: private, no-store')
            ->set_output($bytes);
    }
}
