<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import api from '@/utils/api'
import { RefreshCw } from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Kitchen Monitor', href: '/kitchen' },
        ],
    },
})

interface OrderItem { id: number; quantity: number; special_instructions: string | null; product: { id: number; name: string } }
interface Order {
    id: number; queue_number: number | null; order_type: string; status: string
    table_number: string | null; notes: string | null; created_at: string; items: OrderItem[]
}

const props = defineProps<{ initialOrders: Order[] }>()

const orders = ref<Order[]>([...props.initialOrders])
const updatingId = ref<number | null>(null)
let pollInterval: ReturnType<typeof setInterval> | null = null

const pending = computed(() => orders.value.filter((o) => o.status === 'pending'))
const preparing = computed(() => orders.value.filter((o) => o.status === 'preparing'))
const ready = computed(() => orders.value.filter((o) => o.status === 'ready'))

const fetchOrders = async () => {
    try {
        const res = await api.get('/api/v1/orders/active')
        orders.value = res.data.data ?? res.data
    } catch {
        // Silent refresh failure is acceptable
    }
}

onMounted(() => {
    pollInterval = setInterval(fetchOrders, 5000)
})

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval)
})

const updateStatus = async (orderId: number, status: string) => {
    updatingId.value = orderId
    try {
        await api.patch(`/api/v1/orders/${orderId}/status`, { status })
        await fetchOrders()
        toast.success(`Order #${orderId} → ${status}`)
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to update order')
    } finally {
        updatingId.value = null
    }
}

const ageMinutes = (dateStr: string) => {
    const diff = Date.now() - new Date(dateStr).getTime()
    return Math.floor(diff / 60000)
}

const ageClass = (dateStr: string) => {
    const m = ageMinutes(dateStr)
    if (m >= 15) return 'bg-red-100 text-red-700 dark:bg-red-950/30'
    if (m >= 8) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/30'
    return 'bg-gray-100 text-gray-600 dark:bg-gray-800'
}
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
                    <div
                        v-for="order in pending"
                        :key="order.id"
                        class="rounded-xl border-l-4 border-yellow-500 bg-card shadow-sm overflow-hidden"
                    >
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-2xl font-black">
                                    {{ order.queue_number ? '#' + order.queue_number : 'Order #' + order.id }}
                                </span>
                                <span :class="['text-xs rounded-full px-2 py-0.5 font-medium', ageClass(order.created_at)]">
                                    {{ ageMinutes(order.created_at) }}m ago
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground capitalize mb-2">
                                {{ order.order_type.replace('_', ' ') }}
                                <span v-if="order.table_number"> · {{ order.table_number }}</span>
                            </p>
                            <ul class="text-sm space-y-0.5 mb-3">
                                <li v-for="item in order.items" :key="item.id">
                                    <span class="font-bold">{{ item.quantity }}×</span> {{ item.product.name }}
                                    <span v-if="item.special_instructions" class="text-xs text-muted-foreground italic"> ({{ item.special_instructions }})</span>
                                </li>
                            </ul>
                            <p v-if="order.notes" class="text-xs text-muted-foreground italic mb-3">Note: {{ order.notes }}</p>
                            <button
                                @click="updateStatus(order.id, 'preparing')"
                                :disabled="updatingId === order.id"
                                class="w-full rounded-lg bg-blue-600 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50"
                            >
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
                    <div
                        v-for="order in preparing"
                        :key="order.id"
                        class="rounded-xl border-l-4 border-blue-500 bg-card shadow-sm overflow-hidden"
                    >
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-2xl font-black">
                                    {{ order.queue_number ? '#' + order.queue_number : 'Order #' + order.id }}
                                </span>
                                <span :class="['text-xs rounded-full px-2 py-0.5 font-medium', ageClass(order.created_at)]">
                                    {{ ageMinutes(order.created_at) }}m ago
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground capitalize mb-2">
                                {{ order.order_type.replace('_', ' ') }}
                                <span v-if="order.table_number"> · {{ order.table_number }}</span>
                            </p>
                            <ul class="text-sm space-y-0.5 mb-3">
                                <li v-for="item in order.items" :key="item.id">
                                    <span class="font-bold">{{ item.quantity }}×</span> {{ item.product.name }}
                                </li>
                            </ul>
                            <button
                                @click="updateStatus(order.id, 'ready')"
                                :disabled="updatingId === order.id"
                                class="w-full rounded-lg bg-green-600 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-50"
                            >
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
                    <div
                        v-for="order in ready"
                        :key="order.id"
                        class="rounded-xl border-l-4 border-green-500 bg-card shadow-sm overflow-hidden"
                    >
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-2xl font-black">
                                    {{ order.queue_number ? '#' + order.queue_number : 'Order #' + order.id }}
                                </span>
                                <span :class="['text-xs rounded-full px-2 py-0.5 font-medium', ageClass(order.created_at)]">
                                    {{ ageMinutes(order.created_at) }}m ago
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground capitalize mb-2">
                                {{ order.order_type.replace('_', ' ') }}
                                <span v-if="order.table_number"> · {{ order.table_number }}</span>
                            </p>
                            <ul class="text-sm space-y-0.5 mb-3">
                                <li v-for="item in order.items" :key="item.id">
                                    <span class="font-bold">{{ item.quantity }}×</span> {{ item.product.name }}
                                </li>
                            </ul>
                            <button
                                @click="updateStatus(order.id, 'completed')"
                                :disabled="updatingId === order.id"
                                class="w-full rounded-lg bg-gray-700 py-2 text-sm font-bold text-white hover:bg-gray-800 disabled:opacity-50"
                            >
                                <RefreshCw v-if="updatingId === order.id" class="inline h-3 w-3 animate-spin mr-1" />
                                Mark Completed
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
