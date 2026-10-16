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
});

const emit = defineEmits(["close", "confirm"]);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/55 p-4 backdrop-blur-sm"
            @click.self="!processing && emit('close')"
        >
            <section
                role="dialog"
                aria-modal="true"
                :aria-label="title"
                class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10"
            >
                <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ title }}</h2>
                        <p v-if="description" class="mt-1 text-sm leading-6 text-slate-600">{{ description }}</p>
                    </div>
                    <button
                        type="button"
                        :disabled="processing"
                        aria-label="Close"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:opacity-50"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </header>

                <div class="px-6 py-5">
                    <slot />
                </div>

                <footer class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        :disabled="processing"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="processing"
                        class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                        :class="confirmClasses"
                        @click="emit('confirm')"
                    >
                        <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                        {{ processing ? "Please wait…" : confirmText }}
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>
