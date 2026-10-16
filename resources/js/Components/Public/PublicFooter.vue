<script setup>
import { Link } from '@inertiajs/vue3'

const year = new Date().getFullYear()
const columns = [
    { title: 'Product', items: [
        { label: 'Features', href: '/#features' },
        { label: 'Hiring flow', href: '/#flow' },
        { label: 'Payroll', href: '/#payroll' },
    ] },
    { title: 'Modules', items: [
        { label: 'QR registration', href: '/#features' },
        { label: 'Interview rounds', href: '/#flow' },
        { label: 'Salary offers', href: '/#payroll' },
    ] },
]
</script>

<template>
    <footer class="border-t border-indigo-100 bg-white">
        <div class="page-container py-12 grid gap-10 md:grid-cols-[1.5fr_1fr_1fr]">
            <div>
                <Link href="/" class="flex items-center gap-2.5">
                    <img src="/assets/images/logo.png" alt="Airvincible HRMS" class="h-16 w-auto" />
                    <!-- <span class="font-display text-lg font-bold tracking-tight">Airvincible HRMS</span> -->
                </Link>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-soft">
                    One place to register candidates, run interview rounds and issue salary offers.
                </p>
            </div>

            <div v-for="col in columns" :key="col.title">
                <p class="text-sm font-semibold">{{ col.title }}</p>
                <ul class="mt-4 space-y-2.5">
                    <li v-for="i in col.items" :key="i.label">
                        <a :href="i.href" class="text-sm text-ink-soft hover:text-indigo-600 transition">{{ i.label }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-indigo-100">
            <div class="page-container flex flex-col gap-2 py-5 text-xs text-ink-mute sm:flex-row sm:justify-between">
                <p>© {{ year }} Airvincible HRMS. All rights reserved.</p>
                <Link :href="route('login')" class="hover:text-indigo-600 transition">Staff login</Link>
            </div>
        </div>
    </footer>
</template>