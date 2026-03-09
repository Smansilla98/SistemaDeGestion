const CACHE_NAME = 'restaurante-cache-v1';
const ASSETS_CACHE = [
  '/',
  '/dashboard',
  '/tables',
  '/orders',
  '/m/dashboard',
  '/m/pedidos',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_CACHE);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      )
    )
  );
  self.clients.claim();
});

// Simple heuristic helpers
function isHtmlRequest(request) {
  return request.mode === 'navigate' ||
    (request.headers.get('accept') || '').includes('text/html');
}

function isAssetRequest(request) {
  const url = new URL(request.url);
  return (
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.jpeg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.startsWith('/build/')
  );
}

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Network-first para HTML (páginas)
  if (isHtmlRequest(request)) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() => caches.match(request).then((res) => res || caches.match('/')))
    );
    return;
  }

  // Cache-first para assets estáticos
  if (isAssetRequest(request)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) {
          // Actualizar en background
          fetch(request)
            .then((response) => {
              caches.open(CACHE_NAME).then((cache) => cache.put(request, response));
            })
            .catch(() => {});
          return cached;
        }

        // No está en caché, ir a red y cachear
        return fetch(request)
          .then((response) => {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
            return response;
          })
          .catch(() => new Response('', { status: 503, statusText: 'Offline' }));
      })
    );
  }
});

