<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({ links: Array })
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap gap-1 justify-center mt-6">
        <template v-for="link in links" :key="link.label">
            <Link v-if="link.url" :href="link.url" v-html="link.label" :class="[
                'px-3 py-1.5 text-sm rounded-lg border transition',
                link.active
                    ? 'bg-indigo-600 text-white border-indigo-600 font-semibold'
                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'
            ]" />
            <span v-else v-html="link.label"
                class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-400 bg-gray-50" />
        </template>
    </div>
</template>