// Service Worker — Walisantri PWA
// Strategy: Network First, fallback to cache

const CACHE_NAME = 'walisantri-v1';

// Resources to pre-cache on install
const PRECACHE_URLS = [
    '/wali/dashboard',
    '/wali/pengumuman',
];

// ── Install ──────────────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            // Pre-cache shell pages; ignore failures (requires auth, might redirect)
            return Promise.allSettled(
                PRECACHE_URLS.map((url) => cache.add(url).catch(() => {}))
            );
        })
    );
});

// ── Activate: clean stale caches ─────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) =>
            Promise.all(
                names
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch: Network First, fallback cache ──────────────────────────────────────
self.addEventListener('fetch', (event) => {
    // Only intercept GET requests
    if (event.request.method !== 'GET') return;

    // Skip cross-origin requests
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    // Skip admin panel and Filament asset requests
    if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/filament')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses for wali pages and static assets
                if (
                    response.ok &&
                    (url.pathname.startsWith('/wali') ||
                     url.pathname.startsWith('/build') ||
                     url.pathname.match(/\.(css|js|png|jpg|svg|ico|woff2?)$/))
                ) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                // Network failed — try cache
                return caches.match(event.request).then(
                    (cached) => cached ?? new Response('Tidak ada koneksi internet.', {
                        status: 503,
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                    })
                );
            })
    );
});
