<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Phone-only bar pinned to the bottom of every marketing page: Call + WhatsApp.
 * Ad visitors land on phones; this keeps both actions one tap away while they
 * scroll. Clicks are counted by the tel: / wa.me listener in google_tag.php.
 */
$mcb_tel = function_exists('marketing_business_phone_e164') ? marketing_business_phone_e164() : '+917558783868';
$mcb_wa  = function_exists('marketing_business_whatsapp_url')
    ? marketing_business_whatsapp_url('Hi YMO, I would like to book a car service')
    : 'https://wa.me/917558783868?text='.rawurlencode('Hi YMO, I would like to book a car service');
?>
<style>
.ymo-mobile-cta-bar{display:none}
@media (max-width:767.98px){
  .ymo-mobile-cta-bar{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:1060;gap:.5rem;
    padding:.5rem .75rem calc(.5rem + env(safe-area-inset-bottom,0px));background:#fff;
    box-shadow:0 -2px 12px rgba(0,0,0,.12)}
  .ymo-mobile-cta-bar a{flex:1 1 0;display:flex;align-items:center;justify-content:center;gap:.4rem;
    min-height:48px;border-radius:999px;font-weight:600;font-size:1rem;text-decoration:none;color:#fff}
  .ymo-mobile-cta-bar .mcb-call{background:#1b5e20}
  .ymo-mobile-cta-bar .mcb-wa{background:#25d366;color:#0b3d1f}
  body{padding-bottom:calc(68px + env(safe-area-inset-bottom,0px))}
  .ymo-floating-action--whatsapp{display:none!important}
  .ymo-floating-actions{bottom:calc(80px + env(safe-area-inset-bottom,0px))!important}
}
</style>
<nav class="ymo-mobile-cta-bar" aria-label="Call or WhatsApp YMO">
    <a class="mcb-call" href="tel:<?= html_escape($mcb_tel); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg>
        Call now
    </a>
    <a class="mcb-wa" href="<?= html_escape($mcb_wa); ?>" target="_blank" rel="noopener noreferrer">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2m0 18.2c-1.5 0-3-.4-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2m4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3s-.8.8-.8 2 .8 2.3.9 2.4 1.6 2.5 4 3.5c1.5.6 2 .7 2.8.6.4-.1 1.5-.6 1.7-1.2s.2-1.1.1-1.2-.2-.1-.4-.2"/></svg>
        WhatsApp
    </a>
</nav>
