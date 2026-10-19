<script setup>
import { computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Common/StatCard.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    upcomingInterviews: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const firstName = computed(() => page.props.auth?.user?.first_name ?? "");
const greeting = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? "Good morning" : h < 17 ? "Good afternoon" : "Good evening";
});

// Stats arrive as { some_key: number }, so label, icon and colour are derived from the key
const colors = ["indigo", "teal", "yellow", "green", "blue", "purple"];
const iconFor = (key) => {
    if (key.includes("candidate")) return "👥";
    if (key.includes("interview")) return "🎙️";
    if (key.includes("pending")) return "⏳";
    if (key.includes("select") || key.includes("complete")) return "✅";
    if (key.includes("reject")) return "🚫";
    return "📊";
};
const label = (key) => {
    const text = key.replaceAll("_", " ");
    return text.charAt(0).toUpperCase() + text.slice(1);
};

const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
const dayDiff = (iso) =>
    Math.round((startOfDay(new Date(iso)) - startOfDay(new Date())) / 86400000);

const dayLabel = (iso) => {
    const diff = dayDiff(iso);
    if (diff === 0) return "Today";
    if (diff === 1) return "Tomorrow";
    return new Date(iso).toLocaleDateString("en-IN", { weekday: "long" });
};
const dayNumber = (iso) => new Date(iso).getDate();
const monthShort = (iso) =>
    new Date(iso).toLocaleDateString("en-IN", { month: "short" });
const timeOf = (iso) =>
    new Date(iso).toLocaleTimeString("en-IN", {
        hour: "numeric",
        minute: "2-digit",
    });

const initials = (person) =>
    `${person?.first_name?.[0] ?? ""}${person?.last_name?.[0] ?? ""}`.toUpperCase();
</script>

<template>
    <Head title="HR Dashboard" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1
                    class="font-display text-2xl font-bold tracking-tight sm:text-3xl"
                >
                    {{ greeting
                    }}<span v-if="firstName">, {{ firstName }}</span>
                </h1>
                <p class="mt-1 text-sm text-ink-soft">
                    Your hiring numbers and the interviews coming up.
                </p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard
                    v-for="(value, key, i) in stats"
                    :key="key"
                    :title="label(key)"
                    :value="value"
                    :icon="iconFor(key)"
                    :color="colors[i % colors.length]"
                    :delay="i * 60"
                />
            </div>

            <!-- Upcoming interviews -->
            <section class="surface p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-base font-bold">
                            Upcoming Interviews
                        </h2>
                        <p
                            v-if="upcomingInterviews.length"
                            class="mt-0.5 text-xs text-ink-mute"
                        >
                            {{ upcomingInterviews.length }} scheduled
                        </p>
                    </div>

                    <Link
                        :href="route('recruitment.candidates.index')"
                        class="group inline-flex items-center gap-1 text-sm font-semibold text-violet-600 hover:text-violet-800"
                    >
                        View candidates
                        <svg
                            class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                </div>

                <ul
                    v-if="upcomingInterviews.length"
                    class="mt-4 divide-y divide-indigo-50"
                >
                    <li
                        v-for="(schedule, i) in upcomingInterviews"
                        :key="schedule.id"
                        class="-mx-3 flex animate-fade-up flex-col gap-3 rounded-xl px-3 py-3.5 transition-colors hover:bg-paper sm:flex-row sm:items-center sm:gap-5"
                        :style="{ animationDelay: `${240 + i * 50}ms` }"
                    >
                        <!-- Date tile -->
                        <div
                            :class="[
                                dayDiff(schedule.scheduled_at) === 0
                                    ? 'bg-violet-600 text-white'
                                    : 'bg-violet-50 text-violet-700',
                                'flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl leading-none',
                            ]"
                        >
                            <span class="font-display text-xl font-bold">{{
                                dayNumber(schedule.scheduled_at)
                            }}</span>
                            <span class="mt-1 text-[11px] font-semibold">{{
                                monthShort(schedule.scheduled_at)
                            }}</span>
                        </div>

                        <!-- Candidate and round -->
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">
                                {{ schedule.candidate.first_name }}
                                {{ schedule.candidate.last_name }}
                            </p>
                            <span
                                class="mt-1 inline-block rounded-full bg-paper-warm px-2.5 py-0.5 text-xs font-medium text-ink-soft"
                            >
                                {{ schedule.round.name }}
                            </span>
                        </div>

                        <!-- Interviewer -->
                        <div class="flex items-center gap-2.5 sm:w-52">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-bold text-teal-700"
                            >
                                {{ initials(schedule.interviewer) }}
                            </span>
                            <div class="min-w-0 leading-tight">
                                <p class="truncate text-sm font-medium">
                                    {{ schedule.interviewer.first_name }}
                                    {{ schedule.interviewer.last_name }}
                                </p>
                                <p class="text-xs text-ink-mute">Interviewer</p>
                            </div>
                        </div>

                        <!-- Time -->
                        <div class="sm:w-28 sm:text-right">
                            <p class="text-sm font-semibold">
                                {{ timeOf(schedule.scheduled_at) }}
                            </p>
                            <p
                                :class="[
                                    dayDiff(schedule.scheduled_at) === 0
                                        ? 'font-semibold text-violet-600'
                                        : 'text-ink-mute',
                                    'text-xs',
                                ]"
                            >
                                {{ dayLabel(schedule.scheduled_at) }}
                            </p>
                        </div>
                    </li>
                </ul>

                <!-- Empty state -->
                <div
                    v-else
                    class="mt-4 flex flex-col items-center rounded-xl border border-dashed border-indigo-100 py-12 text-center"
                >
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.6"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z"
                            />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold">
                        No upcoming interviews found.
                    </p>
                    <p class="mt-1 text-xs text-ink-mute">
                        Scheduled interviews will show up here.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
