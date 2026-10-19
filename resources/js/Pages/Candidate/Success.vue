<script setup>
// Candidate/Success.vue
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'

defineProps({ candidate_id: Number, name: String, position: String, branch: String })

const steps = [
    'Our team will review your profile',
    'An interviewer will be assigned to you shortly',
    'Your interview will begin at the venue',
    "You'll be notified of the outcome after all rounds",
]
</script>

<template>
    <Head title="Registration Successful – Airvincible HRMS" />

    <GuestLayout narrow>
        <div class="surface animate-card-in p-8 text-center shadow-lift sm:p-10">
            <!-- Animated check -->
            <svg class="mx-auto h-20 w-20" viewBox="0 0 80 80" fill="none" aria-hidden="true">
                <circle cx="40" cy="40" r="36" class="text-emerald-100" fill="currentColor" />
                <circle cx="40" cy="40" r="36" class="draw-circle text-emerald-500" stroke="currentColor"
                    stroke-width="4" stroke-linecap="round" />
                <path d="M26 41l10 10 18-21" class="draw-check text-emerald-600" stroke="currentColor" stroke-width="5"
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>

            <h1 class="mt-6 font-display text-2xl font-bold tracking-tight sm:text-3xl">Registration Successful!</h1>
            <p class="mt-3 leading-relaxed text-ink-soft">
                Thank you, <strong class="text-ink">{{ name }}</strong>! Your registration for
                <strong class="text-ink">{{ position }}</strong> at <strong class="text-ink">{{ branch }}</strong>
                has been received.
            </p>

            <div class="mt-7 rounded-xl border border-indigo-100 bg-indigo-50/60 p-5 text-left">
                <p class="font-display text-sm font-bold text-indigo-900">What happens next?</p>
                <ol class="mt-4 space-y-3.5">
                    <li v-for="(step, i) in steps" :key="step" class="flex animate-fade-up items-start gap-3"
                        :style="{ animationDelay: `${900 + i * 120}ms` }">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                            {{ i + 1 }}
                        </span>
                        <span class="pt-0.5 text-sm text-ink-soft">{{ step }}</span>
                    </li>
                </ol>
            </div>

            <p class="mt-7 inline-flex items-center gap-2 rounded-full bg-paper-warm px-4 py-1.5 text-xs text-ink-soft">
                Your registration ID
                <strong class="font-display text-sm text-ink">#{{ candidate_id }}</strong>
            </p>
        </div>
    </GuestLayout>
</template>

<style scoped>
.draw-circle {
    stroke-dasharray: 227;
    stroke-dashoffset: 227;
    transform: rotate(-90deg);
    transform-origin: center;
    animation: draw 0.7s cubic-bezier(0.4, 0, 0.2, 1) 0.2s forwards;
}

.draw-check {
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: draw 0.4s ease-out 0.8s forwards;
}

@keyframes draw {
    to {
        stroke-dashoffset: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .draw-circle,
    .draw-check {
        stroke-dashoffset: 0;
        animation: none;
    }
}
</style>