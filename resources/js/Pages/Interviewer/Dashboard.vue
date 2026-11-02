<script setup>
import DashboardIndex from './Dashboard/Index.vue'

const props = defineProps({
    stats: Object,
    assignment_requests: { type: Array, default: () => [] },
    pending_interviews: { type: Array, default: () => [] },
    active_interviews: { type: Array, default: () => [] }
})
</script>

<template>
    <DashboardIndex v-bind="props" :stats="stats" :pending_interviews="pending_interviews"
        :assignment_requests="assignment_requests" :active_interviews="active_interviews" />
</template>
