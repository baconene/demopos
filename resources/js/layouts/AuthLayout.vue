<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { home } from '@/routes'

defineProps<{
    title?: string
    description?: string
}>()

const page = usePage()
const logoUrl = (page.props as any).logoUrl as string | null
</script>

<template>
    <div class="min-h-svh lg:grid lg:grid-cols-2">
        <!-- Left panel — POS branding -->
        <div class="relative hidden lg:flex flex-col justify-between bg-slate-950 p-12 overflow-hidden">
            <!-- Blue glow -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-950/60 via-slate-950 to-slate-950 pointer-events-none" />
            <div class="absolute -bottom-32 -left-16 w-96 h-96 rounded-full bg-blue-600/20 blur-3xl pointer-events-none" />
            <div class="absolute top-24 right-8 w-64 h-64 rounded-full bg-cyan-500/10 blur-2xl pointer-events-none" />

            <!-- Logo -->
            <Link :href="home()" class="relative z-10 flex items-center gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 shadow-lg shadow-blue-500/30"
                >
                    <img
                        v-if="logoUrl"
                        :src="logoUrl"
                        class="h-8 w-8 object-contain rounded-lg"
                        alt="Logo"
                    />

                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        class="h-6 w-6 fill-white"
                    >
                        <path
                            d="M7 4V2H5v2H2v2h1v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6h1V4h-3V2h-2v2H7Zm12 4v10H5V8h14Zm-9 2H7v2h3v-2Zm7 0h-5v2h5v-2Zm-7 4H7v2h3v-2Zm7 0h-5v2h5v-2Z"
                        />
                    </svg>
                </div>

                <div class="flex flex-col leading-tight">
                    <span class="text-white font-black text-xl tracking-tight">
                        Demo<span class="text-blue-400">POS</span>
                    </span>

                    <span class="text-[10px] uppercase tracking-[0.3em] text-blue-300">
                        Point of Sale
                    </span>
                </div>
            </Link>

            <!-- Hero -->
            <div class="relative z-10 space-y-5">
                <p class="text-blue-400 text-sm font-semibold uppercase tracking-widest">
                    Modern POS Platform
                </p>

                <h1 class="text-5xl font-black text-white leading-tight">
                    Sell Faster.<br />

                    <span
                        class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent"
                    >
                        Manage Smarter.
                    </span>
                </h1>

                <p class="text-slate-400 text-base leading-relaxed max-w-sm">
                    Complete point-of-sale software for retail stores,
                    restaurants, cafés, and growing businesses.
                    Track sales, inventory, staff, and analytics in real time.
                </p>

                <!-- Features -->
                <div class="grid grid-cols-2 gap-4 pt-4 max-w-md">
                    <div
                        class="rounded-xl border border-white/5 bg-white/5 p-4 backdrop-blur-sm"
                    >
                        <div class="text-2xl mb-2">💳</div>

                        <p class="text-sm font-semibold text-white">
                            Fast Checkout
                        </p>

                        <p class="text-xs text-slate-400 mt-1">
                            Quick and seamless transactions
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-white/5 bg-white/5 p-4 backdrop-blur-sm"
                    >
                        <div class="text-2xl mb-2">📦</div>

                        <p class="text-sm font-semibold text-white">
                            Inventory
                        </p>

                        <p class="text-xs text-slate-400 mt-1">
                            Real-time stock monitoring
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-white/5 bg-white/5 p-4 backdrop-blur-sm"
                    >
                        <div class="text-2xl mb-2">📊</div>

                        <p class="text-sm font-semibold text-white">
                            Analytics
                        </p>

                        <p class="text-xs text-slate-400 mt-1">
                            Powerful business insights
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-white/5 bg-white/5 p-4 backdrop-blur-sm"
                    >
                        <div class="text-2xl mb-2">👥</div>

                        <p class="text-sm font-semibold text-white">
                            Staff Control
                        </p>

                        <p class="text-xs text-slate-400 mt-1">
                            Employee and role management
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10 flex items-center justify-between">
                <p class="text-slate-600 text-xs">
                    © {{ new Date().getFullYear() }} DemoPOS
                </p>

                <div
                    class="rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-300"
                >
                    Enterprise Ready
                </div>
            </div>
        </div>

        <!-- Right panel -->
        <div class="flex items-center justify-center px-6 py-12 bg-background">
            <div class="w-full max-w-sm space-y-8">
                <!-- Mobile logo -->
                <Link
                    :href="home()"
                    class="flex items-center justify-center gap-3 lg:hidden"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-700"
                    >
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            class="h-7 w-7 object-contain rounded-lg"
                            alt="Logo"
                        />

                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="h-5 w-5 fill-white"
                        >
                            <path
                                d="M7 4V2H5v2H2v2h1v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6h1V4h-3V2h-2v2H7Zm12 4v10H5V8h14Z"
                            />
                        </svg>
                    </div>

                    <span class="font-black text-xl">
                        Demo<span class="text-blue-500">POS</span>
                    </span>
                </Link>

                <!-- Heading -->
                <div class="space-y-2 text-center">
                    <h2 class="text-3xl font-black tracking-tight">
                        {{ title }}
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <!-- Form -->
                <slot />
            </div>
        </div>
    </div>
</template>