<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    prominent: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="rounded-xl border p-4"
        :class="prominent
            ? 'border-indigo-200 bg-indigo-50'
            : 'border-slate-200 bg-white'"
    >
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
        <p
            class="mt-2 text-lg font-bold"
            :class="prominent ? 'text-indigo-700' : 'text-slate-900'"
        >
            {{ value }}
        </p>
    </div>
</template>
