<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Public forms: after the first (valid) submit, lock the submit button and show
 * "Sending…" so impatient double/triple taps don't post the form again.
 * Skips forms handled by JavaScript (preventDefault) and forms marked
 * data-no-submit-lock. Unlocks after 20 s, or when the page is shown again
 * from the back/forward cache, so a stuck network never leaves it disabled.
 */
?>
<script>
(function () {
  function unlock(form) {
    form.removeAttribute('data-submitting');
    form.querySelectorAll('[data-lock-label]').forEach(function (b) {
      b.disabled = false;
      b.innerHTML = b.getAttribute('data-lock-label');
      b.removeAttribute('data-lock-label');
    });
  }
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM' || form.hasAttribute('data-no-submit-lock')) { return; }
    if (form.getAttribute('data-submitting') === '1') { e.preventDefault(); return; }
    if (e.defaultPrevented) { return; }
    form.setAttribute('data-submitting', '1');
    // Disable after this tick so the clicked button's name/value is still sent.
    setTimeout(function () {
      form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(function (b) {
        if (b.tagName === 'INPUT') { return; }
        b.setAttribute('data-lock-label', b.innerHTML);
        b.disabled = true;
        b.innerHTML = 'Sending…';
      });
    }, 0);
    setTimeout(function () { unlock(form); }, 20000);
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) { document.querySelectorAll('form[data-submitting]').forEach(unlock); }
  });
})();
</script>
