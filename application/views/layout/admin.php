<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci      = &get_instance();
$admin   = $ci->session->userdata('admin');
$brand_name = $ci->config->item('ymo_brand_name');
$page_title = isset($title) ? $title.' - Admin · '.$brand_name : 'Admin · '.$brand_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title><?= html_escape($page_title); ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png'); ?>">
    <meta name="theme-color" content="#1d2026">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/ymo.css?v='.(int) @filemtime(FCPATH.'assets/css/ymo.css')); ?>">
</head>
<body class="ymo-admin-body">
<div class="ymo-admin-shell">
    <header class="ymo-admin-mobile-bar d-md-none">
        <button class="ymo-admin-menu-btn" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#ymoAdminDrawer"
                aria-controls="ymoAdminDrawer" aria-label="Open admin menu">
            <span class="mi" aria-hidden="true">menu</span>
        </button>
        <div class="ymo-admin-mobile-bar-meta">
            <span class="ymo-admin-mobile-bar-title"><?= isset($title) ? html_escape($title) : 'Admin'; ?></span>
            <small><?= html_escape($admin['name'] ?? 'Admin'); ?></small>
        </div>
    </header>

    <aside class="ymo-admin-side d-none d-md-block">
        <a class="ymo-admin-brand" href="<?= admin_url('dashboard'); ?>" aria-label="<?= html_escape($brand_name); ?> Admin">
            <img src="<?= base_url('assets/img/logo.png'); ?>" alt="<?= html_escape($brand_name); ?>">
        </a>
        <div class="ymo-admin-brand-tag">Admin Panel</div>
        <nav>
            <?php $this->load->view('layout/partials/admin_nav'); ?>
        </nav>
    </aside>

    <aside class="offcanvas offcanvas-start ymo-admin-drawer d-md-none" tabindex="-1"
           id="ymoAdminDrawer" aria-labelledby="ymoAdminDrawerLabel">
        <div class="ymo-admin-drawer-head">
            <a href="<?= admin_url('dashboard'); ?>" id="ymoAdminDrawerLabel" class="ymo-admin-drawer-brand">
                <img src="<?= base_url('assets/img/logo.png'); ?>" alt="<?= html_escape($brand_name); ?>">
            </a>
            <button type="button" class="ymo-drawer-close" data-bs-dismiss="offcanvas" aria-label="Close menu">
                <span class="mi" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="ymo-admin-drawer-tag">Admin Panel</div>
        <nav class="ymo-admin-drawer-nav" data-admin-drawer-nav>
            <?php $this->load->view('layout/partials/admin_nav'); ?>
        </nav>
    </aside>

    <section class="ymo-admin-main">
        <div class="d-none d-md-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h1 class="h4 mb-0"><?= isset($title) ? html_escape($title) : 'Admin'; ?></h1>
            <small class="ymo-muted">Signed in as <strong><?= html_escape($admin['name'] ?? 'Admin'); ?></strong></small>
        </div>
        <?php $this->load->view('layout/partials/flash'); ?>
        <?= $content; ?>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/ymo.js?v='.(int) @filemtime(FCPATH.'assets/js/ymo.js')); ?>"></script>
</body>
</html>
