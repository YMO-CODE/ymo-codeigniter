<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp message templates — read-only mirror of what is approved in
 * WhatsApp Manager. Templates are created/edited there; "Sync" pulls the
 * current list (GET /{waba_id}/message_templates) so campaigns and booking
 * notifications can use them. Nothing here writes to Meta.
 */
class Wa_templates extends Crm_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('crm_wa_template_model');
        $this->load->library(array('crm_messaging', 'audit'));
    }

    public function index()
    {
        $this->require_perm('campaigns.view');

        $ready = $this->crm_wa_template_model->table_ready();
        $this->config->load('whatsapp_template_library', TRUE);
        $this->render('admin/wa_templates/index', array(
            'title'        => 'WhatsApp templates',
            'migrated'     => $ready,
            'rows'         => $ready ? $this->crm_wa_template_model->all() : array(),
            'can_sync'     => crm_can('campaigns.edit'),
            'api_ready'    => $this->crm_messaging->can_sync_whatsapp_templates(),
            'send_ready'   => $this->crm_messaging->can_send_whatsapp(),
            'notify_on'    => (bool) $this->config->item('crm_whatsapp_notify_enabled'),
            'event_map'    => (array) $this->config->item('crm_whatsapp_templates'),
            'library'      => (array) $this->config->item('wa_template_library', 'whatsapp_template_library'),
        ));
    }

    public function sync()
    {
        $this->require_perm('campaigns.edit');
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
        if (!$this->crm_wa_template_model->table_ready()) {
            $this->flash('error', 'Run database/crm_migration_v5_whatsapp.sql first.');
            redirect(admin_url('whatsapp-templates'));
        }

        $remote = $this->crm_messaging->fetch_whatsapp_templates();
        if ($remote === NULL) {
            $this->flash('error', 'Sync failed: '.($this->crm_messaging->last_error() ?: 'unknown error'));
            redirect(admin_url('whatsapp-templates'));
        }

        $stats = $this->crm_wa_template_model->sync_from_meta($remote);
        $this->audit->log('admin', $this->admin['id'], 'crm.wa_templates.sync', 'crm_wa_template', NULL, $stats);
        $this->flash('success', sprintf('Templates synced: %d new, %d updated, %d removed.',
            $stats['added'], $stats['updated'], $stats['removed']));
        redirect(admin_url('whatsapp-templates'));
    }

    /** POST whatsapp-templates/submit {name} — send a ready-to-use template to Meta for approval. */
    public function submit()
    {
        $this->require_perm('campaigns.edit');
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }
        $this->config->load('whatsapp_template_library', TRUE);
        $name = (string) $this->input->post('name');
        $tpl = NULL;
        foreach ((array) $this->config->item('wa_template_library', 'whatsapp_template_library') as $t) {
            if ($t['name'] === $name) {
                $tpl = $t;
            }
        }
        if (!$tpl) {
            $this->flash('error', 'Unknown template.');
            redirect(admin_url('whatsapp-templates'));
        }
        $id = $this->crm_messaging->create_whatsapp_template($tpl, 'en');
        if (!$id) {
            $this->flash('error', 'Meta did not accept '.$name.': '.($this->crm_messaging->last_error() ?: 'unknown error'));
            redirect(admin_url('whatsapp-templates'));
        }
        $this->audit->log('admin', $this->admin['id'], 'crm.wa_templates.submit', 'crm_wa_template', NULL, array('name' => $name, 'meta_id' => $id));
        $remote = $this->crm_messaging->fetch_whatsapp_templates();
        if ($remote !== NULL && $this->crm_wa_template_model->table_ready()) {
            $this->crm_wa_template_model->sync_from_meta($remote);
        }
        $this->flash('success', 'Submitted '.$name.' to Meta. Approval usually takes a few minutes to a day; press Sync to see the status.');
        redirect(admin_url('whatsapp-templates'));
    }
}
