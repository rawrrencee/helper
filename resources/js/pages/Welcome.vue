<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Button from 'primevue/button';
import { dashboard, login } from '@/routes';
</script>

<template>
    <Head title="Welcome" />

    <div class="relative min-h-screen overflow-hidden bg-background text-foreground">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-40 -top-40 h-[500px] w-[500px] rounded-full bg-gradient-to-br from-violet-500/20 to-fuchsia-500/20 blur-3xl" />
            <div class="absolute -bottom-40 -right-40 h-[600px] w-[600px] rounded-full bg-gradient-to-tl from-blue-500/20 to-cyan-500/20 blur-3xl" />
            <div class="absolute left-1/2 top-1/2 h-[400px] w-[400px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-r from-pink-500/10 to-orange-500/10 blur-3xl" />
        </div>

        <div class="relative z-10">
            <header class="flex items-center justify-between px-6 py-4 lg:px-8">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="size-7 text-violet-600 dark:text-violet-400" />
                    <span class="bg-gradient-to-r from-violet-600 to-fuchsia-600 bg-clip-text text-xl font-bold text-transparent dark:from-violet-400 dark:to-fuchsia-400">
                        Helper
                    </span>
                </div>
                <nav>
                    <Link v-if="$page.props.auth.user" :href="dashboard()">
                        <Button label="Dashboard" severity="secondary" />
                    </Link>
                    <Link v-else :href="login()">
                        <Button label="Log in" severity="secondary" />
                    </Link>
                </nav>
            </header>

            <main class="flex min-h-[calc(100vh-140px)] items-center justify-center px-6">
                <div class="max-w-3xl text-center">
                    <div class="mb-8 inline-flex items-center gap-2 rounded-full border border-violet-500/20 bg-violet-500/10 px-4 py-1.5 text-sm font-medium text-violet-700 dark:text-violet-300">
                        <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-violet-500" />
                        Your Care Companion
                    </div>

                    <h1 class="text-5xl font-bold tracking-tight sm:text-6xl lg:text-7xl">
                        <span class="block">Everything you</span>
                        <span class="bg-gradient-to-r from-violet-600 via-fuchsia-600 to-pink-600 bg-clip-text text-transparent dark:from-violet-400 dark:via-fuchsia-400 dark:to-pink-400">
                            need to care
                        </span>
                        <span class="block">in one place</span>
                    </h1>

                    <p class="mx-auto mt-8 max-w-xl text-lg leading-relaxed text-muted-foreground">
                        Patients, medications, schedules, and more — your daily caregiving companion.
                    </p>

                    <div class="mt-12 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                        <Link v-if="$page.props.auth.user" :href="dashboard()">
                            <Button label="Go to Dashboard" size="large" class="!bg-gradient-to-r !from-violet-600 !to-fuchsia-600 !border-0 !text-white" />
                        </Link>
                        <Link v-else :href="login()">
                            <Button label="Get Started" size="large" class="!bg-gradient-to-r !from-violet-600 !to-fuchsia-600 !border-0 !text-white" />
                        </Link>
                    </div>

                    <div class="mt-20 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="group rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm transition-colors hover:border-violet-500/30 hover:bg-violet-500/5">
                            <div class="mb-2 text-2xl">🩺</div>
                            <p class="text-sm font-medium">Patients</p>
                        </div>
                        <div class="group rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm transition-colors hover:border-fuchsia-500/30 hover:bg-fuchsia-500/5">
                            <div class="mb-2 text-2xl">💊</div>
                            <p class="text-sm font-medium">Medications</p>
                        </div>
                        <div class="group rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm transition-colors hover:border-pink-500/30 hover:bg-pink-500/5">
                            <div class="mb-2 text-2xl">📋</div>
                            <p class="text-sm font-medium">Schedules</p>
                        </div>
                        <div class="group rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm transition-colors hover:border-blue-500/30 hover:bg-blue-500/5">
                            <div class="mb-2 text-2xl">📄</div>
                            <p class="text-sm font-medium">Documents</p>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="relative border-t border-white/10 px-6 py-6 text-center text-sm text-muted-foreground">
                Helper &copy; {{ new Date().getFullYear() }}
            </footer>
        </div>
    </div>
</template>
