<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: "—",
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
});
</script>

<template>
    <div class="flex min-w-0 items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200">
            <component :is="icon" v-if="icon" class="h-5 w-5" />
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
            <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ value || "—" }}</p>
        </div>
    </div>
</template>
