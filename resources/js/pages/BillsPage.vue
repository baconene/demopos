<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import api from '@/utils/api'
import {
    CalendarDays, Plus, X, Pencil, Trash2, TrendingUp, TrendingDown,
} from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Bills', href: '/bills' },
        ],
    },
})

// ── Types ─────────────────────────────────────────────────────────────────────
interface BillInstallment {
    id: number; installment_number: number; amount: number
    due_date: string; paid_at: string | null
    status: 'overdue' | 'due_today' | 'upcoming' | 'scheduled' | 'paid'
}
interface Bill {
    id: number; name: string; description: string | null; amount: number
    frequency: string; due_date: string; category: string | null
    is_active: boolean; is_installment: boolean
    installment_count: number | null; installments_paid: number | null
    last_paid_at: string | null
    status: 'overdue' | 'due_today' | 'upcoming' | 'scheduled' | 'inactive'
    installments: BillInstallment[]
}
interface BillForecastEntry {
    bill_id: number; installment_id: number | null; name: string; label: string | null
    category: string | null; amount: number; due_date: string
    frequency: string; is_installment: boolean; status: string
}
interface BillForecast {
    entries: BillForecastEntry[]
    by_month: Record<string, BillForecastEntry[]>
    total_forecast: number; months: number
}

// ── State ──────────────────────────────────────────────────────────────────────
const bills = ref<Bill[]>([])
const billForecast = ref<BillForecast | null>(null)
const forecastMonths = ref(3)
const loading = ref(false)
const showBillForm = ref(false)
const editingBill = ref<Bill | null>(null)
const billForm = ref({
    name: '', description: '', amount: '', frequency: 'monthly',
    due_date: '', category: '', is_installment: false, installment_count: '3',
})
const billSaving = ref(false)
const billPaying = ref<number | null>(null)
const billDeleting = ref<number | null>(null)
const expandedBillId = ref<number | null>(null)
const payingInstallmentId = ref<number | null>(null)

// ── Helpers ───────────────────────────────────────────────────────────────────
const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December']
const monthName = (n: number) => monthNames[n - 1] ?? ''

const fmt = (v: number | string | null | undefined) =>
    '₱' + parseFloat(String(v ?? 0)).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const fmtDatetime = (s: string) => {
    if (!s) return '—'
    const d = new Date(s)
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) + ' ' +
        d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true })
}

const frequencyLabel = (f: string) => ({
    one_time: 'One Time', daily: 'Daily', weekly: 'Weekly', bi_weekly: 'Bi-Weekly',
    monthly: 'Monthly', quarterly: 'Quarterly', semi_annual: 'Semi-Annual', annual: 'Annual',
}[f] ?? f)

const billStatusBadge = (s: string) => ({
    overdue:   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    due_today: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    upcoming:  'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    inactive:  'bg-muted text-muted-foreground',
}[s] ?? 'bg-muted text-muted-foreground')

const installmentStatusBadge = (s: string) => ({
    overdue:    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    due_today:  'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    upcoming:   'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    scheduled:  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    paid:       'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
}[s] ?? 'bg-muted text-muted-foreground')

// ── Computed ───────────────────────────────────────────────────────────────────
const monthlySummary = computed(() => {
    const multiplier: Record<string, number> = {
        one_time: 0, daily: 30, weekly: 4.33, bi_weekly: 2.17,
        monthly: 1, quarterly: 1/3, semi_annual: 1/6, annual: 1/12,
    }
    return bills.value.filter(b => b.is_active && !b.is_installment).reduce((sum, b) => sum + b.amount * (multiplier[b.frequency] ?? 0), 0)
})

const annualSummary = computed(() => {
    const recurringAnnual = monthlySummary.value * 12
    const paymentPlansTotal = bills.value
        .filter(b => b.is_active && b.is_installment)
        .reduce((sum, b) => sum + (b.installments?.reduce((isum, i) => isum + (i.paid_at ? 0 : i.amount), 0) ?? 0), 0)
    return recurringAnnual + paymentPlansTotal
})

const overdueBills = computed(() => bills.value.filter(b => b.status === 'overdue'))
const dueSoonBills = computed(() => bills.value.filter(b => b.status === 'due_today' || b.status === 'upcoming'))

// ── Actions ────────────────────────────────────────────────────────────────────
const loadBills = async () => {
    const res = await api.get('/api/v1/bills')
    bills.value = res.data.data ?? []
}

const loadForecast = async () => {
    const res = await api.get('/api/v1/bills/forecast', { params: { months: forecastMonths.value } })
    billForecast.value = res.data
}

const openBillForm = (bill?: Bill) => {
    if (bill) {
        editingBill.value = bill
        billForm.value = {
            name: bill.name, description: bill.description ?? '',
            amount: String(bill.amount), frequency: bill.frequency,
            due_date: bill.due_date, category: bill.category ?? '',
            is_installment: bill.is_installment,
            installment_count: String(bill.installment_count ?? 3),
        }
    } else {
        editingBill.value = null
        billForm.value = {
            name: '', description: '', amount: '', frequency: 'monthly',
            due_date: '', category: '', is_installment: false, installment_count: '3',
        }
    }
    showBillForm.value = true
}

const closeBillForm = () => { showBillForm.value = false; editingBill.value = null }

const saveBill = async () => {
    if (!billForm.value.name.trim() || !billForm.value.amount || !billForm.value.due_date) return
    billSaving.value = true
    try {
        const payload: Record<string, any> = {
            name: billForm.value.name,
            description: billForm.value.description || null,
            amount: parseFloat(billForm.value.amount),
            frequency: billForm.value.frequency,
            due_date: billForm.value.due_date,
            category: billForm.value.category || null,
        }
        if (!editingBill.value) {
            payload.is_installment = billForm.value.is_installment
            if (billForm.value.is_installment) {
                payload.installment_count = parseInt(billForm.value.installment_count)
            }
        }
        if (editingBill.value) {
            await api.put(`/api/v1/bills/${editingBill.value.id}`, payload)
            toast.success('Bill updated.')
        } else {
            await api.post('/api/v1/bills', payload)
            toast.success('Bill added.')
        }
        closeBillForm()
        await loadBills(); await loadForecast()
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to save bill.')
    } finally {
        billSaving.value = false
    }
}

const payBill = async (bill: Bill) => {
    if (!confirm(`Mark "${bill.name}" as paid?\n\nAmount: ${fmt(bill.amount)}\nThis records an expense and advances the due date.`)) return
    billPaying.value = bill.id
    try {
        await api.post(`/api/v1/bills/${bill.id}/pay`)
        toast.success('Bill marked as paid.')
        await loadBills(); await loadForecast()
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to mark as paid.')
    } finally {
        billPaying.value = null
    }
}

const payInstallment = async (bill: Bill, inst: BillInstallment) => {
    if (!confirm(`Pay installment #${inst.installment_number} of "${bill.name}"?\n\nAmount: ${fmt(inst.amount)}\nDue: ${inst.due_date}`)) return
    payingInstallmentId.value = inst.id
    try {
        const res = await api.post(`/api/v1/bills/${bill.id}/installments/${inst.id}/pay`)
        const idx = bills.value.findIndex(b => b.id === bill.id)
        if (idx !== -1) bills.value[idx] = res.data.data
        await loadForecast()
        toast.success(`Installment #${inst.installment_number} paid.`)
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to pay installment.')
    } finally {
        payingInstallmentId.value = null
    }
}

const deleteBill = async (bill: Bill) => {
    if (!confirm(`Delete "${bill.name}"? This cannot be undone.`)) return
    billDeleting.value = bill.id
    try {
        await api.delete(`/api/v1/bills/${bill.id}`)
        toast.success('Bill deleted.')
        await loadBills(); await loadForecast()
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to delete bill.')
    } finally {
        billDeleting.value = null
    }
}

onMounted(async () => {
    loading.value = true
    try {
        await loadBills()
        await loadForecast()
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <Head title="Bills & Payables" />

    <div class="space-y-5 p-6">
        <!-- Summary cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1"><CalendarDays class="h-3 w-3" /> Monthly Exposure</p>
                <p class="text-xl font-black text-orange-600">{{ fmt(monthlySummary) }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">recurring bills</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs text-muted-foreground mb-1">Annual Exposure</p>
                <p class="text-xl font-black">{{ fmt(annualSummary) }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">recurring + fixed</p>
            </div>
            <div :class="['rounded-xl border p-4 shadow-sm', overdueBills.length > 0 ? 'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-800' : 'bg-card']">
                <p class="text-xs text-muted-foreground mb-1">Overdue</p>
                <p class="text-3xl font-black" :class="overdueBills.length > 0 ? 'text-red-600' : ''">{{ overdueBills.length }}</p>
            </div>
            <div :class="['rounded-xl border p-4 shadow-sm', dueSoonBills.length > 0 ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-200 dark:border-yellow-800' : 'bg-card']">
                <p class="text-xs text-muted-foreground mb-1">Due Soon</p>
                <p class="text-3xl font-black" :class="dueSoonBills.length > 0 ? 'text-yellow-600' : ''">{{ dueSoonBills.length }}</p>
            </div>
        </div>

        <!-- Bills list -->
        <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between">
                <h2 class="font-bold text-sm flex items-center gap-2"><CalendarDays class="h-4 w-4" /> Payables</h2>
                <button @click="openBillForm()" class="flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-sm font-bold text-primary-foreground hover:bg-primary/90">
                    <Plus class="h-3.5 w-3.5" /> Add Bill
                </button>
            </div>

            <!-- Add / Edit form -->
            <div v-if="showBillForm" class="border-b bg-muted/20 p-4">
                <p class="text-sm font-bold mb-3">{{ editingBill ? 'Edit Bill' : 'New Payable' }}</p>

                <!-- Payment plan toggle (new bills only) -->
                <div v-if="!editingBill" class="flex gap-2 mb-4">
                    <button @click="billForm.is_installment = false"
                        :class="['flex-1 rounded-lg border-2 py-2 text-sm font-semibold transition',
                            !billForm.is_installment ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']">
                        Recurring Bill
                    </button>
                    <button @click="billForm.is_installment = true"
                        :class="['flex-1 rounded-lg border-2 py-2 text-sm font-semibold transition',
                            billForm.is_installment ? 'border-orange-500 bg-orange-50 text-orange-700 dark:bg-orange-950/20 dark:text-orange-400' : 'border-border text-muted-foreground hover:bg-muted']">
                        Payment Plan (Installments)
                    </button>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Name *</label>
                        <input v-model="billForm.name" type="text" placeholder="e.g. Shopee Subscription"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">
                            {{ billForm.is_installment ? 'Total Amount (₱) *' : 'Amount (₱) *' }}
                        </label>
                        <input v-model="billForm.amount" type="number" min="0.01" step="0.01" placeholder="0.00"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">
                            {{ billForm.is_installment ? 'Interval Between Payments *' : 'Frequency *' }}
                        </label>
                        <select v-model="billForm.frequency" class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option v-if="!billForm.is_installment" value="one_time">One Time</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="bi_weekly">Bi-Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="semi_annual">Semi-Annual</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>
                    <!-- Installment count: only for new payment-plan bills -->
                    <div v-if="billForm.is_installment && !editingBill">
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Number of Installments *</label>
                        <input v-model="billForm.installment_count" type="number" min="2" max="360" placeholder="e.g. 3"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="billForm.amount && billForm.installment_count" class="text-xs text-muted-foreground mt-1">
                            ≈ {{ fmt(parseFloat(billForm.amount || '0') / parseInt(billForm.installment_count || '1')) }} / installment
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">
                            {{ billForm.is_installment ? 'First Payment Date *' : 'Next Due Date *' }}
                        </label>
                        <input v-model="billForm.due_date" type="date"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Category</label>
                        <input v-model="billForm.category" type="text" placeholder="e.g. Subscription, Utilities"
                            list="bill-categories"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <datalist id="bill-categories">
                            <option value="Subscription" /><option value="Utilities" /><option value="Rent" />
                            <option value="Platform Fee" /><option value="Loan" /><option value="Insurance" />
                            <option value="Maintenance" /><option value="Tax" />
                        </datalist>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground block mb-1">Notes</label>
                        <input v-model="billForm.description" type="text" placeholder="Optional details"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="saveBill" :disabled="billSaving || !billForm.name.trim() || !billForm.amount || !billForm.due_date"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                        {{ billSaving ? 'Saving…' : (editingBill ? 'Update' : (billForm.is_installment ? 'Create Payment Plan' : 'Add Bill')) }}
                    </button>
                    <button @click="closeBillForm" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted">Cancel</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Bill / Payable</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-left">Frequency</th>
                            <th class="px-4 py-3 text-left">Next Due</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Last Paid</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="bill in bills" :key="bill.id">
                            <tr :class="['border-t', bill.is_active ? '' : 'opacity-50']">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium">{{ bill.name }}</p>
                                        <p v-if="bill.description" class="text-xs text-muted-foreground">{{ bill.description }}</p>
                                        <p v-if="bill.is_installment" class="text-xs text-orange-600 mt-1">
                                            Payment Plan: {{ bill.installments_paid }}/{{ bill.installment_count }} paid
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ bill.category ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ fmt(bill.amount) }}</td>
                                <td class="px-4 py-3 text-sm">{{ frequencyLabel(bill.frequency) }}</td>
                                <td class="px-4 py-3 text-sm">{{ bill.due_date }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', billStatusBadge(bill.status)]">
                                        {{ bill.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ bill.last_paid_at ? fmtDatetime(bill.last_paid_at) : '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="expandedBillId === bill.id ? (expandedBillId = null) : (expandedBillId = bill.id)"
                                        class="inline-block text-primary hover:underline text-xs font-medium">
                                        {{ expandedBillId === bill.id ? 'Hide' : 'Show' }}
                                    </button>
                                </td>
                            </tr>
                            <!-- Expanded row for installments and actions -->
                            <tr v-if="expandedBillId === bill.id" :class="['border-t bg-muted/30']">
                                <td :colspan="8" class="px-4 py-3">
                                    <div class="space-y-3">
                                        <!-- Installments grid (if payment plan) -->
                                        <div v-if="bill.is_installment && bill.installments.length > 0" class="space-y-2">
                                            <p class="text-xs font-semibold text-muted-foreground">Installments</p>
                                            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                                <div v-for="inst in bill.installments" :key="inst.id" class="rounded-lg border p-3 bg-card/50">
                                                    <div class="flex items-start justify-between">
                                                        <div>
                                                            <p class="text-xs font-bold">#{inst.installment_number}</p>
                                                            <p class="text-sm font-semibold mt-1">{{ fmt(inst.amount) }}</p>
                                                            <p class="text-xs text-muted-foreground mt-0.5">{{ inst.due_date }}</p>
                                                        </div>
                                                        <span :class="['px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap', installmentStatusBadge(inst.status)]">
                                                            {{ inst.status }}
                                                        </span>
                                                    </div>
                                                    <button v-if="!inst.paid_at && bill.is_active"
                                                        @click="payInstallment(bill, inst)"
                                                        :disabled="payingInstallmentId === inst.id"
                                                        class="w-full mt-2 rounded bg-green-600 px-2 py-1.5 text-xs font-bold text-white hover:bg-green-700 disabled:opacity-50">
                                                        {{ payingInstallmentId === inst.id ? 'Processing…' : 'Pay' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action buttons -->
                                        <div class="flex gap-2 pt-2">
                                            <button v-if="!bill.is_installment && bill.is_active" @click="payBill(bill)" :disabled="billPaying === bill.id"
                                                class="flex-1 rounded bg-green-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-green-700 disabled:opacity-50">
                                                {{ billPaying === bill.id ? 'Processing…' : '💰 Mark as Paid' }}
                                            </button>
                                            <button @click="openBillForm(bill)" class="flex-1 rounded border px-3 py-1.5 text-xs font-medium hover:bg-muted flex items-center justify-center gap-1">
                                                <Pencil class="h-3 w-3" /> Edit
                                            </button>
                                            <button @click="deleteBill(bill)" :disabled="billDeleting === bill.id"
                                                class="flex-1 rounded border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 disabled:opacity-50">
                                                {{ billDeleting === bill.id ? 'Deleting…' : '🗑️ Delete' }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div v-if="bills.length === 0" class="p-8 text-center text-muted-foreground">
                <p>No bills yet. <button @click="openBillForm()" class="text-primary hover:underline">Add one</button></p>
            </div>
        </div>

        <!-- Monthly forecast -->
        <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between">
                <h2 class="font-bold text-sm">Payment Forecast</h2>
                <select v-model.number="forecastMonths" @change="loadForecast" class="rounded-lg border bg-background px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                    <option :value="1">1 month</option>
                    <option :value="2">2 months</option>
                    <option :value="3">3 months</option>
                    <option :value="6">6 months</option>
                    <option :value="12">12 months</option>
                </select>
            </div>

            <div v-if="billForecast" class="grid lg:grid-cols-2 gap-4 p-4">
                <div v-for="(entries, month) in billForecast.by_month" :key="month" class="border rounded-lg p-3">
                    <p class="text-sm font-bold mb-2">{{ monthName(parseInt(month.split('-')[1])) }} {{ month.split('-')[0] }}</p>
                    <div class="space-y-1">
                        <div v-for="entry in entries" :key="`${entry.bill_id}-${entry.installment_id}`" class="flex items-center justify-between text-xs">
                            <div class="flex-1">
                                <p class="font-medium">{{ entry.name }} {{ entry.label ? `(${entry.label})` : '' }}</p>
                                <p class="text-muted-foreground">{{ entry.category ?? 'Uncategorized' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold">{{ fmt(entry.amount) }}</p>
                                <span :class="['text-xs px-1.5 rounded', billStatusBadge(entry.status)]">{{ entry.status }}</span>
                            </div>
                        </div>
                        <div class="border-t pt-1 mt-1 flex justify-between text-xs font-bold">
                            <span>Subtotal</span>
                            <span>{{ fmt(entries.reduce((s, e) => s + e.amount, 0)) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="billForecast" class="p-4 border-t bg-muted/30 flex items-center justify-between">
                <p class="text-sm font-bold">Total Forecast ({{ forecastMonths }} months)</p>
                <p class="text-2xl font-black text-orange-600">{{ fmt(billForecast.total_forecast) }}</p>
            </div>
        </div>
    </div>
</template>
