<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import FlashMessage from '@/Components/Common/FlashMessage.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)

const nav = [
    { name: 'Dashboard', href: route('interviewer.dashboard'), icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'My Candidates', href: route('interviewer.candidates.index'), icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
]
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <FlashMessage />

        <div class="hidden md:fixed md:inset-y-0 md:flex md:w-60 md:flex-col">
            <div class="flex flex-col flex-grow bg-teal-700 overflow-y-auto">
                <div class="flex items-center flex-shrink-0 px-4 py-5">
                    <span class="text-white text-xl font-bold">⚡ HRMS</span>
                    <span class="ml-2 text-teal-300 text-xs">Interviewer</span>
                </div>
                <nav class="flex-1 px-2 space-y-1">
                    <Link v-for="item in nav" :key="item.name" :href="item.href" :class="[
                        $page.url.includes('/interviewer/' + item.name.toLowerCase().replace(' ', '-'))
                            ? 'bg-teal-800 text-white'
                            : 'text-teal-100 hover:bg-teal-600',
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition'
                    ]">
                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </nav>
                <div class="p-4 border-t border-teal-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="h-9 w-9 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-sm">
                            {{ user?.first_name?.[0] }}{{ user?.last_name?.[0] }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-white text-sm font-medium truncate">{{ user?.first_name }} {{ user?.last_name
                                }}
                            </p>
                            <p class="text-teal-300 text-xs">Interviewer</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link href="/profile"
                            class="flex-1 text-center text-xs text-teal-300 hover:text-white bg-white/10 hover:bg-white/20 px-2 py-1.5 rounded-lg transition">
                            Profile</Link>
                        <Link href="/logout" method="post" as="button"
                            class="flex-1 text-center text-xs text-teal-300 hover:text-white bg-white/10 hover:bg-white/20 px-2 py-1.5 rounded-lg transition">
                            Sign out</Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:pl-60">
            <div class="sticky top-0 z-10 bg-white shadow-sm h-14 flex items-center px-6 justify-between">
                <span class="font-semibold text-teal-700">Interviewer Panel</span>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600">{{ user?.first_name }}</span>
                    <Link :href="route('profile.edit')" class="text-sm text-teal-600 hover:underline">Profile</Link>
                </div>
            </div>
            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>