<script setup>
import { computed } from "vue";

const props = defineProps({
    status: String,
});

const badge = computed(() => {
    switch (props.status) {
        case "pending":
            return {
                text: "Pending",
                class: "bg-yellow-100 text-yellow-700",
            };

        case "approved":
            return {
                text: "Approved",
                class: "bg-green-100 text-green-700",
            };

        case "rejected":
            return {
                text: "Rejected",
                class: "bg-red-100 text-red-700",
            };

        default:
            return {
                text: "-",
                class: "bg-gray-100 text-gray-700",
            };
    }
});
</script>

<template>
    <span
        class="rounded-full px-3 py-1 text-xs font-semibold"
        :class="badge.class"
    >
        {{ badge.text }}
    </span>
</template>
