<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    label: String,
    prev: String,
    next: { type: String, default: null },
    routeName: String,
    params: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="flex items-center gap-2">
        <Link
            :href="route(routeName, { ...params, month: prev })"
            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50"
            preserve-scroll
        >
            ←
        </Link>
        <span class="min-w-[9rem] text-center text-sm font-semibold text-gray-800">{{ label }}</span>
        <Link
            v-if="next"
            :href="route(routeName, { ...params, month: next })"
            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50"
            preserve-scroll
        >
            →
        </Link>
        <span v-else class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-300">→</span>
    </div>
</template>
