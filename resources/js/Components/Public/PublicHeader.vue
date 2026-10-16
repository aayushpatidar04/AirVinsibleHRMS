<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'

const open = ref(false)
const scrolled = ref(false)
const links = [
    { label: 'Features', href: '/#features' },
    { label: 'Hiring flow', href: '/#flow' },
    { label: 'Payroll', href: '/#payroll' },
]

const onScroll = () => (scrolled.value = window.scrollY > 8)
onMounted(() => { onScroll(); window.addEventListener('scroll', onScroll, { passive: true }) })
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
    <header class="sticky top-0 z-40 transition-all duration-300"
        :class="scrolled ? 'bg-white/80 backdrop-blur-md border-b border-indigo-100 shadow-sm' : 'bg-transparent border-b border-transparent'">
        <div class="page-container flex h-16 items-center justify-between">
            <Link href="/" class="flex items-center gap-2.5">
                <img src="/assets/images/logo.png" alt="Airvincible HRMS" class="h-16 w-auto" />
                <!-- <span class="font-display text-lg font-bold tracking-tight">Airvincible <span class="text-indigo-600">HRMS</span></span> -->
            </Link>

            <nav class="hidden md:flex items-center gap-8">
                <a v-for="l in links" :key="l.label" :href="l.href"
                    class="text-sm font-medium text-ink-soft hover:text-indigo-600 transition">{{ l.label }}</a>
            </nav>

            <div class="hidden md:block">
                <Link :href="route('login')" class="btn-primary">Log in</Link>
            </div>

            <button class="md:hidden p-2 -mr-2 rounded-lg text-ink" :aria-expanded="open" aria-label="Toggle menu"
                @click="open = !open">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path v-if="!open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    <path v-else stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="open" class="md:hidden bg-white border-b border-indigo-100 shadow-soft">
                <div class="page-container py-4 flex flex-col gap-1">
                    <a v-for="l in links" :key="l.label" :href="l.href" @click="open = false"
                        class="rounded-lg px-3 py-2.5 text-sm font-medium text-ink-soft hover:bg-paper-warm">{{ l.label }}</a>
                    <Link :href="route('login')" class="btn-primary mt-2">Log in</Link>
                </div>
            </div>
        </Transition>
    </header>
</template>