<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (!function_exists('crm_can') || crm_can('dashboard.view')): ?>
<a href="<?= admin_url('dashboard'); ?>" class="<?= admin_nav_active('dashboard') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">dashboard</span>Dashboard</a>
<?php endif; ?>
<?php if (!function_exists('crm_can') || crm_can('bookings.view')): ?>
<a href="<?= admin_url('bookings'); ?>" class="<?= admin_nav_active('bookings') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">event_note</span>Bookings</a>
<?php endif; ?>
<?php if (!function_exists('crm_can') || crm_can('customers.view')): ?>
<a href="<?= admin_url('online-accounts'); ?>" class="<?= admin_nav_active('online-accounts') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">person</span>Online accounts</a>
<?php endif; ?>
<?php if (!function_exists('crm_can') || crm_can('packages.view')): ?>
<a href="<?= admin_url('packages'); ?>" class="<?= admin_nav_active('packages') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">build</span>Packages</a>
<?php endif; ?>
<?php if (!function_exists('crm_can') || crm_can('offers.view')): ?>
<a href="<?= admin_url('offers'); ?>" class="<?= admin_nav_active('offers') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">campaign</span>Offers</a>
<?php endif; ?>
<?php if (function_exists('crm_can') && crm_can('leads.view')): ?>
<hr class="ymo-admin-nav-div">
<div class="ymo-admin-nav-label">CRM</div>
<a href="<?= admin_url('leads'); ?>" class="<?= admin_nav_active('leads') && ymo_admin_nav_path_normalized() !== 'leads/pipeline' && empty($_GET['source']) ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">person_search</span>Leads</a>
<a href="<?= admin_url('leads/pipeline'); ?>" class="<?= ymo_admin_nav_path_normalized() === 'leads/pipeline' ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">view_kanban</span>Pipeline</a>
<?php if (crm_can('contacts.view')): ?>
<a href="<?= admin_url('customers'); ?>" class="<?= admin_nav_active('customers') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">groups</span>Customers</a>
<?php endif; ?>
<a href="<?= admin_url('leads?source=cold_call'); ?>" class="<?= !empty($_GET['source']) && $_GET['source'] === 'cold_call' ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">call</span>Cold calling</a>
<a href="<?= admin_url('leads?source=offline_marketing'); ?>" class="<?= !empty($_GET['source']) && $_GET['source'] === 'offline_marketing' ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">storefront</span>Offline marketing</a>
<a href="<?= admin_url('leads?source=instagram'); ?>" class="<?= !empty($_GET['source']) && $_GET['source'] === 'instagram' ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">photo_camera</span>Instagram leads</a>
<a href="<?= admin_url('leads?source=referral'); ?>" class="<?= !empty($_GET['source']) && $_GET['source'] === 'referral' ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">share</span>Referral leads</a>
<?php $this->load->view('layout/partials/admin_crm_nav'); ?>
<?php endif; ?>
<?php if (function_exists('crm_can') && (crm_can('team.view') || crm_can('roles.view'))): ?>
<hr class="ymo-admin-nav-div">
<div class="ymo-admin-nav-label">Administration</div>
<?php if (crm_can('team.view')): ?>
<a href="<?= admin_url('team'); ?>" class="<?= admin_nav_active('team') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">group</span>Team</a>
<?php endif; ?>
<?php if (crm_can('roles.view')): ?>
<a href="<?= admin_url('roles'); ?>" class="<?= admin_nav_active('roles') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">admin_panel_settings</span>Roles</a>
<?php endif; ?>
<?php endif; ?>
<?php if (!function_exists('crm_can') || crm_can('settings.manage')): ?>
<a href="<?= admin_url('settings'); ?>" class="<?= admin_nav_active('settings') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">settings</span>Settings</a>
<?php endif; ?>
<hr class="ymo-admin-nav-div">
<?php $ci = &get_instance(); ?>
<form action="<?= admin_url('logout'); ?>" method="post" class="ymo-admin-nav-logout">
    <?= form_hidden($ci->security->get_csrf_token_name(), $ci->security->get_csrf_hash()); ?>
    <button class="btn btn-sm btn-outline-light w-100" type="submit"><span class="mi mi-sm mi-leading">logout</span>Sign out</button>
</form>
