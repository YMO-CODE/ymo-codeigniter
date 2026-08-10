<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$page_title = !empty($meta_title)
    ? $meta_title
    : (isset($title) ? $title.' - '.$ci->config->item('ymo_brand_name') : $ci->config->item('ymo_brand_name'));
$body_class = isset($page_class) ? $page_class : 'ymo-marketing md-theme';
$bs_marketing_js = FCPATH.'assets/js/bootstrap-marketing.min.js';
$bs_js_file = is_file($bs_marketing_js)
    ? 'assets/js/bootstrap-marketing.min.js'
    : 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js';
$bs_js_v = (int) @filemtime(FCPATH.$bs_js_file);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?php $this->load->view('layout/partials/marketing_lcp_preload'); ?>
    <title><?= html_escape($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png'); ?>">
    <meta name="theme-color" content="#3a6f37">
    <?php $this->load->view('layout/partials/marketing_head_assets'); ?>
    <?php $this->load->view('layout/partials/marketing_seo'); ?>
</head>
<body class="<?= html_escape($body_class); ?>"
      data-bootstrap-js="<?= html_escape(base_url($bs_js_file.'?v='.$bs_js_v)); ?>">

<?php $this->load->view('layout/partials/marketing_header'); ?>

<main role="main" class="ymo-main">
    <?php $this->load->view('layout/partials/flash'); ?>
    <?= $content; ?>
</main>

<?php $this->load->view('layout/partials/marketing_footer'); ?>
<?php $this->load->view('layout/partials/whatsapp_cta'); ?>
<?php $this->load->view('layout/partials/marketing_foot_assets'); ?>
<?php $this->load->view('layout/partials/offers_popup_deferred'); ?>
</body>
</html>
