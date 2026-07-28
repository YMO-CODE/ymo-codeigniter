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

    // Contacts list — bulk select bar (fallback if page inline script absent)
    (function () {
        var form = document.getElementById('contacts-bulk-form');
        var table = document.getElementById('contacts-table');
        var bulkBar = document.getElementById('contacts-bulk-bar');
        if (!form || !table || !bulkBar || form.dataset.bulkInit === '1') { return; }

        var selectAll = document.getElementById('contacts-select-all');
        var countEl = document.getElementById('contacts-bulk-count');

        var rowChecks = function () {
            return table.querySelectorAll('.contact-row-check');
        };

        var syncBulkBar = function () {
            var checked = table.querySelectorAll('.contact-row-check:checked');
            var n = checked.length;
            if (countEl) { countEl.textContent = String(n); }
            bulkBar.hidden = n === 0;
            if (selectAll) {
                var all = rowChecks();
                selectAll.checked = all.length > 0 && n === all.length;
                selectAll.indeterminate = n > 0 && n < all.length;
            }
        };

        form.addEventListener('change', function (e) {
            var t = e.target;
            if (!t || t.type !== 'checkbox') { return; }
            if (t.id === 'contacts-select-all') {
                rowChecks().forEach(function (cb) { cb.checked = t.checked; });
            } else if (!t.classList.contains('contact-row-check')) {
                return;
            }
            syncBulkBar();
        });

        form.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-contacts-clear-selection]');
            if (!btn) { return; }
            rowChecks().forEach(function (cb) { cb.checked = false; });
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            syncBulkBar();
        });

        form.addEventListener('submit', function (e) {
            if (table.querySelectorAll('.contact-row-check:checked').length === 0) {
                e.preventDefault();
                window.alert('Select at least one contact.');
            }
        });
    })();

    // Migrated WP pages: activate lazy-loaded hero images
    document.querySelectorAll('.marketing-body img[data-src]').forEach(function (img) {
        img.src = img.getAttribute('data-src');
        img.removeAttribute('data-src');
    });

    // Desktop marketing nav — open Services/Locations on hover (mobile uses drawer)
    (function () {
        var mq = window.matchMedia('(min-width: 992px)');
        var closeDelay = 140;

        function bindHoverNav() {
            document.querySelectorAll('.ymo-navbar .ymo-nav-dropdown').forEach(function (item) {
                if (item.dataset.hoverNavBound === '1') { return; }
                item.dataset.hoverNavBound = '1';

                var timer = null;

                function open() {
                    if (!mq.matches) { return; }
                    clearTimeout(timer);
                    item.classList.add('is-hover-open');
                }

                function scheduleClose() {
                    if (!mq.matches) { return; }
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        item.classList.remove('is-hover-open');
                    }, closeDelay);
                }

                item.addEventListener('mouseenter', open);
                item.addEventListener('mouseleave', scheduleClose);
                item.addEventListener('focusin', open);
                item.addEventListener('focusout', function (e) {
                    if (!item.contains(e.relatedTarget)) {
                        scheduleClose();
                    }
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindHoverNav);
        } else {
            bindHoverNav();
        }

        mq.addEventListener('change', function () {
            document.querySelectorAll('.ymo-navbar .ymo-nav-dropdown.is-hover-open').forEach(function (item) {
                item.classList.remove('is-hover-open');
            });
        });
    })();

    // Mobile drawer — staggered menu reveal + hamburger feedback
    (function () {
        var drawer = document.getElementById('ymoDrawer');
        if (!drawer) { return; }

        var toggler = document.querySelector('[data-bs-target="#ymoDrawer"]');

        drawer.addEventListener('show.bs.offcanvas', function () {
            if (toggler) {
                toggler.classList.add('is-active');
                toggler.setAttribute('aria-expanded', 'true');
            }
            requestAnimationFrame(function () {
                drawer.classList.add('is-revealed');
            });
        });

        drawer.addEventListener('hide.bs.offcanvas', function () {
            drawer.classList.remove('is-revealed');
            if (toggler) {
                toggler.classList.remove('is-active');
                toggler.setAttribute('aria-expanded', 'false');
            }
        });

        drawer.querySelectorAll('.ymo-drawer-link[href], .ymo-drawer-sub-link[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 991.98px)').matches && typeof bootstrap !== 'undefined') {
                    var instance = bootstrap.Offcanvas.getInstance(drawer);
                    if (instance) {
                        instance.hide();
                    }
                }
            });
        });
    })();

    // Homepage city hint — dismiss sets cookie, no server round-trip
    (function () {
        var banner = document.querySelector('.ymo-city-hint');
        if (!banner) { return; }

        var dismissBtn = banner.querySelector('[data-city-hint-dismiss]');
        if (!dismissBtn) { return; }

        var cookieName = 'ymo_city_hint_dismissed';
        var cookieDays = 90;

        var setDismissCookie = function () {
            var maxAge = cookieDays * 24 * 60 * 60;
            document.cookie = cookieName + '=1; path=/; max-age=' + maxAge + '; SameSite=Lax';
        };

        dismissBtn.addEventListener('click', function () {
            setDismissCookie();
            banner.classList.add('is-dismissed');
        });
    })();
})();
