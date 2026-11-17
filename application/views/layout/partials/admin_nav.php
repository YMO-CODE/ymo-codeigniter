<?php defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Admin sidebar. Daily work first (Today → Leads → Bookings → Customers); the full
 * follow-up list opens from Today's "Next 7 days" link,
 * then outreach and reports, then setup. Source views (cold calling, Instagram…)
 * live as chips on the Leads page; online accounts are a filter on Customers.
 */
$can = function ($perm) { return !function_exists('crm_can') || crm_can($perm); };
$crm = function_exists('crm_can');
$item = function ($path, $icon, $label, $active) {
    return '<a href="'.admin_url($path).'" class="'.($active ? 'active' : '').'"><span class="mi mi-sm mi-leading">'.$icon.'</span>'.$label.'</a>';
};
?>
<?php if ($can('dashboard.view')): ?>
<?= $item('dashboard', 'today', 'Today', admin_nav_active('dashboard') || admin_nav_active('tasks')); ?>
<?php elseif ($crm && crm_can('tasks.view')): ?>
<?= $item('tasks', 'task_alt', 'Follow-ups', admin_nav_active('tasks')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('leads.view')): ?>
<a href="<?= admin_url('inbox'); ?>" class="<?= admin_nav_active('inbox') ? 'active' : ''; ?>"><span class="mi mi-sm mi-leading">forum</span>Inbox<span class="ymo-nav-badge" id="ymo-inbox-badge" hidden></span></a>
<?= $item('leads', 'person_search', 'Leads', admin_nav_active('leads')); ?>
<?php endif; ?>
<?php if ($can('bookings.view')): ?>
<?= $item('bookings', 'event_note', 'Bookings', admin_nav_active('bookings')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('contacts.view')): ?>
<?= $item('customers', 'groups', 'Customers', admin_nav_active('customers') || admin_nav_active('online-accounts')); ?>
<?php elseif ($can('customers.view')): ?>
<?= $item('online-accounts', 'person', 'Customers', admin_nav_active('online-accounts')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('campaigns.view')): ?>
<?= $item('campaigns', 'campaign', 'Campaigns', admin_nav_active('campaigns') || admin_nav_active('whatsapp-templates')); ?>
<?php endif; ?>
<?php if ($crm && crm_can('reports.view')): ?>
<?= $item('reports', 'analytics', 'Reports', admin_nav_active('reports')); ?>
<?php endif; ?>

<?php
$setup = array();
if ($can('packages.view'))   { $setup[] = $item('packages', 'build', 'Packages', admin_nav_active('packages')); }
if ($can('offers.view'))     { $setup[] = $item('offers', 'local_offer', 'Offers', admin_nav_active('offers')); }
if ($crm && crm_can('team.view'))  { $setup[] = $item('team', 'group', 'Team', admin_nav_active('team')); }
if ($crm && crm_can('roles.view')) { $setup[] = $item('roles', 'admin_panel_settings', 'Roles', admin_nav_active('roles')); }
if ($can('settings.manage')) { $setup[] = $item('settings', 'settings', 'Settings', admin_nav_active('settings')); }
?>
<?php if ($setup): ?>
<hr class="ymo-admin-nav-div">
<div class="ymo-admin-nav-label">Setup</div>
<?= implode("\n", $setup); ?>
<?php endif; ?>

<?php if ($crm && crm_can('recruitment.view')): ?>
<hr class="ymo-admin-nav-div">
<?= $item('recruitment', 'badge', 'Recruitment', admin_nav_active('recruitment')); ?>
<?php endif; ?>

<hr class="ymo-admin-nav-div">
<?php $ci = &get_instance(); ?>
<form action="<?= admin_url('logout'); ?>" method="post" class="ymo-admin-nav-logout">
    <?= form_hidden($ci->security->get_csrf_token_name(), $ci->security->get_csrf_hash()); ?>
    <button class="btn btn-sm btn-outline-light w-100" type="submit"><span class="mi mi-sm mi-leading">logout</span>Sign out</button>
</form>
<?php if ($crm && crm_can('leads.view')): ?>
<script>
(function () {
    // Unread WhatsApp badge: refresh every 30 seconds while the admin is open.
    var badge = document.getElementById('ymo-inbox-badge');
    if (!badge || !window.fetch) { return; }
    var baseTitle = document.title.replace(/^\(\d+\)\s*/, '');
    function paint(n) {
        badge.hidden = !n;
        badge.textContent = n > 99 ? '99+' : String(n);
        document.title = (n ? '(' + n + ') ' : '') + baseTitle;
    }
    function poll() {
        fetch('<?= admin_url('inbox/unread'); ?>', {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d && typeof d.unread === 'number') { paint(d.unread); } })
            .catch(function () {});
    }
    poll();
    setInterval(function () { if (!document.hidden) { poll(); } }, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });
})();
</script>
<?php endif; ?>
