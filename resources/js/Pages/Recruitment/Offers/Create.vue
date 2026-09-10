<script setup>
import { Head } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";

import OfferBuilder from "./Components/OfferBuilder.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    candidate: {
        type: Object,
        required: true,
    },

    defaults: {
        type: Object,
        required: true,
    },

    options: {
        type: Object,
        default: () => ({}),
    },

    permissions: {
        type: Object,
        default: () => ({}),
    },

    routes: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Head :title="`Create Offer - ${candidate.name ?? 'Candidate'}`" />

    <OfferBuilder
        mode="create"
        :candidate="candidate"
        :defaults="defaults"
        :options="options"
        :permissions="permissions"
        :routes="routes"
    />
</template>
