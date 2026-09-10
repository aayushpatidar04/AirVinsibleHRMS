<script setup>
import { computed, reactive, ref } from "vue";

import {
    ArrowLeft,
    Ban,
    CheckCircle2,
    Download,
    FileCheck2,
    FileText,
    LoaderCircle,
    Mail,
    Pencil,
    RefreshCw,
    Send,
    ShieldCheck,
    XCircle,
} from "lucide-vue-next";

import { Head, Link, router } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";

import ApprovalBadge from "./Components/ApprovalBadge.vue";
import CandidateSummary from "./Components/CandidateSummary.vue";
import OfferActionModal from "./Components/OfferActionModal.vue";
import OfferDetailsCard from "./Components/OfferDetailsCard.vue";
import OfferSalaryBreakdown from "./Components/OfferSalaryBreakdown.vue";
import OfferStatusBadge from "./Components/OfferStatusBadge.vue";
import OfferTimeline from "./Components/OfferTimeline.vue";
import VersionHistory from "./Components/VersionHistory.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    offer: {
        type: Object,
        required: true,
    },

    candidate: {
        type: Object,
        required: true,
    },

    salarySummary: {
        type: Object,
        default: () => ({}),
    },

    versions: {
        type: Array,
        default: () => [],
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

function formatDateTime(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        },
    ).format(date);
}

const action = reactive({
    type: null,
    reason: "",
    error: "",
});

const processing = ref(false);
const generatingPdf = ref(false);

const modalConfig = computed(() => {
    switch (action.type) {
        case "reject":
            return {
                title: "Reject Offer Approval",
                description:
                    "The offer will return to draft status so HR can revise and submit it again.",
                confirmText: "Reject Offer",
                confirmClasses: "bg-red-600 hover:bg-red-700",
                reasonLabel: "Rejection Reason",
                reasonPlaceholder: "Explain why this offer cannot be approved.",
                endpoint: props.routes.reject,
            };

        case "cancel":
            return {
                title: "Cancel Offer",
                description:
                    "The offer will become inactive and cannot be sent or accepted.",
                confirmText: "Cancel Offer",
                confirmClasses: "bg-red-600 hover:bg-red-700",
                reasonLabel: "Cancellation Reason",
                reasonPlaceholder: "Explain why this offer is being cancelled.",
                endpoint: props.routes.cancel,
            };

        default:
            return {
                title: "",
                description: "",
                confirmText: "Confirm",
                confirmClasses: "bg-indigo-600 hover:bg-indigo-700",
                reasonLabel: "Reason",
                reasonPlaceholder: "",
                endpoint: null,
            };
    }
});

const pdfAvailable = computed(() => {
    return Boolean(props.offer.pdf_url ?? props.routes.download_pdf);
});

function openAction(type) {
    action.type = type;
    action.reason = "";
    action.error = "";
}

function closeAction() {
    if (processing.value) {
        return;
    }

    action.type = null;
    action.reason = "";
    action.error = "";
}

function confirmAction() {
    if (!action.reason.trim()) {
        action.error = "Please enter a reason.";
        return;
    }

    if (!modalConfig.value.endpoint) {
        return;
    }

    processing.value = true;
    action.error = "";

    router.post(
        modalConfig.value.endpoint,
        {
            reason: action.reason.trim(),
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                closeAction();
            },

            onError: (errors) => {
                action.error =
                    errors.reason ??
                    errors.rejection_reason ??
                    errors.cancellation_reason ??
                    "Unable to complete the action.";
            },

            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function submitForApproval() {
    router.post(
        props.routes.submit_for_approval,
        {},
        {
            preserveScroll: true,
        },
    );
}

function approveOffer() {
    const confirmed = window.confirm("Approve this candidate offer?");

    if (!confirmed) {
        return;
    }

    router.post(
        props.routes.approve,
        {},
        {
            preserveScroll: true,
        },
    );
}

function generatePdf() {
    if (generatingPdf.value) {
        return;
    }

    generatingPdf.value = true;

    router.post(
        props.routes.generate_pdf,
        {},
        {
            preserveScroll: true,

            onFinish: () => {
                generatingPdf.value = false;
            },
        },
    );
}

function sendOffer() {
    if (props.routes.send_create) {
        router.visit(props.routes.send_create);

        return;
    }

    const confirmed = window.confirm(
        `Send this offer to ${props.candidate.email ?? "the candidate"}?`,
    );

    if (!confirmed) {
        return;
    }

    router.post(
        props.routes.send,
        {},
        {
            preserveScroll: true,
        },
    );
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
</script>

<template>
    <Head :title="`Offer ${offer.offer_number ?? ''}`" />

    <div class="min-h-screen bg-gray-50">
        <header
            class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <Link
                        :href="
                            routes.index ?? route('recruitment.offers.index')
                        "
                        class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50 hover:text-gray-900"
                    >
                        <ArrowLeft class="h-5 w-5" />
                    </Link>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1
                                class="truncate text-xl font-semibold text-gray-900 sm:text-2xl"
                            >
                                {{ offer.offer_number ?? `Offer #${offer.id}` }}
                            </h1>

                            <span
                                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                            >
                                V{{ offer.version ?? 1 }}
                            </span>

                            <OfferStatusBadge :status="offer.status" />

                            <ApprovalBadge :status="offer.approval_status" />
                        </div>

                        <p class="mt-1 text-sm text-gray-500">
                            Created
                            {{ formatDate(offer.created_at) }}
                            <template v-if="offer.generated_by?.name">
                                by
                                {{ offer.generated_by.name }}
                            </template>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="permissions.update"
                        :href="routes.edit"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        <Pencil class="h-4 w-4" />

                        Edit
                    </Link>

                    <Link
                        v-if="permissions.create_revision"
                        :href="routes.revision"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        <RefreshCw class="h-4 w-4" />

                        Create Revision
                    </Link>

                    <button
                        v-if="permissions.generate_pdf"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"
                        :disabled="generatingPdf"
                        @click="generatePdf"
                    >
                        <LoaderCircle
                            v-if="generatingPdf"
                            class="h-4 w-4 animate-spin"
                        />

                        <FileText v-else class="h-4 w-4" />

                        {{ pdfAvailable ? "Regenerate PDF" : "Generate PDF" }}
                    </button>

                    <a
                        v-if="pdfAvailable && routes.download_pdf"
                        :href="routes.download_pdf"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        <Download class="h-4 w-4" />

                        Download PDF
                    </a>
                </div>
            </div>
        </header>

        <main
            class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8"
        >
            <section
                v-if="offer.status === 'draft'"
                class="flex flex-col gap-4 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <FileCheck2 class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                    <div>
                        <p class="font-semibold text-blue-900">Offer draft</p>

                        <p class="mt-1 text-sm text-blue-700">
                            Review all employment and salary details before
                            submitting this offer for approval.
                        </p>
                    </div>
                </div>

                <button
                    v-if="permissions.submit_for_approval"
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    @click="submitForApproval"
                >
                    <ShieldCheck class="h-4 w-4" />

                    Submit for Approval
                </button>
            </section>

            <section
                v-if="
                    offer.approval_status === 'pending' &&
                    (permissions.approve || permissions.reject)
                "
                class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-semibold text-amber-900">
                        Approval required
                    </p>

                    <p class="mt-1 text-sm text-amber-700">
                        Review the offer before approving or rejecting the
                        request.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="permissions.reject"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm hover:bg-red-50"
                        @click="openAction('reject')"
                    >
                        <XCircle class="h-4 w-4" />

                        Reject
                    </button>

                    <button
                        v-if="permissions.approve"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        @click="approveOffer"
                    >
                        <CheckCircle2 class="h-4 w-4" />

                        Approve Offer
                    </button>
                </div>
            </section>

            <section
                v-if="offer.status === 'approved' && permissions.send"
                class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-semibold text-emerald-900">
                        Offer ready to send
                    </p>

                    <p class="mt-1 text-sm text-emerald-700">
                        The offer is approved and can now be sent to the
                        candidate.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                    @click="sendOffer"
                >
                    <Send class="h-4 w-4" />

                    Send Offer
                </button>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                <div class="min-w-0 space-y-6">
                    <CandidateSummary :candidate="candidate" />

                    <OfferDetailsCard :offer="offer" />

                    <OfferSalaryBreakdown
                        :components="offer.salary_components ?? []"
                        :summary="salarySummary"
                        :currency="offer.currency ?? 'INR'"
                    />

                    <section
                        v-if="offer.offer_terms"
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
                            <h2 class="font-semibold text-gray-900">
                                Offer Terms & Conditions
                            </h2>
                        </div>

                        <div
                            class="whitespace-pre-line px-5 py-5 text-sm leading-7 text-gray-700 sm:px-6"
                        >
                            {{ offer.offer_terms }}
                        </div>
                    </section>

                    <section
                        v-if="offer.salary_annexure"
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
                            <h2 class="font-semibold text-gray-900">
                                Salary Annexure Notes
                            </h2>
                        </div>

                        <div
                            class="whitespace-pre-line px-5 py-5 text-sm leading-7 text-gray-700 sm:px-6"
                            v-html="offer.salary_annexure"
                        ></div>
                    </section>

                    <section
                        v-if="offer.internal_notes"
                        class="overflow-hidden rounded-2xl border border-violet-200 bg-violet-50/40 shadow-sm"
                    >
                        <div
                            class="border-b border-violet-200 px-5 py-4 sm:px-6"
                        >
                            <h2 class="font-semibold text-violet-900">
                                Internal HR Notes
                            </h2>

                            <p class="mt-1 text-xs text-violet-700">
                                These notes are not included in the
                                candidate-facing offer.
                            </p>
                        </div>

                        <div
                            class="whitespace-pre-line px-5 py-5 text-sm leading-7 text-violet-900 sm:px-6"
                        >
                            {{ offer.internal_notes }}
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <OfferTimeline :offer="offer" />

                    <VersionHistory
                        :versions="versions"
                        :current-offer-id="offer.id"
                    />

                    <section
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-gray-200 px-5 py-4">
                            <h2 class="font-semibold text-gray-900">
                                Offer Information
                            </h2>
                        </div>

                        <dl class="divide-y divide-gray-100">
                            <div
                                class="flex items-center justify-between gap-4 px-5 py-3.5"
                            >
                                <dt class="text-sm text-gray-500">
                                    Valid From
                                </dt>

                                <dd class="text-sm font-medium text-gray-900">
                                    {{ formatDate(offer.valid_from) }}
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-4 px-5 py-3.5"
                            >
                                <dt class="text-sm text-gray-500">
                                    Valid Till
                                </dt>

                                <dd class="text-sm font-medium text-gray-900">
                                    {{ formatDate(offer.offer_valid_till) }}
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-4 px-5 py-3.5"
                            >
                                <dt class="text-sm text-gray-500">Currency</dt>

                                <dd class="text-sm font-medium text-gray-900">
                                    {{ offer.currency ?? "INR" }}
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-4 px-5 py-3.5"
                            >
                                <dt class="text-sm text-gray-500">
                                    PF Applicable
                                </dt>

                                <dd class="text-sm font-medium text-gray-900">
                                    {{ offer.pf_allowed ? "Yes" : "No" }}
                                </dd>
                            </div>

                            <div
                                v-if="offer.pdf_generated_at"
                                class="flex items-center justify-between gap-4 px-5 py-3.5"
                            >
                                <dt class="text-sm text-gray-500">
                                    PDF Generated
                                </dt>

                                <dd
                                    class="text-right text-sm font-medium text-gray-900"
                                >
                                    {{ formatDateTime(offer.pdf_generated_at) }}
                                </dd>
                            </div>

                            <div
                                v-if="offer.sent_at"
                                class="flex items-center justify-between gap-4 px-5 py-3.5"
                            >
                                <dt class="text-sm text-gray-500">Sent On</dt>

                                <dd class="text-sm font-medium text-gray-900">
                                    {{ formatDate(offer.sent_at) }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section
                        v-if="permissions.cancel"
                        class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4"
                    >
                        <div class="flex items-start gap-3">
                            <Ban class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />

                            <div>
                                <p class="font-semibold text-red-900">
                                    Cancel Offer
                                </p>

                                <p class="mt-1 text-sm leading-5 text-red-700">
                                    Cancelling disables further sending or
                                    acceptance.
                                </p>

                                <button
                                    type="button"
                                    class="mt-3 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-100"
                                    @click="openAction('cancel')"
                                >
                                    Cancel This Offer
                                </button>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="offer.status === 'sent' && candidate.email"
                        class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4"
                    >
                        <div class="flex items-start gap-3">
                            <Mail
                                class="mt-0.5 h-5 w-5 shrink-0 text-blue-700"
                            />

                            <div>
                                <p class="font-semibold text-blue-900">
                                    Offer sent
                                </p>

                                <p class="mt-1 break-all text-sm text-blue-700">
                                    {{ candidate.email }}
                                </p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </main>

        <OfferActionModal
            :show="Boolean(action.type)"
            :title="modalConfig.title"
            :description="modalConfig.description"
            :confirm-text="modalConfig.confirmText"
            :confirm-classes="modalConfig.confirmClasses"
            :processing="processing"
            require-reason
            :reason-label="modalConfig.reasonLabel"
            :reason-placeholder="modalConfig.reasonPlaceholder"
            :reason="action.reason"
            :error="action.error"
            @update:reason="action.reason = $event"
            @close="closeAction"
            @confirm="confirmAction"
        />
    </div>
</template>
