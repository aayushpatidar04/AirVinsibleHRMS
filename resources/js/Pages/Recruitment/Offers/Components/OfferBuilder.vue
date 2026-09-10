<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
    nextTick,
} from "vue";

import {
    ArrowLeft,
    CheckCircle2,
    FileCheck2,
    LoaderCircle,
    Save,
    Send,
    ShieldCheck,
    Maximize2,
    Minimize2,
} from "lucide-vue-next";

import { Link, router, useForm } from "@inertiajs/vue3";

import CandidateSummary from "./CandidateSummary.vue";
import EmploymentCard from "./EmploymentCard.vue";
import InternalNotesCard from "./InternalNotesCard.vue";
import OfferAnnexureCard from "./OfferAnnexureCard.vue";
import OfferSidebar from "./OfferSidebar.vue";
import OfferTermsCard from "./OfferTermsCard.vue";
import OfferValidityCard from "./OfferValidityCard.vue";
import SalaryCard from "./SalaryCard.vue";
import OfferValidationSummary from "./OfferValidationSummary.vue";

const props = defineProps({
    mode: {
        type: String,
        default: "create",
        validator: (value) => ["create", "edit", "revision"].includes(value),
    },

    candidate: {
        type: Object,
        required: true,
    },

    offer: {
        type: Object,
        default: null,
    },

    defaults: {
        type: Object,
        default: () => ({}),
    },

    options: {
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

const showValidationSummary = ref(true);
const isFullScreen = ref(false);

function toggleFullScreen() {
    isFullScreen.value = !isFullScreen.value;
}

function exitFullScreen() {
    isFullScreen.value = false;
}

function handleFullScreenKeydown(event) {
    if (event.key === "Escape" && isFullScreen.value) {
        exitFullScreen();
    }
}

watch(isFullScreen, (enabled) => {
    document.body.classList.toggle("overflow-hidden", enabled);

    if (enabled) {
        window.addEventListener("keydown", handleFullScreenKeydown);
    } else {
        window.removeEventListener("keydown", handleFullScreenKeydown);
    }
});

onBeforeUnmount(() => {
    document.body.classList.remove("overflow-hidden");

    window.removeEventListener("keydown", handleFullScreenKeydown);
});

const emit = defineEmits(["saved", "submitted"]);

const salarySummary = reactive({
    monthly_gross: 0,
    monthly_deductions: 0,
    monthly_employer_contributions: 0,
    monthly_in_hand: 0,
    annual_gross: 0,
    annual_ctc: 0,
});

const submittingForApproval = ref(false);
const approvingOffer = ref(false);
const savingAndSubmitting = ref(false);
const showLeaveWarning = ref(false);
const pendingNavigation = ref(null);

const initialPayload = buildInitialPayload();

const form = useForm(initialPayload);

const isCreate = computed(() => props.mode === "create");

const isEdit = computed(() => props.mode === "edit");

const pageTitle = computed(() => {
    if (props.mode === "revision") {
        return "Create Offer Revision";
    }

    if (isEdit.value) {
        return `Edit Offer ${props.offer?.offer_number ?? ""}`;
    }

    return "Create Candidate Offer";
});

const pageDescription = computed(() => {
    if (props.mode === "revision") {
        return "Review the copied offer details and save the new revision.";
    }

    if (isEdit.value) {
        return "Update employment, compensation and offer letter details.";
    }

    return "Prepare an employment offer for the selected candidate.";
});

const canSubmitForApproval = computed(() => {
    if (isCreate.value) {
        return false;
    }

    return Boolean(
        props.permissions.submit_for_approval ??
        props.permissions.submitForApproval ??
        false,
    );
});

const canApprove = computed(() => {
    return Boolean(props.permissions.approve ?? false);
});

const isLocked = computed(() => {
    if (props.permissions.update === false) {
        return true;
    }

    return [
        "approved",
        "sent",
        "accepted",
        "rejected",
        "cancelled",
        "expired",
    ].includes(props.offer?.status);
});

const backUrl = computed(() => {
    return (
        props.routes.back ??
        props.routes.index ??
        route("recruitment.offers.index")
    );
});

const submitUrl = computed(() => {
    if (isCreate.value || props.mode === "revision") {
        return (
            props.routes.store ??
            route("recruitment.candidates.offers.store", props.candidate.id)
        );
    }

    return (
        props.routes.update ??
        route("recruitment.offers.update", props.offer.id)
    );
});

const submitMethod = computed(() => {
    return isCreate.value || props.mode === "revision" ? "post" : "put";
});

const statusLabel = computed(() => {
    if (!props.offer?.status) {
        return "New Draft";
    }

    return String(props.offer.status)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
});

const approvalLabel = computed(() => {
    if (!props.offer?.approval_status) {
        return null;
    }

    return String(props.offer.approval_status)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
});

const candidateDisplayName = computed(() => {
    return (
        props.candidate.name ??
        [
            props.candidate.first_name,
            props.candidate.middle_name,
            props.candidate.last_name,
        ]
            .filter(Boolean)
            .join(" ")
    );
});

function buildInitialPayload() {
    const source = props.offer ?? props.defaults ?? {};

    return {
        process_name: source.process_name ?? "",

        designation: source.designation ?? "",

        department: source.department ?? "",

        branch_id: source.branch_id ?? "",

        branch: source.branch ?? "",

        reporting_manager_id: source.reporting_manager_id ?? "",

        reporting_manager: source.reporting_manager ?? "",

        employment_type: source.employment_type ?? "full_time",

        work_location: source.work_location ?? "",

        joining_date: source.joining_date ?? "",

        reporting_time: normalizeTime(source.reporting_time ?? "10:00"),

        probation_months: source.probation_months ?? 3,

        notice_period_days: source.notice_period_days ?? 30,

        currency: source.currency ?? "INR",

        pf_allowed: source.pf_allowed ?? true,

        salary_ctc: Number(source.salary_ctc ?? 0),

        salary_in_hand: Number(source.salary_in_hand ?? 0),

        salary_components: cloneSalaryComponents(
            source.salary_components ?? [],
        ),

        offer_terms: source.offer_terms ?? "",

        salary_annexure: source.salary_annexure ?? "",

        internal_notes: source.internal_notes ?? "",

        remarks: source.remarks ?? "",

        valid_from: source.valid_from ?? today(),

        offer_valid_till: source.offer_valid_till ?? addDays(7),

        revision_reason: source.revision_reason ?? "",

        parent_offer_id:
            source.parent_offer_id ?? props.offer?.parent_offer_id ?? null,
    };
}

function cloneSalaryComponents(components) {
    if (!Array.isArray(components)) {
        return [];
    }

    return components.map((component, index) => ({
        id: component.id ?? null,

        row_key: component.row_key ?? `component-${index + 1}`,

        component_name: component.component_name ?? "",

        component_type: component.component_type ?? "earning",

        calculation_type: component.calculation_type ?? "fixed",

        percentage_of_component_id:
            component.percentage_of_component_id ?? null,

        percentage_of_row_key: component.percentage_of_row_key ?? null,

        amount: Number(component.amount ?? 0),

        percentage:
            component.percentage === null || component.percentage === undefined
                ? null
                : Number(component.percentage),

        frequency: component.frequency ?? "monthly",

        show_in_offer: component.show_in_offer ?? true,

        affects_in_hand: component.affects_in_hand ?? true,

        is_taxable: component.is_taxable ?? true,

        sort_order: component.sort_order ?? (index + 1) * 10,

        description: component.description ?? null,
    }));
}

function normalizeTime(value) {
    if (!value) {
        return "";
    }

    return String(value).slice(0, 5);
}

function today() {
    return new Date().toISOString().slice(0, 10);
}

function addDays(days) {
    const date = new Date();

    date.setDate(date.getDate() + days);

    return date.toISOString().slice(0, 10);
}

function updateSalarySummary(summary) {
    Object.assign(salarySummary, summary);

    form.salary_ctc = summary.annual_ctc;

    form.salary_in_hand = summary.monthly_in_hand;
}

function preparePayload(data) {
    const payload = {
        ...data,

        salary_ctc: salarySummary.annual_ctc,

        salary_in_hand: salarySummary.monthly_in_hand,

        salary_components: data.salary_components.map((component, index) => ({
            ...component,

            sort_order: (index + 1) * 10,

            amount: Number(component.amount ?? 0),

            percentage:
                component.percentage === null || component.percentage === ""
                    ? null
                    : Number(component.percentage),
        })),
    };

    if (!payload.designation) {
        payload.designation = null;
    }

    if (!payload.department) {
        payload.department = null;
    }

    if (!payload.branch_id) {
        payload.branch_id = null;
    }

    if (!payload.reporting_manager_id) {
        payload.reporting_manager_id = null;
    }

    return payload;
}

function saveDraft(options = {}) {
    if (form.processing || isLocked.value) {
        return;
    }

    showValidationSummary.value = true;

    form.transform(preparePayload)[submitMethod.value](submitUrl.value, {
        preserveScroll: true,

        onSuccess: (page) => {
            form.defaults(preparePayload(form.data()));
            showValidationSummary.value = false;
            emit("saved", page);

            options.onSuccess?.(page);
        },

        onError: (errors) => {
            showSubmissionErrors(
                errors,
            );
            scrollToFirstError(errors);

            options.onError?.(errors);
        },

        onFinish: () => {
            options.onFinish?.();
        },
    });
}

function saveAndSubmitForApproval() {
    savingAndSubmitting.value = true;

    saveDraft({
        onSuccess: () => {
            /*
             * On create, the controller redirects to Show.
             * The user can submit from the Show/Edit page.
             *
             * On edit, we can call the approval submission
             * endpoint immediately.
             */
            if (isEdit.value && props.offer?.id) {
                submitForApproval();
            }
        },

        onFinish: () => {
            savingAndSubmitting.value = false;
        },
    });
}

function submitForApproval() {
    if (
        !canSubmitForApproval.value ||
        submittingForApproval.value ||
        !props.offer?.id
    ) {
        return;
    }

    const endpoint =
        props.routes.submit_for_approval ??
        route("recruitment.offers.submit-for-approval", props.offer.id);

    submittingForApproval.value = true;

    router.post(
        endpoint,
        {},
        {
            preserveScroll: true,

            onSuccess: (page) => {
                emit("submitted", page);
            },

            onError: (errors) => {
                scrollToFirstError(errors);
            },

            onFinish: () => {
                submittingForApproval.value = false;
            },
        },
    );
}

function approveOffer() {
    if (!canApprove.value || approvingOffer.value || !props.offer?.id) {
        return;
    }

    const confirmed = window.confirm("Approve this candidate offer?");

    if (!confirmed) {
        return;
    }

    approvingOffer.value = true;

    router.post(
        props.routes.approve ??
            route("recruitment.offers.approve", props.offer.id),
        {},
        {
            preserveScroll: true,

            onFinish: () => {
                approvingOffer.value = false;
            },
        },
    );
}

function scrollToFirstError(errors) {
    const firstField = Object.keys(errors ?? {})[0];

    if (!firstField) {
        return;
    }

    window.setTimeout(() => {
        const normalizedField = firstField.replaceAll(".", "\\.");

        const element =
            document.querySelector(`[name="${firstField}"]`) ??
            document.querySelector(`#${normalizedField}`) ??
            document.querySelector(`[data-error-field="${firstField}"]`);

        element?.scrollIntoView({
            behavior: "smooth",
            block: "center",
        });

        element?.focus?.();
    }, 100);
}

function handleKeyboardShortcut(event) {
    const isSaveShortcut =
        (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "s";

    if (!isSaveShortcut) {
        return;
    }

    event.preventDefault();

    saveDraft();
}

function handleBeforeUnload(event) {
    if (!form.isDirty || form.processing) {
        return;
    }

    event.preventDefault();

    event.returnValue = "";
}

function requestBackNavigation() {
    const url = backUrl.value ?? backUrl;

    if (form.processing) {
        return;
    }

    if (!form.isDirty) {
        router.visit(url);
        return;
    }

    pendingNavigation.value = url;
    showLeaveWarning.value = true;
}

function leaveWithoutSaving() {
    const url = pendingNavigation.value;

    showLeaveWarning.value = false;
    pendingNavigation.value = null;

    if (!url) {
        return;
    }

    form.defaults();
    form.reset();

    router.visit(url);
}

function saveAndLeave() {
    const destination = pendingNavigation.value ?? backUrl.value;

    showLeaveWarning.value = false;

    saveDraft({
        onSuccess: () => {
            router.visit(destination);
        },
    });
}

watch(
    () => props.offer,
    (offer) => {
        if (!offer) {
            return;
        }

        form.defaults(buildInitialPayload());
    },
);

onMounted(() => {
    window.addEventListener("keydown", handleKeyboardShortcut);

    window.addEventListener("beforeunload", handleBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleKeyboardShortcut);

    window.removeEventListener("beforeunload", handleBeforeUnload);
});

async function showSubmissionErrors(
    errors = {},
) {
    showValidationSummary.value = true;

    await nextTick();

    document
        .getElementById(
            "offer-validation-summary",
        )
        ?.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });

    console.error(
        "Offer validation errors:",
        errors,
    );
}

const submissionOptions = {
    preserveScroll: true,

    onError: async (errors) => {
        await showSubmissionErrors(
            errors,
        );
    },
};

watch(
    () => form.errors,
    (errors) => {
        if (
            Object.keys(errors ?? {}).length >
            0
        ) {
            showValidationSummary.value =
                true;
        }
    },
    {
        deep: true,
    },
);
</script>

<template>
    <div
        :class="[
            'bg-gray-50 transition-all duration-200',
            isFullScreen
                ? 'fixed inset-0 z-[100] min-h-screen overflow-y-auto'
                : 'min-h-screen',
        ]"
    >
        <header
            class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <button
                        type="button"
                        class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-gray-900"
                        @click="requestBackNavigation"
                    >
                        <ArrowLeft class="h-5 w-5" />
                    </button>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1
                                class="truncate text-xl font-semibold text-gray-900 sm:text-2xl"
                            >
                                {{ pageTitle }}
                            </h1>

                            <span
                                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                            >
                                {{ statusLabel }}
                            </span>

                            <span
                                v-if="approvalLabel"
                                class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            >
                                {{ approvalLabel }}
                            </span>

                            <span
                                v-if="form.isDirty"
                                class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"
                            >
                                Unsaved changes
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ pageDescription }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="backUrl"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                        @click="requestBackNavigation"
                    >
                        Cancel
                    </Link>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900"
                        :title="
                            isFullScreen
                                ? 'Exit full screen'
                                : 'Open full screen'
                        "
                        @click="toggleFullScreen"
                    >
                        <Minimize2 v-if="isFullScreen" class="h-4 w-4" />

                        <Maximize2 v-else class="h-4 w-4" />

                        <span class="hidden sm:inline">
                            {{
                                isFullScreen
                                    ? "Exit Full Screen"
                                    : "Full Screen"
                            }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing || isLocked || !form.isDirty"
                        @click="saveDraft"
                    >
                        <LoaderCircle
                            v-if="form.processing && !savingAndSubmitting"
                            class="h-4 w-4 animate-spin"
                        />

                        <Save v-else class="h-4 w-4" />

                        Save Draft
                    </button>

                    <button
                        v-if="isEdit && canSubmitForApproval"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="
                            form.processing || submittingForApproval || isLocked
                        "
                        @click="
                            form.isDirty
                                ? saveAndSubmitForApproval()
                                : submitForApproval()
                        "
                    >
                        <LoaderCircle
                            v-if="submittingForApproval || savingAndSubmitting"
                            class="h-4 w-4 animate-spin"
                        />

                        <Send v-else class="h-4 w-4" />

                        Submit for Approval
                    </button>

                    <button
                        v-if="canApprove"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="approvingOffer || form.processing"
                        @click="approveOffer"
                    >
                        <LoaderCircle
                            v-if="approvingOffer"
                            class="h-4 w-4 animate-spin"
                        />

                        <ShieldCheck v-else class="h-4 w-4" />

                        Approve Offer
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
            <div
                v-if="isLocked"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800"
            >
                <FileCheck2 class="mt-0.5 h-5 w-5 shrink-0" />

                <div>
                    <p class="font-semibold">This offer is locked.</p>

                    <p class="mt-1">
                        Approved, sent, accepted, rejected, expired or cancelled
                        offers cannot be edited. Create a revision to make
                        changes.
                    </p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-6">
                    <OfferValidationSummary
                        :errors="form.errors"
                        :show="showValidationSummary"
                        @close="
                            showValidationSummary = false
                        "
                    />
                    <CandidateSummary :candidate="candidate" />

                    <EmploymentCard
                        :form="form"
                        :options="options"
                        :disabled="form.processing || isLocked"
                    />

                    <SalaryCard
                        v-model="form.salary_components"
                        :errors="form.errors"
                        :options="options"
                        :disabled="form.processing || isLocked"
                        :currency="form.currency"
                        @update-summary="updateSalarySummary"
                    />

                    <OfferTermsCard
                        :form="form"
                        :disabled="form.processing || isLocked"
                    />

                    <OfferAnnexureCard
                        :form="form"
                        :disabled="form.processing || isLocked"
                    />

                    <div class="grid gap-6 lg:grid-cols-2">
                        <OfferValidityCard
                            :form="form"
                            :disabled="form.processing || isLocked"
                        />

                        <InternalNotesCard
                            :form="form"
                            :disabled="form.processing || isLocked"
                        />
                    </div>

                    <section
                        v-if="mode === 'revision'"
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
                            <h2 class="text-base font-semibold text-gray-900">
                                Revision Details
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Explain why a new offer version is being
                                created.
                            </p>
                        </div>

                        <div class="px-5 py-5 sm:px-6">
                            <label
                                for="revision_reason"
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Revision Reason
                                <span class="text-red-500"> * </span>
                            </label>

                            <textarea
                                id="revision_reason"
                                v-model="form.revision_reason"
                                rows="4"
                                class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :class="{
                                    'border-red-400 focus:border-red-500 focus:ring-red-500':
                                        form.errors.revision_reason,
                                }"
                                placeholder="Example: Compensation updated after management approval."
                            />

                            <p
                                v-if="form.errors.revision_reason"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.revision_reason }}
                            </p>
                        </div>
                    </section>

                    <div
                        class="flex flex-col-reverse gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-xs text-gray-500">
                            Press
                            <kbd
                                class="rounded border border-gray-300 bg-gray-50 px-1.5 py-0.5 font-mono text-[11px]"
                            >
                                Ctrl
                            </kbd>
                            +
                            <kbd
                                class="rounded border border-gray-300 bg-gray-50 px-1.5 py-0.5 font-mono text-[11px]"
                            >
                                S
                            </kbd>
                            to save the draft.
                        </p>

                        <div class="flex flex-wrap justify-end gap-2">
                            <Link
                                :href="backUrl"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                @click="requestBackNavigation"
                            >
                                Cancel
                            </Link>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="form.processing || isLocked"
                                @click="saveDraft"
                            >
                                <LoaderCircle
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                />

                                <Save v-else class="h-4 w-4" />

                                {{
                                    isCreate
                                        ? "Create Offer Draft"
                                        : "Save Changes"
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="hidden xl:block">
                    <OfferSidebar
                        :summary="salarySummary"
                        :candidate="{
                            ...candidate,
                            name: candidateDisplayName,
                        }"
                        :offer="{
                            ...offer,
                            joining_date: form.joining_date,
                        }"
                        :currency="form.currency"
                    />
                </aside>
            </div>
        </main>

        <div
            v-if="showLeaveWarning"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="showLeaveWarning = false"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div class="p-6">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                    >
                        <Save class="h-6 w-6" />
                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-gray-900">
                        Unsaved offer changes
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        You have unsaved changes in this offer. Save them before
                        leaving or discard the changes.
                    </p>
                </div>

                <div
                    class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-100"
                        @click="showLeaveWarning = false"
                    >
                        Continue Editing
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                        @click="leaveWithoutSaving"
                    >
                        Discard Changes
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                        @click="saveAndLeave"
                    >
                        <CheckCircle2 class="h-4 w-4" />

                        Save and Leave
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
