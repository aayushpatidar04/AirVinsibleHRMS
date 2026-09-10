<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    links: {
        type: Array,
        default: () => [],
    },

    from: {
        type: Number,
        default: null,
    },

    to: {
        type: Number,
        default: null,
    },

    total: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <div
        class="flex flex-col gap-4 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-sm text-gray-500">
            Showing
            <span class="font-semibold text-gray-700">
                {{ from ?? 0 }}
            </span>
            to
            <span class="font-semibold text-gray-700">
                {{ to ?? 0 }}
            </span>
            of
            <span class="font-semibold text-gray-700">
                {{ total }}
            </span>
            offers
        </p>

        <nav
            v-if="links.length > 3"
            class="flex flex-wrap items-center justify-end gap-1"
        >
            <template
                v-for="(link, index) in links"
                :key="`${link.label}-${index}`"
            >
                <span
                    v-if="!link.url"
                    class="inline-flex min-h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-400"
                    v-html="link.label"
                />

                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-sm font-medium transition"
                    :class="
                        link.active
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                    "
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
