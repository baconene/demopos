<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import api from '@/utils/api'
import { BarChart3, Download, RefreshCw, TrendingUp } from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Reports', href: '/reports' },
        ],
    },
})

interface DailyReport {
    date: string; total_orders: number; total_sales: number; total_discount: number; total_tax: number
}
interface MonthlyReport {
    month: string; total_orders: number; total_sales: number; total_discount: number; total_tax: number
}
interface ProductSale {
    product_id: number; product_name: string; total_quantity: number; total_sales: number
}

const props = defineProps<{
    initialDailyReport: DailyReport
    initialProductSales: ProductSale[]
}>()

const reportType = ref<'daily' | 'monthly' | 'products' | 'inventory'>('daily')
const selectedDate = ref(new Date().toISOString().split('T')[0])
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(new Date().getMonth() + 1)
const loading = ref(false)
const dailyReport = ref<DailyReport | null>(props.initialDailyReport)
const monthlyReport = ref<MonthlyReport | null>(null)
const productSales = ref<ProductSale[]>(props.initialProductSales)
const inventoryReport = ref<any[]>([])

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const monthName = (n: number) => monthNames[n - 1] ?? ''

const formatCurrency = (v: number | null | undefined) =>
    '₱' + (v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const generateReport = async () => {
    loading.value = true
    try {
        if (reportType.value === 'daily') {
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
        }
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to load report')
    } finally {
        loading.value = false
    }
}

const exportCSV = () => {
    let rows: string[][] = []
    let filename = 'report'

    if (reportType.value === 'daily' && dailyReport.value) {
        filename = `daily-sales-${dailyReport.value.date}`
        rows = [
            ['Date', 'Total Orders', 'Total Sales', 'Discounts', 'VAT'],
            [dailyReport.value.date, String(dailyReport.value.total_orders), String(dailyReport.value.total_sales), String(dailyReport.value.total_discount), String(dailyReport.value.total_tax)],
        ]
    } else if (reportType.value === 'products') {
        filename = `product-sales`
        rows = [['Product', 'Qty Sold', 'Total Sales'], ...productSales.value.map((p) => [p.product_name, String(p.total_quantity), String(p.total_sales)])]
    }

    if (rows.length === 0) { toast.info('No data to export'); return }

    const csv = rows.map((r) => r.join(',')).join('\n')
    const a = document.createElement('a')
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv)
    a.download = `${filename}.csv`
    a.click()
    toast.success('CSV downloaded')
}

const activeReport = computed(() => {
    if (reportType.value === 'daily') return dailyReport.value
    if (reportType.value === 'monthly') return monthlyReport.value
    return null
})

const topProducts = computed(() =>
    [...productSales.value].sort((a, b) => b.total_sales - a.total_sales).slice(0, 10)
)
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
                        <option value="daily">Daily Sales</option>
                        <option value="monthly">Monthly Sales</option>
                        <option value="products">Product Sales</option>
                        <option value="inventory">Inventory Valuation</option>
                    </select>
                </div>

                <div v-if="reportType === 'daily'">
                    <label class="text-xs font-medium text-muted-foreground block mb-1">Date</label>
                    <input v-model="selectedDate" type="date" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

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

                <button
                    @click="generateReport"
                    :disabled="loading"
                    class="rounded-lg bg-primary px-5 py-2 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 flex items-center gap-1.5"
                >
                    <RefreshCw v-if="loading" class="h-3.5 w-3.5 animate-spin" />
                    <BarChart3 v-else class="h-3.5 w-3.5" />
                    Generate
                </button>
                <button
                    @click="exportCSV"
                    class="rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted flex items-center gap-1.5"
                >
                    <Download class="h-3.5 w-3.5" />
                    Export CSV
                </button>
                <button
                    @click="() => window.print()"
                    class="rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted"
                >
                    Print
                </button>
            </div>
        </div>

        <!-- Daily / Monthly Summary Cards -->
        <template v-if="(reportType === 'daily' || reportType === 'monthly') && activeReport">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <h2 class="font-bold text-base mb-4">
                    <span v-if="reportType === 'daily'">Daily Sales — {{ (activeReport as any).date }}</span>
                    <span v-else>Monthly Sales — {{ monthName(Number((activeReport as any).month?.split('-')[1])) }} {{ (activeReport as any).month?.split('-')[0] }}</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="rounded-lg bg-muted/40 p-4">
                        <p class="text-xs text-muted-foreground mb-1">Total Orders</p>
                        <p class="text-3xl font-black">{{ activeReport.total_orders }}</p>
                    </div>
                    <div class="rounded-lg bg-green-50 dark:bg-green-950/20 p-4">
                        <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><TrendingUp class="h-3 w-3" /> Revenue</p>
                        <p class="text-2xl font-black text-green-600">{{ formatCurrency(activeReport.total_sales) }}</p>
                    </div>
                    <div class="rounded-lg bg-yellow-50 dark:bg-yellow-950/20 p-4">
                        <p class="text-xs text-muted-foreground mb-1">Discounts</p>
                        <p class="text-2xl font-black text-yellow-600">{{ formatCurrency(activeReport.total_discount) }}</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-950/20 p-4">
                        <p class="text-xs text-muted-foreground mb-1">VAT Collected</p>
                        <p class="text-2xl font-black text-blue-600">{{ formatCurrency(activeReport.total_tax) }}</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Product Sales Table -->
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
                                <td class="px-4 py-2 text-right font-bold text-green-600">{{ formatCurrency(item.total_sales) }}</td>
                                <td class="px-4 py-2 w-40">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 bg-muted rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-primary rounded-full"
                                                :style="{ width: topProducts[0]?.total_sales ? ((item.total_sales / topProducts[0].total_sales) * 100) + '%' : '0%' }"
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

        <!-- Inventory Valuation -->
        <template v-if="reportType === 'inventory' && inventoryReport.length > 0">
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold text-sm">Inventory Valuation</h2>
                </div>
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
    </div>
</template>
