<script setup>
import DashboardIndex from './Dashboard/Index.vue'

const props = defineProps({
    stats: Object,
    pending_interviews: Array,
    active_interviews: Array
})
</script>

<template>
    <DashboardIndex v-bind="props" :stats="stats" :pending_interviews="pending_interviews"
        :active_interviews="active_interviews" />
</template>
