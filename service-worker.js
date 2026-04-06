const CACHE_NAME = 'capacitrek-cache-v1';
const urlsToCache = [
  '/capacitrack/frontend/index.html',
  '/capacitrack/frontend/assets/logo.png',
  '/capacitrack/frontend/assets/grupo-gusi.png',
  '/capacitrack/frontend/assets/esr.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .then(() => console.log('Archivos cacheados correctamente'))
      .catch(err => console.error('Error al cachear:', err))
  );
});

self.addEventListener('activate', event => {
  console.log('Service Worker activado');
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});