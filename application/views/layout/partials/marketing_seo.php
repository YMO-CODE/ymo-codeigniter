<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();
$brand = $ci->config->item('ymo_brand_name');
$title = isset($title) ? $title : $brand;
$page_title = !empty($meta_title)
    ? $meta_title
    : (isset($title) && $title !== $brand ? $title.' - '.$brand : $title);
$description = isset($meta_description) ? $meta_description : 'Book car servicing online with '.$brand;
$canonical = marketing_canonical_url(isset($canonical_path) ? $canonical_path : '');
$page_meta = array(
    'canonical_path'   => isset($canonical_path) ? $canonical_path : '',
    'h1'               => isset($h1) ? $h1 : '',
    'meta_description' => $description,
    'meta_title'       => $page_title,
    'page_type'        => isset($page_type) ? $page_type : '',
    'city_slug'        => isset($city_slug) ? $city_slug : '',
    'locality_slug'    => isset($locality_slug) ? $locality_slug : '',
    'locality_label'   => isset($locality_label) ? $locality_label : '',
    'brand_slug'       => isset($brand_slug) ? $brand_slug : '',
    'brand_name'       => isset($brand_name) ? $brand_name : '',
    'service_key'      => isset($service_key) ? $service_key : '',
    'updated_at'       => isset($updated_at) ? $updated_at : '',
    'faq'              => isset($faq) ? $faq : array(),
    'pricing_tiers'    => isset($pricing_tiers) ? $pricing_tiers : array(),
    'og_image'         => isset($og_image) ? $og_image : '',
);
$page_meta['og_image'] = marketing_resolve_og_image($page_meta['canonical_path'], $page_meta);
$og_image = marketing_hero_image_url($page_meta['og_image']);
$og_image_alt = isset($h1) && $h1 !== '' ? $h1 : $page_title;
$schema = marketing_schema_graph($page_meta);
?>
<meta name="description" content="<?= html_escape($description); ?>">
<link rel="canonical" href="<?= html_escape($canonical); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= html_escape($brand); ?>">
<meta property="og:title" content="<?= html_escape($page_title); ?>">
<meta property="og:description" content="<?= html_escape($description); ?>">
<meta property="og:url" content="<?= html_escape($canonical); ?>">
<meta property="og:image" content="<?= html_escape($og_image); ?>">
<meta property="og:image:alt" content="<?= html_escape($og_image_alt); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= html_escape($page_title); ?>">
<meta name="twitter:description" content="<?= html_escape($description); ?>">
<meta name="twitter:image" content="<?= html_escape($og_image); ?>">
<meta name="twitter:image:alt" content="<?= html_escape($og_image_alt); ?>">
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
