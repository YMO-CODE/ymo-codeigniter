<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci      = &get_instance();
$user    = $ci->session->userdata('user');
$brand   = $ci->config->item('ymo_brand_name');
$marketing = $ci->config->item('ymo_marketing_url');
$phone   = $ci->config->item('ymo_support_phone');

/* Active-state helper. The drawer mirrors the desktop nav so we want the
   same link to highlight in both — driven purely by URL prefix matching. */
$current = trim($ci->uri->uri_string(), '/');
$nav_active = function ($prefix) use ($current) {
    if ($prefix === '') {
        return $current === '' ? 'active' : '';
    }
    return strpos($current, $prefix) === 0 ? 'active' : '';
};
$drawer_active = function ($prefix) use ($nav_active) {
    return $nav_active($prefix) ? 'is-active' : '';
};
$first_name   = $user ? strtok($user['name'], ' ') : '';
$user_initial = $user ? strtoupper(mb_substr($first_name, 0, 1)) : '';
$tel_href     = 'tel:'.preg_replace('/[^+\d]/', '', $phone);
?>
<div class="ymo-topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Trusted car servicing experts at your doorstep</span>
        <span>
            <a href="<?= html_escape($tel_href); ?>"><?= html_escape($phone); ?></a>
            <span class="mx-2 opacity-50">|</span>
            <a href="<?= html_escape($marketing); ?>">Main site</a>
        </span>
    </div>
</div>

<nav class="ymo-navbar navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand ymo-brand" href="<?= site_url('/'); ?>" aria-label="<?= html_escape($brand); ?>">
            <img src="<?= base_url('assets/img/logo.png'); ?>" alt="<?= html_escape($brand); ?>" class="ymo-brand-logo">
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#ymoDrawer"
                aria-controls="ymoDrawer" aria-label="Open menu">
            <span class="mi" aria-hidden="true">menu</span>
        </button>
        <div class="collapse navbar-collapse" id="ymoNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link <?= $nav_active(''); ?>" href="<?= site_url('/'); ?>"><span class="mi mi-sm mi-leading">home</span>Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $nav_active('packages'); ?>" href="<?= site_url('packages'); ?>"><span class="mi mi-sm mi-leading">build</span>Packages</a></li>
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link <?= $nav_active('account/bookings'); ?>" href="<?= site_url('account/bookings'); ?>"><span class="mi mi-sm mi-leading">event_note</span>My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link <?= $nav_active('vehicles'); ?>" href="<?= site_url('vehicles'); ?>"><span class="mi mi-sm mi-leading">directions_car</span>My Vehicles</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('account'); ?>"><span class="mi mi-sm mi-leading">account_circle</span>Hi, <?= html_escape($first_name); ?></a></li>
                    <li class="nav-item">
                        <form action="<?= site_url('logout'); ?>" method="post" class="d-inline">
                            <?= form_hidden($ci->security->get_csrf_token_name(), $ci->security->get_csrf_hash()); ?>
                            <button type="submit" class="btn btn-link nav-link"><span class="mi mi-sm mi-leading">logout</span>Sign out</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('login'); ?>"><span class="mi mi-sm mi-leading">login</span>Sign in</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-primary" href="<?= site_url('signup'); ?>"><span class="mi mi-sm mi-leading">arrow_forward</span>Get started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<aside class="offcanvas offcanvas-end ymo-drawer" tabindex="-1" id="ymoDrawer"
       aria-labelledby="ymoDrawerLabel" data-bs-scroll="false">
    <header class="ymo-drawer-head">
        <a href="<?= site_url('/'); ?>" id="ymoDrawerLabel" aria-label="<?= html_escape($brand); ?>">
            <img src="<?= base_url('assets/img/logo.png'); ?>" alt="<?= html_escape($brand); ?>" class="ymo-brand-logo">
        </a>
        <button type="button" class="ymo-drawer-close" data-bs-dismiss="offcanvas" aria-label="Close menu">
            <span class="mi" aria-hidden="true">close</span>
        </button>
    </header>

    <?php if ($user): ?>
        <a href="<?= site_url('account'); ?>" class="ymo-drawer-user text-decoration-none">
            <span class="ymo-drawer-avatar" aria-hidden="true"><?= html_escape($user_initial); ?></span>
            <span class="ymo-drawer-user-meta">
                <strong><?= html_escape($user['name']); ?></strong>
                <small>+91 <?= html_escape(substr($user['mobile'], -10)); ?></small>
            </span>
        </a>
    <?php endif; ?>

    <div class="ymo-drawer-body">
        <nav class="ymo-drawer-section" aria-label="Primary">
            <a class="ymo-drawer-link <?= $drawer_active(''); ?>" href="<?= site_url('/'); ?>">
                <span class="mi" aria-hidden="true">home</span>Home
            </a>
            <a class="ymo-drawer-link <?= $drawer_active('packages'); ?>" href="<?= site_url('packages'); ?>">
                <span class="mi" aria-hidden="true">build</span>Packages
            </a>
            <?php if ($user): ?>
                <a class="ymo-drawer-link <?= $drawer_active('account/bookings'); ?>" href="<?= site_url('account/bookings'); ?>">
                    <span class="mi" aria-hidden="true">event_note</span>My Bookings
                </a>
                <a class="ymo-drawer-link <?= $drawer_active('vehicles'); ?>" href="<?= site_url('vehicles'); ?>">
                    <span class="mi" aria-hidden="true">directions_car</span>My Vehicles
                </a>
            <?php endif; ?>
        </nav>

        <hr class="ymo-drawer-div">

        <nav class="ymo-drawer-section" aria-label="Account &amp; support">
            <?php if ($user): ?>
                <a class="ymo-drawer-link <?= $drawer_active('account/profile'); ?>" href="<?= site_url('account/profile'); ?>">
                    <span class="mi" aria-hidden="true">manage_accounts</span>Profile
                </a>
            <?php endif; ?>
            <a class="ymo-drawer-link" href="<?= html_escape($tel_href); ?>">
                <span class="mi" aria-hidden="true">call</span>Call us
                <span class="ms-auto small ymo-muted"><?= html_escape($phone); ?></span>
            </a>
            <a class="ymo-drawer-link" href="<?= html_escape($marketing); ?>" target="_blank" rel="noopener">
                <span class="mi" aria-hidden="true">language</span>Main site
                <span class="ms-auto mi mi-sm" aria-hidden="true">open_in_new</span>
            </a>
        </nav>
    </div>

    <footer class="ymo-drawer-foot">
        <?php if ($user): ?>
            <a href="<?= site_url('packages'); ?>" class="btn btn-primary w-100">
                <span class="mi mi-leading">add</span>Book a service
            </a>
            <div class="ymo-drawer-foot-secondary">
                <?= form_open(site_url('logout'), array('class' => 'd-inline')); ?>
                    <button type="submit" class="btn btn-link p-0 small">
                        <span class="mi mi-sm mi-leading">logout</span>Sign out
                    </button>
                <?= form_close(); ?>
            </div>
        <?php else: ?>
            <a href="<?= site_url('signup'); ?>" class="btn btn-primary w-100">
                <span class="mi mi-leading">arrow_forward</span>Get started
            </a>
            <div class="ymo-drawer-foot-secondary">
                Already a member? <a href="<?= site_url('login'); ?>">Sign in</a>
            </div>
        <?php endif; ?>
        <p class="ymo-drawer-tagline mb-0">&copy; <?= date('Y'); ?> <?= html_escape($brand); ?></p>
    </footer>
</aside>
