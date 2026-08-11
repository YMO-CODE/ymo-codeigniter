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
 *   crm             — CRM-only (tasks + campaigns)
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
        $this->load->library(array('sms_gateway', 'mailer'));
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
                    $sms_ok = $this->sms_gateway->send_template($r['user_mobile'], 'service_reminder', array(
                        'name'    => strtok($r['user_name'], ' '),
                        'vehicle' => $r['vehicle_number'],
                    ));
                    ymo_sms_log('cron.service_reminder', 'service_reminder', $r['user_mobile'], $sms_ok, $this->sms_gateway);
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
                    $err = 'sms='.($this->sms_gateway->last_error() ?: 'fail').' mail='.($this->mailer->last_error() ?: 'fail');
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
                    $sms_ok = $this->sms_gateway->send_template($r['user_mobile'], 'review_request', array(
                        'name' => strtok($r['user_name'], ' '),
                        'ref'  => $r['reference'],
                    ));
                    ymo_sms_log('cron.review_request', 'review_request', $r['user_mobile'], $sms_ok, $this->sms_gateway);
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
                    $err = 'sms='.($this->sms_gateway->last_error() ?: 'fail').' mail='.($this->mailer->last_error() ?: 'fail');
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

        $this->load->model(array('crm_task_model', 'crm_campaign_model'));
        $hours = (int) $this->config->item('crm_task_reminder_hours');
        $tasks = $this->crm_task_model->due_for_reminder($hours, 100);
        foreach ($tasks as $t) {
            if (empty($t['assignee_email'])) {
                continue;
            }
            $ok = $this->mailer->send_view(
                $t['assignee_email'],
                'CRM follow-up due: '.$t['title'],
                'emails/task_reminder',
                array('task' => $t)
            );
            if ($ok) {
                $this->crm_task_model->mark_reminder_sent($t['id']);
                $out['tasks']++;
            }
        }

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
