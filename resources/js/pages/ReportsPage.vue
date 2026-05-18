<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import api from '@/utils/api'
import {
    BarChart3, Download, RefreshCw, TrendingUp, TrendingDown,
    DollarSign, Plus, X, Search, ChevronLeft, ChevronRight,
    ShoppingBag, ClipboardList,
} from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Reports', href: '/reports' },
        ],
    },
})

// ── Types ─────────────────────────────────────────────────────────────────────
interface DailyReport {
    date: string; total_orders: number; total_sales: number; total_discount: number
}
interface MonthlyReport {
    month: string; total_orders: number; total_sales: number; total_discount: number
}
interface ProductSale {
    product_id: number; product_name: string; total_quantity: number; total_sales: number
}
interface FtSummary {
    period: { start: string; end: string }
    orders: { total: number; count: number }
    payments: { total: number; count: number }
    expenses: { total: number; count: number }
    net: number
    by_tender: { tender: string; total: number; count: number }[]
}
interface FtTransaction {
    id: number; type: string; amount: number; description: string; transacted_at: string
    user?: { name: string }; tender?: { name: string }
}
interface OrderRow {
    id: number; queue_number: number | null; order_type: string; status: string
    payment_status: string; table_number: string | null; notes: string | null
    total_amount: number; items: { data: any[] } | any[]; user?: { data?: any; name?: string }
    created_at: string
}

const props = defineProps<{
    initialDailyReport: DailyReport
    initialProductSales: ProductSale[]
}>()

// ── Report type ────────────────────────────────────────────────────────────────
const reportType = ref<'orders' | 'daily' | 'monthly' | 'products' | 'inventory' | 'financial'>('orders')
const loading = ref(false)

// ── Daily / Monthly ────────────────────────────────────────────────────────────
const selectedDate = ref(new Date().toISOString().split('T')[0])
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(new Date().getMonth() + 1)
const dailyReport = ref<DailyReport | null>(props.initialDailyReport)
const monthlyReport = ref<MonthlyReport | null>(null)

// ── Products ───────────────────────────────────────────────────────────────────
const productSales = ref<ProductSale[]>(props.initialProductSales)

// ── Inventory ──────────────────────────────────────────────────────────────────
const inventoryReport = ref<any[]>([])

// ── Orders (searchable list) ───────────────────────────────────────────────────
const ordSearch = ref('')
const ordDateFrom = ref(new Date().toISOString().split('T')[0])
const ordDateTo = ref(new Date().toISOString().split('T')[0])
const ordStatus = ref('')
const ordPayment = ref('')
const ordersData = ref<OrderRow[]>([])
const ordersMeta = ref<any>(null)
const ordPage = ref(1)

// ── Financial ─────────────────────────────────────────────────────────────────
const ftStartDate = ref(new Date().toISOString().split('T')[0])
const ftEndDate = ref(new Date().toISOString().split('T')[0])
const ftTypeFilter = ref('')
const ftSummary = ref<FtSummary | null>(null)
const ftTransactions = ref<FtTransaction[]>([])
const ftMeta = ref<any>(null)
const showExpenseForm = ref(false)
const expenseForm = ref({ description: '', amount: '', notes: '' })
const expenseSaving = ref(false)

// ── Helpers ───────────────────────────────────────────────────────────────────
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const monthName = (n: number) => monthNames[n - 1] ?? ''

const fmt = (v: number | string | null | undefined) =>
    '₱' + parseFloat(String(v ?? 0)).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const itemCount = (items: any) =>
    Array.isArray(items) ? items.length : (items?.data?.length ?? 0)

const fmtDatetime = (s: string) => {
    if (!s) return '—'
    const d = new Date(s)
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) + ' ' +
        d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true })
}

const statusBadge = (s: string) => ({
    pending:   'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    preparing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    ready:     'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}[s] ?? 'bg-muted text-muted-foreground')

const payBadge = (s: string) => ({
    paid:     'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    pending:  'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    refunded: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    voided:   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}[s] ?? 'bg-muted text-muted-foreground')

const typeLabel = (t: string) => ({ order: 'Order', payment: 'Payment', expense: 'Expense' }[t] ?? t)
const typeBadgeClass = (t: string) => ({
    order:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    payment: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    expense: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}[t] ?? 'bg-muted text-muted-foreground')

const orderTypeBadge = (t: string) => ({
    dine_in: 'Dine-In',
    takeout: 'Takeout',
    delivery: 'Delivery',
}[t] ?? t)

const activeReport = computed(() => {
    if (reportType.value === 'daily') return dailyReport.value
    if (reportType.value === 'monthly') return monthlyReport.value
    return null
})

const topProducts = computed(() =>
    [...productSales.value].sort((a, b) => b.total_sales - a.total_sales).slice(0, 10)
)

// ── Data loading ──────────────────────────────────────────────────────────────
const loadOrders = async (page = 1) => {
    ordPage.value = page
    const res = await api.get('/api/v1/orders', {
        params: {
            page,
            search: ordSearch.value || undefined,
            date_from: ordDateFrom.value || undefined,
            date_to: ordDateTo.value || undefined,
            status: ordStatus.value || undefined,
            payment_status: ordPayment.value || undefined,
        },
    })
    ordersData.value = (res.data.data ?? []).map((o: any) => ({
        ...o,
        total_amount: parseFloat(o.total_amount ?? 0),
    }))
    ordersMeta.value = res.data.meta ?? null
}

const loadFinancial = async () => {
    const [summaryRes, listRes] = await Promise.all([
        api.get('/api/v1/financial-transactions/summary', {
            params: { start_date: ftStartDate.value, end_date: ftEndDate.value },
        }),
        api.get('/api/v1/financial-transactions', {
            params: {
                start_date: ftStartDate.value,
                end_date: ftEndDate.value,
                type: ftTypeFilter.value || undefined,
            },
        }),
    ])
    ftSummary.value = summaryRes.data
    ftTransactions.value = listRes.data.data ?? listRes.data
    ftMeta.value = listRes.data.meta ?? null
}

const generateReport = async () => {
    loading.value = true
    try {
        if (reportType.value === 'orders') {
            await loadOrders(1)
        } else if (reportType.value === 'daily') {
            const res = await api.get('/api/v1/reports/daily-sales', { params: { date: selectedDate.value } })
            dailyReport.value = res.data
        } else if (reportType.value === 'monthly') {
            const res = await api.get('/api/v1/reports/monthly-sales', { params: { year: selectedYear.value, month: selectedMonth.value } })
            monthlyReport.value = res.data
        } else if (reportType.value === 'products') {
            const res = await api.get('/api/v1/reports/product-sales')
            productSales.value = res.data
        } else if (reportType.value === 'inventory') {
            const res = await api.get('/api/v1/reports/inventory-valuation')
            inventoryReport.value = res.data
        } else if (reportType.value === 'financial') {
            await loadFinancial()
        }
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const saveExpense = async () => {
    if (!expenseForm.value.description.trim() || !expenseForm.value.amount) return
    expenseSaving.value = true
    try {
        await api.post('/api/v1/financial-transactions', {
            type: 'expense',
            amount: parseFloat(expenseForm.value.amount),
            description: expenseForm.value.description,
            notes: expenseForm.value.notes || null,
        })
        toast.success('Expense recorded')
        expenseForm.value = { description: '', amount: '', notes: '' }
        showExpenseForm.value = false
        await loadFinancial()
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to save expense')
    } finally {
        expenseSaving.value = false
    }
}

// ── Export ────────────────────────────────────────────────────────────────────
const exportCSV = () => {
    let rows: string[][] = []
    let filename = 'report'

    if (reportType.value === 'orders' && ordersData.value.length > 0) {
        filename = `orders-${ordDateFrom.value}-to-${ordDateTo.value}`
        rows = [
            ['ID', 'Queue#', 'Date', 'Type', 'Table', 'Status', 'Payment', 'Items', 'Total'],
            ...ordersData.value.map((o) => [
                String(o.id),
                String(o.queue_number ?? ''),
                o.created_at?.slice(0, 16) ?? '',
                o.order_type,
                o.table_number ?? '',
                o.status,
                o.payment_status,
                String(itemCount(o.items)),
                String(o.total_amount),
            ]),
        ]
    } else if (reportType.value === 'daily' && dailyReport.value) {
        filename = `daily-sales-${dailyReport.value.date}`
        rows = [
            ['Date', 'Total Orders', 'Total Sales', 'Discounts'],
            [dailyReport.value.date, String(dailyReport.value.total_orders), String(dailyReport.value.total_sales), String(dailyReport.value.total_discount)],
        ]
    } else if (reportType.value === 'products') {
        filename = `product-sales`
        rows = [['Product', 'Qty Sold', 'Total Sales'], ...productSales.value.map((p) => [p.product_name, String(p.total_quantity), String(p.total_sales)])]
    } else if (reportType.value === 'financial' && ftTransactions.value.length > 0) {
        filename = `financial-transactions-${ftStartDate.value}-to-${ftEndDate.value}`
        rows = [
            ['Date', 'Type', 'Description', 'Tender', 'Amount', 'User'],
            ...ftTransactions.value.map((t) => [
                t.transacted_at?.slice(0, 10) ?? '',
                t.type, t.description,
                t.tender?.name ?? '',
                String(t.amount),
                t.user?.name ?? '',
            ]),
        ]
    }

    if (rows.length === 0) { toast.info('No data to export'); return }

    const csv = rows.map((r) => r.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n')
    const a = document.createElement('a')
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv)
    a.download = `${filename}.csv`
    a.click()
    toast.success('CSV downloaded')
}

const printReport = () => window.print()

onMounted(() => generateReport())
</script>

<template>
    <Head title="Reports" />

    <div class="space-y-6">
        <!-- Filters Bar -->
        <div class="rounded-xl border bg-card shadow-sm p-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs font-medium text-muted-foreground block mb-1">Report Type</label>
                    <select v-model="reportType" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="orders">Orders List</option>
                        <option value="daily">Daily Sales</option>
                        <option value="monthly">Monthly Sales</option>
                        <option value="products">Product Sales</option>
                        <option value="inventory">Inventory Valuation</option>
                        <option value="financial">Financial Records</option>
                    </select>
                </div>

                <!-- Orders filters -->
                <template v-if="reportType === 'orders'">
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">From</label>
                        <input v-model="ordDateFrom" type="date" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">To</label>
                        <input v-model="ordDateTo" type="date" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Status</label>
                        <select v-model="ordStatus" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="preparing">Preparing</option>
                            <option value="ready">Ready</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Payment</label>
                        <select v-model="ordPayment" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Unpaid</option>
                            <option value="refunded">Refunded</option>
                            <option value="voided">Voided</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Search</label>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
                            <input
                                v-model="ordSearch"
                                type="text"
                                placeholder="Order #, table, notes…"
                                class="rounded-lg border bg-background pl-8 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary w-48"
                                @keydown.enter="generateReport"
                            />
                        </div>
                    </div>
                </template>

                <!-- Daily date picker -->
                <div v-if="reportType === 'daily'">
                    <label class="text-xs font-medium text-muted-foreground block mb-1">Date</label>
                    <input v-model="selectedDate" type="date" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

                <!-- Monthly pickers -->
                <template v-if="reportType === 'monthly'">
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Year</label>
                        <input v-model.number="selectedYear" type="number" min="2020" class="w-24 rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Month</label>
                        <select v-model.number="selectedMonth" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option v-for="m in 12" :key="m" :value="m">{{ monthName(m) }}</option>
                        </select>
                    </div>
                </template>

                <!-- Financial date range -->
                <template v-if="reportType === 'financial'">
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">From</label>
                        <input v-model="ftStartDate" type="date" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">To</label>
                        <input v-model="ftEndDate" type="date" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Type</label>
                        <select v-model="ftTypeFilter" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All Types</option>
                            <option value="order">Orders</option>
                            <option value="payment">Payments</option>
                            <option value="expense">Expenses</option>
                        </select>
                    </div>
                </template>

                <button
                    @click="generateReport"
                    :disabled="loading"
                    class="rounded-lg bg-primary px-5 py-2 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 flex items-center gap-1.5"
                >
                    <RefreshCw v-if="loading" class="h-3.5 w-3.5 animate-spin" />
                    <BarChart3 v-else class="h-3.5 w-3.5" />
                    Generate
                </button>
                <button @click="exportCSV" class="rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted flex items-center gap-1.5">
                    <Download class="h-3.5 w-3.5" /> Export CSV
                </button>
                <button @click="printReport" class="rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted">
                    Print
                </button>
            </div>
        </div>

        <!-- ── Orders List ──────────────────────────────────────────────────── -->
        <template v-if="reportType === 'orders'">
            <!-- Summary row -->
            <div v-if="ordersMeta" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><ClipboardList class="h-3 w-3" /> Total Orders</p>
                    <p class="text-3xl font-black">{{ ordersMeta.total }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1">Paid</p>
                    <p class="text-3xl font-black text-green-600">{{ ordersData.filter(o => o.payment_status === 'paid').length }}</p>
                    <p class="text-xs text-muted-foreground">on this page</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1">Unpaid</p>
                    <p class="text-3xl font-black text-yellow-600">{{ ordersData.filter(o => o.payment_status === 'pending').length }}</p>
                    <p class="text-xs text-muted-foreground">on this page</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><TrendingUp class="h-3 w-3" /> Revenue (page)</p>
                    <p class="text-xl font-black text-green-600">{{ fmt(ordersData.filter(o => o.payment_status === 'paid').reduce((s, o) => s + o.total_amount, 0)) }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b flex items-center justify-between">
                    <h2 class="font-bold text-sm flex items-center gap-2">
                        <ShoppingBag class="h-4 w-4" /> Orders
                    </h2>
                    <span v-if="ordersMeta" class="text-xs text-muted-foreground">
                        Page {{ ordersMeta.current_page }} of {{ ordersMeta.last_page }} &nbsp;·&nbsp; {{ ordersMeta.total }} total
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-muted-foreground text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Order</th>
                                <th class="px-4 py-3 text-left">Date & Time</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Table</th>
                                <th class="px-4 py-3 text-center">Items</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Payment</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-left">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="order in ordersData" :key="order.id" class="hover:bg-muted/20">
                                <td class="px-4 py-3">
                                    <p class="font-bold">#{{ order.id }}</p>
                                    <p v-if="order.queue_number" class="text-xs text-muted-foreground">Q{{ order.queue_number }}</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-muted-foreground text-xs">
                                    {{ fmtDatetime(order.created_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                        {{ orderTypeBadge(order.order_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ order.table_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-medium">{{ itemCount(order.items) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold capitalize', statusBadge(order.status)]">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold capitalize', payBadge(order.payment_status)]">
                                        {{ order.payment_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold">{{ fmt(order.total_amount) }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground max-w-[140px] truncate">{{ order.notes ?? '—' }}</td>
                            </tr>
                            <tr v-if="ordersData.length === 0 && !loading">
                                <td colspan="9" class="px-4 py-10 text-center text-muted-foreground">
                                    No orders found. Adjust filters and click Generate.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="ordersMeta && ordersMeta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t">
                    <button
                        @click="loadOrders(ordPage - 1)"
                        :disabled="ordPage <= 1 || loading"
                        class="flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm font-medium hover:bg-muted disabled:opacity-40"
                    >
                        <ChevronLeft class="h-3.5 w-3.5" /> Prev
                    </button>
                    <span class="text-xs text-muted-foreground">
                        Showing {{ ordersMeta.from }}–{{ ordersMeta.to }} of {{ ordersMeta.total }}
                    </span>
                    <button
                        @click="loadOrders(ordPage + 1)"
                        :disabled="ordPage >= ordersMeta.last_page || loading"
                        class="flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm font-medium hover:bg-muted disabled:opacity-40"
                    >
                        Next <ChevronRight class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </template>

        <!-- ── Daily / Monthly Summary Cards ──────────────────────────────── -->
        <template v-if="(reportType === 'daily' || reportType === 'monthly') && activeReport">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <h2 class="font-bold text-base mb-4">
                    <span v-if="reportType === 'daily'">Daily Sales — {{ (activeReport as any).date }}</span>
                    <span v-else>Monthly Sales — {{ monthName(Number((activeReport as any).month?.split('-')[1])) }} {{ (activeReport as any).month?.split('-')[0] }}</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div class="rounded-lg bg-muted/40 p-4">
                        <p class="text-xs text-muted-foreground mb-1">Total Orders</p>
                        <p class="text-3xl font-black">{{ activeReport.total_orders }}</p>
                    </div>
                    <div class="rounded-lg bg-green-50 dark:bg-green-950/20 p-4">
                        <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><TrendingUp class="h-3 w-3" /> Revenue</p>
                        <p class="text-2xl font-black text-green-600">{{ fmt(activeReport.total_sales) }}</p>
                    </div>
                    <div class="rounded-lg bg-yellow-50 dark:bg-yellow-950/20 p-4">
                        <p class="text-xs text-muted-foreground mb-1">Discounts</p>
                        <p class="text-2xl font-black text-yellow-600">{{ fmt(activeReport.total_discount) }}</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- ── Product Sales ───────────────────────────────────────────────── -->
        <template v-if="reportType === 'products'">
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold text-sm">Product Sales — This Month</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-muted-foreground text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Product</th>
                                <th class="px-4 py-3 text-right">Qty Sold</th>
                                <th class="px-4 py-3 text-right">Revenue</th>
                                <th class="px-4 py-3 text-left">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="(item, i) in topProducts" :key="item.product_id" class="hover:bg-muted/20">
                                <td class="px-4 py-2 text-muted-foreground">{{ i + 1 }}</td>
                                <td class="px-4 py-2 font-medium">{{ item.product_name }}</td>
                                <td class="px-4 py-2 text-right">{{ item.total_quantity }}</td>
                                <td class="px-4 py-2 text-right font-bold text-green-600">{{ fmt(item.total_sales) }}</td>
                                <td class="px-4 py-2 w-40">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 bg-muted rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-primary rounded-full"
                                                :style="{ width: topProducts[0]?.total_sales ? ((Number(item.total_sales) / Number(topProducts[0].total_sales)) * 100) + '%' : '0%' }"
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="topProducts.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No data available</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- ── Inventory Valuation ─────────────────────────────────────────── -->
        <template v-if="reportType === 'inventory' && inventoryReport.length > 0">
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b"><h2 class="font-bold text-sm">Inventory Valuation</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-muted-foreground text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Ingredient</th>
                                <th class="px-4 py-3 text-right">Stock</th>
                                <th class="px-4 py-3 text-left">Unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in inventoryReport" :key="item.id" class="hover:bg-muted/20">
                                <td class="px-4 py-2 font-medium">{{ item.name }}</td>
                                <td class="px-4 py-2 text-right">{{ item.current_quantity }}</td>
                                <td class="px-4 py-2 text-muted-foreground">{{ item.unit }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
        <div v-if="reportType === 'inventory' && inventoryReport.length === 0 && !loading" class="rounded-xl border bg-card p-10 text-center shadow-sm text-muted-foreground text-sm">
            Click <strong>Generate</strong> to load the inventory valuation report.
        </div>

        <!-- ── Financial Records ───────────────────────────────────────────── -->
        <template v-if="reportType === 'financial'">
            <!-- Summary Cards -->
            <div v-if="ftSummary" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><BarChart3 class="h-3 w-3" /> Orders</p>
                    <p class="text-2xl font-black">{{ ftSummary.orders.count }}</p>
                    <p class="text-sm font-semibold text-blue-600 mt-0.5">{{ fmt(ftSummary.orders.total) }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><TrendingUp class="h-3 w-3" /> Payments</p>
                    <p class="text-2xl font-black">{{ ftSummary.payments.count }}</p>
                    <p class="text-sm font-semibold text-green-600 mt-0.5">{{ fmt(ftSummary.payments.total) }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><TrendingDown class="h-3 w-3" /> Expenses</p>
                    <p class="text-2xl font-black">{{ ftSummary.expenses.count }}</p>
                    <p class="text-sm font-semibold text-red-600 mt-0.5">{{ fmt(ftSummary.expenses.total) }}</p>
                </div>
                <div :class="['rounded-xl border p-4 shadow-sm', ftSummary.net >= 0 ? 'bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-800']">
                    <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><DollarSign class="h-3 w-3" /> Net Cash</p>
                    <p class="text-2xl font-black" :class="ftSummary.net >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600'">
                        {{ fmt(ftSummary.net) }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-0.5">Payments − Expenses</p>
                </div>
            </div>

            <!-- Payment by Tender -->
            <div v-if="ftSummary && ftSummary.by_tender.length > 0" class="rounded-xl border bg-card shadow-sm p-4">
                <h3 class="font-bold text-sm mb-3">Payments by Tender</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="row in ftSummary.by_tender" :key="row.tender" class="flex items-center justify-between rounded-lg bg-muted/40 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold">{{ row.tender }}</p>
                            <p class="text-xs text-muted-foreground">{{ row.count }} transaction{{ row.count !== 1 ? 's' : '' }}</p>
                        </div>
                        <p class="text-base font-bold text-green-600">{{ fmt(row.total) }}</p>
                    </div>
                </div>
            </div>

            <!-- Add Expense button + form -->
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-sm text-muted-foreground uppercase tracking-wider">Transaction Log</h3>
                <button @click="showExpenseForm = !showExpenseForm" class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium hover:bg-muted transition">
                    <Plus v-if="!showExpenseForm" class="h-3.5 w-3.5" />
                    <X v-else class="h-3.5 w-3.5" />
                    {{ showExpenseForm ? 'Cancel' : 'Record Expense' }}
                </button>
            </div>

            <div v-if="showExpenseForm" class="rounded-xl border bg-card shadow-sm p-4">
                <h4 class="font-semibold text-sm mb-3">New Expense</h4>
                <div class="grid sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Description</label>
                        <input v-model="expenseForm.description" type="text" placeholder="e.g. Charcoal supply, LPG refill" class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Amount (₱)</label>
                        <input v-model="expenseForm.amount" type="number" min="0.01" step="0.01" placeholder="0.00" class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Notes (optional)</label>
                        <input v-model="expenseForm.notes" type="text" placeholder="Additional details" class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div class="flex items-end">
                        <button @click="saveExpense" :disabled="expenseSaving" class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            {{ expenseSaving ? 'Saving…' : 'Save Expense' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transaction list -->
            <div v-if="ftTransactions.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-muted-foreground text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Description</th>
                                <th class="px-4 py-3 text-left">Tender</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-left">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="tx in ftTransactions" :key="tx.id" class="hover:bg-muted/20">
                                <td class="px-4 py-2 text-muted-foreground whitespace-nowrap">{{ tx.transacted_at?.slice(0, 10) }}</td>
                                <td class="px-4 py-2">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', typeBadgeClass(tx.type)]">{{ typeLabel(tx.type) }}</span>
                                </td>
                                <td class="px-4 py-2 max-w-xs truncate">{{ tx.description }}</td>
                                <td class="px-4 py-2 text-muted-foreground">{{ tx.tender?.name ?? '—' }}</td>
                                <td class="px-4 py-2 text-right font-bold" :class="tx.type === 'expense' ? 'text-red-600' : 'text-green-600'">
                                    {{ tx.type === 'expense' ? '-' : '' }}{{ fmt(tx.amount) }}
                                </td>
                                <td class="px-4 py-2 text-muted-foreground text-xs">{{ tx.user?.name ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="ftMeta" class="px-4 py-3 border-t text-xs text-muted-foreground">
                    Showing {{ ftTransactions.length }} of {{ ftMeta.total }} transactions
                </div>
            </div>

            <div v-if="ftTransactions.length === 0 && !loading && !ftSummary" class="rounded-xl border bg-card p-10 text-center shadow-sm text-muted-foreground text-sm">
                Select a date range and click <strong>Generate</strong> to load financial records.
            </div>
            <div v-else-if="ftTransactions.length === 0 && !loading && ftSummary" class="rounded-xl border bg-card p-6 text-center shadow-sm text-muted-foreground text-sm">
                No transactions found for this period.
            </div>
        </template>
    </div>
</template>
