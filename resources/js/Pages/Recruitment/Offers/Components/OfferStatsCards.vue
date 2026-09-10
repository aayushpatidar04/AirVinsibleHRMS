<script setup>
import {
    CheckCircle2,
    Clock3,
    FileEdit,
    Send,
    ThumbsUp,
    XCircle,
} from "lucide-vue-next";

defineProps({
    statistics: {
        type: Object,
        default: () => ({}),
    },
});

const cards = [
    {
        key: "total",
        label: "Total Offers",
        icon: FileEdit,
        iconClasses: "bg-gray-100 text-gray-700",
    },
    {
        key: "draft",
        label: "Drafts",
        icon: FileEdit,
        iconClasses: "bg-slate-100 text-slate-700",
    },
    {
        key: "pending_approval",
        label: "Pending Approval",
        icon: Clock3,
        iconClasses: "bg-amber-100 text-amber-700",
    },
    {
        key: "approved",
        label: "Approved",
        icon: CheckCircle2,
        iconClasses: "bg-emerald-100 text-emerald-700",
    },
    {
        key: "sent",
        label: "Sent",
        icon: Send,
        iconClasses: "bg-blue-100 text-blue-700",
    },
    {
        key: "accepted",
        label: "Accepted",
        icon: ThumbsUp,
        iconClasses: "bg-green-100 text-green-700",
    },
    {
        key: "declined",
        label: "Declined",
        icon: XCircle,
        iconClasses: "bg-red-100 text-red-700",
    },
];
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7">
        <article
            v-for="card in cards"
            :key="card.key"
            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p
                        class="truncate text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        {{ card.label }}
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{
                            Number(statistics[card.key] ?? 0).toLocaleString(
                                "en-IN",
                            )
                        }}
                    </p>
                </div>

                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    :class="card.iconClasses"
                >
                    <component :is="card.icon" class="h-5 w-5" />
                </div>
            </div>
        </article>
    </div>
</template>
