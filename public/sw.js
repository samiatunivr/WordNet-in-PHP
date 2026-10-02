/* Asl service worker — makes the shop installable and gives an offline page.
 *
 * Pages (HTML) are never cached: they contain per-visitor data (cart, CSRF
 * tokens), so they always come from the network, with an offline fallback.
 * Only static assets and product images are cached.
 * Bump VERSION when CSS/JS/icons change so installed apps pick them up.
 */
'use strict';
const VERSION = 'asl-v1';
const STATIC_CACHE = VERSION + '-static';
const LOCALES = ['ar', 'en', 'nl'];
const PRECACHE = [
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/img/logo.svg',
  '/assets/img/placeholder.svg',
  '/assets/img/icon-192.png',
].concat(LOCALES.map((l) => '/' + l + '/offline'));

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE.map((u) => new Request(u, { credentials: 'omit' }))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

function offlineFallback(url) {
  const m = url.pathname.match(/^\/(ar|en|nl)(\/|$)/);
  return caches.match('/' + (m ? m[1] : 'ar') + '/offline');
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Page navigations: network only, offline page when there is no connection.
  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => offlineFallback(url)));
    return;
  }

  // Static assets and product images: stale-while-revalidate.
  if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/uploads/products/')) {
    event.respondWith(
      caches.open(STATIC_CACHE).then((cache) =>
        cache.match(req).then((cached) => {
          const network = fetch(req).then((res) => {
            if (res.ok && res.type === 'basic') cache.put(req, res.clone());
            return res;
          }).catch(() => cached);
          return cached || network;
        })
      )
    );
  }
  // Everything else (manifest, admin, webhooks…) goes straight to the network.
});
