<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Send a test email via configured SMTP.
 *
 *   php index.php cli/test_mail send you@example.com
 */
class Test_mail extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        $this->load->library('mailer');
    }

    public function send($to = '')
    {
        $to = trim((string) $to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            echo "Usage: php index.php cli/test_mail send you@example.com\n";
            exit(1);
        }

        $host = $this->config->item('mail_host');
        $user = $this->config->item('mail_username');
        $from = $this->config->item('mail_from_email');

        echo "SMTP host: {$host}\n";
        echo "SMTP user: {$user}\n";
        echo "From:      {$from}\n";
        echo "To:        {$to}\n\n";

        $ok = $this->mailer->send(
            $to,
            'YMO SMTP test',
            '<p>If you received this, SMTP is working for Your Mechanic Online.</p>'
        );

        if ($ok) {
            echo "OK — test email sent.\n";
            exit(0);
        }

        echo 'FAIL — '.$this->mailer->last_error()."\n";
        exit(1);
    }
}
