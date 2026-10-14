<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
if (empty($svc) || !is_array($svc)) {
    return;
}
$href = isset($url) ? $url : site_url($svc['slug']);
$face_args = array('svc' => $svc, 'show_arrow' => TRUE);
?>
<a href="<?= html_escape($href); ?>" class="ymo-v2-card ymo-v2-card--interactive ymo-v2-card--duplex h-100 marketing-service-card">
    <span class="ymo-v2-card__faces">
        <span class="ymo-v2-card__face ymo-v2-card__face--front">
            <?php $this->load->view('marketing/partials/service_card_face_content', $face_args); ?>
        </span>
        <span class="ymo-v2-card__face ymo-v2-card__face--back" aria-hidden="true">
            <?php $this->load->view('marketing/partials/service_card_face_content', $face_args); ?>
        </span>
    </span>
</a>
