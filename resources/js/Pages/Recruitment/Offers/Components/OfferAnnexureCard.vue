<script setup>
import { Info, ScrollText } from "lucide-vue-next";
import RichTextEditor from "@/Components/Common/RichTextEditor.vue";

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
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700"
                >
                    <ScrollText class="h-5 w-5" />
                </div>

                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        Salary Annexure Notes
                    </h2>

                    <p class="mt-0.5 text-sm text-gray-500">
                        Add salary-specific notes that should appear below the
                        salary structure.
                    </p>
                </div>
            </div>
        </div>

        <div class="px-5 py-5 sm:px-6">
            <RichTextEditor
                v-model="form.salary_annexure"
                :disabled="disabled"
                :class="fieldClasses('salary_annexure')"
                placeholder="Paste salary breakup, tables, screenshots or detailed notes here..."
                />


            <p
                v-if="errorFor('salary_annexure')"
                class="mt-1.5 text-sm text-red-600"
            >
                {{ errorFor("salary_annexure") }}
            </p>

            <div
                class="mt-3 flex items-start gap-2 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                <Info class="mt-0.5 h-4 w-4 shrink-0" />

                <p>
                    The salary component table is generated automatically. Use
                    this field only for explanatory clauses and special
                    compensation conditions.
                </p>
            </div>
        </div>
    </section>
</template>