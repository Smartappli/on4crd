const VERSION = 'v354-secure';
const STATIC_CACHE = `on4crd-static-${VERSION}`;
const PAGE_CACHE = `on4crd-pages-${VERSION}`;
const DATA_CACHE = `on4crd-data-${VERSION}`;

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
      .then((cache) => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => ![STATIC_CACHE, PAGE_CACHE, DATA_CACHE].includes(key)).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  const networkPromise = fetch(request, { credentials: 'same-origin' })
    .then((response) => {
      if (response && response.status === 200) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(() => cached);
  return cached || networkPromise;
}

async function networkFirst(request, cacheName, fallbackUrl = './offline.html') {
  const cache = await caches.open(cacheName);
  try {
    const response = await fetch(request, { credentials: 'same-origin' });
    if (response && response.status === 200) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    return (await cache.match(request)) || (await caches.match(fallbackUrl));
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
    event.respondWith(fetch(request, { credentials: 'same-origin' }));
    return;
  }

  if (url.origin !== location.origin) {
    event.respondWith(staleWhileRevalidate(request, DATA_CACHE));
  }
});
