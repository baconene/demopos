// BypassGrill POS — Service Worker
// Bump SW_VERSION when deploying a new build so old caches are cleared.
const SW_VERSION = 'v1'
const STATIC_CACHE  = `static-${SW_VERSION}`
const API_CACHE     = `api-menu-${SW_VERSION}`
const PAGE_CACHE    = `pages-${SW_VERSION}`
const ALL_CACHES    = [STATIC_CACHE, API_CACHE, PAGE_CACHE]

// ── Lifecycle ──────────────────────────────────────────────────────────────────
self.addEventListener('install', () => self.skipWaiting())

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => !ALL_CACHES.includes(k)).map(k => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    )
})

// ── Fetch routing ──────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event
    const url = new URL(request.url)

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) return

    // Skip non-GET POSTs etc. — those are handled by the app queue
    if (request.method !== 'GET') return

    // Menu-data API: StaleWhileRevalidate (offline = serve cache)
    if (isMenuApi(url)) {
        event.respondWith(staleWhileRevalidate(request, API_CACHE))
        return
    }

    // Full-page navigations + Inertia XHR navigations: NetworkFirst
    if (request.mode === 'navigate' || request.headers.get('X-Inertia')) {
        event.respondWith(networkFirst(request, PAGE_CACHE))
        return
    }

    // Static assets (JS, CSS, fonts, images): CacheFirst
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE))
        return
    }
})

// ── Background Sync ────────────────────────────────────────────────────────────
// When the browser reconnects (even if the tab is in the background), fire
// a message to all open page clients so they process their IndexedDB queue.
self.addEventListener('sync', event => {
    if (event.tag === 'pos-queue-sync') {
        event.waitUntil(notifyClientsToSync())
    }
})

async function notifyClientsToSync() {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
    clients.forEach(c => c.postMessage({ type: 'SYNC_QUEUE' }))
}

// ── Cache helpers ──────────────────────────────────────────────────────────────
async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName)
    const cached = await cache.match(request)
    const networkFetch = fetch(request).then(res => {
        if (res.ok) cache.put(request, res.clone())
        return res
    }).catch(() => null)
    return cached ?? await networkFetch ?? offline503()
}

async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName)
    try {
        const response = await fetch(request)
        if (response.ok || response.status === 304) cache.put(request, response.clone())
        return response
    } catch {
        return await cache.match(request) ?? offline503('Page not available offline. Please connect and refresh.')
    }
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName)
    const cached = await cache.match(request)
    if (cached) return cached
    try {
        const response = await fetch(request)
        if (response.ok) cache.put(request, response.clone())
        return response
    } catch {
        return offline503()
    }
}

function offline503(body = 'Offline') {
    return new Response(body, { status: 503, headers: { 'Content-Type': 'text/plain' } })
}

// ── URL classifiers ────────────────────────────────────────────────────────────
function isMenuApi(url) {
    return ['/api/v1/products', '/api/v1/categories', '/api/v1/payment-tenders', '/api/v1/modifiers']
        .some(p => url.pathname.startsWith(p))
}

function isStaticAsset(url) {
    return url.pathname.startsWith('/build/') ||
        /\.(js|css|woff2?|ttf|eot|png|jpg|jpeg|gif|svg|ico)$/.test(url.pathname)
}
