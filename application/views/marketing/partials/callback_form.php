<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/*
 * Call-back + WhatsApp banner. Shown once per page on every marketing page:
 * inside the content on area/service pages (right after the hero), otherwise
 * just above the footer (layout/marketing.php).
 * Area pages say "in Warje", city pages "in Pune", other pages stay generic.
 * Posts to /request-callback; the lead lands in the CRM tagged Google Ads
 * when the visit came from an ad.
 */
$GLOBALS['ymo_callback_form_rendered'] = TRUE;
$cb_from = isset($callback_from) ? '/'.ltrim((string) $callback_from, '/') : '/';
$cb_page = isset($callback_page) && is_array($callback_page) ? $callback_page : array();
$cb_area = isset($callback_area) && $callback_area !== '' ? (string) $callback_area : '';
if ($cb_area === '' && !empty($cb_page['locality_label'])) {
    $cb_area = (string) $cb_page['locality_label'];
}
if ($cb_area === '' && !empty($cb_page['city_slug']) && function_exists('marketing_city_by_slug')) {
    $cb_city = marketing_city_by_slug($cb_page['city_slug']);
    $cb_area = $cb_city && !empty($cb_city['name']) ? (string) $cb_city['name'] : '';
}
$cb_wa_msg = 'Hi YMO, I need a car service'.($cb_area !== '' ? ' in '.$cb_area : '').'.';
$cb_wa = function_exists('marketing_business_whatsapp_url') ? marketing_business_whatsapp_url($cb_wa_msg) : 'https://wa.me/917558783868?text='.rawurlencode($cb_wa_msg);
?>
<div id="callback" class="ymo-callback-card md-card-elevated p-3 p-md-4 mb-4">
    <h2 class="md-title-lg mb-1">Get a free call back<?= $cb_area !== '' ? ' in '.html_escape($cb_area) : ''; ?></h2>
    <p class="md-body-md ymo-muted mb-3">Leave your number and our team calls you with a price and a pick-up slot, or chat with us on WhatsApp.</p>
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
        <div class="ymo-callback-actions">
            <div class="ymo-callback-actions__call">
                <button type="submit" class="md-btn md-btn--filled w-100">
                    <span class="mi mi-leading">call</span>Call me back
                </button>
            </div>
            <div class="ymo-callback-actions__wa">
                <a class="md-btn w-100 ymo-callback-wa" href="<?= html_escape($cb_wa); ?>" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" style="margin-right:.4rem"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2m0 18.2c-1.5 0-3-.4-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2m4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3s-.8.8-.8 2 .8 2.3.9 2.4 1.6 2.5 4 3.5c1.5.6 2 .7 2.8.6.4-.1 1.5-.6 1.7-1.2s.2-1.1.1-1.2-.2-.1-.4-.2"/></svg>WhatsApp us
                </a>
            </div>
        </div>
        <style>.ymo-callback-actions{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.5rem}.ymo-callback-actions__call{flex:2 1 260px}.ymo-callback-actions__wa{flex:1 1 200px}.ymo-callback-wa{background:#25d366!important;color:#0b3d1f!important;border-color:#25d366!important;font-weight:600;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.ymo-callback-wa:hover{background:#1fbe5b!important}</style>
    <?= form_close(); ?>
</div>
