<script setup>
import { computed } from "vue";

import {
    BadgeIndianRupee,
    Building2,
    CircleMinus,
    CirclePlus,
    WalletCards,
} from "lucide-vue-next";

const props = defineProps({
    components: {
        type: Array,
        default: () => [],
    },

    summary: {
        type: Object,
        default: () => ({}),
    },

    currency: {
        type: String,
        default: "INR",
    },
});

const earnings = computed(() =>
    props.components.filter(
        (component) => component.component_type === "earning",
    ),
);

const deductions = computed(() =>
    props.components.filter(
        (component) => component.component_type === "deduction",
    ),
);

const employerContributions = computed(() =>
    props.components.filter(
        (component) => component.component_type === "employer_contribution",
    ),
);

function money(value) {
    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: props.currency,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function formatLabel(value) {
    if (!value) {
        return "—";
    }

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function componentAmount(component) {
    return (
        component.monthly_amount ??
        component.calculated_monthly_amount ??
        component.amount ??
        0
    );
}

function annualAmount(component) {
    return (
        component.annual_amount ??
        component.calculated_annual_amount ??
        Number(componentAmount(component)) * 12
    );
}

function percentageDescription(component) {
    if (component.calculation_type !== "percentage") {
        return null;
    }

    const percentage = component.percentage ?? 0;

    const reference =
        component.percentage_of_component?.component_name ??
        component.percentage_of_name ??
        component.reference_component_name ??
        "base component";

    return `${percentage}% of ${reference}`;
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"
                    >
                        <WalletCards class="h-5 w-5" />
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-gray-900">
                            Compensation Structure
                        </h2>

                        <p class="mt-0.5 text-sm text-gray-500">
                            Monthly and annual salary component breakdown.
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-500"
                    >
                        Annual CTC
                    </p>

                    <p class="mt-1 text-xl font-bold text-indigo-700">
                        {{ money(summary.annual_ctc) }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="grid gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-5 sm:grid-cols-2 xl:grid-cols-4 sm:px-6"
        >
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                >
                    Monthly Gross
                </p>

                <p class="mt-2 text-lg font-bold text-gray-900">
                    {{ money(summary.monthly_gross) }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                >
                    Monthly Deductions
                </p>

                <p class="mt-2 text-lg font-bold text-red-600">
                    {{ money(summary.monthly_deductions) }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                >
                    Monthly In-Hand
                </p>

                <p class="mt-2 text-lg font-bold text-emerald-700">
                    {{ money(summary.monthly_in_hand) }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                >
                    Employer Contribution
                </p>

                <p class="mt-2 text-lg font-bold text-blue-700">
                    {{ money(summary.monthly_employer_contributions) }}
                </p>
            </div>
        </div>

        <div class="space-y-6 px-5 py-5 sm:px-6">
            <div v-if="earnings.length">
                <div class="mb-3 flex items-center gap-2">
                    <CirclePlus class="h-5 w-5 text-emerald-600" />

                    <h3 class="font-semibold text-gray-900">Earnings</h3>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Component
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Calculation
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Frequency
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Monthly
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Annual
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="component in earnings"
                                :key="component.id ?? component.row_key"
                            >
                                <td class="px-4 py-3">
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ component.component_name }}
                                    </p>

                                    <p
                                        v-if="component.description"
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        {{ component.description }}
                                    </p>
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{
                                        percentageDescription(component) ??
                                        formatLabel(component.calculation_type)
                                    }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ formatLabel(component.frequency) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right text-sm font-semibold text-gray-900"
                                >
                                    {{ money(componentAmount(component)) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right text-sm font-semibold text-gray-900"
                                >
                                    {{ money(annualAmount(component)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="deductions.length">
                <div class="mb-3 flex items-center gap-2">
                    <CircleMinus class="h-5 w-5 text-red-600" />

                    <h3 class="font-semibold text-gray-900">Deductions</h3>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Component
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Calculation
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Frequency
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Monthly
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Annual
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="component in deductions"
                                :key="component.id ?? component.row_key"
                            >
                                <td class="px-4 py-3">
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ component.component_name }}
                                    </p>
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{
                                        percentageDescription(component) ??
                                        formatLabel(component.calculation_type)
                                    }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ formatLabel(component.frequency) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right text-sm font-semibold text-red-600"
                                >
                                    {{ money(componentAmount(component)) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right text-sm font-semibold text-red-600"
                                >
                                    {{ money(annualAmount(component)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="employerContributions.length">
                <div class="mb-3 flex items-center gap-2">
                    <Building2 class="h-5 w-5 text-blue-600" />

                    <h3 class="font-semibold text-gray-900">
                        Employer Contributions
                    </h3>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Component
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Calculation
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Frequency
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Monthly
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Annual
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="component in employerContributions"
                                :key="component.id ?? component.row_key"
                            >
                                <td class="px-4 py-3">
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ component.component_name }}
                                    </p>
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{
                                        percentageDescription(component) ??
                                        formatLabel(component.calculation_type)
                                    }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ formatLabel(component.frequency) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right text-sm font-semibold text-blue-700"
                                >
                                    {{ money(componentAmount(component)) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right text-sm font-semibold text-blue-700"
                                >
                                    {{ money(annualAmount(component)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="!components.length"
                class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center"
            >
                <BadgeIndianRupee class="mx-auto h-8 w-8 text-gray-400" />

                <p class="mt-3 text-sm font-medium text-gray-700">
                    No salary components available
                </p>
            </div>
        </div>
    </section>
</template>
