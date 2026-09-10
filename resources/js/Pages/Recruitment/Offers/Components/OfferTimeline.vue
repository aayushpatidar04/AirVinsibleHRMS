<script setup>
import { computed } from "vue";

import {
    Ban,
    CheckCircle2,
    Clock3,
    Eye,
    FileEdit,
    Send,
    ThumbsDown,
    ThumbsUp,
    XCircle,
} from "lucide-vue-next";

const props = defineProps({
    offer: {
        type: Object,
        required: true,
    },
});

const timeline = computed(() => {
    const items = [];

    addItem(
        items,
        props.offer.created_at,
        "Offer Created",
        "The offer draft was created.",
        FileEdit,
        "bg-blue-100 text-blue-700",
    );

    addItem(
        items,
        props.offer.submitted_for_approval_at,
        "Submitted for Approval",
        "The offer was submitted for management approval.",
        Clock3,
        "bg-amber-100 text-amber-700",
    );

    addItem(
        items,
        props.offer.approved_at,
        "Offer Approved",
        "The offer was approved.",
        CheckCircle2,
        "bg-emerald-100 text-emerald-700",
    );

    addItem(
        items,
        props.offer.rejected_at,
        "Approval Rejected",
        props.offer.rejection_reason ??
            "The offer approval request was rejected.",
        XCircle,
        "bg-red-100 text-red-700",
    );

    addItem(
        items,
        props.offer.sent_at,
        "Offer Sent",
        "The offer was sent to the candidate.",
        Send,
        "bg-indigo-100 text-indigo-700",
    );

    addItem(
        items,
        props.offer.viewed_at,
        "Offer Viewed",
        "The candidate viewed the offer.",
        Eye,
        "bg-cyan-100 text-cyan-700",
    );

    addItem(
        items,
        props.offer.accepted_at,
        "Offer Accepted",
        "The candidate accepted the offer.",
        ThumbsUp,
        "bg-green-100 text-green-700",
    );

    addItem(
        items,
        props.offer.declined_at,
        "Offer Declined",
        props.offer.decline_reason ?? "The candidate declined the offer.",
        ThumbsDown,
        "bg-red-100 text-red-700",
    );

    addItem(
        items,
        props.offer.expired_at,
        "Offer Expired",
        "The offer acceptance period expired.",
        Clock3,
        "bg-orange-100 text-orange-700",
    );

    addItem(
        items,
        props.offer.cancelled_at,
        "Offer Cancelled",
        props.offer.cancellation_reason ?? "The offer was cancelled.",
        Ban,
        "bg-gray-200 text-gray-700",
    );

    return items.sort(
        (first, second) => new Date(first.date) - new Date(second.date),
    );
});

function addItem(items, date, title, description, icon, iconClasses) {
    if (!date) {
        return;
    }

    items.push({
        date,
        title,
        description,
        icon,
        iconClasses,
    });
}

function formatDateTime(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-gray-900">Offer Timeline</h2>

            <p class="mt-1 text-sm text-gray-500">
                Chronological offer activity.
            </p>
        </div>

        <div class="px-5 py-5 sm:px-6">
            <div v-if="timeline.length" class="space-y-0">
                <div
                    v-for="(item, index) in timeline"
                    :key="`${item.title}-${item.date}`"
                    class="relative flex gap-4 pb-7 last:pb-0"
                >
                    <div
                        v-if="index < timeline.length - 1"
                        class="absolute left-5 top-10 h-[calc(100%-2.5rem)] w-px bg-gray-200"
                    />

                    <div
                        class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="item.iconClasses"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                    </div>

                    <div class="min-w-0 pt-0.5">
                        <p class="font-medium text-gray-900">
                            {{ item.title }}
                        </p>

                        <p class="mt-1 text-sm leading-5 text-gray-500">
                            {{ item.description }}
                        </p>

                        <p class="mt-1.5 text-xs font-medium text-gray-400">
                            {{ formatDateTime(item.date) }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="py-8 text-center text-sm text-gray-500">
                No timeline activity available.
            </div>
        </div>
    </section>
</template>
