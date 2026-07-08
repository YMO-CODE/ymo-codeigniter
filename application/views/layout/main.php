<?php
/**
 * Public-facing layout. Loaded by the controllers via $this->load->view().
 *
 * @var string  $content    Inner page HTML (already rendered).
 * @var string  $title      Optional page title.
 * @var string  $page_class Optional <body class="..."> hook.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$ci          = &get_instance();
$page_title  = isset($title) ? $title.' — '.$ci->config->item('ymo_brand_name') : $ci->config->item('ymo_brand_name');
$brand_name  = $ci->config->item('ymo_brand_name');
$brand_short = $ci->config->item('ymo_brand_short');
$body_class  = isset($page_class) ? $page_class : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Book car servicing online with <?= html_escape($brand_name); ?> — trusted car repair, AC service, denting & polishing at your doorstep.">
    <title><?= html_escape($page_title); ?></title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png'); ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/logo.png'); ?>">
    <meta name="theme-color" content="#3a6f37">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/ymo.css'); ?>">
</head>
<body class="<?= html_escape($body_class); ?>">

<?php $this->load->view('layout/partials/header'); ?>

<main role="main" class="ymo-main">
    <?php $this->load->view('layout/partials/flash'); ?>
    <?= $content; ?>
</main>

<?php $this->load->view('layout/partials/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/ymo.js'); ?>"></script>
<?php $this->load->view('layout/partials/offers_popup'); ?>
</body>
</html>
