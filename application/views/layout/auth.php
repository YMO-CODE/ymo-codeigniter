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
    <?php $this->load->view('layout/partials/head_assets'); ?>
</head>
<body>
<?php $this->load->view('layout/partials/header'); ?>
<main class="container py-5">
    <?php $this->load->view('layout/partials/flash'); ?>
    <?= $content; ?>
</main>
<?php $this->load->view('layout/partials/footer'); ?>
<?php $this->load->view('layout/partials/foot_assets'); ?>
</body>
</html>
