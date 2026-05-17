<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import api from '@/utils/api'
import { RefreshCw, Pencil, X, Plus, Minus, Search } from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Kitchen Monitor', href: '/kitchen' },
        ],
    },
})

interface OrderItem {
    id: number; quantity: number; unit_price: number
    special_instructions: string | null
    product: { id: number; name: string; price: number }
}
interface Order {
    id: number; queue_number: number | null; order_type: string
    status: string; payment_status: string
    table_number: string | null; notes: string | null
    total_amount: number; created_at: string; items: OrderItem[]
}
interface Product { id: number; name: string; price: number; category: string | null }

const props = defineProps<{ initialOrders: Order[]; products: Product[] }>()

const orders = ref<Order[]>([...props.initialOrders])
const updatingId = ref<number | null>(null)
let pollInterval: ReturnType<typeof setInterval> | null = null

// Edit modal state
const editOpen = ref(false)
const editOrder = ref<Order | null>(null)
const editItems = ref<{ product_id: number; name: string; price: number; quantity: number }[]>([])
const editNotes = ref('')
const editDiscount = ref(0)
const editSaving = ref(false)
const addSearch = ref('')

const pending   = computed(() => orders.value.filter((o) => o.status === 'pending'))
const preparing = computed(() => orders.value.filter((o) => o.status === 'preparing'))
const ready     = computed(() => orders.value.filter((o) => o.status === 'ready'))

const editSubtotal = computed(() =>
    editItems.value.reduce((s, i) => s + i.price * i.quantity, 0)
)
const editTotal = computed(() => Math.max(0, editSubtotal.value - editDiscount.value))

const filteredAddProducts = computed(() => {
    const q = addSearch.value.toLowerCase().trim()
    if (!q) return props.products.slice(0, 12)
    return props.products.filter((p) => p.name.toLowerCase().includes(q)).slice(0, 12)
})

const fetchOrders = async () => {
    try {
        const res = await api.get('/api/v1/orders/active')
        const fresh: Order[] = res.data.data ?? res.data
        orders.value = fresh.map((o: any) => ({
            ...o,
            total_amount: parseFloat(o.total_amount ?? 0),
        }))
    } catch {
        // silent
    }
}

onMounted(() => { pollInterval = setInterval(fetchOrders, 5000) })
onUnmounted(() => { if (pollInterval) clearInterval(pollInterval) })

const updateStatus = async (orderId: number, status: string) => {
    updatingId.value = orderId
    try {
        await api.patch(`/api/v1/orders/${orderId}/status`, { status })
        await fetchOrders()
        toast.success(`Order updated → ${status}`)
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to update order')
    } finally {
        updatingId.value = null
    }
}

const openEdit = (order: Order) => {
    editOrder.value = order
    editItems.value = order.items.map((i) => ({
        product_id: i.product.id,
        name: i.product.name,
        price: i.product.price,
        quantity: i.quantity,
    }))
    editNotes.value = order.notes ?? ''
    editDiscount.value = 0
    addSearch.value = ''
    editOpen.value = true
}

const changeQty = (idx: number, delta: number) => {
    const next = editItems.value[idx].quantity + delta
    if (next <= 0) editItems.value.splice(idx, 1)
    else editItems.value[idx].quantity = next
}

const addProduct = (product: Product) => {
    const existing = editItems.value.find((i) => i.product_id === product.id)
    if (existing) { existing.quantity++; return }
    editItems.value.push({ product_id: product.id, name: product.name, price: product.price, quantity: 1 })
}

const saveEdit = async () => {
    if (!editOrder.value || editItems.value.length === 0) return
    editSaving.value = true
    try {
        const res = await api.put(`/api/v1/orders/${editOrder.value.id}`, {
            notes: editNotes.value || null,
            discount_amount: editDiscount.value,
            items: editItems.value.map((i) => ({ product_id: i.product_id, quantity: i.quantity })),
        })
        const updated = res.data.data ?? res.data
        const idx = orders.value.findIndex((o) => o.id === editOrder.value!.id)
        if (idx !== -1) {
            orders.value[idx] = {
                ...orders.value[idx],
                ...updated,
                total_amount: parseFloat(updated.total_amount ?? 0),
                items: (updated.items ?? []).map((i: any) => ({
                    id: i.id, quantity: i.quantity, unit_price: parseFloat(i.unit_price ?? 0),
                    special_instructions: i.special_instructions ?? null,
                    product: { id: i.product?.id, name: i.product?.name, price: parseFloat(i.product?.price ?? 0) },
                })),
            }
        }
        toast.success('Order updated')
        editOpen.value = false
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to save changes')
    } finally {
        editSaving.value = false
    }
}

const ageMinutes = (dateStr: string) => Math.floor((Date.now() - new Date(dateStr).getTime()) / 60000)
const ageClass   = (dateStr: string) => {
    const m = ageMinutes(dateStr)
    if (m >= 15) return 'bg-red-100 text-red-700 dark:bg-red-950/30'
    if (m >= 8)  return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/30'
    return 'bg-gray-100 text-gray-600 dark:bg-gray-800'
}

const paymentBadge = (status: string) => {
    const map: Record<string, { label: string; cls: string }> = {
        paid:     { label: 'Paid',    cls: 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' },
        pending:  { label: 'Unpaid',  cls: 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400' },
        refunded: { label: 'Refunded', cls: 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400' },
        voided:   { label: 'Voided',  cls: 'bg-gray-100 text-gray-500 dark:bg-gray-800' },
    }
    return map[status] ?? { label: status, cls: 'bg-gray-100 text-gray-500' }
}

const formatPrice = (v: number) => '₱' + v.toFixed(2)
</script>

<template>
    <Head title="Kitchen Monitor" />

    <div class="space-y-6">
        <!-- Header stats -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border bg-yellow-50 dark:bg-yellow-950/20 p-4 text-center">
                <p class="text-xs font-medium text-yellow-700 dark:text-yellow-400 uppercase tracking-wide">Pending</p>
                <p class="text-4xl font-black text-yellow-600 mt-1">{{ pending.length }}</p>
            </div>
            <div class="rounded-xl border bg-blue-50 dark:bg-blue-950/20 p-4 text-center">
                <p class="text-xs font-medium text-blue-700 dark:text-blue-400 uppercase tracking-wide">Preparing</p>
                <p class="text-4xl font-black text-blue-600 mt-1">{{ preparing.length }}</p>
            </div>
            <div class="rounded-xl border bg-green-50 dark:bg-green-950/20 p-4 text-center">
                <p class="text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wide">Ready</p>
                <p class="text-4xl font-black text-green-600 mt-1">{{ ready.length }}</p>
            </div>
        </div>

        <!-- Queue Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Pending -->
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-yellow-600 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-yellow-400" /> Pending
                </h2>
                <div class="space-y-3">
                    <div v-if="pending.length === 0" class="rounded-xl border-2 border-dashed p-6 text-center text-sm text-muted-foreground">
                        No pending orders
                    </div>
                    <div v-for="order in pending" :key="order.id"
                        class="rounded-xl border-l-4 border-yellow-500 bg-card shadow-sm overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-1">
                                <span class="text-2xl font-black">
                                    {{ order.queue_number ? '#' + order.queue_number : 'Order #' + order.id }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span :class="['text-xs rounded-full px-2 py-0.5 font-medium', ageClass(order.created_at)]">
                                        {{ ageMinutes(order.created_at) }}m ago
                                    </span>
                                    <button @click="openEdit(order)" class="rounded-full p-1 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors" title="Edit order">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                            <!-- Payment status -->
                            <div class="flex items-center gap-2 mb-2">
                                <span :class="['text-xs rounded-full px-2 py-0.5 font-semibold', paymentBadge(order.payment_status).cls]">
                                    {{ paymentBadge(order.payment_status).label }}
                                </span>
                                <span class="text-xs text-muted-foreground capitalize">
                                    {{ order.order_type.replace('_', ' ') }}
                                    <span v-if="order.table_number"> · {{ order.table_number }}</span>
                                </span>
                            </div>
                            <ul class="text-sm space-y-0.5 mb-3">
                                <li v-for="item in order.items" :key="item.id">
                                    <span class="font-bold">{{ item.quantity }}×</span> {{ item.product.name }}
                                    <span v-if="item.special_instructions" class="text-xs text-muted-foreground italic"> ({{ item.special_instructions }})</span>
                                </li>
                            </ul>
                            <div class="flex justify-between items-center mb-3">
                                <p v-if="order.notes" class="text-xs text-muted-foreground italic">{{ order.notes }}</p>
                                <span class="ml-auto text-sm font-bold text-primary">{{ formatPrice(order.total_amount) }}</span>
                            </div>
                            <button @click="updateStatus(order.id, 'preparing')" :disabled="updatingId === order.id"
                                class="w-full rounded-lg bg-blue-600 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                                <RefreshCw v-if="updatingId === order.id" class="inline h-3 w-3 animate-spin mr-1" />
                                Start Preparing
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preparing -->
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-blue-600 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-blue-400" /> Preparing
                </h2>
                <div class="space-y-3">
                    <div v-if="preparing.length === 0" class="rounded-xl border-2 border-dashed p-6 text-center text-sm text-muted-foreground">
                        Nothing being prepared
                    </div>
                    <div v-for="order in preparing" :key="order.id"
                        class="rounded-xl border-l-4 border-blue-500 bg-card shadow-sm overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-1">
                                <span class="text-2xl font-black">
                                    {{ order.queue_number ? '#' + order.queue_number : 'Order #' + order.id }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span :class="['text-xs rounded-full px-2 py-0.5 font-medium', ageClass(order.created_at)]">
                                        {{ ageMinutes(order.created_at) }}m ago
                                    </span>
                                    <button @click="openEdit(order)" class="rounded-full p-1 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors" title="Edit order">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                <span :class="['text-xs rounded-full px-2 py-0.5 font-semibold', paymentBadge(order.payment_status).cls]">
                                    {{ paymentBadge(order.payment_status).label }}
                                </span>
                                <span class="text-xs text-muted-foreground capitalize">
                                    {{ order.order_type.replace('_', ' ') }}
                                    <span v-if="order.table_number"> · {{ order.table_number }}</span>
                                </span>
                            </div>
                            <ul class="text-sm space-y-0.5 mb-3">
                                <li v-for="item in order.items" :key="item.id">
                                    <span class="font-bold">{{ item.quantity }}×</span> {{ item.product.name }}
                                    <span v-if="item.special_instructions" class="text-xs text-muted-foreground italic"> ({{ item.special_instructions }})</span>
                                </li>
                            </ul>
                            <div class="flex justify-between items-center mb-3">
                                <p v-if="order.notes" class="text-xs text-muted-foreground italic">{{ order.notes }}</p>
                                <span class="ml-auto text-sm font-bold text-primary">{{ formatPrice(order.total_amount) }}</span>
                            </div>
                            <button @click="updateStatus(order.id, 'ready')" :disabled="updatingId === order.id"
                                class="w-full rounded-lg bg-green-600 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-50">
                                <RefreshCw v-if="updatingId === order.id" class="inline h-3 w-3 animate-spin mr-1" />
                                Mark Ready
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ready -->
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-green-600 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-green-400" /> Ready for Pickup
                </h2>
                <div class="space-y-3">
                    <div v-if="ready.length === 0" class="rounded-xl border-2 border-dashed p-6 text-center text-sm text-muted-foreground">
                        No orders ready
                    </div>
                    <div v-for="order in ready" :key="order.id"
                        class="rounded-xl border-l-4 border-green-500 bg-card shadow-sm overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-1">
                                <span class="text-2xl font-black">
                                    {{ order.queue_number ? '#' + order.queue_number : 'Order #' + order.id }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span :class="['text-xs rounded-full px-2 py-0.5 font-medium', ageClass(order.created_at)]">
                                        {{ ageMinutes(order.created_at) }}m ago
                                    </span>
                                    <button @click="openEdit(order)" class="rounded-full p-1 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors" title="Edit order">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                <span :class="['text-xs rounded-full px-2 py-0.5 font-semibold', paymentBadge(order.payment_status).cls]">
                                    {{ paymentBadge(order.payment_status).label }}
                                </span>
                                <span class="text-xs text-muted-foreground capitalize">
                                    {{ order.order_type.replace('_', ' ') }}
                                    <span v-if="order.table_number"> · {{ order.table_number }}</span>
                                </span>
                            </div>
                            <ul class="text-sm space-y-0.5 mb-3">
                                <li v-for="item in order.items" :key="item.id">
                                    <span class="font-bold">{{ item.quantity }}×</span> {{ item.product.name }}
                                    <span v-if="item.special_instructions" class="text-xs text-muted-foreground italic"> ({{ item.special_instructions }})</span>
                                </li>
                            </ul>
                            <div class="flex justify-between items-center mb-3">
                                <p v-if="order.notes" class="text-xs text-muted-foreground italic">{{ order.notes }}</p>
                                <span class="ml-auto text-sm font-bold text-primary">{{ formatPrice(order.total_amount) }}</span>
                            </div>
                            <button @click="updateStatus(order.id, 'completed')" :disabled="updatingId === order.id"
                                class="w-full rounded-lg bg-gray-700 py-2 text-sm font-bold text-white hover:bg-gray-800 disabled:opacity-50">
                                <RefreshCw v-if="updatingId === order.id" class="inline h-3 w-3 animate-spin mr-1" />
                                Mark Completed
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Edit Order Modal -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="editOpen && editOrder"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                @click.self="editOpen = false">
                <div class="w-full max-w-lg rounded-2xl bg-background shadow-2xl flex flex-col max-h-[90vh]">

                    <!-- Modal header -->
                    <div class="p-5 border-b flex items-center justify-between shrink-0">
                        <div>
                            <h3 class="font-bold text-lg">
                                Edit Order {{ editOrder.queue_number ? '#' + editOrder.queue_number : '#' + editOrder.id }}
                            </h3>
                            <p class="text-xs text-muted-foreground capitalize mt-0.5">
                                {{ editOrder.order_type.replace('_', ' ') }}
                                <span v-if="editOrder.table_number"> · {{ editOrder.table_number }}</span>
                                <span :class="['ml-2 rounded-full px-2 py-0.5 font-semibold inline-block', paymentBadge(editOrder.payment_status).cls]">
                                    {{ paymentBadge(editOrder.payment_status).label }}
                                </span>
                            </p>
                        </div>
                        <button @click="editOpen = false" class="rounded-full p-1 hover:bg-muted">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="overflow-y-auto flex-1 p-5 space-y-5">

                        <!-- Current items -->
                        <div>
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">Order Items</p>
                            <div v-if="editItems.length === 0" class="text-sm text-muted-foreground py-3 text-center border-2 border-dashed rounded-lg">
                                No items — add from the product list below
                            </div>
                            <div class="space-y-2">
                                <div v-for="(item, idx) in editItems" :key="item.product_id"
                                    class="flex items-center gap-3 rounded-lg border bg-muted/30 px-3 py-2">
                                    <span class="flex-1 text-sm font-medium truncate">{{ item.name }}</span>
                                    <span class="text-xs text-muted-foreground shrink-0">{{ formatPrice(item.price) }}</span>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button @click="changeQty(idx, -1)" class="rounded bg-muted p-0.5 hover:bg-muted/80">
                                            <Minus class="h-3 w-3" />
                                        </button>
                                        <span class="w-6 text-center text-sm font-bold">{{ item.quantity }}</span>
                                        <button @click="changeQty(idx, 1)" class="rounded bg-muted p-0.5 hover:bg-muted/80">
                                            <Plus class="h-3 w-3" />
                                        </button>
                                    </div>
                                    <button @click="editItems.splice(idx, 1)" class="rounded p-0.5 text-destructive hover:bg-destructive/10">
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Add products -->
                        <div>
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">Add Items</p>
                            <div class="relative mb-2">
                                <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                                <input v-model="addSearch" type="text" placeholder="Search products…"
                                    class="w-full rounded-lg border bg-background pl-8 pr-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                            </div>
                            <div class="grid grid-cols-2 gap-1.5 max-h-40 overflow-y-auto">
                                <button v-for="p in filteredAddProducts" :key="p.id"
                                    @click="addProduct(p)"
                                    class="flex items-center justify-between rounded-lg border bg-background px-3 py-2 text-left hover:border-primary hover:bg-primary/5 transition text-xs">
                                    <span class="font-medium truncate">{{ p.name }}</span>
                                    <span class="shrink-0 text-primary font-bold ml-1">{{ formatPrice(p.price) }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Notes & discount -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-muted-foreground block mb-1">Notes</label>
                                <input v-model="editNotes" type="text" placeholder="Special instructions…"
                                    class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-muted-foreground block mb-1">Discount (₱)</label>
                                <input v-model.number="editDiscount" type="number" min="0" step="0.01"
                                    class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                            </div>
                        </div>

                        <!-- Order total -->
                        <div class="rounded-lg bg-muted/40 px-4 py-3 space-y-1 text-sm">
                            <div class="flex justify-between text-muted-foreground">
                                <span>Subtotal</span><span>{{ formatPrice(editSubtotal) }}</span>
                            </div>
                            <div v-if="editDiscount > 0" class="flex justify-between text-red-600">
                                <span>Discount</span><span>-{{ formatPrice(editDiscount) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-base border-t pt-1 mt-1">
                                <span>Total</span>
                                <span class="text-primary">{{ formatPrice(editTotal) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="p-5 border-t flex gap-3 shrink-0">
                        <button @click="editOpen = false"
                            class="flex-1 rounded-lg border py-2 text-sm font-medium hover:bg-muted">
                            Cancel
                        </button>
                        <button @click="saveEdit" :disabled="editSaving || editItems.length === 0"
                            class="flex-1 rounded-lg bg-primary py-2 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            {{ editSaving ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
