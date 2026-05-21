<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { dashboard, login } from '@/routes'

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: false })
</script>

<template>
    <Head title="DemoPOS — Modern Point of Sale System" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white font-sans overflow-x-hidden">

        <!-- ── NAV ─────────────────────────────────────────── -->
        <nav class="fixed top-0 inset-x-0 z-50 flex items-center justify-between px-6 py-4 bg-slate-900/80 backdrop-blur-md border-b border-blue-900/30">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center border border-blue-400">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 6H6.28l-.31-1.243A1 1 0 005 4H3z"></path>
                        <circle cx="5.5" cy="16.5" r="1.5"></circle>
                        <circle cx="14.5" cy="16.5" r="1.5"></circle>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">Demo<span class="text-blue-400">POS</span></span>
            </div>
            <div class="flex items-center gap-3">
                <Link v-if="$page.props.auth?.user" :href="dashboard()"
                    class="rounded-lg border border-blue-400 px-5 py-2 text-sm font-semibold text-blue-300 hover:bg-blue-600/30 transition-colors">
                    Dashboard
                </Link>
                <Link v-else :href="login()"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white hover:bg-blue-500 transition-colors">
                    Sign In
                </Link>
            </div>
        </nav>

        <!-- ── HERO ───────────────────────────────────────── -->
        <section class="relative min-h-screen flex items-center justify-center pt-20 pb-16 px-6 overflow-hidden">
            <!-- gradient glow bg -->
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute bottom-0 right-1/4 w-[800px] h-[400px] rounded-full opacity-20"
                    style="background: radial-gradient(ellipse, #3b82f6 0%, #1e40af 30%, transparent 70%); filter: blur(60px);"></div>
                <div class="absolute top-1/3 left-1/4 w-96 h-96 rounded-full opacity-10"
                    style="background: radial-gradient(circle, #60a5fa 0%, transparent 70%); filter: blur(50px);"></div>
            </div>

            <div class="relative z-10 max-w-5xl mx-auto text-center">
                <!-- badge -->
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-700 bg-blue-950/50 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-blue-400 mb-8">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                    Modern POS Solution · Enterprise Ready
                </div>

                <!-- headline -->
                <h1 class="text-6xl sm:text-7xl md:text-8xl font-black leading-none tracking-tight mb-6">
                    <span class="block text-white">SEAMLESS</span>
                    <span class="block" style="background: linear-gradient(90deg, #3b82f6, #60a5fa, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        POINT OF SALE
                    </span>
                    <span class="block text-white text-4xl sm:text-5xl font-light tracking-widest mt-2">MANAGEMENT</span>
                </h1>

                <p class="max-w-2xl mx-auto text-lg text-gray-300 leading-relaxed mb-12">
                    Complete point-of-sale solution for retail, restaurants, and small businesses.<br>
                    <span class="text-blue-400 font-semibold">Real-time inventory, sales tracking, and staff management.</span>
                </p>

                <!-- CTA buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-12">
                    <Link :href="login()"
                        class="bg-blue-600 text-white font-bold px-8 py-3 rounded-lg hover:bg-blue-500 transition-colors shadow-lg shadow-blue-600/30">
                        Get Started
                    </Link>
                    <button class="border-2 border-blue-400 text-blue-300 font-bold px-8 py-3 rounded-lg hover:bg-blue-900/30 transition-colors">
                        View Demo
                    </button>
                </div>

                <!-- Feature icons -->
                <div class="flex justify-center items-center gap-6 select-none mb-8 flex-wrap">
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 rounded-lg bg-blue-900/50 border border-blue-700 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-300">Fast Checkout</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 rounded-lg bg-blue-900/50 border border-blue-700 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 6H6.28l-.31-1.243A1 1 0 005 4H3z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-300">Inventory</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 rounded-lg bg-blue-900/50 border border-blue-700 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-300">Analytics</span>
                    </div>
                </div>

                <p class="text-xs uppercase tracking-widest text-gray-500">Used by 10,000+ businesses worldwide</p>
            </div>
        </section>

        <!-- ── KEY FEATURES ────────────────────────────────── -->
        <section class="py-24 px-6 border-t border-blue-900/30">
            <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-16 items-center">
                <!-- visual -->
                <div class="relative flex items-center justify-center">
                    <div class="absolute w-72 h-72 rounded-full opacity-30"
                        style="background: radial-gradient(circle, #3b82f6, transparent 70%); filter: blur(50px);"></div>
                    <div class="relative z-10 w-56 h-56 rounded-2xl bg-gradient-to-br from-blue-700 via-blue-600 to-slate-800 border-4 border-blue-500 flex items-center justify-center shadow-2xl shadow-blue-600/50">
                        <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="absolute top-4 right-8 bg-blue-500 text-white text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider rotate-12 shadow-lg">
                        Enterprise
                    </div>
                </div>

                <!-- text -->
                <div>
                    <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-3">Core Platform</p>
                    <h2 class="text-4xl sm:text-5xl font-black mb-5 leading-tight">
                        All-in-One<br>
                        <span class="text-blue-300">POS System</span>
                    </h2>
                    <p class="text-gray-300 leading-relaxed mb-6">
                        Manage your entire business from one unified platform. Process transactions, track inventory in real-time, monitor staff performance, and generate detailed sales reports instantly.
                    </p>
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="rounded-full bg-blue-950 border border-blue-800 px-4 py-1.5 text-sm text-blue-300">💳 Payments</span>
                        <span class="rounded-full bg-blue-950 border border-blue-800 px-4 py-1.5 text-sm text-blue-300">📦 Inventory</span>
                        <span class="rounded-full bg-blue-950 border border-blue-800 px-4 py-1.5 text-sm text-blue-300">👥 Staff Mgmt</span>
                    </div>
                                      <Link
                        v-if="$page.props.auth?.user"
                        :href="dashboard()"
                        class="text-blue-400 font-bold hover:text-blue-300 transition-colors"
                    >
                        Go to Dashboard →
                    </Link>
                </div>
            </div>
        </section>

        <!-- ── FEATURES GRID ────────────────────────────────── -->
        <section class="py-24 px-6 bg-gradient-to-b from-transparent to-slate-900/50">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-14">
                    <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-3">
                        Powerful Capabilities
                    </p>

                    <h2 class="text-4xl font-black">
                        Packed with Features
                    </h2>
                </div>

                <div class="grid sm:grid-cols-2 gap-6">
                    <div
                        v-for="item in [
                            {
                                icon: '💳',
                                name: 'Multi-Payment Methods',
                                desc: 'Accept cash, cards, mobile payments, and online transfers all in one place.',
                                tag: 'Popular'
                            },
                            {
                                icon: '📊',
                                name: 'Advanced Analytics',
                                desc: 'Real-time sales dashboards with detailed reports and business insights.',
                                tag: null
                            },
                            {
                                icon: '📦',
                                name: 'Smart Inventory',
                                desc: 'Track stock levels, auto-reorder alerts, and multi-location management.',
                                tag: null
                            },
                            {
                                icon: '👥',
                                name: 'Staff Management',
                                desc: 'Employee profiles, shift scheduling, sales tracking, and performance metrics.',
                                tag: null
                            },
                        ]"
                        :key="item.name"
                        class="group relative flex gap-5 items-start rounded-2xl border border-white/5 bg-white/3 p-5 hover:border-blue-700/60 hover:bg-blue-950/30 transition-all duration-300"
                    >
                        <div
                            v-if="item.tag"
                            class="absolute top-3 right-3 bg-blue-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase"
                        >
                            {{ item.tag }}
                        </div>

                        <div
                            class="shrink-0 w-16 h-16 rounded-xl bg-gradient-to-br from-blue-900 to-blue-950 border border-blue-800/40 flex items-center justify-center text-3xl shadow-inner"
                        >
                            {{ item.icon }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-white text-sm leading-tight mb-2">
                                {{ item.name }}
                            </h3>

                            <p class="text-xs text-gray-400 leading-relaxed">
                                {{ item.desc }}
                            </p>
                        </div>
                    </div>
                </div>

                <p class="text-center text-xs text-gray-500 mt-6">
                    Everything you need to run your business efficiently
                </p>
            </div>
        </section>

        <!-- ── WHY DEMOPOS ────────────────────────────────── -->
        <section class="py-24 px-6 border-t border-blue-900/30">
            <div class="max-w-5xl mx-auto text-center mb-16">
                <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-3">
                    Why Choose DemoPOS
                </p>

                <h2 class="text-4xl sm:text-5xl font-black mb-6 leading-tight">
                    Built For<br />
                    <span class="text-blue-300">Modern Retailers</span>
                </h2>

                <p class="max-w-2xl mx-auto text-gray-300 leading-relaxed text-lg">
                    DemoPOS is a
                    <strong class="text-white">comprehensive point-of-sale solution</strong>
                    designed for
                    <strong class="text-white">
                        retail stores, restaurants, and small businesses
                    </strong>.
                    We provide
                    <strong class="text-blue-400">
                        powerful tools, easy operations
                    </strong>,
                    and exceptional support.
                </p>
            </div>

            <div class="max-w-5xl mx-auto grid sm:grid-cols-3 gap-6">
                <div
                    v-for="card in [
                        {
                            icon: '⚡',
                            title: 'Fast Setup',
                            body: 'Get your POS system up and running in minutes. No complicated configuration required.'
                        },
                        {
                            icon: '🔐',
                            title: 'Secure & Reliable',
                            body: 'Enterprise-grade security with data encryption, backups, and 99.9% uptime guarantee.'
                        },
                        {
                            icon: '📞',
                            title: '24/7 Support',
                            body: 'Dedicated support team ready to help you 24/7. Priority assistance for all clients.'
                        },
                    ]"
                    :key="card.title"
                    class="rounded-2xl border border-white/5 p-6 text-center hover:border-blue-700/50 transition-colors"
                    style="background: rgba(255,255,255,0.025)"
                >
                    <div class="text-5xl mb-5">
                        {{ card.icon }}
                    </div>

                    <h3 class="font-bold text-white mb-3">
                        {{ card.title }}
                    </h3>

                    <p class="text-sm text-gray-400 leading-relaxed">
                        {{ card.body }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ── CTA BANNER ──────────────────────────────── -->
        <section class="py-20 px-6 text-center relative overflow-hidden">
            <div
                class="absolute inset-0 pointer-events-none"
                style="background: linear-gradient(180deg, transparent, rgba(59,130,246,0.08), transparent);"
            ></div>

            <div class="relative z-10 max-w-3xl mx-auto">
                <p class="text-3xl sm:text-4xl font-black leading-tight text-white mb-6">
                    Ready to transform your retail business?<br />
                    <span class="text-blue-400">
                        Start your free trial today.
                    </span>
                </p>

                <Link
                    :href="login()"
                    class="inline-block bg-blue-600 text-white font-bold px-8 py-3 rounded-lg hover:bg-blue-500 transition-colors"
                >
                    Get Started for Free
                </Link>

                <p class="text-gray-400 text-sm uppercase tracking-widest mt-6">
                    DemoPOS · Modern Point of Sale System
                </p>
            </div>
        </section>

        <!-- ── FOOTER ─────────────────────────────────────── -->
        <footer class="border-t border-blue-900/30 px-6 py-8 text-center">
            <div class="flex items-center justify-center gap-3 mb-3">
                <div
                    class="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center"
                >
                    <svg
                        class="w-4 h-4 text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 6H6.28l-.31-1.243A1 1 0 005 4H3z"
                        />
                    </svg>
                </div>

                <span class="font-black tracking-widest text-white">
                    Demo<span class="text-blue-400">POS</span>
                </span>
            </div>

            <p class="text-xs text-gray-600">
                © 2026 DemoPOS. All rights reserved.
            </p>
        </footer>
    </div>
</template>

<style scoped>
.bg-white\/3 {
    background-color: rgba(255, 255, 255, 0.03);
}
</style>
