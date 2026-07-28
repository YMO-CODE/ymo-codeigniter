(function () {
    'use strict';

    function renderOffer(offer) {
        if (!offer || !offer.title) {
            return;
        }

        var overlay = document.createElement('div');
        overlay.className = 'ymo-offer-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', offer.title);

        var modal = document.createElement('div');
        modal.className = 'ymo-offer-modal';

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'ymo-offer-close';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function () {
            overlay.remove();
        });

        modal.appendChild(closeBtn);

        if (offer.image_url) {
            var img = document.createElement('img');
            img.className = 'ymo-offer-image';
            img.src = offer.image_url;
            img.alt = '';
            modal.appendChild(img);
        }

        var title = document.createElement('h2');
        title.className = 'ymo-offer-title';
        title.textContent = offer.title;
        modal.appendChild(title);

        if (offer.body) {
            var body = document.createElement('p');
            body.className = 'ymo-offer-body';
            body.textContent = offer.body;
            modal.appendChild(body);
        }

        if (offer.cta_label && offer.cta_url) {
            var cta = document.createElement('a');
            cta.className = 'ymo-offer-cta';
            cta.href = offer.cta_url;
            cta.textContent = offer.cta_label;
            cta.target = '_blank';
            cta.rel = 'noopener noreferrer';
            modal.appendChild(cta);
        }

        overlay.appendChild(modal);
        overlay.addEventListener('click', function (ev) {
            if (ev.target === overlay) {
                overlay.remove();
            }
        });

        document.body.appendChild(overlay);
    }

    function pickOffer(payload) {
        if (!payload) {
            return null;
        }
        var offers = payload.offers || payload;
        if (!Array.isArray(offers) || !offers.length) {
            return null;
        }
        return offers[0];
    }

    function bootstrapFromInline() {
        var payload = window.YMO_OFFERS_BOOTSTRAP;
        var offer = pickOffer(payload);
        if (offer) {
            renderOffer(offer);
        }
    }

    function bootstrapFromApi() {
        var cfg = window.YMO_OFFERS || {};
        if (!cfg.apiUrl) {
            return;
        }
        fetch(cfg.apiUrl, { credentials: 'omit', mode: 'cors' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var offer = pickOffer(data);
                if (offer) {
                    renderOffer(offer);
                }
            })
            .catch(function () { /* silent */ });
    }

    function init() {
        if (window.YMO_OFFERS_BOOTSTRAP) {
            bootstrapFromInline();
            return;
        }
        bootstrapFromApi();
    }

    function scheduleInit() {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(init, { timeout: 2500 });
        } else {
            setTimeout(init, 400);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleInit);
    } else {
        scheduleInit();
    }
})();
