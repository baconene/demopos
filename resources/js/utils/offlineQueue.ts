import { openDB, type IDBPDatabase } from 'idb'

// ── Schema ─────────────────────────────────────────────────────────────────────
export interface PendingOrder {
    localId: string
    offlineQueueNumber: string
    payload: Record<string, unknown>
    status: 'pending' | 'synced' | 'failed'
    serverOrderId?: number
    createdAt: string
    error?: string
}

export interface PendingPayment {
    localId: string
    orderLocalId: string
    serverOrderId?: number
    payload: Record<string, unknown>
    status: 'pending' | 'synced' | 'failed'
    createdAt: string
    error?: string
}

// ── DB init ────────────────────────────────────────────────────────────────────
let _db: IDBPDatabase | null = null

async function getDB(): Promise<IDBPDatabase> {
    if (_db) return _db
    _db = await openDB('bypassgrill-pos', 1, {
        upgrade(db) {
            if (!db.objectStoreNames.contains('pending_orders')) {
                const s = db.createObjectStore('pending_orders', { keyPath: 'localId' })
                s.createIndex('by_status', 'status')
            }
            if (!db.objectStoreNames.contains('pending_payments')) {
                const s = db.createObjectStore('pending_payments', { keyPath: 'localId' })
                s.createIndex('by_status', 'status')
                s.createIndex('by_order_local_id', 'orderLocalId')
            }
        },
    })
    return _db
}

// ── Offline queue number ───────────────────────────────────────────────────────
let _counter = parseInt(localStorage.getItem('bypassgrill_offline_seq') ?? '0')

function nextOfflineQueueNumber(): string {
    _counter++
    localStorage.setItem('bypassgrill_offline_seq', String(_counter))
    return `OFF-${String(_counter).padStart(3, '0')}`
}

// ── Public API ─────────────────────────────────────────────────────────────────
export async function queueOrder(payload: Record<string, unknown>): Promise<PendingOrder> {
    const db = await getDB()
    const entry: PendingOrder = {
        localId: crypto.randomUUID(),
        offlineQueueNumber: nextOfflineQueueNumber(),
        payload,
        status: 'pending',
        createdAt: new Date().toISOString(),
    }
    await db.put('pending_orders', entry)
    requestBackgroundSync()
    return entry
}

export async function queuePayment(
    orderLocalId: string,
    payload: Record<string, unknown>,
): Promise<PendingPayment> {
    const db = await getDB()
    const entry: PendingPayment = {
        localId: crypto.randomUUID(),
        orderLocalId,
        payload,
        status: 'pending',
        createdAt: new Date().toISOString(),
    }
    await db.put('pending_payments', entry)
    return entry
}

export async function getPendingCounts(): Promise<{ orders: number; payments: number }> {
    const db = await getDB()
    const [orders, payments] = await Promise.all([
        db.getAllFromIndex('pending_orders', 'by_status', 'pending'),
        db.getAllFromIndex('pending_payments', 'by_status', 'pending'),
    ])
    return { orders: orders.length, payments: payments.length }
}

export async function syncQueue(): Promise<{ synced: number; failed: number }> {
    const db = await getDB()
    let synced = 0
    let failed = 0
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
    const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken

    // 1. Sync pending orders first
    const pendingOrders = await db.getAllFromIndex('pending_orders', 'by_status', 'pending')
    for (const order of pendingOrders) {
        try {
            const res = await fetch('/api/v1/orders', {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify(order.payload),
            })
            if (!res.ok) {
                const body = await res.json().catch(() => ({}))
                throw new Error((body as any).message ?? `HTTP ${res.status}`)
            }
            const data = await res.json()
            const serverOrderId: number = (data.data ?? data).id
            await db.put('pending_orders', { ...order, status: 'synced', serverOrderId })

            // Update any queued payments for this local order with the real server ID
            const linked = await db.getAllFromIndex('pending_payments', 'by_order_local_id', order.localId)
            for (const p of linked) {
                await db.put('pending_payments', {
                    ...p,
                    serverOrderId,
                    payload: { ...p.payload, order_id: serverOrderId },
                })
            }
            synced++
        } catch (err) {
            await db.put('pending_orders', { ...order, status: 'failed', error: String(err) })
            failed++
        }
    }

    // 2. Sync pending payments that now have a resolved server order ID
    const pendingPayments = await db.getAllFromIndex('pending_payments', 'by_status', 'pending')
    for (const payment of pendingPayments) {
        if (!payment.serverOrderId) continue
        try {
            const res = await fetch('/api/v1/payments', {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify(payment.payload),
            })
            if (!res.ok) {
                const body = await res.json().catch(() => ({}))
                throw new Error((body as any).message ?? `HTTP ${res.status}`)
            }
            await db.put('pending_payments', { ...payment, status: 'synced' })
            synced++
        } catch (err) {
            await db.put('pending_payments', { ...payment, status: 'failed', error: String(err) })
            failed++
        }
    }

    return { synced, failed }
}

function requestBackgroundSync() {
    navigator.serviceWorker?.ready
        .then(reg => (reg as any).sync?.register('pos-queue-sync'))
        .catch(() => {})
}
