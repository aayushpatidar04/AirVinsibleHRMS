<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

defineProps({ canLogin: Boolean })

// Hero pipeline: cards drop in column by column (one orchestrated moment)
const pipeline = [
    { stage: 'Registered', tone: 'bg-indigo-500', cards: [
        { name: 'Riya Sharma', role: 'PHP Developer' },
        { name: 'Karan Mehta', role: 'UI Designer' },
    ] },
    { stage: 'Interview', tone: 'bg-amber-500', cards: [
        { name: 'Neha Verma', role: 'Round 2 of 3' },
        { name: 'Arjun Singh', role: 'Round 1 of 3' },
    ] },
    { stage: 'Offer', tone: 'bg-teal-500', cards: [
        { name: 'Sana Khan', role: 'Offer sent', final: true },
    ] },
]

const features = [
    { icon: '📱', title: 'QR registration', text: 'Print one QR code. Candidates scan it, fill in their details on their phone and land in your list.' },
    { icon: '🎙️', title: 'Interview rounds', text: 'Create as many rounds as the role needs, assign interviewers and record a result for each round.' },
    { icon: '💰', title: 'Salary offers', text: 'Offers follow rules for each profile type, so the amount is calculated, not typed from memory.' },
    { icon: '🗂️', title: 'Candidate records', text: 'Resume, round feedback and offer history stay on one profile that your whole team can open.' },
    { icon: '🔔', title: 'Status tracking', text: 'See who is waiting for a round, who has cleared it and who still needs an offer.' },
    { icon: '🔐', title: 'Role-based access', text: 'HR, interviewers and admins each see only the screens and actions they need.' },
]

const steps = [
    { title: 'Candidate scans the QR code', text: 'Registration opens on their phone. No account or app is needed.' },
    { title: 'HR reviews the profile', text: 'Shortlist candidates and assign them to the first interview round.' },
    { title: 'Interviewers score each round', text: 'Feedback is saved per round, so the next interviewer starts informed.' },
    { title: 'Offer goes out', text: 'The salary is calculated from the profile type and sent for approval.' },
]

const stats = [
    { value: '3', label: 'steps from scan to offer' },
    { value: 'Unlimited', label: 'interview rounds per role' },
    { value: '1', label: 'profile for every candidate' },
]

// Run once: reveal elements the first time they scroll into view
const vReveal = {
    mounted(el, { value }) {
        el.classList.add('opacity-0', 'translate-y-4', 'transition-all', 'duration-700')
        if (value) el.style.transitionDelay = `${value}ms`
        const io = new IntersectionObserver(([e]) => {
            if (!e.isIntersecting) return
            el.classList.remove('opacity-0', 'translate-y-4')
            io.disconnect()
        }, { threshold: 0.15 })
        io.observe(el)
    },
}
</script>

<template>
    <Head title="Airvincible HRMS — Hiring and payroll in one place" />

    <PublicLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-dots [mask-image:linear-gradient(to_bottom,black,transparent_85%)]" aria-hidden="true"></div>
            <div class="page-container relative grid items-center gap-14 py-16 lg:grid-cols-[1fr_1.1fr] lg:py-24">
                <div>
                    <h1 class="font-display text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">
                        Hire, interview and pay from one screen
                    </h1>
                    <p class="mt-6 max-w-md text-lg leading-relaxed text-ink-soft">
                        Airvincible HRMS takes a candidate from QR scan to signed offer, with every round and every
                        rupee recorded along the way.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link :href="route('login')" class="btn-primary !px-6 !py-3 !text-base">Log in to dashboard</Link>
                        <a href="#flow" class="btn-secondary !px-6 !py-3 !text-base">See how it works</a>
                    </div>
                </div>

                <!-- Pipeline board -->
                <div class="surface p-4 shadow-lift sm:p-5" aria-label="Example hiring pipeline">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="font-display font-bold">Hiring pipeline</p>
                        <span class="rounded-full bg-paper-warm px-2.5 py-1 text-xs font-medium text-ink-soft">5 candidates</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div v-for="(col, ci) in pipeline" :key="col.stage" class="rounded-xl bg-paper p-2.5">
                            <div class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold text-ink-soft">
                                <span class="h-2 w-2 rounded-full" :class="col.tone"></span>{{ col.stage }}
                            </div>
                            <div class="space-y-2">
                                <div v-for="(c, i) in col.cards" :key="c.name"
                                    class="animate-card-in rounded-lg border bg-white p-2.5"
                                    :class="c.final ? 'animate-ring-pulse border-teal-300' : 'border-indigo-100'"
                                    :style="{ animationDelay: `${ci * 450 + i * 180 + 300}ms` }">
                                    <p class="truncate text-xs font-semibold">{{ c.name }}</p>
                                    <p class="mt-0.5 truncate text-[11px]" :class="c.final ? 'font-semibold text-teal-600' : 'text-ink-mute'">{{ c.role }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex h-14 items-end gap-1.5 rounded-xl bg-paper px-3 pb-2" aria-hidden="true">
                        <span v-for="(h, i) in [30, 45, 38, 60, 52, 75, 68, 90]" :key="i"
                            class="w-full origin-bottom animate-bar-grow rounded-t bg-indigo-300 last:bg-indigo-600"
                            :style="{ height: h + '%', animationDelay: `${1700 + i * 70}ms` }"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="border-y border-indigo-100 bg-white">
            <dl class="page-container grid divide-y divide-indigo-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div v-for="s in stats" :key="s.label" class="px-2 py-6 sm:px-8">
                    <dt class="order-2 text-sm text-ink-soft">{{ s.label }}</dt>
                    <dd class="font-display text-3xl font-bold text-indigo-600">{{ s.value }}</dd>
                </div>
            </dl>
        </section>

        <!-- Features -->
        <section id="features" class="page-container scroll-mt-20 py-20">
            <div v-reveal class="max-w-xl">
                <h2 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Everything HR does before day one</h2>
                <p class="mt-4 text-ink-soft">Each module covers one part of hiring, and they all share the same candidate profile.</p>
            </div>
            <div class="mt-12 grid gap-x-12 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="(f, i) in features" :key="f.title" v-reveal="(i % 3) * 100" class="flex gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl">{{ f.icon }}</span>
                    <div>
                        <h3 class="font-display font-bold">{{ f.title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ f.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hiring flow -->
        <section id="flow" class="scroll-mt-20 bg-white py-20">
            <div class="page-container grid gap-12 lg:grid-cols-[1fr_1.2fr]">
                <div v-reveal class="lg:sticky lg:top-28 lg:self-start">
                    <h2 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">From scan to offer in four steps</h2>
                    <p class="mt-4 max-w-sm text-ink-soft">No spreadsheets passed between teams. Each step hands over to the next automatically.</p>
                </div>
                <ol class="relative space-y-8 border-l-2 border-indigo-100 pl-8">
                    <li v-for="(s, i) in steps" :key="s.title" v-reveal="i * 80" class="relative">
                        <span class="absolute -left-[3.05rem] flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 font-display text-sm font-bold text-white ring-4 ring-white">{{ i + 1 }}</span>
                        <h3 class="font-display text-lg font-bold">{{ s.title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ s.text }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Payroll / CTA -->
        <section id="payroll" class="page-container scroll-mt-20 py-20">
            <div v-reveal class="relative overflow-hidden rounded-3xl bg-indigo-600 px-6 py-14 text-center text-white sm:px-14">
                <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-indigo-500/60" aria-hidden="true"></div>
                <div class="absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-indigo-700/50" aria-hidden="true"></div>
                <div class="relative">
                    <h2 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Offers that match the profile</h2>
                    <p class="mx-auto mt-4 max-w-lg text-indigo-100">
                        Salary rules are set once per profile type. Every offer is calculated the same way and kept on the candidate's record.
                    </p>
                    <Link :href="route('login')"
                        class="mt-8 inline-flex items-center justify-center rounded-xl bg-white px-7 py-3 text-sm font-bold text-indigo-700 shadow-lift transition hover:bg-indigo-50 active:scale-[.98]">
                        Log in to dashboard
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>