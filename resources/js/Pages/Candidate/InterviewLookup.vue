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

const tone = computed(() => {
    switch (props.result?.state) {
        case "in_progress":
            return "border-teal-200 bg-teal-50";
        case "today":
            return "border-green-200 bg-green-50";
        default:
            return "border-amber-200 bg-amber-50";
    }
});

// Only "today" and "in progress" mean the candidate should go to the interviewer now.
const goNow = computed(() =>
    ["today", "in_progress"].includes(props.result?.state),
);
</script>

<template>
    <Head title="Find My Interview" />

    <GuestLayout>
        <div class="mx-auto w-full max-w-lg px-4">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">Find my interview</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Enter the email you registered with to see who your
                    interviewer is.
                </p>
            </div>

            <form
                class="mt-8 space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100"
                @submit.prevent="submit"
            >
                <label for="email" class="block text-sm font-medium text-gray-700">
                    Email address
                </label>
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
                    class="w-full rounded-xl border-gray-300 px-4 py-3 text-lg focus:border-indigo-500 focus:ring-indigo-500"
                />
                <p v-if="form.errors.email" class="text-sm text-red-600">
                    {{ form.errors.email }}
                </p>
                <p v-else-if="$page.props.errors?.message" class="text-sm text-red-600">
                    {{ $page.props.errors.message }}
                </p>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-lg font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                >
                    {{ form.processing ? "Searching…" : "Find my interview" }}
                </button>
            </form>

            <!-- Result -->
            <div v-if="searched && result" :class="['mt-6 rounded-2xl border p-6', tone]">
                <p class="text-sm font-medium text-gray-600">
                    Hello {{ result.candidate }}
                </p>
                <h2 class="mt-1 text-xl font-bold text-gray-900">{{ headline }}</h2>

                <div v-if="result.interviewer" class="mt-5 rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {{ goNow ? "Please meet" : "Your interviewer" }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{ result.interviewer.name }}
                    </p>
                    <p v-if="result.interviewer.designation" class="text-sm text-gray-500">
                        {{ result.interviewer.designation }}
                    </p>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Round</dt>
                        <dd class="font-medium text-gray-900">{{ result.round ?? "—" }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Position</dt>
                        <dd class="font-medium text-gray-900">{{ result.position ?? "—" }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Branch</dt>
                        <dd class="font-medium text-gray-900">{{ result.branch ?? "—" }}</dd>
                    </div>
                    <div v-if="result.scheduled_label">
                        <dt class="text-gray-500">Scheduled</dt>
                        <dd class="font-medium text-gray-900">{{ result.scheduled_label }}</dd>
                    </div>
                </dl>

                <p v-if="note" class="mt-4 text-sm text-gray-700">{{ note }}</p>
            </div>

            <div
                v-else-if="searched"
                class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 text-center"
            >
                <h2 class="text-lg font-semibold text-gray-900">
                    No active interview found
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                    Please check the email address and try again, or ask the
                    reception desk for help.
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
