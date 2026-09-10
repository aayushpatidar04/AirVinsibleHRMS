<script setup>
import { Head } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";

import OfferBuilder from "./Components/OfferBuilder.vue";

defineOptions({
    layout: AppLayout,
});

defineProps({
    candidate: {
        type: Object,
        required: true,
    },

    offer: {
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
    <Head :title="`Revise Offer ${offer.offer_number ?? ''}`" />

    <OfferBuilder
        mode="revision"
        :candidate="candidate"
        :offer="offer"
        :options="options"
        :permissions="permissions"
        :routes="routes"
    />
</template>
