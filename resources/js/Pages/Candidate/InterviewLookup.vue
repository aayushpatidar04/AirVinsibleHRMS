<script setup>
import { computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import GuestLayout from "@/Layouts/GuestLayout.vue";

const props = defineProps({
    result: { type: Object, default: null },
    searched: { type: Boolean, default: false },
});

const form = useForm({ email: "" });

const submit = () => {
    form.post(route("interview-lookup.search"), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const headline = computed(() => {
    const r = props.result;
    if (!r) return "";

    switch (r.state) {
        case "in_progress":
            return "Your interview is in progress";
        case "today":
            return `Your interview is today at ${r.scheduled_time}`;
        case "upcoming":
            return `Your interview is scheduled for ${r.scheduled_label}`;
        case "missed":
            return `Your interview was scheduled for ${r.scheduled_label}`;
        default:
            return "Your interview is not scheduled yet";
    }
});

const note = computed(() => {
    switch (props.result?.state) {
        case "upcoming":
            return "This is not today. Please come back on the scheduled date.";
        case "missed":
            return "That time has passed. Please speak to HR at reception.";
        case "unscheduled":
            return "Please wait at reception. HR will confirm your time.";
        default:
            return "";
    }
});

// Look of the result card for each state
const icons = {
    mic: "M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z",
    check: "M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
    calendar: "M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z",
    clock: "M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z",
    search: "M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z",
};
const looks = {
    in_progress: { card: "border-teal-200 bg-teal-50", tile: "bg-teal-100 text-teal-700", icon: icons.mic },
    today: { card: "border-emerald-200 bg-emerald-50", tile: "bg-emerald-100 text-emerald-700", icon: icons.check },
    upcoming: { card: "border-amber-200 bg-amber-50", tile: "bg-amber-100 text-amber-700", icon: icons.calendar },
    missed: { card: "border-amber-200 bg-amber-50", tile: "bg-amber-100 text-amber-700", icon: icons.clock },
    unscheduled: { card: "border-amber-200 bg-amber-50", tile: "bg-amber-100 text-amber-700", icon: icons.clock },
};
const look = computed(() => looks[props.result?.state] ?? looks.unscheduled);

// Only "today" and "in progress" mean the candidate should go to the interviewer now.
const goNow = computed(() =>
    ["today", "in_progress"].includes(props.result?.state),
);

const interviewerInitials = computed(() =>
    (props.result?.interviewer?.name ?? "")
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0])
        .join("")
        .toUpperCase(),
);

// New key on every search so the result animates in again
const resultKey = computed(() => JSON.stringify(props.result));
</script>

<template>
    <Head title="Find My Interview" />

    <GuestLayout narrow>
        <div class="text-center">
            <h1 class="font-display text-3xl font-bold tracking-tight">Find my interview</h1>
            <p class="mt-2 text-sm text-ink-soft">
                Enter the email you registered with to see who your interviewer is.
            </p>
        </div>

        <form class="surface mt-8 space-y-4 p-6 shadow-lift" @submit.prevent="submit">
            <div>
                <label for="email" class="label">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    inputmode="email"
                    autocomplete="email"
                    autocapitalize="none"
                    autofocus
                    required
                    placeholder="you@example.com"
                    class="input-field !py-3.5 !text-lg"
                    :class="{ '!border-red-400': form.errors.email }"
                />
                <p v-if="form.errors.email" class="error !text-sm" role="alert">{{ form.errors.email }}</p>
                <p v-else-if="$page.props.errors?.message" class="error !text-sm" role="alert">
                    {{ $page.props.errors.message }}
                </p>
            </div>

            <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-3.5 !text-lg">
                <svg v-if="form.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ form.processing ? "Searching…" : "Find my interview" }}
            </button>
        </form>

        <!-- Result -->
        <div v-if="searched && result" :key="resultKey" :class="['mt-6 animate-card-in rounded-2xl border p-6', look.card]">
            <div class="flex items-start gap-4">
                <span :class="[look.tile, 'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl']">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="look.icon" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-ink-soft">Hello {{ result.candidate }}</p>
                    <h2 class="mt-0.5 font-display text-xl font-bold leading-snug">{{ headline }}</h2>
                    <span v-if="goNow"
                        class="mt-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700 shadow-soft">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Please go to your interviewer now
                    </span>
                </div>
            </div>

            <div v-if="result.interviewer" class="mt-5 flex items-center gap-4 rounded-xl bg-white p-4 shadow-soft">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-600 font-display text-base font-bold text-white">
                    {{ interviewerInitials }}
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-ink-mute">
                        {{ goNow ? "Please meet" : "Your interviewer" }}
                    </p>
                    <p class="truncate font-display text-xl font-bold">{{ result.interviewer.name }}</p>
                    <p v-if="result.interviewer.designation" class="text-sm text-ink-soft">
                        {{ result.interviewer.designation }}
                    </p>
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                <div>
                    <dt class="text-ink-mute">Round</dt>
                    <dd class="font-semibold">{{ result.round ?? "—" }}</dd>
                </div>
                <div>
                    <dt class="text-ink-mute">Position</dt>
                    <dd class="font-semibold">{{ result.position ?? "—" }}</dd>
                </div>
                <div>
                    <dt class="text-ink-mute">Branch</dt>
                    <dd class="font-semibold">{{ result.branch ?? "—" }}</dd>
                </div>
                <div v-if="result.scheduled_label">
                    <dt class="text-ink-mute">Scheduled</dt>
                    <dd class="font-semibold">{{ result.scheduled_label }}</dd>
                </div>
            </dl>

            <p v-if="note" class="mt-5 rounded-xl bg-white/70 px-4 py-3 text-sm text-ink-soft">{{ note }}</p>
        </div>

        <div v-else-if="searched" class="surface mt-6 animate-card-in p-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-paper-warm text-ink-soft">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="icons.search" />
                </svg>
            </span>
            <h2 class="mt-3 font-display text-lg font-bold">No active interview found</h2>
            <p class="mt-2 text-sm text-ink-soft">
                Please check the email address and try again, or ask the reception desk for help.
            </p>
        </div>
    </GuestLayout>
</template>