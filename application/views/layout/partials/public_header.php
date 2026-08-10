<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$ci      = &get_instance();
$user    = $ci->session->userdata('user');
$brand   = $ci->config->item('ymo_brand_name');
$phone   = $ci->config->item('ymo_support_phone');
$booking_nav = ymo_show_booking_nav();

$current = trim($ci->uri->uri_string(), '/');

$nav_active = function ($prefix) use ($current, $booking_nav) {
    if (!$booking_nav) {
        if ($prefix === '' && $current === '') {
            return 'active';
        }
        return ($prefix !== '' && strpos($current, $prefix) === 0) ? 'active' : '';
    }
    if ($prefix === '') {
        return $current === '' ? 'active' : '';
    }
    return strpos($current, $prefix) === 0 ? 'active' : '';
};

$drawer_active = function ($prefix) use ($nav_active) {
    return $nav_active($prefix) ? 'is-active' : '';
};

$drawer_path_active = function ($path) use ($current) {
    $path = trim((string) $path, '/');
    return ($path !== '' && ($current === $path || strpos($current, $path.'/') === 0)) ? 'is-active' : '';
};

$marketing_nav = (!$booking_nav && function_exists('marketing_public_nav_items'))
    ? marketing_public_nav_items()
    : array('services' => array(), 'locations' => array(), 'luxury' => array());

$services_active  = !$booking_nav && function_exists('marketing_nav_services_active') && marketing_nav_services_active($current);
$locations_active = !$booking_nav && function_exists('marketing_nav_locations_active') && marketing_nav_locations_active($current);
$luxury_active    = !$booking_nav && function_exists('marketing_nav_luxury_active') && marketing_nav_luxury_active($current);
$luxury_nav       = (!$booking_nav && !empty($marketing_nav['luxury'])) ? $marketing_nav['luxury'] : NULL;

$first_name   = $user ? strtok($user['name'], ' ') : '';
$user_initial = $user ? strtoupper(mb_substr($first_name, 0, 1)) : '';
$tel_href     = 'tel:'.preg_replace('/[^+\d]/', '', $phone);
$home_url     = $booking_nav ? site_url('/') : ymo_public_nav_url('');
$login_url    = ymo_booking_url('login');
$book_url     = ymo_booking_url('packages');
$quick_book_url = ymo_booking_url('quick-book');
$account_url  = ymo_booking_url('account');
$logout_url   = ymo_booking_url('logout');
$bookings_url = ymo_booking_url('account/bookings');

?>
<?php if (!empty($city_hint)): ?>
    <?php $this->load->view('marketing/partials/city_hint_banner', array('city_hint' => $city_hint)); ?>
<?php endif; ?>
<?php $trust_badge = function_exists('marketing_site_trust_badge') ? marketing_site_trust_badge() : array('show' => FALSE); ?>
<div class="ymo-topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="d-flex align-items-center gap-2 flex-wrap">
            <?php if (!empty($trust_badge['show'])): ?>
                <?php if (!empty($trust_badge['url'])): ?>
                    <a href="<?= html_escape($trust_badge['url']); ?>" class="ymo-topbar-trust d-flex align-items-center gap-1 text-decoration-none" target="_blank" rel="noopener noreferrer">
                        <span class="mi mi-sm" aria-hidden="true">star</span>
                        <?= html_escape($trust_badge['text']); ?>
                    </a>
                <?php else: ?>
                    <span class="ymo-topbar-trust d-flex align-items-center gap-1">
                        <span class="mi mi-sm" aria-hidden="true">star</span>
                        <?= html_escape($trust_badge['text']); ?>
                    </span>
                <?php endif; ?>
                <span class="d-none d-md-inline opacity-50">|</span>
            <?php endif; ?>
            <span class="d-none d-md-inline d-flex align-items-center gap-1">
                <span class="mi mi-sm" aria-hidden="true">verified</span>
                Trusted car servicing experts at your doorstep
            </span>
        </span>
        <span>
            <a href="<?= html_escape($tel_href); ?>"><?= html_escape($phone); ?></a>
            <?php if ($booking_nav): ?>
                <span class="mx-2 opacity-50">|</span>
                <a href="<?= html_escape(ymo_marketing_url('')); ?>">Main site</a>
            <?php endif; ?>
        </span>
    </div>
</div>

<nav class="ymo-navbar md-top-app-bar navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand ymo-brand" href="<?= html_escape($home_url); ?>" aria-label="<?= html_escape($brand); ?>">
            <?= function_exists('marketing_brand_logo_html') ? marketing_brand_logo_html(array('class' => 'ymo-brand-logo', 'width' => 120, 'height' => 44, 'priority' => !(isset($ymo_defer_logo_priority) && $ymo_defer_logo_priority))) : '<img src="'.html_escape(base_url('assets/img/logo.png')).'" alt="'.html_escape($brand).'" class="ymo-brand-logo" width="120" height="44" fetchpriority="high" decoding="async">'; ?>
        </a>
        <button class="navbar-toggler md-icon-btn" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#ymoDrawer"
                aria-controls="ymoDrawer" aria-label="Open menu">
            <span class="mi" aria-hidden="true">menu</span>
        </button>
        <div class="collapse navbar-collapse" id="ymoNav">
            <?php if ($booking_nav): ?>
                <ul class="navbar-nav me-auto ymo-nav-primary">
                    <li class="nav-item"><a class="nav-link ymo-nav-link <?= $nav_active(''); ?>" href="<?= site_url('/'); ?>"><span class="mi mi-sm mi-leading">home</span>Home</a></li>
                    <li class="nav-item"><a class="nav-link ymo-nav-link <?= $nav_active('packages'); ?>" href="<?= site_url('packages'); ?>"><span class="mi mi-sm mi-leading">build</span>Packages</a></li>
                    <li class="nav-item"><a class="nav-link ymo-nav-link <?= $nav_active('quick-book'); ?>" href="<?= site_url('quick-book'); ?>"><span class="mi mi-sm mi-leading">send</span>Quick book</a></li>
                    <li class="nav-item"><a class="nav-link ymo-nav-link <?= $nav_active('account/bookings'); ?>" href="<?= site_url('account/bookings'); ?>"><span class="mi mi-sm mi-leading">event_note</span>My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link ymo-nav-link <?= $nav_active('vehicles'); ?>" href="<?= site_url('vehicles'); ?>"><span class="mi mi-sm mi-leading">directions_car</span>My Vehicles</a></li>
                </ul>
                <ul class="navbar-nav ymo-nav-actions align-items-lg-center">
                    <li class="nav-item"><a class="nav-link ymo-nav-link" href="<?= site_url('account'); ?>"><span class="mi mi-sm mi-leading">account_circle</span>Hi, <?= html_escape($first_name); ?></a></li>
                    <li class="nav-item">
                        <form action="<?= site_url('logout'); ?>" method="post" class="d-inline">
                            <?= form_hidden($ci->security->get_csrf_token_name(), $ci->security->get_csrf_hash()); ?>
                            <button type="submit" class="btn btn-link nav-link ymo-nav-link"><span class="mi mi-sm mi-leading">logout</span>Sign out</button>
                        </form>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav me-auto ymo-nav-primary align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link ymo-nav-link <?= $nav_active(''); ?>" href="<?= html_escape(ymo_public_nav_url('')); ?>">Home</a>
                    </li>
                    <li class="nav-item dropdown ymo-nav-dropdown">
                        <a class="nav-link ymo-nav-link dropdown-toggle <?= $services_active ? 'active' : ''; ?>"
                           href="<?= html_escape(ymo_public_nav_url('services')); ?>"
                           id="ymoNavServices" aria-haspopup="true">
                            Services
                            <span class="mi ymo-nav-chevron" aria-hidden="true">expand_more</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-md" aria-labelledby="ymoNavServices">
                            <?php foreach ($marketing_nav['services'] as $item): ?>
                                <?php
                                $item_path = trim($item['slug'], '/');
                                $item_active = ($current === $item_path) ? ' active' : '';
                                $item_class = !empty($item['emphasis']) ? ' dropdown-item-emphasis' : '';
                                $item_icon = !empty($item['icon']) ? $item['icon'] : 'build';
                                ?>
                                <li>
                                    <a class="dropdown-item ymo-nav-menu-item<?= $item_class.$item_active; ?>"
                                       href="<?= html_escape(ymo_public_nav_url($item['slug'])); ?>">
                                        <span class="mi" aria-hidden="true"><?= html_escape($item_icon); ?></span>
                                        <span><?= html_escape($item['label']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown ymo-nav-dropdown ymo-nav-locations">
                        <a class="nav-link ymo-nav-link dropdown-toggle <?= $locations_active ? 'active' : ''; ?>"
                           href="<?= html_escape(ymo_public_nav_url('locations/pune')); ?>"
                           id="ymoNavLocations" aria-haspopup="true">
                            Locations
                            <span class="mi ymo-nav-chevron" aria-hidden="true">expand_more</span>
                        </a>
                        <div class="dropdown-menu ymo-nav-mega" aria-labelledby="ymoNavLocations">
                            <div class="ymo-nav-mega-grid">
                                <?php foreach ($marketing_nav['locations'] as $city): ?>
                                    <div class="ymo-nav-mega-col">
                                        <a class="ymo-nav-mega-city<?= $drawer_path_active($city['slug']) ? ' is-active' : ''; ?>"
                                           href="<?= html_escape(ymo_public_nav_url($city['slug'])); ?>">
                                            <span class="mi" aria-hidden="true">location_city</span>
                                            <?= html_escape($city['label']); ?>
                                        </a>
                                        <?php if (!empty($city['children'])): ?>
                                            <ul class="ymo-nav-mega-links">
                                                <?php foreach ($city['children'] as $child): ?>
                                                    <li>
                                                        <a class="<?= $drawer_path_active($child['slug']) ? 'is-active' : ''; ?>"
                                                           href="<?= html_escape(ymo_public_nav_url($child['slug'])); ?>">
                                                            <?= html_escape($child['label']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>
                    <?php if ($luxury_nav): ?>
                    <li class="nav-item">
                        <a class="nav-link ymo-nav-link<?= $luxury_active ? ' active' : ''; ?>"
                           href="<?= html_escape(ymo_public_nav_url($luxury_nav['slug'])); ?>">
                            <span class="mi mi-sm mi-leading" aria-hidden="true"><?= html_escape($luxury_nav['icon']); ?></span>
                            <?= html_escape($luxury_nav['label']); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link ymo-nav-link <?= $nav_active('about-us'); ?>" href="<?= html_escape(ymo_public_nav_url('about-us')); ?>">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ymo-nav-link <?= $nav_active('contact-us'); ?>" href="<?= html_escape(ymo_public_nav_url('contact-us')); ?>">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ymo-nav-link <?= $nav_active('quick-book'); ?>" href="<?= html_escape($quick_book_url); ?>"><span class="mi mi-sm mi-leading">send</span>Quick book</a>
                    </li>
                </ul>
                <ul class="navbar-nav ymo-nav-actions align-items-lg-center">
                    <?php if ($user): ?>
                    <li class="nav-item d-none d-lg-block">
                        <a class="md-btn md-btn--text md-btn--sm" href="<?= html_escape($account_url); ?>">
                            <span class="mi mi-leading">account_circle</span>Hi, <?= html_escape($first_name); ?>
                        </a>
                    </li>
                    <li class="nav-item d-none d-lg-block">
                        <a class="md-btn md-btn--text md-btn--sm" href="<?= html_escape($bookings_url); ?>">My bookings</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item d-none d-lg-block">
                        <a class="md-btn md-btn--text md-btn--sm" href="<?= html_escape($login_url); ?>">Sign in</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="md-btn md-btn--filled md-btn--sm" href="<?= html_escape($book_url); ?>">
                            <span class="mi mi-leading">event_available</span>Book now
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<aside class="offcanvas offcanvas-end ymo-drawer" tabindex="-1" id="ymoDrawer"
       aria-labelledby="ymoDrawerLabel" data-bs-scroll="false">
    <header class="ymo-drawer-head">
        <a href="<?= html_escape($home_url); ?>" id="ymoDrawerLabel" aria-label="<?= html_escape($brand); ?>">
            <?= function_exists('marketing_brand_logo_html') ? marketing_brand_logo_html(array('class' => 'ymo-brand-logo', 'width' => 120, 'height' => 44, 'lazy' => TRUE)) : '<img src="'.html_escape(base_url('assets/img/logo.png')).'" alt="'.html_escape($brand).'" class="ymo-brand-logo" width="120" height="44" loading="lazy" decoding="async">'; ?>
        </a>
        <button type="button" class="ymo-drawer-close md-icon-btn" data-bs-dismiss="offcanvas" aria-label="Close menu">
            <span class="mi" aria-hidden="true">close</span>
        </button>
    </header>

    <?php if ($booking_nav || ($user && !$booking_nav)): ?>
        <a href="<?= html_escape($booking_nav ? site_url('account') : $account_url); ?>" class="ymo-drawer-user text-decoration-none">
            <span class="ymo-drawer-avatar" aria-hidden="true"><?= html_escape($user_initial); ?></span>
            <span class="ymo-drawer-user-meta">
                <strong><?= html_escape($user['name']); ?></strong>
                <small>+91 <?= html_escape(substr($user['mobile'], -10)); ?></small>
            </span>
        </a>
    <?php endif; ?>

    <div class="ymo-drawer-body">
        <nav class="ymo-drawer-section" aria-label="Primary">
            <?php if ($booking_nav): ?>
                <a class="ymo-drawer-link <?= $drawer_active(''); ?>" href="<?= site_url('/'); ?>">
                    <span class="mi" aria-hidden="true">home</span>Home
                </a>
                <a class="ymo-drawer-link <?= $drawer_active('packages'); ?>" href="<?= site_url('packages'); ?>">
                    <span class="mi" aria-hidden="true">build</span>Packages
                </a>
                <a class="ymo-drawer-link <?= $drawer_active('quick-book'); ?>" href="<?= site_url('quick-book'); ?>">
                    <span class="mi" aria-hidden="true">send</span>Quick book
                </a>
                <a class="ymo-drawer-link <?= $drawer_active('account/bookings'); ?>" href="<?= site_url('account/bookings'); ?>">
                    <span class="mi" aria-hidden="true">event_note</span>My Bookings
                </a>
                <a class="ymo-drawer-link <?= $drawer_active('vehicles'); ?>" href="<?= site_url('vehicles'); ?>">
                    <span class="mi" aria-hidden="true">directions_car</span>My Vehicles
                </a>
            <?php else: ?>
                <a class="ymo-drawer-link <?= $drawer_active(''); ?>" href="<?= html_escape(ymo_public_nav_url('')); ?>">
                    <span class="mi" aria-hidden="true">home</span>Home
                </a>

                <details class="ymo-drawer-expand"<?= $services_active ? ' open' : ''; ?>>
                    <summary class="ymo-drawer-link <?= $services_active ? 'is-active' : ''; ?>">
                        <span class="mi" aria-hidden="true">build</span>Services
                        <span class="mi ymo-drawer-chevron" aria-hidden="true">expand_more</span>
                    </summary>
                    <div class="ymo-drawer-sub">
                        <div class="ymo-drawer-sub-inner">
                        <?php foreach ($marketing_nav['services'] as $item): ?>
                            <?php $item_icon = !empty($item['icon']) ? $item['icon'] : 'build'; ?>
                            <a class="ymo-drawer-sub-link ymo-drawer-sub-link--icon<?= $drawer_path_active($item['slug']) ? ' is-active' : ''; ?><?= !empty($item['emphasis']) ? ' fw-semibold' : ''; ?>"
                               href="<?= html_escape(ymo_public_nav_url($item['slug'])); ?>">
                                <span class="mi mi-sm" aria-hidden="true"><?= html_escape($item_icon); ?></span>
                                <?= html_escape($item['label']); ?>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </details>

                <details class="ymo-drawer-expand"<?= $locations_active ? ' open' : ''; ?>>
                    <summary class="ymo-drawer-link <?= $locations_active ? 'is-active' : ''; ?>">
                        <span class="mi" aria-hidden="true">location_on</span>Locations
                        <span class="mi ymo-drawer-chevron" aria-hidden="true">expand_more</span>
                    </summary>
                    <div class="ymo-drawer-sub">
                        <div class="ymo-drawer-sub-inner">
                        <?php foreach ($marketing_nav['locations'] as $city): ?>
                            <div class="ymo-drawer-sub-label"><?= html_escape($city['label']); ?></div>
                            <a class="ymo-drawer-sub-link ymo-drawer-sub-link--icon<?= $drawer_path_active($city['slug']) ? ' is-active' : ''; ?>"
                               href="<?= html_escape(ymo_public_nav_url($city['slug'])); ?>">
                                <span class="mi mi-sm" aria-hidden="true">location_city</span>
                                All of <?= html_escape($city['label']); ?>
                            </a>
                            <?php if (!empty($city['children'])): ?>
                                <?php foreach ($city['children'] as $child): ?>
                                    <a class="ymo-drawer-sub-link<?= $drawer_path_active($child['slug']) ? ' is-active' : ''; ?>"
                                       href="<?= html_escape(ymo_public_nav_url($child['slug'])); ?>">
                                        <?= html_escape($child['label']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </details>

                <?php if ($luxury_nav): ?>
                <a class="ymo-drawer-link<?= $luxury_active ? ' is-active' : ''; ?>"
                   href="<?= html_escape(ymo_public_nav_url($luxury_nav['slug'])); ?>">
                    <span class="mi" aria-hidden="true"><?= html_escape($luxury_nav['icon']); ?></span>
                    <?= html_escape($luxury_nav['label']); ?>
                </a>
                <?php endif; ?>

                <a class="ymo-drawer-link <?= $drawer_active('about-us'); ?>" href="<?= html_escape(ymo_public_nav_url('about-us')); ?>">
                    <span class="mi" aria-hidden="true">info</span>About
                </a>
                <a class="ymo-drawer-link <?= $drawer_active('contact-us'); ?>" href="<?= html_escape(ymo_public_nav_url('contact-us')); ?>">
                    <span class="mi" aria-hidden="true">mail</span>Contact
                </a>
                <a class="ymo-drawer-link <?= $drawer_path_active('quick-book'); ?>" href="<?= html_escape($quick_book_url); ?>">
                    <span class="mi" aria-hidden="true">send</span>Quick book
                </a>
            <?php endif; ?>
        </nav>

        <hr class="ymo-drawer-div">

        <nav class="ymo-drawer-section" aria-label="Account &amp; support">
            <?php if ($booking_nav): ?>
                <a class="ymo-drawer-link <?= $drawer_active('account/profile'); ?>" href="<?= site_url('account/profile'); ?>">
                    <span class="mi" aria-hidden="true">manage_accounts</span>Profile
                </a>
            <?php elseif ($user): ?>
                <a class="ymo-drawer-link" href="<?= html_escape($account_url); ?>">
                    <span class="mi" aria-hidden="true">account_circle</span>My account
                </a>
                <a class="ymo-drawer-link" href="<?= html_escape($bookings_url); ?>">
                    <span class="mi" aria-hidden="true">event_note</span>My bookings
                </a>
            <?php endif; ?>
            <a class="ymo-drawer-link" href="<?= html_escape($tel_href); ?>">
                <span class="mi" aria-hidden="true">call</span>Call us
                <span class="ms-auto small ymo-muted"><?= html_escape($phone); ?></span>
            </a>
            <?php if ($booking_nav): ?>
                <a class="ymo-drawer-link" href="<?= html_escape(ymo_marketing_url('')); ?>">
                    <span class="mi" aria-hidden="true">language</span>Main site
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <footer class="ymo-drawer-foot">
        <?php if ($booking_nav): ?>
            <a href="<?= site_url('quick-book'); ?>" class="md-btn md-btn--tonal w-100 mb-2">
                <span class="mi mi-leading">send</span>Quick book
            </a>
            <a href="<?= site_url('packages'); ?>" class="md-btn md-btn--filled w-100">
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
            <a href="<?= html_escape($quick_book_url); ?>" class="md-btn md-btn--tonal w-100 mb-2">
                <span class="mi mi-leading">send</span>Quick book - no login
            </a>
            <a href="<?= html_escape($book_url); ?>" class="md-btn md-btn--filled w-100">
                <span class="mi mi-leading">event_available</span>Book a service
            </a>
            <div class="ymo-drawer-foot-secondary">
                <?php if ($user): ?>
                    <?= form_open($logout_url, array('class' => 'd-inline')); ?>
                        <button type="submit" class="btn btn-link p-0 small">
                            <span class="mi mi-sm mi-leading">logout</span>Sign out
                        </button>
                    <?= form_close(); ?>
                <?php else: ?>
                    Already a member? <a href="<?= html_escape($login_url); ?>">Sign in</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <p class="ymo-drawer-tagline mb-0">&copy; <?= date('Y'); ?> <?= html_escape($brand); ?></p>
    </footer>
</aside>
