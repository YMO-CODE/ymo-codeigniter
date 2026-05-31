<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$page_title = isset($title) ? $title.' — '.$ci->config->item('ymo_brand_name') : $ci->config->item('ymo_brand_name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= html_escape($page_title); ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png'); ?>">
    <meta name="theme-color" content="#3a6f37">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/ymo.css'); ?>">
</head>
<body>
<?php $this->load->view('layout/partials/header'); ?>
<main class="container py-5">
    <?php $this->load->view('layout/partials/flash'); ?>
    <?= $content; ?>
</main>
<?php $this->load->view('layout/partials/footer'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/ymo.js'); ?>"></script>
</body>
</html>
