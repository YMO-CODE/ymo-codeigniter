<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
/* YMO Workshop service worker: keeps the app page and photo thumbnails available offline. */
var BASE = <?= json_encode($base); ?>;
var SHELL = 'ymo-tech-shell-<?= preg_replace('/\D/', '', $version); ?>';
var PHOTOS = 'ymo-tech-photos-1';

self.addEventListener('install', function (e) {
  e.waitUntil(caches.open(SHELL).then(function (c) { return c.addAll([BASE, BASE + '/manifest.webmanifest', BASE + '/icon.svg']); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
  e.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.filter(function (k) { return k.indexOf('ymo-tech-shell-') === 0 && k !== SHELL; }).map(function (k) { return caches.delete(k); }));
  }).then(function () { return self.clients.claim(); }));
});

self.addEventListener('fetch', function (e) {
  var req = e.request;
  if (req.method !== 'GET') return;
  var url = new URL(req.url);
  var path = url.pathname, base = new URL(BASE).pathname;
  if (url.origin !== location.origin) return;

  // The app page: network first, saved copy when offline.
  if (req.mode === 'navigate' && (path === base || path === base + '/')) {
    e.respondWith(fetch(req).then(function (r) {
      var copy = r.clone(); caches.open(SHELL).then(function (c) { c.put(BASE, copy); }); return r;
    }).catch(function () { return caches.match(BASE); }));
    return;
  }
  // Photos never change: cache first (thumbnails seen once show offline too).
  if (path.indexOf(base + '/photo/') === 0) {
    e.respondWith(caches.open(PHOTOS).then(function (c) {
      return c.match(req).then(function (hit) {
        return hit || fetch(req).then(function (r) {
          if (r.ok) { c.put(req, r.clone()).then(function () { return c.keys(); }).then(function (k) { if (k.length > 400) { k.slice(0, k.length - 400).forEach(function (x) { c.delete(x); }); } }); }
          return r;
        });
      });
    }));
    return;
  }
  if (path === base + '/manifest.webmanifest' || path === base + '/icon.svg') {
    e.respondWith(caches.match(req).then(function (hit) { return hit || fetch(req); }));
  }
});
