// Marketing site only — snackbars, nav drawer, hero carousel bootstrap loader.
(function () {
    'use strict';

    var bootstrapUrl = document.body.getAttribute('data-bootstrap-js') || '';
    var bootstrapLoading = false;
    var bootstrapQueue = [];

    function runBootstrapQueue() {
        bootstrapQueue.forEach(function (fn) { fn(); });
        bootstrapQueue = [];
    }

    function loadBootstrap(callback) {
        if (window.bootstrap) {
            if (callback) { callback(); }
            return;
        }
        if (callback) { bootstrapQueue.push(callback); }
        if (bootstrapLoading || !bootstrapUrl) { return; }
        bootstrapLoading = true;
        var s = document.createElement('script');
        s.src = bootstrapUrl;
        s.defer = true;
        s.onload = function () {
            bootstrapLoading = false;
            runBootstrapQueue();
        };
        document.body.appendChild(s);
    }

    window.ymoLoadBootstrap = loadBootstrap;

    // Snackbar auto-dismiss
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

    // Material ripple
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn, .md-fab, .md-btn');
        if (!btn || btn.disabled) { return; }
        var rect = btn.getBoundingClientRect();
        var size = Math.max(rect.width, rect.height);
        var ripple = document.createElement('span');
        ripple.className = 'md-ripple';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
        var prior = btn.querySelector('.md-ripple');
        if (prior) { prior.remove(); }
        btn.appendChild(ripple);
        setTimeout(function () { ripple.remove(); }, 600);
    });

    // Lazy hero / body images (data-src)
    document.querySelectorAll('.marketing-body img[data-src]').forEach(function (img) {
        img.src = img.getAttribute('data-src');
        img.removeAttribute('data-src');
    });

    // Desktop marketing nav hover
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
                    if (!item.contains(e.relatedTarget)) { scheduleClose(); }
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

    // Mobile drawer — load Bootstrap on first open
    (function () {
        var drawer = document.getElementById('ymoDrawer');
        var toggler = document.querySelector('[data-bs-target="#ymoDrawer"]');
        if (!drawer) { return; }

        if (toggler) {
            toggler.addEventListener('click', function (e) {
                if (window.bootstrap) { return; }
                e.preventDefault();
                loadBootstrap(function () {
                    var instance = bootstrap.Offcanvas.getOrCreateInstance(drawer);
                    instance.show();
                });
            });
        }

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
                if (window.matchMedia('(max-width: 991.98px)').matches && window.bootstrap) {
                    var instance = bootstrap.Offcanvas.getInstance(drawer);
                    if (instance) { instance.hide(); }
                }
            });
        });
    })();

    // Hero carousel — load Bootstrap when carousel is present
    (function () {
        var carousel = document.querySelector('.ymo-hero-slider.carousel');
        if (!carousel) { return; }
        loadBootstrap(function () {
            if (bootstrap.Carousel) {
                bootstrap.Carousel.getOrCreateInstance(carousel);
            }
        });
    })();

    // City hint dismiss
    (function () {
        var banner = document.querySelector('.ymo-city-hint');
        if (!banner) { return; }
        var dismissBtn = banner.querySelector('[data-city-hint-dismiss]');
        if (!dismissBtn) { return; }
        dismissBtn.addEventListener('click', function () {
            document.cookie = 'ymo_city_hint_dismissed=1; path=/; max-age=' + (90 * 24 * 60 * 60) + '; SameSite=Lax';
            banner.classList.add('is-dismissed');
        });
    })();

    // Defer third-party widgets until idle (LiveChat, offers)
    function deferIdle(fn, timeoutMs) {
        timeoutMs = timeoutMs || 4000;
        if ('requestIdleCallback' in window) {
            requestIdleCallback(fn, { timeout: timeoutMs });
        } else {
            setTimeout(fn, Math.min(timeoutMs, 2500));
        }
    }

    deferIdle(function () {
        document.querySelectorAll('script[data-defer-load]').forEach(function (placeholder) {
            var s = document.createElement('script');
            var src = placeholder.getAttribute('data-defer-load');
            if (!src) { return; }
            s.src = src;
            s.defer = true;
            placeholder.parentNode.insertBefore(s, placeholder.nextSibling);
        });
        document.querySelectorAll('link[data-defer-style]').forEach(function (link) {
            link.rel = 'stylesheet';
        });
        initDeferredLiveChat();
    });

    function initDeferredLiveChat() {
        var cfgEl = document.getElementById('ymo-livechat-config');
        if (!cfgEl) { return; }
        var cfg;
        try {
            cfg = JSON.parse(cfgEl.textContent || '{}');
        } catch (e) {
            return;
        }
        if (!cfg.license) { return; }

        window.__lc = window.__lc || {};
        window.__lc.license = cfg.license;
        window.__lc.integration_name = 'manual_onboarding';
        window.__lc.product_name = 'livechat';
        (function (n, t) {
            function i(args) { return e._h ? e._h.apply(null, args) : e._q.push(args); }
            var e = { _q: [], _h: null, _v: '2.0',
                on: function () { i(['on', [].slice.call(arguments)]); },
                once: function () { i(['once', [].slice.call(arguments)]); },
                off: function () { i(['off', [].slice.call(arguments)]); },
                get: function () { return i(['get', [].slice.call(arguments)]); },
                call: function () { i(['call', [].slice.call(arguments)]); },
                init: function () {
                    var s = t.createElement('script');
                    s.async = true;
                    s.src = 'https://cdn.livechatinc.com/tracking.js';
                    t.head.appendChild(s);
                }
            };
            if (!n.__lc.asyncInit) { e.init(); }
            n.LiveChatWidget = n.LiveChatWidget || e;
        }(window, document));

        function hideBubble() {
            try { LiveChatWidget.call('hide'); } catch (e) {}
        }
        LiveChatWidget.on('ready', hideBubble);
        LiveChatWidget.on('visibility_changed', function (data) {
            if (!data || data.visibility === 'maximized') { return; }
            hideBubble();
        });
        var btn = document.getElementById('ymo-livechat-open');
        if (btn) {
            btn.addEventListener('click', function () {
                LiveChatWidget.call('maximize');
            });
        }
    }
})();
