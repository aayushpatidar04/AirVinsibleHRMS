<script setup>
import { EyeOff, NotebookPen } from "lucide-vue-next";

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
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700"
                >
                    <NotebookPen class="h-5 w-5" />
                </div>

                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        Internal HR Notes
                    </h2>

                    <p class="mt-0.5 text-sm text-gray-500">
                        Store negotiation details and internal references.
                    </p>
                </div>
            </div>
        </div>

        <div class="px-5 py-5 sm:px-6">
            <textarea
                id="internal_notes"
                v-model="form.internal_notes"
                rows="6"
                :disabled="disabled"
                class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                :class="{
                    'border-red-400 focus:border-red-500 focus:ring-red-500':
                        errorFor('internal_notes'),
                }"
                placeholder="Example: Candidate initially requested ₹8.5 LPA. Final compensation approved at ₹8.2 LPA."
            />

            <p
                v-if="errorFor('internal_notes')"
                class="mt-1.5 text-sm text-red-600"
            >
                {{ errorFor("internal_notes") }}
            </p>

            <div
                class="mt-3 flex items-center gap-2 text-xs font-medium text-gray-500"
            >
                <EyeOff class="h-4 w-4" />

                Never included in the candidate PDF, email or portal.
            </div>
        </div>
    </section>
</template>
