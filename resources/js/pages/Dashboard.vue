<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ShoppingCart, ChefHat, Package, BarChart3, ClipboardList, TrendingUp, AlertTriangle, CheckCircle } from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
    },
})

const props = defineProps<{
    stats: Record<string, number>
    recentOrders: any[]
}>()

const page = usePage()
const user = computed(() => page.props.auth?.user)
const roles = computed<string[]>(() => page.props.auth?.roles ?? [])

const hasRole = (role: string) => roles.value.includes(role)

const statusColor: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    preparing: 'bg-blue-100 text-blue-800',
    ready: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-700',
}

const formatCurrency = (val: number) =>
    '₱' + (val ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const greeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
})
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 p-4">
        <!-- Greeting -->
        <div>
            <h1 class="text-2xl font-bold text-foreground">
                {{ greeting }}, {{ user?.name ?? 'User' }}
            </h1>
            <p class="text-muted-foreground text-sm capitalize">
                {{ roles.join(', ') || 'No role assigned' }}
            </p>
        </div>

        <!-- Quick Nav Cards -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Link
                v-if="hasRole('cashier') || hasRole('admin')"
                href="/pos"
                class="flex flex-col items-center gap-2 rounded-xl border bg-card p-4 text-center shadow-sm transition hover:shadow-md hover:border-primary"
            >
                <ShoppingCart class="h-8 w-8 text-primary" />
                <span class="text-sm font-semibold">Point of Sale</span>
            </Link>
            <Link
                v-if="hasRole('kitchen') || hasRole('admin')"
                href="/kitchen"
                class="flex flex-col items-center gap-2 rounded-xl border bg-card p-4 text-center shadow-sm transition hover:shadow-md hover:border-primary"
            >
                <ChefHat class="h-8 w-8 text-orange-500" />
                <span class="text-sm font-semibold">Kitchen Monitor</span>
            </Link>
            <Link
                v-if="hasRole('auditor') || hasRole('admin')"
                href="/inventory"
                class="flex flex-col items-center gap-2 rounded-xl border bg-card p-4 text-center shadow-sm transition hover:shadow-md hover:border-primary"
            >
                <Package class="h-8 w-8 text-green-600" />
                <span class="text-sm font-semibold">Inventory</span>
            </Link>
            <Link
                v-if="hasRole('auditor') || hasRole('admin')"
                href="/reports"
                class="flex flex-col items-center gap-2 rounded-xl border bg-card p-4 text-center shadow-sm transition hover:shadow-md hover:border-primary"
            >
                <BarChart3 class="h-8 w-8 text-purple-600" />
                <span class="text-sm font-semibold">Reports</span>
            </Link>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <!-- Cashier / Admin stats -->
            <template v-if="hasRole('cashier') || hasRole('admin')">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-muted-foreground">Today's Orders</p>
                        <ClipboardList class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="text-3xl font-bold">{{ stats.today_orders ?? 0 }}</p>
                </div>
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-muted-foreground">Today's Revenue</p>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </div>
                    <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.today_revenue ?? 0) }}</p>
                </div>
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-muted-foreground">Active Orders</p>
                        <ShoppingCart class="h-4 w-4 text-blue-500" />
                    </div>
                    <p class="text-3xl font-bold text-blue-600">{{ stats.active_orders ?? 0 }}</p>
                </div>
            </template>

            <!-- Kitchen stats -->
            <template v-if="hasRole('kitchen') || hasRole('admin')">
                <div class="rounded-xl border bg-yellow-50 dark:bg-yellow-950/20 p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground mb-2">Pending</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ stats.pending_orders ?? 0 }}</p>
                </div>
                <div class="rounded-xl border bg-blue-50 dark:bg-blue-950/20 p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground mb-2">Preparing</p>
                    <p class="text-3xl font-bold text-blue-600">{{ stats.preparing_orders ?? 0 }}</p>
                </div>
                <div class="rounded-xl border bg-green-50 dark:bg-green-950/20 p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground mb-2">Ready</p>
                    <p class="text-3xl font-bold text-green-600">{{ stats.ready_orders ?? 0 }}</p>
                </div>
            </template>

            <!-- Auditor stats -->
            <template v-if="hasRole('auditor') || hasRole('admin')">
                <div
                    class="rounded-xl border p-5 shadow-sm"
                    :class="(stats.low_stock_count ?? 0) > 0 ? 'bg-red-50 dark:bg-red-950/20 border-red-200' : 'bg-card'"
                >
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-muted-foreground">Low Stock Items</p>
                        <AlertTriangle
                            class="h-4 w-4"
                            :class="(stats.low_stock_count ?? 0) > 0 ? 'text-red-500' : 'text-muted-foreground'"
                        />
                    </div>
                    <p
                        class="text-3xl font-bold"
                        :class="(stats.low_stock_count ?? 0) > 0 ? 'text-red-600' : 'text-foreground'"
                    >
                        {{ stats.low_stock_count ?? 0 }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-1">of {{ stats.total_ingredients ?? 0 }} ingredients</p>
                </div>
            </template>
        </div>

        <!-- Recent Orders -->
        <div v-if="recentOrders.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="p-4 border-b">
                <h2 class="font-semibold text-base">Recent Orders</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-muted-foreground">Queue #</th>
                            <th class="px-4 py-2 text-left font-medium text-muted-foreground">Type</th>
                            <th class="px-4 py-2 text-left font-medium text-muted-foreground">Items</th>
                            <th class="px-4 py-2 text-left font-medium text-muted-foreground">Total</th>
                            <th class="px-4 py-2 text-left font-medium text-muted-foreground">Status</th>
                            <th class="px-4 py-2 text-left font-medium text-muted-foreground">Payment</th>
                            <th class="px-4 py-2 text-left font-medium text-muted-foreground">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-muted/30">
                            <td class="px-4 py-2 font-bold">
                                {{ order.queue_number ? '#' + order.queue_number : '—' }}
                            </td>
                            <td class="px-4 py-2 capitalize">{{ order.order_type?.replace('_', ' ') }}</td>
                            <td class="px-4 py-2">{{ order.items_count }}</td>
                            <td class="px-4 py-2 font-semibold">{{ formatCurrency(order.total_amount) }}</td>
                            <td class="px-4 py-2">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    :class="statusColor[order.status] ?? 'bg-gray-100 text-gray-700'"
                                >
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 capitalize">{{ order.payment_status }}</td>
                            <td class="px-4 py-2 text-muted-foreground">
                                {{ order.created_at ? new Date(order.created_at).toLocaleTimeString() : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty state for new accounts -->
        <div v-else class="rounded-xl border bg-card p-10 text-center shadow-sm">
            <CheckCircle class="h-10 w-10 text-green-500 mx-auto mb-3" />
            <p class="font-semibold">No orders yet today.</p>
            <p class="text-sm text-muted-foreground mt-1">Orders will appear here once created.</p>
        </div>
    </div>
</template>
