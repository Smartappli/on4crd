const VERSION = 'v356-offline-mode';
const STATIC_CACHE = `on4crd-static-${VERSION}`;
const PAGE_CACHE = `on4crd-pages-${VERSION}`;
const DATA_CACHE = `on4crd-data-${VERSION}`;
const CACHE_LIMITS = {
  [STATIC_CACHE]: 120,
  [PAGE_CACHE]: 50,
  [DATA_CACHE]: 80
};

const APP_SHELL = [
  './',
  './index.php?route=home',
  './index.php?route=news',
  './index.php?route=press',
  './index.php?route=schools',
  './index.php?route=articles',
  './index.php?route=committee',
  './index.php?route=events',
  './assets/css/app.css',
  './assets/js/app.js',
  './offline.html',
  './manifest.webmanifest',
  './assets/icons/icon.svg',
  './assets/icons/icon-maskable.svg',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png',
  './assets/icons/icon-maskable-512.png',
  './assets/icons/apple-touch-icon.png'
];

const PUBLIC_ROUTES = new Set([
  '',
  'home',
  'news',
  'news_view',
  'articles',
  'article',
  'wiki',
  'wiki_view',
  'albums',
  'album',
  'directory',
  'committee',
  'press',
  'schools',
  'events',
  'event_view',
  'sitemap.xml',
  'robots.txt'
]);

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(APP_SHELL.map((url) => new Request(url, { cache: 'reload' }))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => ![STATIC_CACHE, PAGE_CACHE, DATA_CACHE].includes(key)).map((key) => caches.delete(key))))
      .then(() => Promise.all([trimCache(STATIC_CACHE), trimCache(PAGE_CACHE), trimCache(DATA_CACHE)]))
      .then(() => self.clients.claim())
  );
});

async function trimCache(cacheName) {
  const limit = CACHE_LIMITS[cacheName] || 80;
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  if (keys.length <= limit) {
    return;
  }
  const stale = keys.slice(0, keys.length - limit);
  await Promise.all(stale.map((request) => cache.delete(request)));
}

function isCacheableResponse(response) {
  return response && (response.status === 200 || response.type === 'opaque');
}

async function putInCache(cache, request, response, cacheName) {
  if (!isCacheableResponse(response)) {
    return;
  }
  await cache.put(request, response.clone());
  await trimCache(cacheName);
}

async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  const networkPromise = fetch(request, { credentials: 'same-origin' })
    .then((response) => {
      return putInCache(cache, request, response, cacheName).then(() => response);
    })
    .catch(async () => {
      if (cached) {
        return cached;
      }
      if (request.destination === 'image') {
        return (await caches.match('./assets/icons/icon-192.png')) || Response.error();
      }
      if (request.destination === 'style') {
        return new Response('', { headers: { 'Content-Type': 'text/css; charset=utf-8' } });
      }
      if (request.destination === 'script') {
        return new Response('// offline fallback', { headers: { 'Content-Type': 'application/javascript; charset=utf-8' } });
      }
      return Response.error();
    });
  return cached || networkPromise;
}

async function networkFirst(request, cacheName, fallbackUrl = './offline.html') {
  const cache = await caches.open(cacheName);
  try {
    const response = await fetch(request, { credentials: 'same-origin' });
    await putInCache(cache, request, response, cacheName);
    return response;
  } catch (error) {
    const cached = await cache.match(request);
    if (cached) {
      return cached;
    }
    if (request.destination === 'document' || request.mode === 'navigate') {
      return (await caches.match(fallbackUrl)) || Response.error();
    }
    return Response.error();
  }
}

function isPublicNavigation(url) {
  if (url.origin !== location.origin) {
    return false;
  }

  if (url.pathname === '/' || url.pathname.endsWith('/index.php')) {
    return PUBLIC_ROUTES.has(url.searchParams.get('route') || 'home');
  }

  const trimmed = url.pathname.replace(/\/+/g, '/').replace(/^\//, '').replace(/\/$/, '');
  return PUBLIC_ROUTES.has(trimmed);
}

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  const route = url.searchParams.get('route') || '';

  if (request.mode === 'navigate') {
    if (isPublicNavigation(url)) {
      event.respondWith(networkFirst(request, PAGE_CACHE));
    } else {
      event.respondWith(fetch(request, { credentials: 'same-origin' }).catch(() => caches.match('./offline.html')));
    }
    return;
  }

  if (url.origin === location.origin && (url.pathname.endsWith('.css') || url.pathname.endsWith('.js') || url.pathname.endsWith('.svg') || url.pathname.endsWith('.png') || url.pathname.endsWith('.jpg') || url.pathname.endsWith('.jpeg') || url.pathname.endsWith('.webmanifest'))) {
    event.respondWith(staleWhileRevalidate(request, STATIC_CACHE));
    return;
  }

  if (route === 'widget_render' || route === 'dashboard' || route === 'save_dashboard' || route === 'profile' || route.startsWith('admin')) {
    event.respondWith(
      fetch(request, { credentials: 'same-origin' })
        .catch(async () => (await caches.match('./offline.html')) || Response.error())
    );
    return;
  }

  if (url.origin !== location.origin) {
    event.respondWith(staleWhileRevalidate(request, DATA_CACHE));
  }
});
