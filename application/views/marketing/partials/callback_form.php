<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/*
 * Short call-back form for area and service pages (ad landing pages).
 * Posts to /request-callback; the lead lands in the CRM tagged Google Ads
 * when the visit came from an ad.
 */
$cb_from = isset($callback_from) ? '/'.ltrim((string) $callback_from, '/') : '/';
$cb_area = isset($callback_area) && $callback_area !== '' ? $callback_area : '';
?>
<div id="callback" class="ymo-callback-card md-card-elevated p-3 p-md-4 mb-4">
    <h2 class="md-title-lg mb-1">Get a free call back<?= $cb_area !== '' ? ' in '.html_escape($cb_area) : ''; ?></h2>
    <p class="md-body-md ymo-muted mb-3">Leave your number. Our team calls you with a price and a pick-up slot.</p>
    <?= form_open(site_url('request-callback'), array('class' => 'ymo-callback-form', 'autocomplete' => 'on')); ?>
    <?= ymo_form_trap_fields(); ?>
        <input type="hidden" name="from" value="<?= html_escape($cb_from); ?>">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="cb-name">Your name</label>
                <input type="text" class="form-control" id="cb-name" name="name" placeholder="Your name" required minlength="2" maxlength="120" autocomplete="name">
            </div>
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="cb-mobile">Mobile number</label>
                <input type="tel" class="form-control" id="cb-mobile" name="mobile" placeholder="Mobile number" required maxlength="16" inputmode="tel" pattern="[+0-9 \-]{10,16}" autocomplete="tel">
            </div>
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="cb-car">Car model</label>
                <input type="text" class="form-control" id="cb-car" name="car" placeholder="Car model (optional)" maxlength="80">
            </div>
        </div>
        <button type="submit" class="md-btn md-btn--filled w-100 mt-3">
            <span class="mi mi-leading">call</span>Call me back
        </button>
    <?= form_close(); ?>
</div>
