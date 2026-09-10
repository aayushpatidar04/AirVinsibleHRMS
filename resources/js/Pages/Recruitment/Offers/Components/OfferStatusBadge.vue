<script setup>
import { computed } from "vue";

const props = defineProps({
    status: String,
});

const config = computed(() => {
    switch (props.status) {
        case "draft":
            return {
                text: "Draft",
                classes: "bg-gray-100 text-gray-700 ring-gray-300",
            };

        case "approval_pending":
            return {
                text: "Pending Approval",
                classes: "bg-yellow-100 text-yellow-800 ring-yellow-300",
            };

        case "approved":
            return {
                text: "Approved",
                classes: "bg-green-100 text-green-700 ring-green-300",
            };

        case "sent":
            return {
                text: "Sent",
                classes: "bg-blue-100 text-blue-700 ring-blue-300",
            };

        case "viewed":
            return {
                text: "Viewed",
                classes: "bg-cyan-100 text-cyan-700 ring-cyan-300",
            };

        case "accepted":
            return {
                text: "Accepted",
                classes: "bg-emerald-100 text-emerald-700 ring-emerald-300",
            };

        case "declined":
            return {
                text: "Declined",
                classes: "bg-red-100 text-red-700 ring-red-300",
            };

        case "expired":
            return {
                text: "Expired",
                classes: "bg-orange-100 text-orange-700 ring-orange-300",
            };

        case "cancelled":
            return {
                text: "Cancelled",
                classes: "bg-red-100 text-red-700 ring-red-300",
            };

        default:
            return {
                text: props.status,
                classes: "bg-gray-100 text-gray-700 ring-gray-300",
            };
    }
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
        :class="config.classes"
    >
        {{ config.text }}
    </span>
</template>
