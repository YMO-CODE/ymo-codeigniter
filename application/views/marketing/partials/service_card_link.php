<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
if (empty($svc) || !is_array($svc)) {
    return;
}
$href = isset($url) ? $url : site_url($svc['slug']);
$face_args = array('svc' => $svc, 'show_arrow' => TRUE);
$city_variants = (!empty($svc['slug']) && function_exists('marketing_city_variants_for_path'))
    ? marketing_city_variants_for_path($svc['slug'], FALSE) : array();
?>
<a href="<?= html_escape($href); ?>" class="ymo-v2-card ymo-v2-card--interactive ymo-v2-card--duplex h-100 marketing-service-card"<?= count($city_variants) > 1 ? ' data-city-variants="'.html_escape(json_encode($city_variants)).'"' : ''; ?>>
    <span class="ymo-v2-card__faces">
        <span class="ymo-v2-card__face ymo-v2-card__face--front">
            <?php $this->load->view('marketing/partials/service_card_face_content', $face_args); ?>
        </span>
        <span class="ymo-v2-card__face ymo-v2-card__face--back" aria-hidden="true">
            <?php $this->load->view('marketing/partials/service_card_face_content', $face_args + array('as_heading' => FALSE)); ?>
        </span>
    </span>
</a>
