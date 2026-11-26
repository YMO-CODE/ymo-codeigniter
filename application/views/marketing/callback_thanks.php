<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$tel = function_exists('marketing_business_phone_e164') ? marketing_business_phone_e164() : '+917558783868';
$wa  = function_exists('marketing_business_whatsapp_url') ? marketing_business_whatsapp_url('Hi YMO, I just asked for a call back') : 'https://wa.me/917744065904';
?>
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 text-center">
            <span class="mi mi-xl md-icon-primary">check_circle</span>
            <h1 class="md-headline-md mt-2 mb-3"><?= html_escape(isset($h1) ? $h1 : 'Thanks - we will call you shortly'); ?></h1>
            <p class="md-body-lg mb-4">Our team will call you back within working hours (9 AM to 8 PM). In a hurry? Call or WhatsApp us now.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                <a href="tel:<?= html_escape($tel); ?>" class="md-btn md-btn--filled"><span class="mi mi-leading">call</span>Call <?= html_escape($phone); ?></a>
                <a href="<?= html_escape($wa); ?>" class="md-btn md-btn--tonal" target="_blank" rel="noopener">WhatsApp us</a>
            </div>
            <a href="/" class="md-body-md">Back to the home page</a>
        </div>
    </div>
</section>
<script>if (window.ymoConversion) { window.ymoConversion('quick_book'); }</script>
