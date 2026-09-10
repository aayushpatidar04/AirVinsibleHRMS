<script setup>
import { computed, watch } from "vue";

import {
    BadgeIndianRupee,
    Building2,
    CircleMinus,
    Landmark,
    WalletCards,
} from "lucide-vue-next";

import SalaryComponentTable from "./SalaryComponentTable.vue";

import useOfferSalary from "@/Composables/Recruitment/useOfferSalary";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },

    errors: {
        type: Object,
        default: () => ({}),
    },

    options: {
        type: Object,
        default: () => ({}),
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    currency: {
        type: String,
        default: "INR",
    },
});

const emit = defineEmits([
    "update:modelValue",
    "update-summary",
]);

const components = computed({
    get() {
        return props.modelValue ?? [];
    },

    set(value) {
        emit("update:modelValue", value);
    },
});

const {
    monthlyGross,
    monthlyDeductions,
    monthlyEmployerContributions,
    monthlyInHand,
    annualGross,
    annualCtc,
    formatCurrency,
} = useOfferSalary(components);

const summary = computed(() => ({
    monthly_gross:
        monthlyGross.value,

    monthly_deductions:
        monthlyDeductions.value,

    monthly_employer_contributions:
        monthlyEmployerContributions.value,

    monthly_in_hand:
        monthlyInHand.value,

    annual_gross:
        annualGross.value,

    annual_ctc:
        annualCtc.value,
}));

watch(
    summary,
    (value) => {
        emit("update-summary", value);
    },
    {
        immediate: true,
        deep: true,
    },
);

const summaryCards = computed(() => [
    {
        label: "Monthly Gross",
        value: monthlyGross.value,
        icon: WalletCards,
        help: "Total monthly earnings",
    },
    {
        label: "Monthly Deductions",
        value: monthlyDeductions.value,
        icon: CircleMinus,
        help: "Deductions affecting in-hand",
    },
    {
        label: "Estimated In-Hand",
        value: monthlyInHand.value,
        icon: BadgeIndianRupee,
        help: "Gross minus deductions",
    },
    {
        label: "Employer Contribution",
        value:
            monthlyEmployerContributions.value,
        icon: Building2,
        help: "Monthly employer-side cost",
    },
    {
        label: "Annual Gross",
        value: annualGross.value,
        icon: Landmark,
        help: "Annual employee earnings",
    },
    {
        label: "Annual CTC",
        value: annualCtc.value,
        icon: BadgeIndianRupee,
        help: "Earnings plus employer cost",
    },
]);
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div
            class="border-b border-gray-200 px-5 py-4 sm:px-6"
        >
            <div
                class="flex items-start justify-between gap-4"
            >
                <div>
                    <div
                        class="flex items-center gap-2"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"
                        >
                            <BadgeIndianRupee
                                class="h-5 w-5"
                            />
                        </div>

                        <div>
                            <h2
                                class="text-base font-semibold text-gray-900"
                            >
                                Salary Structure
                            </h2>

                            <p
                                class="text-sm text-gray-500"
                            >
                                Configure earnings,
                                deductions and employer
                                contributions.
                            </p>
                        </div>
                    </div>
                </div>

                <span
                    class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600"
                >
                    {{ components.length }}
                    components
                </span>
            </div>
        </div>

        <div
            class="border-b border-gray-200 bg-gray-50/70 px-5 py-5 sm:px-6"
        >
            <div
                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6"
            >
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div
                        class="flex items-start justify-between gap-3"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                {{ card.label }}
                            </p>

                            <p
                                class="mt-2 text-lg font-semibold text-gray-900"
                            >
                                {{
                                    formatCurrency(
                                        card.value,
                                        currency,
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg bg-gray-50 p-2 text-gray-500"
                        >
                            <component
                                :is="card.icon"
                                class="h-4 w-4"
                            />
                        </div>
                    </div>

                    <p
                        class="mt-2 text-xs text-gray-500"
                    >
                        {{ card.help }}
                    </p>
                </div>
            </div>

            <div
                v-if="monthlyInHand < 0"
                class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                The calculated deductions exceed
                monthly gross earnings. Please review
                the salary components.
            </div>
        </div>

        <div class="px-5 py-5 sm:px-6">
            <SalaryComponentTable
                v-model="components"
                :errors="errors"
                :options="options"
                :disabled="disabled"
                :currency="currency"
            />
        </div>
    </section>
</template>