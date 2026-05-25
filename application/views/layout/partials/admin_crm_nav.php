<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (function_exists('crm_can')): ?>
    <?php if (crm_can('tasks.view')): ?>
        <a href="<?= admin_url('tasks'); ?>" class="<?= admin_nav_active('tasks') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">task_alt</span>Follow-ups</a>
    <?php endif; ?>
    <?php if (crm_can('contacts.view')): ?>
        <a href="<?= admin_url('contacts'); ?>" class="<?= admin_nav_active('contacts') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">contacts</span>Contacts</a>
    <?php endif; ?>
    <?php if (crm_can('campaigns.view')): ?>
        <a href="<?= admin_url('campaigns'); ?>" class="<?= admin_nav_active('campaigns') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">campaign</span>Campaigns</a>
    <?php endif; ?>
    <?php if (crm_can('recruitment.view')): ?>
        <a href="<?= admin_url('recruitment'); ?>" class="<?= admin_nav_active('recruitment') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">badge</span>Recruitment</a>
    <?php endif; ?>
    <?php if (crm_can('reports.view')): ?>
        <a href="<?= admin_url('reports'); ?>" class="<?= admin_nav_active('reports') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">analytics</span>Reports</a>
    <?php endif; ?>
<?php endif; ?>
