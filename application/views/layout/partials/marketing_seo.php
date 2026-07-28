<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$brand = $ci->config->item('ymo_brand_name');
$title = isset($title) ? $title : $brand;
$page_title = isset($meta_title) ? $meta_title : $title.' - '.$brand;
$description = isset($meta_description) ? $meta_description : 'Book car servicing online with '.$brand;
$canonical = marketing_canonical_url(isset($canonical_path) ? $canonical_path : '');
$og_image_path = isset($og_image) && $og_image !== '' ? ltrim($og_image, '/') : 'assets/img/logo.png';
$og_image = base_url($og_image_path);
$schema = marketing_schema_graph(array(
    'canonical_path'   => isset($canonical_path) ? $canonical_path : '',
    'h1'               => isset($h1) ? $h1 : '',
    'meta_description' => $description,
    'page_type'        => isset($page_type) ? $page_type : '',
    'city_slug'        => isset($city_slug) ? $city_slug : '',
    'faq'              => isset($faq) ? $faq : array(),
));
?>
<meta name="description" content="<?= html_escape($description); ?>">
<link rel="canonical" href="<?= html_escape($canonical); ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= html_escape($page_title); ?>">
<meta property="og:description" content="<?= html_escape($description); ?>">
<meta property="og:url" content="<?= html_escape($canonical); ?>">
<meta property="og:image" content="<?= html_escape($og_image); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= html_escape($page_title); ?>">
<meta name="twitter:description" content="<?= html_escape($description); ?>">
<meta name="twitter:image" content="<?= html_escape($og_image); ?>">
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
