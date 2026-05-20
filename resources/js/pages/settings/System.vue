<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { AlertTriangle, Trash2, X, ShieldCheck } from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'System', href: '/settings/system' },
        ],
    },
})

const showModal = ref(false)
const confirmText = ref('')
const resetting = ref(false)

const KEPT_DATA = [
    'User accounts & passwords',
    'Roles & permissions',
    'Menu products & categories',
    'Ingredients & recipes',
    'Payment tenders',
    'Employee records',
]

const DELETED_DATA = [
    'All orders & order items',
    'All payments & refunds',
    'Queue numbers',
    'Inventory transaction history',
    'Financial transactions',
    'Payroll records',
    'Purchase orders',
    'Audit logs',
    'Inventory quantities (reset to 0)',
]

function openModal() {
    confirmText.value = ''
    showModal.value = true
}

function executeReset() {
    if (confirmText.value !== 'RESET') {
        toast.error('Type RESET exactly to proceed.')
        return
    }
    resetting.value = true
    router.post('/settings/system/reset', { confirmation: confirmText.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            toast.success('System has been reset. All transaction history cleared.')
        },
        onError: (errors) => {
            const first = Object.values(errors)[0] as string
            toast.error(first ?? 'Reset failed.')
        },
        onFinish: () => { resetting.value = false },
    })
}
</script>

<template>
    <Head title="System Settings" />

    <div class="space-y-8">
        <div>
            <h2 class="text-base font-semibold">System Settings</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Manage system-level operations. Admin access only.</p>
        </div>

        <!-- Danger Zone -->
        <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-900/50 overflow-hidden">
            <div class="flex items-center gap-2 border-b border-red-200 dark:border-red-900/50 px-5 py-3 bg-red-100 dark:bg-red-950/40">
                <AlertTriangle class="h-4 w-4 text-red-600" />
                <span class="font-semibold text-sm text-red-700 dark:text-red-400">Danger Zone</span>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4 justify-between">
                    <div>
                        <h3 class="font-semibold text-sm text-red-800 dark:text-red-300">Reset System Data</h3>
                        <p class="text-xs text-red-700/80 dark:text-red-400/80 mt-1 max-w-md">
                            Permanently deletes all transaction history — orders, payments, inventory logs, financial records, and payroll records.
                            Inventory quantities will be zeroed. This action is <strong>irreversible</strong>.
                        </p>
                    </div>
                    <button
                        @click="openModal"
                        class="flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 px-4 py-2 text-sm font-semibold text-white transition shrink-0"
                    >
                        <Trash2 class="h-4 w-4" />
                        Reset System
                    </button>
                </div>

                <!-- What's kept vs deleted -->
                <div class="grid sm:grid-cols-2 gap-4 pt-2">
                    <div class="rounded-lg bg-white dark:bg-background/50 border border-green-200 dark:border-green-900/40 p-3">
                        <p class="text-xs font-semibold text-green-700 dark:text-green-400 mb-2 flex items-center gap-1">
                            <ShieldCheck class="h-3.5 w-3.5" /> Will be kept
                        </p>
                        <ul class="space-y-1">
                            <li v-for="item in KEPT_DATA" :key="item" class="text-xs text-muted-foreground flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500 shrink-0"></span>{{ item }}
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-lg bg-white dark:bg-background/50 border border-red-200 dark:border-red-900/40 p-3">
                        <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-2 flex items-center gap-1">
                            <Trash2 class="h-3.5 w-3.5" /> Will be deleted
                        </p>
                        <ul class="space-y-1">
                            <li v-for="item in DELETED_DATA" :key="item" class="text-xs text-muted-foreground flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500 shrink-0"></span>{{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-xl bg-background shadow-2xl border border-red-200">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-red-200 p-4 bg-red-50 dark:bg-red-950/30 rounded-t-xl">
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                        <h2 class="font-bold text-red-700 dark:text-red-400">Confirm System Reset</h2>
                    </div>
                    <button @click="showModal = false" class="rounded p-1 hover:bg-red-100 dark:hover:bg-red-900/30">
                        <X class="h-4 w-4 text-red-600" />
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-4">
                    <div class="rounded-lg bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 p-3 text-sm text-red-800 dark:text-red-300">
                        <strong>Warning:</strong> This will permanently delete all orders, payments, inventory logs,
                        financial records, and payroll entries. Inventory quantities will be zeroed.
                        <strong>This cannot be undone.</strong>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Type <span class="font-mono bg-muted px-1.5 py-0.5 rounded text-red-600">RESET</span> to confirm
                        </label>
                        <input
                            v-model="confirmText"
                            type="text"
                            placeholder="Type RESET here"
                            autocomplete="off"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 border-red-200 bg-background"
                            @keyup.enter="executeReset"
                        />
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-2 border-t p-4">
                    <button
                        @click="showModal = false"
                        class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        @click="executeReset"
                        :disabled="confirmText !== 'RESET' || resetting"
                        class="flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed px-4 py-2 text-sm font-bold text-white transition"
                    >
                        <Trash2 class="h-4 w-4" />
                        {{ resetting ? 'Resetting…' : 'Reset System Data' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
