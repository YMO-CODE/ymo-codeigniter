/*
 * YMO CRM service worker: shows YMOChat alerts (new website visitor, chat
 * message, number shared) even when the CRM is closed, and opens the visitor
 * in YMOChat when the alert is tapped. It does not cache any pages.
 */
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });

self.addEventListener('push', function (e) {
    var d = {};
    try { d = e.data ? e.data.json() : {}; } catch (err) { d = { title: 'YMOChat', body: e.data ? e.data.text() : '' }; }
    var title = d.title || 'YMOChat';
    e.waitUntil(self.registration.showNotification(title, {
        body: d.body || '',
        tag: d.tag || 'ymochat',
        renotify: true,
        icon: '/assets/img/crm-icon-192.png',
        badge: '/assets/img/crm-badge-96.png',
        vibrate: [200, 100, 200],
        data: { url: d.url || '/' }
    }));
});

self.addEventListener('notificationclick', function (e) {
    e.notification.close();
    var url = (e.notification.data && e.notification.data.url) || '/';
    e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
        for (var i = 0; i < list.length; i++) {
            var c = list[i];
            if ('focus' in c) {
                return c.focus().then(function (w) { return w && 'navigate' in w ? w.navigate(url) : null; }).catch(function () { return self.clients.openWindow(url); });
            }
        }
        return self.clients.openWindow(url);
    }));
});
