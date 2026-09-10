<script setup>
import { LoaderCircle, X } from "lucide-vue-next";

defineProps({
    show: {
        type: Boolean,
        default: false,
    },

    title: {
        type: String,
        required: true,
    },

    description: {
        type: String,
        default: "",
    },

    confirmText: {
        type: String,
        default: "Confirm",
    },

    confirmClasses: {
        type: String,
        default: "bg-indigo-600 hover:bg-indigo-700",
    },

    processing: {
        type: Boolean,
        default: false,
    },

    requireReason: {
        type: Boolean,
        default: false,
    },

    reasonLabel: {
        type: String,
        default: "Reason",
    },

    reasonPlaceholder: {
        type: String,
        default: "",
    },

    reason: {
        type: String,
        default: "",
    },

    error: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["close", "confirm", "update:reason"]);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="$emit('close')"
        >
            <div
                class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ title }}
                        </h2>

                        <p
                            v-if="description"
                            class="mt-1 text-sm leading-6 text-gray-500"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                        @click="$emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="requireReason" class="px-6 py-5">
                    <label
                        for="offer-action-reason"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        {{ reasonLabel }}
                        <span class="text-red-500"> * </span>
                    </label>

                    <textarea
                        id="offer-action-reason"
                        :value="reason"
                        rows="5"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{
                            'border-red-400 focus:border-red-500 focus:ring-red-500':
                                error,
                        }"
                        :placeholder="reasonPlaceholder"
                        @input="$emit('update:reason', $event.target.value)"
                    />

                    <p v-if="error" class="mt-1.5 text-sm text-red-600">
                        {{ error }}
                    </p>
                </div>

                <div
                    class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        :disabled="processing"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                        :class="confirmClasses"
                        :disabled="processing"
                        @click="$emit('confirm')"
                    >
                        <LoaderCircle
                            v-if="processing"
                            class="h-4 w-4 animate-spin"
                        />

                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
