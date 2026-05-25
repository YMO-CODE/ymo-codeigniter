<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci      = &get_instance();
$admin   = $ci->session->userdata('admin');
$brand_name = $ci->config->item('ymo_brand_name');
$page_title = isset($title) ? $title.' — Admin · '.$brand_name : 'Admin · '.$brand_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= html_escape($page_title); ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png'); ?>">
    <meta name="theme-color" content="#1d2026">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/ymo.css'); ?>">
</head>
<body class="ymo-admin-body">
<div class="ymo-admin-shell">
    <aside class="ymo-admin-side">
        <a class="ymo-admin-brand" href="<?= admin_url('dashboard'); ?>" aria-label="<?= html_escape($brand_name); ?> Admin">
            <img src="<?= base_url('assets/img/logo.png'); ?>" alt="<?= html_escape($brand_name); ?>">
        </a>
        <div class="ymo-admin-brand-tag">Admin Panel</div>
        <nav>
            <?php if (!function_exists('crm_can') || crm_can('dashboard.view')): ?>
            <a href="<?= admin_url('dashboard'); ?>" class="<?= admin_nav_active('dashboard') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">dashboard</span>Dashboard</a>
            <?php endif; ?>
            <?php if (!function_exists('crm_can') || crm_can('bookings.view')): ?>
            <a href="<?= admin_url('bookings'); ?>" class="<?= admin_nav_active('bookings') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">event_note</span>Bookings</a>
            <?php endif; ?>
            <?php if (!function_exists('crm_can') || crm_can('customers.view')): ?>
            <a href="<?= admin_url('customers'); ?>" class="<?= admin_nav_active('customers') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">groups</span>Customers</a>
            <?php endif; ?>
            <?php if (!function_exists('crm_can') || crm_can('packages.view')): ?>
            <a href="<?= admin_url('packages'); ?>" class="<?= admin_nav_active('packages') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">build</span>Packages</a>
            <?php endif; ?>
            <?php if (function_exists('crm_can') && crm_can('leads.view')): ?>
            <hr style="border-color:rgba(255,255,255,0.1); margin:0.75rem 1.5rem">
            <div class="ymo-admin-nav-label">CRM</div>
            <a href="<?= admin_url('leads'); ?>" class="<?= admin_nav_active('leads') && ymo_admin_nav_path_normalized() !== 'leads/pipeline' ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">person_search</span>Leads</a>
            <a href="<?= admin_url('leads/pipeline'); ?>" class="<?= ymo_admin_nav_path_normalized() === 'leads/pipeline' ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">view_kanban</span>Pipeline</a>
            <?php $this->load->view('layout/partials/admin_crm_nav'); ?>
            <?php endif; ?>
            <?php if (function_exists('crm_can') && (crm_can('team.view') || crm_can('roles.view'))): ?>
            <hr style="border-color:rgba(255,255,255,0.1); margin:0.75rem 1.5rem">
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
            <hr style="border-color:rgba(255,255,255,0.1)">
            <form action="<?= admin_url('logout'); ?>" method="post" style="padding:0 1.5rem;">
                <?= form_hidden($ci->security->get_csrf_token_name(), $ci->security->get_csrf_hash()); ?>
                <button class="btn btn-sm btn-outline-light w-100"><span class="mi mi-sm mi-leading">logout</span>Sign out</button>
            </form>
        </nav>
    </aside>
    <section class="ymo-admin-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0"><?= isset($title) ? html_escape($title) : 'Admin'; ?></h1>
            <small class="ymo-muted">Signed in as <strong><?= html_escape($admin['name'] ?? 'Admin'); ?></strong></small>
        </div>
        <?php $this->load->view('layout/partials/flash'); ?>
        <?= $content; ?>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/ymo.js'); ?>"></script>
</body>
</html>
