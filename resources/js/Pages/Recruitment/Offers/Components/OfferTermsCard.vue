<script setup>
import { FileText } from "lucide-vue-next";

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

function error(field) {
    return props.form.errors?.[field];
}

function classes(field) {
    return [
        "block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100",

        error(field)
            ? "border-red-400 focus:border-red-500 focus:ring-red-500"
            : "",
    ];
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b px-6 py-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700"
                >
                    <FileText class="h-5 w-5"/>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-900">
                        Offer Terms & Conditions
                    </h2>

                    <p class="text-sm text-gray-500">
                        Clauses that appear in the offer letter.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <textarea
                v-model="form.offer_terms"
                rows="12"
                :disabled="disabled"
                :class="classes('offer_terms')"
                placeholder="Enter offer terms..."
            />

            <p
                v-if="error('offer_terms')"
                class="mt-2 text-sm text-red-600"
            >
                {{ error("offer_terms") }}
            </p>
        </div>
    </section>
</template>