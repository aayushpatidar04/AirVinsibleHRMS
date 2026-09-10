<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import FlashMessage from '@/Components/Common/FlashMessage.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const sideOpen = ref(false)

const nav = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Branches', href: '/admin/branches', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { name: 'Employees', href: '/admin/employees', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { name: 'Int. Rounds', href: '/admin/rounds', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
    { name: 'Forms', href: '/admin/forms', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'QR Codes', href: '/admin/qrcodes', icon: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z' },
    { name: 'Candidates', href: '/recruitment/candidates', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
]

const isActive = (href) => {
    const path = usePage().url?.split('?')[0] ?? ''
    if (href === '/admin/dashboard') return path === href
    return path.startsWith(href)
}
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex">
        <FlashMessage />

        <!-- Sidebar -->
        <aside class="hidden md:flex md:flex-col md:w-60 md:fixed md:inset-y-0 bg-indigo-800 z-20">
            <div class="flex items-center gap-2 px-5 py-5 border-b border-indigo-700/50">
                <div class="h-8 w-8 rounded-lg bg-white/20 flex items-center justify-center text-white font-bold">⚡
                </div>
                <div>
                    <p class="text-white font-bold text-sm">HRMS</p>
                    <p class="text-indigo-300 text-xs">Admin Panel</p>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <Link v-for="item in nav" :key="item.name" :href="item.href" :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all',
                    isActive(item.href) ? 'bg-white/20 text-white' : 'text-indigo-200 hover:bg-white/10 hover:text-white']">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="px-4 py-4 border-t border-indigo-700/50">
                <div class="flex items-center gap-3 mb-3">
                    <div
                        class="h-9 w-9 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-sm">
                        {{ user?.first_name?.[0] }}{{ user?.last_name?.[0] }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-sm font-medium truncate">{{ user?.first_name }} {{ user?.last_name }}
                        </p>
                        <p class="text-indigo-300 text-xs">Administrator</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link href="/profile"
                        class="flex-1 text-center text-xs text-indigo-300 hover:text-white bg-white/10 hover:bg-white/20 px-2 py-1.5 rounded-lg transition">
                        Profile</Link>
                    <Link href="/logout" method="post" as="button"
                        class="flex-1 text-center text-xs text-indigo-300 hover:text-white bg-white/10 hover:bg-white/20 px-2 py-1.5 rounded-lg transition">
                        Sign out</Link>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 md:pl-60 flex flex-col">
            <header class="sticky top-0 z-10 bg-white border-b border-gray-200 h-14 flex items-center px-5">
                <button @click="sideOpen = !sideOpen" class="md:hidden text-gray-500 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex-1" />
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-gray-600 hidden sm:inline">{{ user?.first_name }} {{ user?.last_name }}</span>
                    <Link href="/profile" class="text-indigo-600 hover:underline text-sm">Profile</Link>
                </div>
            </header>
            <main class="flex-1 p-5 sm:p-7">
                <slot />
            </main>
        </div>
    </div>
</template>