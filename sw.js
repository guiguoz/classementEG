// Copie-colle dans sw.js
const CACHE_NAME = 'escapegame-v1';
self.addEventListener('install', e => e.waitUntil(
  caches.open(CACHE_NAME).then(cache => cache.addAll([
    './shared/style.css','./shared/common.js',
    './S1-accueil.html','./E1-test.html','./E2-test.html',
    './E3-test.html','./E4-test.html','./F1-final.html'
  ]))
));
self.addEventListener('fetch', e => e.respondWith(
  caches.match(e.request).then(r => r || fetch(e.request))
));
