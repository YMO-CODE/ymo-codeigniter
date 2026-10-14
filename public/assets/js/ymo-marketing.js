// Marketing site only — snackbars, nav drawer, hero carousel bootstrap loader.
(function () {
    'use strict';

    document.documentElement.classList.add('js');

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

    function deferIdle(fn, timeoutMs) {
        timeoutMs = timeoutMs || 4000;
        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(fn, { timeout: timeoutMs });
        } else {
            setTimeout(fn, Math.min(timeoutMs, 2500));
        }
    }

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

    // Hero carousel — load Bootstrap only on interaction or after idle (not on first paint)
    (function () {
        var carousel = document.querySelector('.ymo-hero-slider.carousel');
        if (!carousel) { return; }

        var started = false;
        function startCarousel() {
            if (started) { return; }
            started = true;
            loadBootstrap(function () {
                if (!window.bootstrap || !bootstrap.Carousel) { return; }
                var instance = bootstrap.Carousel.getOrCreateInstance(carousel, {
                    ride: carousel.querySelectorAll('.carousel-item').length > 1 ? 'carousel' : false,
                    interval: parseInt(carousel.getAttribute('data-bs-interval'), 10) || 6000,
                    pause: carousel.getAttribute('data-bs-pause') || 'hover',
                });
                if (instance && typeof instance.cycle === 'function') {
                    instance.cycle();
                }
            });
        }

        carousel.querySelectorAll('[data-bs-slide], [data-bs-slide-to]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (!window.bootstrap) {
                    e.preventDefault();
                    startCarousel();
                }
            });
        });

        deferIdle(startCarousel, 8000);
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

    // Defer third-party widgets until idle (offers popup only — LiveChat loads on click)
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
    });

    // LiveChat — load only when user opens chat (saves ~100 KiB on initial load)
    (function () {
        var cfgEl = document.getElementById('ymo-livechat-config');
        var btn = document.getElementById('ymo-livechat-open');
        if (!cfgEl || !btn) { return; }

        var loaded = false;
        function loadLiveChat() {
            if (loaded) {
                try { LiveChatWidget.call('maximize'); } catch (e) {}
                return;
            }
            loaded = true;
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
            LiveChatWidget.on('ready', function () {
                hideBubble();
                try { LiveChatWidget.call('maximize'); } catch (e) {}
            });
            LiveChatWidget.on('visibility_changed', function (data) {
                if (!data || data.visibility === 'maximized') { return; }
                hideBubble();
            });
        }

        btn.addEventListener('click', loadLiveChat);
    })();

    // v2 service card orbit — flatten to grid on hover (is-flat + optional WAAPI FLIP)
    (function () {
        var mq = window.matchMedia('(min-width: 992px)');
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var FLIP_MS = 880;
        var FLIP_EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

        function getSlots(viewport) {
            return Array.prototype.slice.call(
                viewport.querySelectorAll('.ymo-v2-card-grid--orbit > [class*="col-"]')
            );
        }

        function freezeRing(ring) {
            if (!ring) {
                return;
            }
            try {
                if (typeof ring.getAnimations === 'function') {
                    ring.getAnimations().forEach(function (anim) {
                        anim.pause();
                    });
                }
            } catch (e) {}
            var matrix = window.getComputedStyle(ring).transform;
            if (matrix && matrix !== 'none') {
                ring.style.animation = 'none';
                ring.style.transform = matrix;
            }
        }

        function clearRingInline(ring) {
            if (!ring) {
                return;
            }
            ring.style.animation = '';
            ring.style.transform = '';
        }

        function resumeRing(ring) {
            clearRingInline(ring);
        }

        function cancelSlotAnimations(slots) {
            slots.forEach(function (el) {
                try {
                    if (typeof el.getAnimations === 'function') {
                        el.getAnimations().forEach(function (anim) {
                            anim.cancel();
                        });
                    }
                } catch (e) {}
            });
        }

        function endFlipPhase(viewport) {
            window.clearTimeout(viewport._orbitFlipTimer);
            viewport._orbitFlipTimer = null;
            viewport.classList.remove('is-orbit-flip');
        }

        function setFlat(viewport, ring, flat) {
            if (!mq.matches) {
                return;
            }
            var slots = getSlots(viewport);
            endFlipPhase(viewport);

            if (flat) {
                if (viewport.classList.contains('is-flat')) {
                    return;
                }
                cancelSlotAnimations(slots);
                if (ring) {
                    freezeRing(ring);
                }
                var first = slots.map(function (el) {
                    return el.getBoundingClientRect();
                });
                viewport.classList.add('is-flat');
                if (ring) {
                    clearRingInline(ring);
                }
                void viewport.offsetHeight;

                if (reduceMotion.matches || !slots.length) {
                    return;
                }

                var last = slots.map(function (el) {
                    return el.getBoundingClientRect();
                });
                var anims = [];

                viewport.classList.add('is-orbit-flip');
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        slots.forEach(function (el, i) {
                            var f = first[i];
                            var l = last[i];
                            if (!f || !l || l.width < 1 || f.width < 1 || typeof el.animate !== 'function') {
                                return;
                            }
                            var dx = f.left - l.left;
                            var dy = f.top - l.top;
                            var sx = f.width / l.width;
                            var sy = f.height / l.height;
                            if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5 && Math.abs(sx - 1) < 0.01 && Math.abs(sy - 1) < 0.01) {
                                return;
                            }
                            anims.push(el.animate(
                                [
                                    { transform: 'translate3d(' + dx + 'px, ' + dy + 'px, 0) scale(' + sx + ', ' + sy + ')' },
                                    { transform: 'translate3d(0, 0, 0) scale(1, 1)' }
                                ],
                                { duration: FLIP_MS, easing: FLIP_EASE, fill: 'forwards' }
                            ));
                        });

                        if (!anims.length) {
                            endFlipPhase(viewport);
                            return;
                        }

                        var flipDone = false;
                        function finishMotion() {
                            if (flipDone) {
                                return;
                            }
                            flipDone = true;
                            endFlipPhase(viewport);
                        }

                        viewport._orbitFlipTimer = window.setTimeout(finishMotion, FLIP_MS + 120);
                        Promise.all(anims.map(function (a) {
                            return a.finished;
                        })).then(finishMotion).catch(finishMotion);
                    });
                });
                return;
            }

            if (!viewport.classList.contains('is-flat')) {
                return;
            }
            cancelSlotAnimations(slots);
            viewport.classList.remove('is-flat');
            if (ring) {
                resumeRing(ring);
            }
        }

        function bindOrbitFlatten() {
            document.querySelectorAll('.ymo-v2-card-orbit-viewport--active').forEach(function (viewport) {
                if (viewport.dataset.orbitFlattenBound === '1') {
                    return;
                }
                viewport.dataset.orbitFlattenBound = '1';

                var ring = viewport.querySelector('.ymo-v2-card-grid--orbit');

                viewport.addEventListener('pointerenter', function () {
                    setFlat(viewport, ring, true);
                });
                viewport.addEventListener('pointerleave', function () {
                    setFlat(viewport, ring, false);
                });
                viewport.addEventListener('focusin', function () {
                    setFlat(viewport, ring, true);
                });
                viewport.addEventListener('focusout', function (e) {
                    if (!viewport.contains(e.relatedTarget)) {
                        setFlat(viewport, ring, false);
                    }
                });
            });
        }

        function initOrbitFlatten() {
            bindOrbitFlatten();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initOrbitFlatten);
        } else {
            initOrbitFlatten();
        }
        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', bindOrbitFlatten);
        }
    })();
})();
