<script setup>
import { CalendarClock, Clock4 } from "lucide-vue-next";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },

    disabled: {
        type: Boolean,
        default: false,
    },
});

function errorFor(field) {
    return props.form.errors?.[field];
}

function fieldClasses(field) {
    return [
        "block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500",

        errorFor(field)
            ? "border-red-400 focus:border-red-500 focus:ring-red-500"
            : "",
    ];
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"
                >
                    <CalendarClock class="h-5 w-5" />
                </div>

                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        Offer Validity
                    </h2>

                    <p class="mt-0.5 text-sm text-gray-500">
                        Define the offer issue period and acceptance deadline.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
            <div>
                <label
                    for="valid_from"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Valid From
                </label>

                <input
                    id="valid_from"
                    v-model="form.valid_from"
                    type="date"
                    :disabled="disabled"
                    :class="fieldClasses('valid_from')"
                />

                <p
                    v-if="errorFor('valid_from')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("valid_from") }}
                </p>
            </div>

            <div>
                <label
                    for="offer_valid_till"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Valid Till
                    <span class="text-red-500"> * </span>
                </label>

                <input
                    id="offer_valid_till"
                    v-model="form.offer_valid_till"
                    type="date"
                    :disabled="disabled"
                    :min="form.valid_from || undefined"
                    :class="fieldClasses('offer_valid_till')"
                />

                <p
                    v-if="errorFor('offer_valid_till')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("offer_valid_till") }}
                </p>
            </div>

            <div class="sm:col-span-2">
                <label
                    for="reporting_time_validity"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Reporting Time
                </label>

                <div class="relative">
                    <Clock4
                        class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                    />

                    <input
                        id="reporting_time_validity"
                        v-model="form.reporting_time"
                        type="time"
                        :disabled="disabled"
                        :class="fieldClasses('reporting_time')"
                        class="pl-9"
                    />
                </div>

                <p
                    v-if="errorFor('reporting_time')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("reporting_time") }}
                </p>
            </div>
        </div>
    </section>
</template>
