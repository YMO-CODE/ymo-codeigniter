<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin sign-in — <?= html_escape($ci->config->item('ymo_brand_name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/ymo.css'); ?>">
    <style>
        body { background:#1d2026; min-height:100vh; display:flex; align-items:center; }
        .ymo-admin-login { width:100%; max-width:420px; margin:auto; }
    </style>
</head>
<body>
<div class="container ymo-admin-login">
    <div class="ymo-card">
        <div class="text-center mb-4">
            <span class="mi mi-xl" style="color:var(--ymo-primary);">admin_panel_settings</span>
            <h1 class="h4 mb-1 mt-2">YMO Admin</h1>
            <p class="ymo-muted small mb-0">Authorized personnel only.</p>
        </div>

        <?php $err = $ci->session->flashdata('error'); if ($err): ?>
            <div class="alert alert-danger small"><?= html_escape($err); ?></div>
        <?php endif; ?>
        <?php $info = $ci->session->flashdata('info'); if ($info): ?>
            <div class="alert alert-info small"><?= html_escape($info); ?></div>
        <?php endif; ?>
        <?php echo validation_errors('<div class="alert alert-danger small">', '</div>'); ?>

        <?= form_open(admin_url('login')); ?>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="al_email" name="email" placeholder=" "
                       value="<?= set_value('email'); ?>" required autofocus>
                <label for="al_email">Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="al_pw" name="password" placeholder=" " required>
                <label for="al_pw">Password</label>
            </div>
            <button class="btn btn-primary w-100" type="submit">
                <span class="mi mi-leading">login</span>Sign in
            </button>
        <?= form_close(); ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/ymo.js'); ?>"></script>
</body>
</html>
