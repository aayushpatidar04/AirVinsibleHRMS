<script setup>
import { GitBranchPlus, History } from "lucide-vue-next";

import { Link } from "@inertiajs/vue3";

import OfferStatusBadge from "./OfferStatusBadge.vue";

defineProps({
    versions: {
        type: Array,
        default: () => [],
    },

    currentOfferId: {
        type: [Number, String],
        default: null,
    },
});

function formatDate(value) {
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
        <div
            class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 sm:px-6"
        >
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700"
            >
                <GitBranchPlus class="h-5 w-5" />
            </div>

            <div>
                <h2 class="font-semibold text-gray-900">Version History</h2>

                <p class="mt-0.5 text-sm text-gray-500">
                    Previous and current offer revisions.
                </p>
            </div>
        </div>

        <div v-if="versions.length" class="divide-y divide-gray-200">
            <Link
                v-for="version in versions"
                :key="version.id"
                :href="
                    version.routes?.show ??
                    route('recruitment.offers.show', version.id)
                "
                class="flex items-start justify-between gap-4 px-5 py-4 transition hover:bg-gray-50 sm:px-6"
                :class="{
                    'bg-indigo-50/60':
                        String(version.id) === String(currentOfferId),
                }"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
                    >
                        <History class="h-4 w-4" />
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900">
                                Version
                                {{ version.version ?? 1 }}
                            </p>

                            <span
                                v-if="
                                    String(version.id) ===
                                    String(currentOfferId)
                                "
                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700"
                            >
                                Current
                            </span>

                            <span
                                v-if="version.is_latest"
                                class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700"
                            >
                                Latest
                            </span>
                        </div>

                        <p
                            v-if="version.revision_reason"
                            class="mt-1 line-clamp-2 text-sm text-gray-500"
                        >
                            {{ version.revision_reason }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            {{ formatDate(version.created_at) }}
                            <template v-if="version.generated_by?.name">
                                by
                                {{ version.generated_by.name }}
                            </template>
                        </p>
                    </div>
                </div>

                <OfferStatusBadge :status="version.status" />
            </Link>
        </div>

        <div v-else class="px-6 py-10 text-center text-sm text-gray-500">
            No version history available.
        </div>
    </section>
</template>
