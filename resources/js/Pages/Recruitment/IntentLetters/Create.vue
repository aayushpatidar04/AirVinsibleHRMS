<script setup>
import { Head } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";

import IntentLetterForm from "./IntentLetterForm.vue";

defineOptions({ layout: AppLayout });

defineProps({
    candidate: { type: Object, required: true },
    defaults: { type: Object, required: true },
    routes: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`Create Letter of Intent - ${candidate.name}`" />
    <IntentLetterForm
        mode="create"
        :candidate="candidate"
        :defaults="defaults"
        :routes="routes"
    />
</template>
