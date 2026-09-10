<script setup>
import { computed, ref } from "vue";

import {
    BriefcaseBusiness,
    Download,
    FilePlus2,
    Inbox,
    LoaderCircle,
    Mail,
    Phone,
    RefreshCw,
    Trash2,
} from "lucide-vue-next";

import { Head, Link, router } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";

import ApprovalBadge from "./Components/ApprovalBadge.vue";
import OfferActionsDropdown from "./Components/OfferActionsDropdown.vue";
import OfferFilters from "./Components/OfferFilters.vue";
import OfferPagination from "./Components/OfferPagination.vue";
import OfferStatsCards from "./Components/OfferStatsCards.vue";
import OfferStatusBadge from "./Components/OfferStatusBadge.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    offers: {
        type: Object,
        required: true,
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    options: {
        type: Object,
        default: () => ({}),
    },

    statistics: {
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

const selectedIds = ref([]);
const loading = ref(false);
const exporting = ref(false);
const bulkDeleting = ref(false);

const rows = computed(() => {
    return props.offers.data ?? [];
});

const allSelected = computed(() => {
    return (
        rows.value.length > 0 &&
        rows.value.every((offer) => selectedIds.value.includes(offer.id))
    );
});

const partiallySelected = computed(() => {
    return selectedIds.value.length > 0 && !allSelected.value;
});

const canBulkDelete = computed(() => {
    return props.permissions.bulk_delete && selectedIds.value.length > 0;
});

function applyFilters(filters) {
    loading.value = true;

    router.get(
        props.routes.index ?? route("recruitment.offers.index"),
        removeEmpty(filters),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,

            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

function resetFilters() {
    loading.value = true;

    router.get(
        props.routes.index ?? route("recruitment.offers.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,

            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

function removeEmpty(values) {
    return Object.fromEntries(
        Object.entries(values).filter(
            ([, value]) =>
                value !== "" && value !== null && value !== undefined,
        ),
    );
}

function toggleAll() {
    if (allSelected.value) {
        selectedIds.value = [];
        return;
    }

    selectedIds.value = rows.value.map((offer) => offer.id);
}

function toggleRow(id) {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(
            (selectedId) => selectedId !== id,
        );

        return;
    }

    selectedIds.value = [...selectedIds.value, id];
}

function viewOffer(offer) {
    router.visit(
        offer.routes?.show ?? route("recruitment.offers.show", offer.id),
    );
}

function editOffer(offer) {
    router.visit(
        offer.routes?.edit ?? route("recruitment.offers.edit", offer.id),
    );
}

function createRevision(offer) {
    router.visit(
        offer.routes?.revision ??
            route("recruitment.offers.revisions.create", offer.id),
    );
}

function generatePdf(offer) {
    router.post(
        offer.routes?.generate_pdf ??
            route("recruitment.offers.generate-pdf", offer.id),
        {},
        {
            preserveScroll: true,
        },
    );
}

function sendOffer(offer) {
    router.visit(
        offer.routes?.send ?? route("recruitment.offers.send.create", offer.id),
    );
}

function deleteOffer(offer) {
    const confirmed = window.confirm(
        `Delete offer ${
            offer.offer_number ?? ""
        }? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    router.delete(
        offer.routes?.destroy ?? route("recruitment.offers.destroy", offer.id),
        {
            preserveScroll: true,
        },
    );
}

function bulkDelete() {
    if (!canBulkDelete.value) {
        return;
    }

    const confirmed = window.confirm(
        `Delete ${selectedIds.value.length} selected offers? Only eligible draft offers will be deleted.`,
    );

    if (!confirmed) {
        return;
    }

    bulkDeleting.value = true;

    router.delete(
        props.routes.bulk_delete ?? route("recruitment.offers.bulk-destroy"),
        {
            data: {
                offer_ids: selectedIds.value,
            },

            preserveScroll: true,

            onSuccess: () => {
                selectedIds.value = [];
            },

            onFinish: () => {
                bulkDeleting.value = false;
            },
        },
    );
}

function exportOffers() {
    exporting.value = true;

    const query = new URLSearchParams(
        removeEmpty(props.filters ?? {}),
    ).toString();

    const baseUrl = props.routes.export ?? route("recruitment.offers.export");

    window.location.href = query.length > 0 ? `${baseUrl}?${query}` : baseUrl;

    window.setTimeout(() => {
        exporting.value = false;
    }, 1200);
}

function refreshPage() {
    router.reload({
        only: ["offers", "statistics"],
    });
}

function candidateName(offer) {
    return (
        offer.candidate?.name ??
        [
            offer.candidate?.first_name,
            offer.candidate?.middle_name,
            offer.candidate?.last_name,
        ]
            .filter(Boolean)
            .join(" ") ??
        "Unknown Candidate"
    );
}

function formatMoney(value, currency = "INR") {
    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency,
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
}

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
    }).format(date);
}

function versionLabel(offer) {
    return `V${offer.version ?? 1}`;
}
</script>

<template>
    <Head title="Candidate Offers" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"
                        >
                            <BriefcaseBusiness class="h-6 w-6" />
                        </div>

                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Candidate Offers
                            </h1>

                            <p class="mt-1 text-sm text-gray-500">
                                Create, review, approve and track candidate
                                offers.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        @click="refreshPage"
                    >
                        <RefreshCw class="h-4 w-4" />

                        Refresh
                    </button>

                    <button
                        v-if="permissions.export"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"
                        :disabled="exporting"
                        @click="exportOffers"
                    >
                        <LoaderCircle
                            v-if="exporting"
                            class="h-4 w-4 animate-spin"
                        />

                        <Download v-else class="h-4 w-4" />

                        Export
                    </button>

                    <Link
                        v-if="permissions.create && routes.create"
                        :href="routes.create"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        <FilePlus2 class="h-4 w-4" />

                        Create Offer
                    </Link>
                </div>
            </div>
        </header>

        <main
            class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8"
        >
            <OfferStatsCards :statistics="statistics" />

            <OfferFilters
                :filters="filters"
                :options="options"
                :loading="loading"
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <div
                v-if="selectedIds.length > 0"
                class="flex flex-col gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm font-semibold text-indigo-800">
                    {{ selectedIds.length }}
                    offer{{ selectedIds.length === 1 ? "" : "s" }}
                    selected
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                        @click="selectedIds = []"
                    >
                        Clear selection
                    </button>

                    <button
                        v-if="canBulkDelete"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                        :disabled="bulkDeleting"
                        @click="bulkDelete"
                    >
                        <LoaderCircle
                            v-if="bulkDeleting"
                            class="h-4 w-4 animate-spin"
                        />

                        <Trash2 v-else class="h-4 w-4" />

                        Delete Selected
                    </button>
                </div>
            </div>

            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    v-if="loading"
                    class="flex items-center justify-center gap-3 border-b border-gray-200 bg-indigo-50 px-5 py-3 text-sm font-medium text-indigo-700"
                >
                    <LoaderCircle class="h-4 w-4 animate-spin" />

                    Updating offer list...
                </div>

                <div v-if="rows.length > 0" class="overflow-x-auto">
                    <table
                        class="min-w-[1450px] w-full divide-y divide-gray-200"
                    >
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-12 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="allSelected"
                                        :indeterminate="partiallySelected"
                                        @change="toggleAll"
                                    />
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Candidate
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Offer
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Position
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Compensation
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Joining
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Status
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Approval
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Created
                                </th>

                                <th class="w-16 px-4 py-3" />
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="offer in rows"
                                :key="offer.id"
                                class="cursor-pointer align-top transition hover:bg-gray-50"
                                @click="viewOffer(offer)"
                            >
                                <td class="px-4 py-4" @click.stop>
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="
                                            selectedIds.includes(offer.id)
                                        "
                                        @change="toggleRow(offer.id)"
                                    />
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-indigo-100 text-sm font-bold text-indigo-700"
                                        >
                                            <img
                                                v-if="
                                                    offer.candidate?.photo_url
                                                "
                                                :src="offer.candidate.photo_url"
                                                class="h-full w-full object-cover"
                                                alt=""
                                            />

                                            <span v-else>
                                                {{
                                                    candidateName(offer)
                                                        .split(" ")
                                                        .filter(Boolean)
                                                        .slice(0, 2)
                                                        .map((item) => item[0])
                                                        .join("")
                                                        .toUpperCase()
                                                }}
                                            </span>
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="max-w-56 truncate text-sm font-semibold text-gray-900"
                                            >
                                                {{ candidateName(offer) }}
                                            </p>

                                            <div
                                                v-if="offer.candidate?.email"
                                                class="mt-1 flex items-center gap-1.5 text-xs text-gray-500"
                                            >
                                                <Mail class="h-3.5 w-3.5" />

                                                <span class="max-w-52 truncate">
                                                    {{ offer.candidate.email }}
                                                </span>
                                            </div>

                                            <div
                                                v-if="offer.candidate?.phone"
                                                class="mt-1 flex items-center gap-1.5 text-xs text-gray-500"
                                            >
                                                <Phone class="h-3.5 w-3.5" />

                                                {{ offer.candidate.phone }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{
                                            offer.offer_number ??
                                            `Offer #${offer.id}`
                                        }}
                                    </p>

                                    <div class="mt-1 flex items-center gap-2">
                                        <span
                                            class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600"
                                        >
                                            {{ versionLabel(offer) }}
                                        </span>

                                        <span
                                            v-if="
                                                offer.is_latest_version ===
                                                false
                                            "
                                            class="text-xs font-medium text-amber-600"
                                        >
                                            Older version
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <p
                                        class="max-w-52 truncate text-sm font-medium text-gray-900"
                                    >
                                        {{
                                            offer.designation ?? "—"
                                        }}
                                    </p>

                                    <p
                                        class="mt-1 max-w-52 truncate text-xs text-gray-500"
                                    >
                                        {{
                                            offer.department ?? "No department"
                                        }}
                                    </p>

                                    <p
                                        v-if="offer.work_location"
                                        class="mt-1 max-w-52 truncate text-xs text-gray-500"
                                    >
                                        {{ offer.work_location }}
                                    </p>
                                </td>

                                <td class="px-4 py-4">
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{
                                            formatMoney(
                                                offer.salary_ctc,
                                                offer.currency ?? "INR",
                                            )
                                        }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        CTC per annum
                                    </p>

                                    <p
                                        v-if="offer.salary_in_hand !== null"
                                        class="mt-1 text-xs font-medium text-emerald-700"
                                    >
                                        {{
                                            formatMoney(
                                                offer.salary_in_hand,
                                                offer.currency ?? "INR",
                                            )
                                        }}
                                        in-hand
                                    </p>
                                </td>

                                <td class="px-4 py-4">
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ formatDate(offer.joining_date) }}
                                    </p>

                                    <p
                                        v-if="offer.offer_valid_till"
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        Valid till
                                        {{ formatDate(offer.offer_valid_till) }}
                                    </p>
                                </td>

                                <td class="px-4 py-4">
                                    <OfferStatusBadge :status="offer.status" />
                                </td>

                                <td class="px-4 py-4">
                                    <ApprovalBadge
                                        :status="offer.approval_status"
                                    />
                                </td>

                                <td class="px-4 py-4">
                                    <p class="text-sm text-gray-700">
                                        {{ formatDate(offer.created_at) }}
                                    </p>

                                    <p
                                        class="mt-1 max-w-40 truncate text-xs text-gray-500"
                                    >
                                        {{ offer.generated_by?.name ?? "—" }}
                                    </p>
                                </td>

                                <td class="px-4 py-4" @click.stop>
                                    <OfferActionsDropdown
                                        :offer="offer"
                                        :permissions="offer.permissions ?? {}"
                                        @edit="editOffer(offer)"
                                        @revision="createRevision(offer)"
                                        @pdf="generatePdf(offer)"
                                        @send="sendOffer(offer)"
                                        @delete="deleteOffer(offer)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-20 text-center">
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400"
                    >
                        <Inbox class="h-8 w-8" />
                    </div>

                    <h2 class="mt-4 text-lg font-semibold text-gray-900">
                        No offers found
                    </h2>

                    <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                        No candidate offers match the current filters. Clear the
                        filters or create a new offer from a candidate profile.
                    </p>

                    <button
                        type="button"
                        class="mt-5 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        @click="resetFilters"
                    >
                        <RefreshCw class="h-4 w-4" />

                        Clear Filters
                    </button>
                </div>

                <OfferPagination
                    v-if="rows.length > 0"
                    :links="offers.links ?? []"
                    :from="offers.from"
                    :to="offers.to"
                    :total="offers.total ?? 0"
                />
            </section>
        </main>
    </div>
</template>
