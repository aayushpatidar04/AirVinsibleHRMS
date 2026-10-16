<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'

defineProps({
    title: String,
    subtitle: String,
    icon: { type: String, default: 'lock' },
    aside: { type: Boolean, default: false }, // show the intro panel beside the card (lg screens)
})

const icons = {
    lock: 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
    mail: 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
    key: 'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z',
    user: 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z',
    shield: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
}
const points = [
    'Review candidates who registered by QR code',
    'Record feedback for every interview round',
    'Send salary offers calculated from the profile type',
]
</script>

<template>
    <PublicLayout>
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-dots [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]" aria-hidden="true"></div>

            <div class="page-container relative grid items-center gap-12 py-12 sm:py-16"
                :class="aside ? 'lg:grid-cols-[1fr_28rem] lg:gap-20' : 'justify-items-center'">

                <div v-if="aside" class="hidden lg:block">
                    <h2 class="font-display text-4xl font-extrabold leading-tight tracking-tight">Your hiring desk is ready when you are</h2>
                    <ul class="mt-8 space-y-4">
                        <li v-for="p in points" :key="p" class="flex items-start gap-3 text-ink-soft">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" /></svg>
                            </span>
                            {{ p }}
                        </li>
                    </ul>
                </div>

                <div class="surface w-full max-w-md animate-card-in p-7 shadow-lift sm:p-9">
                    <div class="mb-7">
                        <span class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                            <slot name="icon">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path :d="icons[icon]" /></svg>
                            </slot>
                        </span>
                        <h1 class="font-display text-2xl font-bold tracking-tight">{{ title }}</h1>
                        <p v-if="subtitle" class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ subtitle }}</p>
                    </div>
                    <slot />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>