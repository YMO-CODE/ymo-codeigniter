<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
if (empty($icon) && empty($title) && empty($body)) {
    return;
}
$face_args = array(
    'icon'  => isset($icon) ? $icon : '',
    'title' => isset($title) ? $title : '',
    'body'  => isset($body) ? $body : '',
);
?>
<div class="ymo-v2-card ymo-v2-card--static ymo-v2-card--duplex h-100">
    <span class="ymo-v2-card__faces">
        <span class="ymo-v2-card__face ymo-v2-card__face--front">
            <?php $this->load->view('marketing/partials/feature_card_face_content', $face_args); ?>
        </span>
        <span class="ymo-v2-card__face ymo-v2-card__face--back" aria-hidden="true">
            <?php $this->load->view('marketing/partials/feature_card_face_content', $face_args); ?>
        </span>
    </span>
</div>
