<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Thin PHPMailer wrapper used everywhere we send transactional email.
 *
 * PHPMailer is vendored under `application/third_party/PHPMailer/` so the
 * project does not require Composer at install time. If you DO run
 * `composer install`, the Composer autoloader takes precedence — the
 * classes resolve to the same source either way.
 *
 * Public API:
 *   $this->mailer->send($to, $subject, $body_html, $body_text = NULL);
 *   $this->mailer->send_view($to, $subject, $view, $data = []);
 *
 * Falls back to logging in non-production environments when SMTP creds
 * are blank, so signup / booking flows remain testable locally.
 */
class Mailer
{
    /** @var CI_Controller */
    protected $CI;
    /** @var string|null */
    protected $last_error = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->_load_phpmailer();
    }

    /**
     * Load PHPMailer from the vendored copy if Composer hasn't already
     * loaded it. Idempotent — safe to call multiple times.
     */
    protected function _load_phpmailer()
    {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return;
        }
        $base = APPPATH.'third_party/PHPMailer/';
        if (file_exists($base.'PHPMailer.php')) {
            require_once $base.'Exception.php';
            require_once $base.'PHPMailer.php';
            require_once $base.'SMTP.php';
        }
    }

    public function last_error()
    {
        return $this->last_error;
    }

    /**
     * @param string|array $to       Recipient address, or [email => name] map.
     * @param string       $subject
     * @param string       $body_html
     * @param string|null  $body_text Plain-text alternative (auto-generated when NULL).
     * @param array        $attachments Optional [['path' => '/full/path', 'name' => 'file.pdf']]
     */
    public function send($to, $subject, $body_html, $body_text = NULL, array $attachments = array())
    {
        $host = $this->CI->config->item('mail_host');
        $user = $this->CI->config->item('mail_username');
        $pass = $this->CI->config->item('mail_password');

        if (empty($host) || empty($user) || empty($pass)) {
            $this->last_error = 'SMTP credentials are not configured.';
            log_message('error', '[mail] '.$this->last_error);
            if (ENVIRONMENT !== 'production') {
                log_message('debug', sprintf('[mail-stub] to=%s subject=%s', json_encode($to), $subject));
                return TRUE;
            }
            return FALSE;
        }

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $this->last_error = 'PHPMailer is not installed (run composer install).';
            log_message('error', '[mail] '.$this->last_error);
            return FALSE;
        }

        $mail = new PHPMailer(TRUE);
        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = TRUE;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->Port       = (int) $this->CI->config->item('mail_port');
            $enc = strtolower((string) $this->CI->config->item('mail_encryption'));
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(
                $this->CI->config->item('mail_from_email'),
                $this->CI->config->item('mail_from_name')
            );

            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_int($email)) { $email = $name; $name = ''; }
                    $mail->addAddress($email, $name);
                }
            } else {
                $mail->addAddress($to);
            }

            $mail->isHTML(TRUE);
            $mail->Subject = $subject;
            $mail->Body    = $body_html;
            $mail->AltBody = $body_text ?: trim(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body_html)));

            foreach ($attachments as $att) {
                if (!empty($att['path']) && file_exists($att['path'])) {
                    $mail->addAttachment(
                        $att['path'],
                        isset($att['name']) ? $att['name'] : basename($att['path'])
                    );
                }
            }

            return $mail->send();
        } catch (PHPMailerException $e) {
            $this->last_error = $e->getMessage();
            log_message('error', '[mail] '.$this->last_error);
            return FALSE;
        }
    }

    /**
     * Render a view-as-email-body and dispatch it.
     *
     * @param string|array $to
     * @param string       $subject
     * @param string       $view    e.g. 'emails/booking_confirmed'
     * @param array        $data
     */
    public function send_view($to, $subject, $view, array $data = array())
    {
        $body = $this->CI->load->view($view, $data, TRUE);
        return $this->send($to, $subject, $body);
    }

    /**
     * Render a view-as-email-body and dispatch with optional attachments.
     *
     * @param string|array $to
     * @param string       $subject
     * @param string       $view
     * @param array        $data
     * @param array        $attachments
     */
    public function send_view_with_attachment($to, $subject, $view, array $data = array(), array $attachments = array())
    {
        $body = $this->CI->load->view($view, $data, TRUE);
        return $this->send($to, $subject, $body, NULL, $attachments);
    }
}
