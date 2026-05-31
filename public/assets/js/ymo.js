// Tiny progressive-enhancement layer. The booking app is server-rendered;
// JS is only used for non-critical conveniences.

(function () {
    'use strict';

    // ---- Snackbar host + auto-dismiss --------------------------------
    // Server-rendered snackbars sit inside a fixed host; auto-dismiss after
    // 6 seconds with a fade-out animation, or when the close button is hit.
    var dismissSnackbar = function (el) {
        if (!el || el.classList.contains('is-leaving')) { return; }
        el.classList.add('is-leaving');
        setTimeout(function () { el.remove(); }, 250);
    };
    document.querySelectorAll('.md-snackbar').forEach(function (el) {
        var ttl = parseInt(el.getAttribute('data-ttl'), 10);
        if (isNaN(ttl) || ttl < 0) { ttl = 6000; }
        if (ttl > 0) {
            setTimeout(function () { dismissSnackbar(el); }, ttl);
        }
        var btn = el.querySelector('.md-snackbar-close');
        if (btn) {
            btn.addEventListener('click', function () { dismissSnackbar(el); });
        }
    });

    // Backwards-compat: any leftover Bootstrap alert wrapped in .ymo-flash
    document.querySelectorAll('.ymo-flash .alert').forEach(function (el) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                bootstrap.Alert.getOrCreateInstance(el).close();
            } else {
                el.remove();
            }
        }, 6000);
    });

    // ---- Material ripple on .btn -------------------------------------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn, .md-fab');
        if (!btn || btn.disabled) { return; }
        var rect = btn.getBoundingClientRect();
        var size = Math.max(rect.width, rect.height);
        var ripple = document.createElement('span');
        ripple.className = 'md-ripple';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top  = (e.clientY - rect.top  - size / 2) + 'px';
        // Strip any older ripple on the same button to avoid stacking
        var prior = btn.querySelector('.md-ripple');
        if (prior) { prior.remove(); }
        btn.appendChild(ripple);
        setTimeout(function () { ripple.remove(); }, 600);
    });

    // OTP resend countdown
    var resendBtn = document.querySelector('[data-otp-resend]');
    if (resendBtn) {
        var seconds = parseInt(resendBtn.getAttribute('data-otp-resend'), 10) || 30;
        var label   = resendBtn.textContent;
        var disable = function (s) {
            resendBtn.disabled = true;
            resendBtn.textContent = 'Resend in ' + s + 's';
            if (s <= 0) {
                resendBtn.disabled = false;
                resendBtn.textContent = label;
                return;
            }
            setTimeout(function () { disable(s - 1); }, 1000);
        };
        disable(seconds);
    }

    // Confirm-on-click for destructive actions
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Auto-uppercase vehicle number inputs
    document.querySelectorAll('input[data-uppercase]').forEach(function (el) {
        el.addEventListener('input', function () {
            var caret = el.selectionStart;
            el.value = el.value.toUpperCase();
            try { el.setSelectionRange(caret, caret); } catch (e) {}
        });
    });

    document.querySelectorAll('[data-admin-drawer-nav] a').forEach(function (link) {
        link.addEventListener('click', function () {
            var drawer = document.getElementById('ymoAdminDrawer');
            if (!drawer || !window.bootstrap) { return; }
            var instance = bootstrap.Offcanvas.getInstance(drawer);
            if (instance) { instance.hide(); }
        });
    });
})();
