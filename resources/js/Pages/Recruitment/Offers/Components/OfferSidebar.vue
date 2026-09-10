<script setup>
import { computed } from "vue";

import {
    BadgeIndianRupee,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    Mail,
    Phone,
    UserRound,
    WalletCards,
} from "lucide-vue-next";

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },

    candidate: {
        type: Object,
        required: true,
    },

    offer: {
        type: Object,
        default: null,
    },

    currency: {
        type: String,
        default: "INR",
    },
});

const candidateName = computed(() => {
    return (
        props.candidate.name ??
        [
            props.candidate.first_name,
            props.candidate.middle_name,
            props.candidate.last_name,
        ]
            .filter(Boolean)
            .join(" ")
    );
});

const initials = computed(() => {
    return candidateName.value
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join("")
        .toUpperCase();
});

function money(value) {
    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: props.currency,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

const salaryRows = computed(() => [
    {
        label: "Monthly Gross",
        value: props.summary.monthly_gross,
        icon: WalletCards,
    },

    {
        label: "Estimated In-Hand",
        value: props.summary.monthly_in_hand,
        icon: BadgeIndianRupee,
    },

    {
        label: "Employer Contribution",
        value: props.summary.monthly_employer_contributions,
        icon: Building2,
    },

    {
        label: "Annual CTC",
        value: props.summary.annual_ctc,
        icon: BriefcaseBusiness,
        emphasize: true,
    },
]);
</script>

<template>
    <div class="sticky top-28 space-y-5">
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="font-semibold text-gray-900">
                    Compensation Summary
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    Automatically calculated from salary components.
                </p>
            </div>

            <div class="divide-y divide-gray-100">
                <div
                    v-for="row in salaryRows"
                    :key="row.label"
                    class="flex items-center justify-between gap-3 px-5 py-4"
                    :class="{
                        'bg-indigo-50/70': row.emphasize,
                    }"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="rounded-lg bg-gray-100 p-2 text-gray-500"
                            :class="{
                                'bg-indigo-100 text-indigo-700': row.emphasize,
                            }"
                        >
                            <component :is="row.icon" class="h-4 w-4" />
                        </div>

                        <p
                            class="truncate text-sm text-gray-600"
                            :class="{
                                'font-semibold text-indigo-800': row.emphasize,
                            }"
                        >
                            {{ row.label }}
                        </p>
                    </div>

                    <p
                        class="shrink-0 text-sm font-semibold text-gray-900"
                        :class="{
                            'text-indigo-800': row.emphasize,
                        }"
                    >
                        {{ money(row.value) }}
                    </p>
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div
                class="bg-gradient-to-br from-indigo-50 to-purple-50 px-5 py-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 font-semibold text-white"
                    >
                        {{ initials || "C" }}
                    </div>

                    <div class="min-w-0">
                        <h3 class="truncate font-semibold text-gray-900">
                            {{ candidateName }}
                        </h3>

                        <p class="truncate text-xs text-gray-500">
                            {{
                                candidate.candidate_code ??
                                candidate.code ??
                                "Candidate"
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 px-5 py-5">
                <div class="flex items-start gap-3">
                    <Mail class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

                    <p class="break-all text-sm text-gray-600">
                        {{ candidate.email ?? candidate.personal_email ?? "—" }}
                    </p>
                </div>

                <div class="flex items-start gap-3">
                    <Phone class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

                    <p class="text-sm text-gray-600">
                        {{ candidate.phone ?? candidate.mobile ?? "—" }}
                    </p>
                </div>

                <div
                    v-if="candidate.applied_for || candidate.position"
                    class="flex items-start gap-3"
                >
                    <BriefcaseBusiness
                        class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"
                    />

                    <p class="text-sm text-gray-600">
                        {{ candidate.applied_for ?? candidate.position }}
                    </p>
                </div>

                <div v-if="offer?.joining_date" class="flex items-start gap-3">
                    <CalendarDays
                        class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"
                    />

                    <p class="text-sm text-gray-600">
                        Joining:
                        {{ offer.joining_date }}
                    </p>
                </div>
            </div>
        </section>

        <section
            class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4"
        >
            <div class="flex items-start gap-3">
                <UserRound class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                <div>
                    <p class="text-sm font-semibold text-blue-900">
                        Candidate-facing document
                    </p>

                    <p class="mt-1 text-xs leading-5 text-blue-700">
                        Internal notes are excluded from the PDF. Only visible
                        salary components, annexure and terms will be included.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
