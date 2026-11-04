/*
 * Visitor city for the marketing site (Pune / Indore / Nashik).
 *
 * - Header switcher shows the visitor's city; a choice is remembered for a year (ymo_city cookie).
 * - Without a choice, asks /geo/city (local DB-IP lookup) once per visit.
 * - Pages without a city of their own (home, services hub): service cards link to the
 *   visitor's city version.
 * - City pages for another city: a small, dismissible hint links to the same page for the
 *   visitor's city. Nothing is ever redirected, and server HTML is the same for everyone.
 */
(function () {
    'use strict';

    var CITIES = { pune: 'Pune', indore: 'Indore', nashik: 'Nashik' };
    var COOKIE = 'ymo_city';
    var SESSION_KEY = 'ymo_city_detected';
    var body = document.body;
    if (!body) {
        return;
    }

    function parse(json) {
        try {
            var v = JSON.parse(json || '{}');
            return v && typeof v === 'object' ? v : {};
        } catch (e) {
            return {};
        }
    }

    function getCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }

    function setCookie(name, value, days) {
        document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + (days * 86400) + '; SameSite=Lax';
    }

    function store(kind, key, value) {
        try {
            var s = kind === 'session' ? window.sessionStorage : window.localStorage;
            if (value === undefined) {
                return s.getItem(key);
            }
            s.setItem(key, value);
        } catch (e) {
            return null;
        }
        return null;
    }

    var pageCity = body.getAttribute('data-ymo-page-city') || '';
    var variants = parse(body.getAttribute('data-ymo-city-variants'));

    function toHref(path) {
        return '/' + String(path || '').replace(/^\/+/, '');
    }

    function setLabel(slug) {
        var text = CITIES[slug] || 'Select city';
        document.querySelectorAll('[data-city-switch-label]').forEach(function (el) {
            el.textContent = text;
        });
        document.querySelectorAll('[data-city-option]').forEach(function (el) {
            el.setAttribute('aria-current', el.getAttribute('data-city-option') === slug ? 'true' : 'false');
        });
    }

    function swapLinks(slug) {
        if (pageCity) {
            return;
        }
        document.querySelectorAll('a[data-city-variants]').forEach(function (a) {
            if (!a.hasAttribute('data-default-href')) {
                a.setAttribute('data-default-href', a.getAttribute('href') || '');
            }
            var v = parse(a.getAttribute('data-city-variants'));
            a.setAttribute('href', v[slug] ? toHref(v[slug]) : a.getAttribute('data-default-href'));
        });
    }

    function removeHint() {
        var old = document.querySelector('.ymo-city-hint[data-ymo-city-hint]');
        if (old && old.parentNode) {
            old.parentNode.removeChild(old);
        }
    }

    function showHint(slug) {
        removeHint();
        if (!slug || !variants[slug]) {
            return;
        }
        var label;
        if (pageCity && pageCity !== slug) {
            label = 'See this for ' + CITIES[slug];
        } else if (!pageCity && slug !== 'pune') {
            label = 'View car services in ' + CITIES[slug];
        } else {
            return;
        }
        if (store('session', 'ymo_city_hint_closed_' + slug) === '1') {
            return;
        }
        var anchor = document.querySelector('.ymo-topbar');
        if (!anchor || !anchor.parentNode) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'ymo-city-hint';
        wrap.setAttribute('data-ymo-city-hint', '');
        wrap.setAttribute('role', 'region');
        wrap.setAttribute('aria-label', 'City suggestion');
        wrap.innerHTML =
            '<div class="container d-flex align-items-center justify-content-between gap-3 flex-wrap py-2">' +
            '<p class="ymo-city-hint-text mb-0 md-body-md">' +
            '<span class="mi mi-sm ymo-city-hint-icon" aria-hidden="true">location_on</span> ' +
            '<span data-hint-lead></span> ' +
            '<a class="ymo-city-hint-link" data-hint-link></a></p>' +
            '<button type="button" class="ymo-city-hint-dismiss md-btn md-btn--text md-btn--sm" aria-label="Dismiss city suggestion">' +
            '<span class="mi mi-sm" aria-hidden="true">close</span> Not now</button></div>';
        wrap.querySelector('[data-hint-lead]').textContent = 'Serving ' + CITIES[slug] + ' —';
        var link = wrap.querySelector('[data-hint-link]');
        link.textContent = label;
        link.setAttribute('href', toHref(variants[slug]));
        wrap.querySelector('button').addEventListener('click', function () {
            store('session', 'ymo_city_hint_closed_' + slug, '1');
            removeHint();
        });
        anchor.parentNode.insertBefore(wrap, anchor);
    }

    function apply(slug) {
        if (!CITIES[slug]) {
            slug = '';
        }
        document.documentElement.setAttribute('data-ymo-city', slug);
        setLabel(slug);
        swapLinks(slug);
        showHint(slug);
    }

    // --- Switcher ------------------------------------------------------------
    function closeMenus(except) {
        document.querySelectorAll('[data-city-switch]').forEach(function (sw) {
            if (sw === except) {
                return;
            }
            var btn = sw.querySelector('[data-city-switch-toggle]');
            var menu = sw.querySelector('[data-city-switch-menu]');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }
            if (menu) {
                menu.hidden = true;
            }
        });
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest ? e.target.closest('[data-city-switch-toggle]') : null;
        if (toggle) {
            e.preventDefault();
            var sw = toggle.closest('[data-city-switch]');
            var menu = sw ? sw.querySelector('[data-city-switch-menu]') : null;
            var open = toggle.getAttribute('aria-expanded') === 'true';
            closeMenus(sw);
            toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            if (menu) {
                menu.hidden = open;
                if (!open) {
                    var first = menu.querySelector('[data-city-option]');
                    if (first) {
                        first.focus();
                    }
                }
            }
            return;
        }

        var opt = e.target.closest ? e.target.closest('[data-city-option]') : null;
        if (opt) {
            var slug = opt.getAttribute('data-city-option');
            if (!CITIES[slug]) {
                return;
            }
            setCookie(COOKIE, slug, 365);
            closeMenus(null);
            if (pageCity && pageCity !== slug && variants[slug]) {
                // On another city's page: go to the same page for the chosen city.
                opt.setAttribute('href', toHref(variants[slug]));
                return; // let the link navigate
            }
            e.preventDefault();
            apply(slug);
            return;
        }

        if (!(e.target.closest && e.target.closest('[data-city-switch]'))) {
            closeMenus(null);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenus(null);
        }
    });

    // --- Detect ----------------------------------------------------------------
    var chosen = getCookie(COOKIE);
    if (CITIES[chosen]) {
        apply(chosen);
        return;
    }

    var cached = store('session', SESSION_KEY);
    if (cached !== null) {
        apply(cached);
        return;
    }

    setLabel('');
    if (!window.fetch) {
        return;
    }
    window.fetch('/geo/city', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            var slug = data && CITIES[data.city] ? data.city : '';
            store('session', SESSION_KEY, slug);
            apply(slug);
        })
        .catch(function () {});
})();
