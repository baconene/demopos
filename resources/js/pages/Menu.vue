<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ id: string }>();
const id = props.id || '1';
</script>

<template>
    <Head title="Bypass Grill Menu" />

    <div class="mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[320px_minmax(0,1fr)]">
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800/80 dark:bg-slate-950">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-orange-500 to-rose-500 text-white shadow-lg shadow-orange-500/20">
                        <span class="text-3xl font-black tracking-tight">BG</span>
                    </div>
                    <div>
                        <p class="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Bypass Grill</p>
                        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Menu #{{ id }}</h1>
                    </div>
                </div>

                <div class="mt-8 rounded-3xl bg-slate-100 p-4 dark:bg-slate-900">
                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm shadow-slate-900/5 dark:bg-slate-950">
                        <div class="relative h-44 overflow-hidden bg-gradient-to-br from-orange-400 via-rose-400 to-fuchsia-500">
                            <svg viewBox="0 0 512 512" class="absolute inset-0 h-full w-full opacity-10" xmlns="http://www.w3.org/2000/svg">
                                <path d="M256 16C132.288 16 32 116.288 32 240c0 123.712 100.288 224 224 224s224-100.288 224-224C480 116.288 379.712 16 256 16z" fill="white"/>
                                <path d="M186 184c0-22.091 17.909-40 40-40h60c22.091 0 40 17.909 40 40v16c0 22.091-17.909 40-40 40h-60c-22.091 0-40-17.909-40-40v-16zm-40 64c0-13.255 10.745-24 24-24h192c13.255 0 24 10.745 24 24v16c0 13.255-10.745 24-24 24H170c-13.255 0-24-10.745-24-24v-16z" fill="white"/>
                            </svg>
                            <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                                <p class="text-sm uppercase tracking-[0.24em] text-white/80">Featured</p>
                                <h2 class="text-3xl font-semibold">Bypass Grill Special</h2>
                            </div>
                        </div>
                        <div class="space-y-4 p-5">
                            <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">
                                A small taste of the Bypass Grill experience—smoky, spicy, and designed to be shared. Enjoy burgers, sides, and drinks curated for this sample menu.
                            </p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-3xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-800/80 dark:bg-slate-900">
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Hours</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">11am — 10pm</p>
                                </div>
                                <div class="rounded-3xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-800/80 dark:bg-slate-900">
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Location</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">Downtown Bypass</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800/80 dark:bg-slate-950">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.26em] text-slate-500 dark:text-slate-400">Menu Preview</p>
                        <h2 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">What’s on the grill</h2>
                    </div>
                    <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 dark:bg-slate-900 dark:text-slate-300">ID {id}</div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-4 rounded-3xl bg-slate-100 p-5 dark:bg-slate-900">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Flame Kissed Burgers</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">A range of juicy burgers with house sauces and toasted brioche buns.</p>
                        </div>
                        <ul class="space-y-3">
                            <li class="rounded-3xl bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-950 dark:shadow-none">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">The Bypass Classic</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Beef patty, cheddar, pickled onion, secret sauce.</p>
                                    </div>
                                    <span class="text-sm font-semibold text-orange-500">$14</span>
                                </div>
                            </li>
                            <li class="rounded-3xl bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-950 dark:shadow-none">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">Smoky Bird</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Charred chicken, slaw, chipotle mayo, house pickle.</p>
                                    </div>
                                    <span class="text-sm font-semibold text-orange-500">$13</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="space-y-4 rounded-3xl bg-slate-100 p-5 dark:bg-slate-900">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Sides & Drinks</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Perfect complements to the main course.</p>
                        </div>
                        <ul class="space-y-3">
                            <li class="rounded-3xl bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-950 dark:shadow-none">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">Crispy Fries</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Hand-cut potatoes with smoked paprika salt.</p>
                                    </div>
                                    <span class="text-sm font-semibold text-orange-500">$5</span>
                                </div>
                            </li>
                            <li class="rounded-3xl bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-950 dark:shadow-none">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">House Lemonade</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Fresh lemon, mint, and a hint of ginger.</p>
                                    </div>
                                    <span class="text-sm font-semibold text-orange-500">$4</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
