const CACHE_NAME = 'doctorchat-v1';
const STATIC_ASSETS = [
    '/',
    '/guide',
];

// Install - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip API calls and auth routes
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/api') ||
        url.pathname.startsWith('/login') ||
        url.pathname.startsWith('/register') ||
        url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/doctor') ||
        url.pathname.includes('livewire')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((response) => {
            // Return cached version or fetch from network
            return response || fetch(event.request).then((fetchResponse) => {
                // Don't cache non-successful responses
                if (!fetchResponse || fetchResponse.status !== 200) {
                    return fetchResponse;
                }

                // Clone the response
                const responseToCache = fetchResponse.clone();

                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseToCache);
                });

                return fetchResponse;
            });
        }).catch(() => {
            // Return offline page for navigation requests
            if (event.request.mode === 'navigate') {
                return caches.match('/');
            }
        })
    );
});
