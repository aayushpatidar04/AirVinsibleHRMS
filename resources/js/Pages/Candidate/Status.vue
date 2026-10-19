<script setup>
import { computed } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    candidate: Object,
    progress_history: Array,
    status_labels: Object,
    final_labels: Object,
})

const statusColor = {
    new: 'bg-sky-100 text-sky-700',
    in_progress: 'bg-amber-100 text-amber-700',
    round_completed: 'bg-violet-100 text-violet-700',
    all_rounds_cleared: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-600',
}

const finalColor = {
    pending: 'bg-slate-100 text-slate-600',
    selected: 'bg-emerald-100 text-emerald-700',
    not_selected: 'bg-red-100 text-red-600',
}

// Look of each interview round in the timeline
const roundLook = {
    completed: { dot: 'bg-emerald-100 text-emerald-700 ring-emerald-50', pill: 'bg-emerald-100 text-emerald-700' },
    rejected: { dot: 'bg-red-100 text-red-600 ring-red-50', pill: 'bg-red-100 text-red-600' },
}
const fallbackLook = { dot: 'bg-amber-100 text-amber-700 ring-amber-50', pill: 'bg-amber-100 text-amber-700' }
const look = (status) => roundLook[status] ?? fallbackLook

const initials = computed(() =>
    (props.candidate.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase()
)
</script>

<template>
    <Head :title="`Status – ${candidate.name}`" />

    <GuestLayout narrow>
        <div class="text-center">
            <h1 class="font-display text-3xl font-bold tracking-tight">Interview Status</h1>
            <p class="mt-1.5 text-sm text-ink-soft">{{ candidate.name }}</p>
        </div>

        <!-- Candidate card -->
        <div class="surface mt-7 animate-card-in overflow-hidden shadow-lift">
            <div class="flex items-center gap-4 border-b border-indigo-100 bg-indigo-50/60 px-6 py-5">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 font-display text-lg font-bold text-white">
                    {{ initials }}
                </span>
                <div class="min-w-0">
                    <p class="truncate font-display text-lg font-bold">{{ candidate.name }}</p>
                    <p class="truncate text-sm text-ink-soft">{{ candidate.position }}, {{ candidate.branch }}</p>
                    <p class="mt-0.5 text-xs text-ink-mute">
                        {{ candidate.profile }}. Registered {{ candidate.registered_at }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 p-6">
                <div class="rounded-xl bg-paper p-3.5 text-center">
                    <p class="mb-1.5 text-xs font-medium text-ink-mute">Current Status</p>
                    <span :class="['inline-block rounded-full px-3 py-1 text-xs font-semibold', statusColor[candidate.current_status] ?? 'bg-slate-100 text-slate-600']">
                        {{ status_labels[candidate.current_status] ?? candidate.current_status }}
                    </span>
                </div>
                <div class="rounded-xl bg-paper p-3.5 text-center">
                    <p class="mb-1.5 text-xs font-medium text-ink-mute">Final Decision</p>
                    <span :class="['inline-block rounded-full px-3 py-1 text-xs font-semibold', finalColor[candidate.final_status] ?? 'bg-slate-100 text-slate-600']">
                        {{ final_labels[candidate.final_status] ?? candidate.final_status }}
                    </span>
                </div>
                <div v-if="candidate.average_rating" class="col-span-2 rounded-xl bg-amber-50 p-3.5 text-center">
                    <p class="mb-0.5 text-xs font-medium text-ink-mute">Average Rating</p>
                    <p class="font-display text-2xl font-bold text-amber-600">{{ candidate.average_rating }} ★</p>
                </div>
            </div>

            <!-- Selected message -->
            <div v-if="candidate.final_status === 'selected'"
                class="mx-6 mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                <span class="mx-auto flex h-12 w-12 animate-ring-pulse items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <p class="mt-3 font-display font-bold text-emerald-800">Congratulations! You've been selected.</p>
                <p class="mt-1 text-sm text-emerald-700">Our HR team will contact you with the offer details.</p>
            </div>

            <!-- Not selected message -->
            <div v-if="candidate.final_status === 'not_selected'"
                class="mx-6 mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-500 shadow-soft">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                </span>
                <p class="mt-3 font-display font-bold text-slate-800">Thank you for your interest.</p>
                <p class="mt-1 text-sm text-slate-600">We appreciate your time. Better opportunities may come your way.</p>
            </div>
        </div>

        <!-- Round history -->
        <section class="surface mt-5 overflow-hidden">
            <div class="border-b border-indigo-100 px-6 py-4">
                <h2 class="font-display font-bold">Interview Rounds</h2>
            </div>

            <ol v-if="progress_history?.length" class="relative px-6 py-5">
                <li v-for="(p, i) in progress_history" :key="i"
                    class="relative animate-fade-up pb-6 pl-12 last:pb-0"
                    :style="{ animationDelay: `${200 + i * 90}ms` }">
                    <!-- connecting line -->
                    <span v-if="i < progress_history.length - 1"
                        class="absolute left-4 top-9 -ml-px h-[calc(100%-2.25rem)] w-0.5 bg-indigo-100" aria-hidden="true" />
                    <span :class="[look(p.status).dot, 'absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold ring-4']">
                        {{ i + 1 }}
                    </span>

                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ p.round }}</p>
                            <p class="text-xs text-ink-mute">{{ p.date ?? 'Scheduled' }}</p>
                        </div>
                        <span :class="[look(p.status).pill, 'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold']">
                            {{ p.status.charAt(0).toUpperCase() + p.status.slice(1) }}
                        </span>
                    </div>

                    <div v-if="p.status === 'completed' && p.rating" class="mt-2 flex gap-0.5" :aria-label="`Rated ${p.rating} out of 5`">
                        <span v-for="n in 5" :key="n" :class="n <= p.rating ? 'text-amber-400' : 'text-slate-200'" class="text-lg leading-none">★</span>
                    </div>
                    <p v-if="p.status === 'rejected' && p.feedback" class="mt-2 rounded-lg bg-paper px-3 py-2 text-xs text-ink-soft">
                        {{ p.feedback }}
                    </p>
                </li>
            </ol>

            <p v-else class="px-6 py-10 text-center text-sm text-ink-mute">
                No interview rounds completed yet.
            </p>
        </section>

        <p class="mt-6 px-4 text-center text-xs text-ink-mute">
            For any queries please contact your recruiter or visit the branch.
        </p>
    </GuestLayout>
</template>