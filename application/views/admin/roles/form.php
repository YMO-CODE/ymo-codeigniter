<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = !empty($role);
$action  = $is_edit ? admin_url('roles/'.$role['id'].'/edit') : admin_url('roles/new');
$is_admin_role = $is_edit && ($role['slug'] ?? '') === 'admin';

$section_map = array(
    'Booking & operations' => array('dashboard', 'bookings', 'customers', 'packages', 'settings'),
    'CRM'                  => array('leads', 'contacts', 'tasks', 'campaigns', 'recruitment', 'reports'),
    'Administration'       => array('team', 'roles'),
    'Integrations'         => array('integrations'),
);
$section_titles = array(
    'dashboard'    => 'Dashboard',
    'bookings'     => 'Bookings',
    'customers'    => 'Customers',
    'packages'     => 'Packages',
    'settings'     => 'Settings',
    'leads'        => 'Leads',
    'contacts'     => 'Contacts',
    'tasks'        => 'Tasks',
    'campaigns'    => 'Campaigns',
    'recruitment'  => 'Recruitment',
    'reports'      => 'Reports',
    'team'         => 'Team',
    'roles'        => 'Roles',
    'integrations' => 'Integrations',
);
$selected = array_flip(array_map('intval', (array) $perm_ids));
?>
<a href="<?= admin_url('roles'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>All roles
</a>

<div class="row justify-content-center mt-2">
    <div class="col-lg-10">
        <div class="ymo-card">
            <h2 class="h4 mb-4">
                <span class="mi mi-leading"><?= $is_edit ? 'edit' : 'add_moderator'; ?></span>
                <?= $is_edit ? 'Edit role' : 'New role'; ?>
            </h2>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

            <?= form_open($action); ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control" id="rl_label" name="label" placeholder=" " required
                                   value="<?= html_escape(set_value('label', $role['label'] ?? '')); ?>">
                            <label for="rl_label">Role name *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control font-monospace" id="rl_slug" name="slug" placeholder=" "
                                   value="<?= html_escape(set_value('slug', $role['slug'] ?? '')); ?>"
                                   <?= $is_admin_role ? 'readonly' : ''; ?>>
                            <label for="rl_slug">Slug<?= $is_admin_role ? ' (fixed)' : ' (auto from name if blank)'; ?></label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input class="form-control" id="rl_sort" type="number" name="sort_order" placeholder=" "
                                   value="<?= html_escape(set_value('sort_order', $role['sort_order'] ?? 100)); ?>">
                            <label for="rl_sort">Sort order</label>
                        </div>
                    </div>
                </div>

                <h3 class="h6 text-uppercase ymo-muted mb-3">Permissions</h3>

                <?php foreach ($section_map as $section_label => $prefixes): ?>
                    <?php
                    $section_perms = array();
                    foreach ($prefixes as $prefix) {
                        if (!empty($permission_groups[$prefix])) {
                            $section_perms[$prefix] = $permission_groups[$prefix];
                        }
                    }
                    if (!$section_perms) { continue; }
                    ?>
                    <div class="border rounded p-3 mb-3">
                        <strong class="d-block mb-3"><?= html_escape($section_label); ?></strong>
                        <?php foreach ($section_perms as $prefix => $perms): ?>
                            <p class="small fw-semibold mb-2 ymo-muted"><?= html_escape($section_titles[$prefix] ?? ucfirst($prefix)); ?></p>
                            <div class="row g-2 mb-3">
                                <?php foreach ($perms as $p): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="perm_ids[]" id="perm_<?= (int) $p['id']; ?>"
                                                   value="<?= (int) $p['id']; ?>"
                                                   <?= isset($selected[(int) $p['id']]) ? 'checked' : ''; ?>>
                                            <label class="form-check-label small" for="perm_<?= (int) $p['id']; ?>">
                                                <?= html_escape($p['label']); ?>
                                                <span class="ymo-muted d-block font-monospace" style="font-size:0.75rem;"><?= html_escape($p['perm_key']); ?></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <button class="btn btn-primary">
                    <span class="mi mi-sm mi-leading">save</span><?= $is_edit ? 'Save role' : 'Create role'; ?>
                </button>
            <?= form_close(); ?>
        </div>
    </div>
</div>
