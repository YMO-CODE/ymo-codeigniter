<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only worker. Designed to be invoked from the server cron daily:
 *
 *   0 7 * * * cd /var/www/ymo-codeigniter && \
 *       /usr/bin/php public/index.php cli/cron run >> storage/logs/cron.log 2>&1
 *
 * Sub-commands:
 *   run             — booking reminders + CRM tasks + CRM campaigns + OTP purge
 *   purge_otp       — delete expired OTP rows
 *   crm             — CRM-only (campaigns, lead stages)
 *   digest          — one summary email per admin (run at midnight IST)
 */
class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        ymo_load_db_settings();
        $this->load->model(array('reminder_model', 'otp_model'));
        $this->load->library(array('sms_gateway', 'mailer', 'customer_notifier'));
        $this->load->helper('marketing');
    }

    public function run()
    {
        $started = microtime(TRUE);
        $sent = 0; $failed = 0;
        $chunk = (int) $this->config->item('cron_chunk_size');

        $due = $this->reminder_model->due_next_service($chunk);
        foreach ($due as $r) {
            try {
                $sms_ok = TRUE; $mail_ok = TRUE;
                if (in_array($r['channel'], array('sms', 'both'))) {
                    $sms_ok = $this->customer_notifier->notify($r['user_mobile'], 'service_reminder', array(
                        'name'    => strtok($r['user_name'], ' '),
                        'vehicle' => $r['vehicle_number'],
                    ), array('context' => 'cron.service_reminder', 'booking_id' => (int) ($r['booking_id'] ?? 0)));
                }
                if (in_array($r['channel'], array('email', 'both'))) {
                    $mail_ok = $this->mailer->send_view(
                        $r['user_email'],
                        'Time for your next car service',
                        'emails/service_reminder',
                        array('reminder' => $r)
                    );
                }

                if ($sms_ok || $mail_ok) {
                    $this->reminder_model->mark_sent($r['id']);
                    $sent++;
                } else {
                    $err = 'msg='.($this->customer_notifier->last_error() ?: 'fail').' mail='.($this->mailer->last_error() ?: 'fail');
                    $this->reminder_model->mark_failed($r['id'], $err);
                    $failed++;
                }
            } catch (Exception $e) {
                $this->reminder_model->mark_failed($r['id'], $e->getMessage());
                $failed++;
            }
        }

        $review_due = $this->reminder_model->due_review($chunk);
        foreach ($review_due as $r) {
            try {
                $sms_ok = TRUE; $mail_ok = TRUE;
                if (in_array($r['channel'], array('sms', 'both'))) {
                    $sms_ok = $this->customer_notifier->notify($r['user_mobile'], 'review_request', array(
                        'name' => strtok($r['user_name'], ' '),
                        'ref'  => $r['reference'],
                    ), array('context' => 'cron.review_request', 'booking_id' => (int) ($r['booking_id'] ?? 0)));
                }
                if (in_array($r['channel'], array('email', 'both'))) {
                    $mail_ok = $this->mailer->send_view(
                        $r['user_email'],
                        'How did we do? - '.$r['reference'],
                        'emails/review_request',
                        array('booking' => array(
                            'reference'  => $r['reference'],
                            'user_name'  => $r['user_name'],
                            'user_email' => $r['user_email'],
                        ))
                    );
                }

                if ($sms_ok || $mail_ok) {
                    $this->reminder_model->mark_sent($r['id']);
                    $sent++;
                } else {
                    $err = 'msg='.($this->customer_notifier->last_error() ?: 'fail').' mail='.($this->mailer->last_error() ?: 'fail');
                    $this->reminder_model->mark_failed($r['id'], $err);
                    $failed++;
                }
            } catch (Exception $e) {
                $this->reminder_model->mark_failed($r['id'], $e->getMessage());
                $failed++;
            }
        }

        $this->otp_model->purge_expired();
        $crm = $this->_run_crm_jobs();

        $secs = round(microtime(TRUE) - $started, 2);
        $this->_log("cron.run sent=$sent failed=$failed service=".count($due)." review=".count($review_due)
            ." crm_tasks={$crm['tasks']} crm_camps={$crm['campaigns']} in {$secs}s");
    }

    public function crm()
    {
        $crm = $this->_run_crm_jobs();
        $this->_log('cron.crm tasks='.$crm['tasks'].' campaigns='.$crm['campaigns']);
    }

    protected function _run_crm_jobs()
    {
        $out = array('tasks' => 0, 'campaigns' => 0);
        if (!$this->db->table_exists('crm_tasks')) {
            return $out;
        }

        // No email per follow-up any more: everyone gets one summary at midnight (cli/cron digest).
        $this->load->model(array('crm_task_model', 'crm_campaign_model'));

        $camps = $this->crm_campaign_model->due_scheduled(10);
        foreach ($camps as $c) {
            if ($c['status'] === 'scheduled') {
                $stats = $this->crm_campaign_model->recipient_stats($c['id']);
                if (array_sum($stats) === 0) {
                    $segment = json_decode($c['segment_json'] ?: '{}', TRUE) ?: array();
                    $recipients = $this->crm_campaign_model->build_recipients_from_segment($segment, $c['channel']);
                    if ($recipients) {
                        $this->crm_campaign_model->add_recipients($c['id'], $recipients);
                    }
                }
            }
            do {
                $n = $this->crm_campaign_model->process_send_batch($c['id'], $this->mailer, $this->sms_gateway);
                $out['campaigns'] += $n;
                $remaining = $this->crm_campaign_model->pending_recipients($c['id'], 1);
            } while ($n > 0 && !empty($remaining));
        }

        if ($this->db->table_exists('crm_leads') && $this->db->field_exists('next_follow_up_at', 'crm_leads')) {
            $this->load->model('crm_lead_model');
            $out['lead_stages'] = $this->crm_lead_model->recalculate_open_stages();
        }

        return $out;
    }

    /**
     * Midnight summary, one email per active admin who has something to do today:
     * their overdue and today's follow-ups, plus new leads, unread WhatsApp and
     * pending bookings for everyone.
     */
    public function digest()
    {
        if (!$this->db->table_exists('crm_tasks')) {
            return;
        }
        $today = date('Y-m-d 00:00:00');
        $tomorrow = date('Y-m-d 00:00:00', strtotime('tomorrow'));
        $team = array(
            'new_leads' => $this->db->where('created_at >=', date('Y-m-d H:i:s', time() - 86400))
                ->where('deleted_at IS NULL', NULL, FALSE)->count_all_results('crm_leads'),
            'pending_bookings' => $this->db->table_exists('bookings')
                ? $this->db->where('status', 'pending')->count_all_results('bookings') : 0,
            'unread' => 0,
        );
        if ($this->db->table_exists('crm_wa_messages')) {
            $this->load->model('crm_wa_message_model');
            $team['unread'] = (int) $this->crm_wa_message_model->unread_count();
        }

        $admins = $this->db->select('id, name, email')->where('is_active', 1)->where('email !=', '')->get('admin_users')->result_array();
        $sent = 0;
        foreach ($admins as $a) {
            $tasks = $this->db->select('t.id, t.title, t.due_at, t.notes, t.priority, t.lead_id, l.mobile', FALSE)
                ->from('crm_tasks t')->join('crm_leads l', 'l.id = t.lead_id', 'left')
                ->where('t.status', 'pending')->where('t.assignee_admin_id', (int) $a['id'])
                ->where('t.due_at <', $tomorrow)
                ->order_by('t.priority', 'DESC')->order_by('t.due_at', 'ASC')->limit(200)->get()->result_array();
            $overdue = array_values(array_filter($tasks, function ($t) use ($today) { return $t['due_at'] < $today; }));
            $due_today = array_values(array_filter($tasks, function ($t) use ($today) { return $t['due_at'] >= $today; }));
            if (!$tasks && !$team['new_leads'] && !$team['unread'] && !$team['pending_bookings']) {
                continue;
            }
            $subject = 'Today in the CRM: '.count($due_today).' call'.(count($due_today) === 1 ? '' : 's')
                .($overdue ? ', '.count($overdue).' overdue' : '').' · '.date('d M');
            if ($this->mailer->send_view($a['email'], $subject, 'emails/crm_digest', array(
                'admin' => $a, 'overdue' => $overdue, 'due_today' => $due_today, 'team' => $team, 'subject' => $subject,
            ))) {
                $sent++;
            }
        }
        $this->_log('cron.digest sent='.$sent);
    }

    public function purge_otp()
    {
        $this->otp_model->purge_expired();
        $this->_log('cron.purge_otp ok');
    }

    protected function _log($msg)
    {
        $line = '['.date('Y-m-d H:i:s').'] '.$msg.PHP_EOL;
        fwrite(STDOUT, $line);
        @file_put_contents(FCPATH.'../storage/logs/cron.log', $line, FILE_APPEND);
        log_message('info', '[cron] '.$msg);
    }
}
