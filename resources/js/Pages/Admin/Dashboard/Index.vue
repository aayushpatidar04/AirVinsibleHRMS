<script setup>
import { computed, onMounted, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Common/StatCard.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({ stats: Object })

const page = usePage()
const firstName = computed(() => page.props.auth?.user?.first_name ?? '')
const greeting = computed(() => {
    const h = new Date().getHours()
    return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening'
})
const today = new Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

// Bars start empty and grow once the page is mounted
const ready = ref(false)
onMounted(() => requestAnimationFrame(() => (ready.value = true)))
const pct = (count) => (props.stats.total_candidates > 0 ? (count / props.stats.total_candidates) * 100 : 0)

const th = 'pb-3 text-xs font-semibold text-ink-mute'
const profiles = computed(() => [
    { label: 'Advisor / Executive', note: 'Salary range captured in OPS, finalised in HR', count: props.stats.candidates_by_profile.advisory_executive, box: 'bg-amber-50 border-amber-100', title: 'text-amber-800', sub: 'text-amber-600', num: 'text-amber-700' },
    { label: 'TL / QA / AM / OM', note: 'Salary mandatory in HR round', count: props.stats.candidates_by_profile.leadership, box: 'bg-indigo-50 border-indigo-100', title: 'text-indigo-800', sub: 'text-indigo-600', num: 'text-indigo-700' },
    { label: 'Other Profiles', note: 'Standard process', count: props.stats.candidates_by_profile.other, box: 'bg-slate-50 border-slate-100', title: 'text-slate-800', sub: 'text-slate-500', num: 'text-slate-700' },
])
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ greeting }}<span v-if="firstName">, {{ firstName }}</span>
                    </h1>
                    <p class="mt-1 text-sm text-ink-soft">Here is where hiring stands today.</p>
                </div>
                <span class="text-sm text-ink-mute">{{ today }}</span>
            </div>

            <!-- Top stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <StatCard title="Total Candidates" :value="stats.total_candidates" icon="👥" color="indigo" :delay="0" />
                <StatCard title="Branches" :value="stats.total_branches" icon="🏢" color="blue" :delay="60" />
                <StatCard title="Interviewers" :value="stats.total_interviewers" icon="🎙️" color="teal" :delay="120" />
                <StatCard title="Pending Interviews" :value="stats.pending_interviews" icon="⏳" color="yellow" :delay="180" />
                <StatCard title="Selected" :value="stats.selected_candidates" icon="✅" color="green" :delay="240"
                    class="col-span-2 lg:col-span-1" :subtitle="`${stats.selection_rate}% selection rate`" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Candidate status -->
                <section class="surface p-6">
                    <h2 class="font-display text-base font-bold">Candidate Status</h2>
                    <div class="mt-5 space-y-3.5">
                        <div v-for="(count, status, i) in stats.candidates_by_status" :key="status" class="flex items-center gap-3">
                            <StatusBadge :status="status" class="w-36 shrink-0" />
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-paper-warm">
                                <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400 transition-[width] duration-1000 ease-out"
                                    :style="{ width: ready ? pct(count) + '%' : '0%', transitionDelay: `${i * 70}ms` }" />
                            </div>
                            <span class="w-8 text-right text-sm font-semibold text-ink">{{ count }}</span>
                        </div>
                    </div>
                </section>

                <!-- Profile category -->
                <section class="surface p-6">
                    <h2 class="font-display text-base font-bold">Profile Category</h2>
                    <div class="mt-5 space-y-3">
                        <div v-for="p in profiles" :key="p.label"
                            :class="[p.box, 'flex items-center justify-between gap-4 rounded-xl border p-3.5']">
                            <div>
                                <p :class="[p.title, 'text-sm font-semibold']">{{ p.label }}</p>
                                <p :class="[p.sub, 'mt-0.5 text-xs']">{{ p.note }}</p>
                            </div>
                            <span :class="[p.num, 'font-display text-2xl font-bold']">{{ p.count }}</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Round stats -->
            <section class="surface p-6">
                <h2 class="font-display text-base font-bold">Interview Rounds Overview</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-indigo-100 text-left">
                                <th :class="th">Round</th>
                                <th :class="[th, 'text-center']">Total</th>
                                <th :class="[th, 'text-center']">Pending</th>
                                <th :class="[th, 'text-center']">Completed</th>
                                <th :class="[th, 'text-center']">Rejected</th>
                                <th :class="[th, 'text-center']">Avg Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in stats.round_stats" :key="r.id"
                                class="border-b border-indigo-50 transition-colors last:border-0 hover:bg-paper">
                                <td class="py-3.5 font-semibold">{{ r.name }}</td>
                                <td class="py-3.5 text-center text-ink-soft">{{ r.stats.total }}</td>
                                <td class="py-3.5 text-center font-semibold text-amber-600">{{ r.stats.pending }}</td>
                                <td class="py-3.5 text-center font-semibold text-emerald-600">{{ r.stats.completed }}</td>
                                <td class="py-3.5 text-center font-semibold text-red-600">{{ r.stats.rejected }}</td>
                                <td class="py-3.5 text-center font-semibold text-indigo-600">
                                    {{ r.stats.avg_rating > 0 ? r.stats.avg_rating + ' ★' : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent candidates -->
            <section class="surface p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-base font-bold">Recent Candidates</h2>
                    <Link :href="route('recruitment.candidates.index')"
                        class="group inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        View all
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead>
                            <tr class="border-b border-indigo-100 text-left">
                                <th :class="th">Name</th>
                                <th :class="th">Position</th>
                                <th :class="th">Profile</th>
                                <th :class="th">Branch</th>
                                <th :class="th">Status</th>
                                <th :class="th">Round</th>
                                <th :class="th">Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in stats.recent_candidates" :key="c.id"
                                class="border-b border-indigo-50 transition-colors last:border-0 hover:bg-paper">
                                <td class="py-3.5 pr-3">
                                    <Link :href="route('recruitment.candidates.show', c.id)"
                                        class="font-semibold text-indigo-600 hover:text-indigo-800">{{ c.name }}</Link>
                                </td>
                                <td class="py-3.5 pr-3 text-ink-soft">{{ c.position }}</td>
                                <td class="py-3.5 pr-3 text-xs text-ink-mute">{{ c.profile }}</td>
                                <td class="py-3.5 pr-3 text-ink-soft">{{ c.branch }}</td>
                                <td class="py-3.5 pr-3"><StatusBadge :status="c.status" /></td>
                                <td class="py-3.5 pr-3 text-ink-mute">{{ c.round ?? '—' }}</td>
                                <td class="py-3.5 text-ink-mute">{{ c.registered }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>