/**
 * Service Worker - Admin Pages Cache Strategy
 * Caches static assets for instant page loads (offline support too)
 * 
 * Register in your page with:
 * <script>
 * if ('serviceWorker' in navigator) {
 *   navigator.serviceWorker.register('/teacher-eval/assets/js/admin-service-worker.js');
 * }
 * </script>
 */

const CACHE_NAME = 'teacher-eval-admin-v1';
const ASSETS_TO_CACHE = [
    // CSS Files
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
    'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    
    // JS Files
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js'
];

// Install event - cache critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('Service Worker: Caching assets');
            // Don't fail on individual cache failures - cache what we can
            return Promise.allSettled(
                ASSETS_TO_CACHE.map(asset =>
                    cache.add(asset).catch(err =>
                        console.warn(`Failed to cache ${asset}:`, err)
                    )
                )
            );
        }).then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        console.log('Deleting old cache:', name);
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fall back to network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Only cache GET requests to external CDNs or our assets
    if (request.method !== 'GET') {
        return;
    }
    
    // Don't cache API calls - always fetch fresh
    if (url.pathname.includes('/api/')) {
        return;
    }
    
    // Cache-first strategy for static assets (CSS, JS, fonts)
    if (url.pathname.match(/\.(css|js|woff2|woff|ttf)$/i) ||
        url.hostname.includes('cdn.jsdelivr.net') ||
        url.hostname.includes('fonts.googleapis.com')) {
        
        event.respondWith(
            caches.match(request).then((response) => {
                if (response) {
                    // Serve from cache
                    return response;
                }
                
                // Not in cache, fetch from network
                return fetch(request).then((response) => {
                    // Cache successful responses for future use
                    if (response.ok) {
                        const cache = caches.open(CACHE_NAME);
                        cache.then((c) => c.put(request, response.clone()));
                    }
                    return response;
                }).catch(() => {
                    // Network failed, return offline error page
                    return new Response(
                        'Network error - please check your connection',
                        { status: 503, statusText: 'Service Unavailable' }
                    );
                });
            })
        );
    }
});
