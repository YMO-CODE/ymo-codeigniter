<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$page_title = !empty($meta_title)
    ? $meta_title
    : (isset($title) ? $title.' - '.$ci->config->item('ymo_brand_name') : $ci->config->item('ymo_brand_name'));
$body_class = isset($page_class) ? $page_class : 'ymo-marketing md-theme';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= html_escape($page_title); ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png'); ?>">
    <meta name="theme-color" content="#3a6f37">
    <?php $this->load->view('layout/partials/marketing_seo'); ?>
    <?php $this->load->view('layout/partials/head_assets'); ?>
    <?php
    $mk_css_v = (int) @filemtime(FCPATH.'assets/css/marketing.css');
    if ($mk_css_v): ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/marketing.css?v='.$mk_css_v); ?>">
    <?php endif; ?>
</head>
<body class="<?= html_escape($body_class); ?>">

<?php $this->load->view('layout/partials/marketing_header'); ?>

<main role="main" class="ymo-main">
    <?php $this->load->view('layout/partials/flash'); ?>
    <?= $content; ?>
</main>

<?php $this->load->view('layout/partials/marketing_footer'); ?>
<?php $this->load->view('layout/partials/whatsapp_cta'); ?>
<?php $this->load->view('layout/partials/foot_assets'); ?>
<?php $this->load->view('layout/partials/offers_popup'); ?>
</body>
</html>
