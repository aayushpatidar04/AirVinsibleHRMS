<script setup>
import {
    computed,
    reactive,
    ref,
} from "vue";

import {
    AlertTriangle,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Download,
    FileCheck2,
    FileText,
    Landmark,
    LoaderCircle,
    MapPin,
    ShieldCheck,
    ThumbsDown,
    WalletCards,
    X,
} from "lucide-vue-next";

import {
    Head,
    router,
    useForm,
} from "@inertiajs/vue3";

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },

    candidate: {
        type: Object,
        required: true,
    },

    offer: {
        type: Object,
        required: true,
    },

    portal: {
        type: Object,
        required: true,
    },
});

const showAcceptModal = ref(false);
const showDeclineModal = ref(false);

const acceptForm = useForm({
    candidate_name:
        props.candidate.name ?? "",

    consent: false,
});

const declineForm = useForm({
    candidate_name:
        props.candidate.name ?? "",

    reason: "",

    consent: false,
});

const earnings = computed(() =>
    props.offer.salary_components.filter(
        (component) =>
            component.type === "earning",
    ),
);

const deductions = computed(() =>
    props.offer.salary_components.filter(
        (component) =>
            component.type === "deduction",
    ),
);

const employerContributions = computed(() =>
    props.offer.salary_components.filter(
        (component) =>
            component.type ===
            "employer_contribution",
    ),
);

const responseMessage = computed(() => {
    if (
        props.portal.response === "accepted"
    ) {
        return {
            title: "Offer accepted",
            description:
                "Thank you. Your acceptance has been recorded successfully.",
            classes:
                "border-emerald-200 bg-emerald-50 text-emerald-900",
        };
    }

    if (
        props.portal.response === "declined"
    ) {
        return {
            title: "Response recorded",
            description:
                "Your decision to decline this offer has been recorded.",
            classes:
                "border-gray-200 bg-gray-50 text-gray-900",
        };
    }

    if (props.portal.is_expired) {
        return {
            title: "Offer expired",
            description:
                "The response period for this offer has ended. Contact HR for assistance.",
            classes:
                "border-amber-200 bg-amber-50 text-amber-900",
        };
    }

    return null;
});

function acceptOffer() {
    acceptForm.post(
        props.portal.routes.accept,
        {
            preserveScroll: true,

            onSuccess: () => {
                showAcceptModal.value = false;
            },
        },
    );
}

function declineOffer() {
    declineForm.post(
        props.portal.routes.decline,
        {
            preserveScroll: true,

            onSuccess: () => {
                showDeclineModal.value = false;
            },
        },
    );
}

function money(value) {
    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency:
            props.offer.currency ?? "INR",
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function formatDate(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(
        `${value}T00:00:00`,
    );

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    }).format(date);
}

function formatDateTime(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
}

function label(value) {
    if (!value) {
        return "—";
    }

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        );
}
</script>

<template>
    <Head
        :title="`Offer ${offer.offer_number}`"
    />

    <div class="min-h-screen bg-slate-50">
        <header
            class="border-b border-slate-200 bg-white"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-5 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8"
            >
                <div class="flex items-center gap-4">
                    <img
                        v-if="company.logo_url"
                        :src="company.logo_url"
                        :alt="company.name"
                        class="max-h-12 max-w-[180px] object-contain"
                    />

                    <div>
                        <p
                            class="text-lg font-bold text-slate-900"
                        >
                            {{ company.legal_name }}
                        </p>

                        <p
                            class="text-sm text-slate-500"
                        >
                            Private and confidential
                            employment offer
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="
                            portal.routes.download_pdf
                        "
                        :href="
                            portal.routes.download_pdf
                        "
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <Download class="h-4 w-4" />

                        Download Offer PDF
                    </a>
                </div>
            </div>
        </header>

        <main
            class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8"
        >
            <section
                class="overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-indigo-700 to-violet-700 px-6 py-8 text-white shadow-xl sm:px-9"
            >
                <div
                    class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-200"
                        >
                            Employment Offer
                        </p>

                        <h1
                            class="mt-3 text-3xl font-bold sm:text-4xl"
                        >
                            Welcome,
                            {{ candidate.first_name }}
                        </h1>

                        <p
                            class="mt-3 max-w-2xl text-base leading-7 text-indigo-100"
                        >
                            We are pleased to offer you the
                            position of
                            <strong class="text-white">
                                {{ offer.designation }}
                            </strong>
                            at
                            {{ company.name }}.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-white/20 bg-white/10 px-5 py-4 backdrop-blur"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-indigo-200"
                        >
                            Offer Number
                        </p>

                        <p
                            class="mt-1 text-lg font-bold"
                        >
                            {{ offer.offer_number }}
                        </p>

                        <p
                            class="mt-1 text-sm text-indigo-200"
                        >
                            Version {{ offer.version }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="responseMessage"
                class="rounded-2xl border p-5"
                :class="
                    responseMessage.classes
                "
            >
                <div class="flex items-start gap-3">
                    <CheckCircle2
                        v-if="
                            portal.response ===
                            'accepted'
                        "
                        class="mt-0.5 h-6 w-6 shrink-0"
                    />

                    <AlertTriangle
                        v-else
                        class="mt-0.5 h-6 w-6 shrink-0"
                    />

                    <div>
                        <h2 class="font-bold">
                            {{ responseMessage.title }}
                        </h2>

                        <p
                            class="mt-1 text-sm leading-6 opacity-80"
                        >
                            {{
                                responseMessage.description
                            }}
                        </p>

                        <p
                            v-if="offer.responded_at"
                            class="mt-2 text-xs font-medium opacity-70"
                        >
                            Recorded on
                            {{
                                formatDateTime(
                                    offer.responded_at,
                                )
                            }}
                        </p>
                    </div>
                </div>
            </section>

            <div
                class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
            >
                <div class="space-y-6">
                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="border-b border-slate-200 px-5 py-4 sm:px-6"
                        >
                            <h2
                                class="text-lg font-bold text-slate-900"
                            >
                                Employment Details
                            </h2>

                            <p
                                class="mt-1 text-sm text-slate-500"
                            >
                                Review your role, location,
                                reporting, and joining
                                information.
                            </p>
                        </div>

                        <div
                            class="grid gap-5 px-5 py-5 sm:grid-cols-2 lg:grid-cols-3 sm:px-6"
                        >
                            <DetailItem
                                label="Designation"
                                :value="
                                    offer.designation
                                "
                                :icon="
                                    BriefcaseBusiness
                                "
                            />

                            <DetailItem
                                label="Department"
                                :value="
                                    offer.department
                                "
                                :icon="Building2"
                            />

                            <DetailItem
                                label="Branch"
                                :value="offer.branch"
                                :icon="Landmark"
                            />

                            <DetailItem
                                label="Employment Type"
                                :value="
                                    label(
                                        offer.employment_type,
                                    )
                                "
                                :icon="
                                    BriefcaseBusiness
                                "
                            />

                            <DetailItem
                                label="Joining Date"
                                :value="
                                    formatDate(
                                        offer.joining_date,
                                    )
                                "
                                :icon="CalendarDays"
                            />

                            <DetailItem
                                label="Reporting Time"
                                :value="
                                    offer.reporting_time ||
                                    '—'
                                "
                                :icon="Clock3"
                            />

                            <DetailItem
                                label="Work Location"
                                :value="
                                    offer.work_location ||
                                    '—'
                                "
                                :icon="MapPin"
                            />

                            <DetailItem
                                label="Reporting Manager"
                                :value="
                                    offer.reporting_manager ||
                                    '—'
                                "
                                :icon="
                                    BriefcaseBusiness
                                "
                            />

                            <DetailItem
                                label="Process / Business Unit"
                                :value="
                                    offer.process || '—'
                                "
                                :icon="Building2"
                            />

                            <DetailItem
                                label="Probation"
                                :value="
                                    offer.probation_months
                                        ? `${offer.probation_months} months`
                                        : '—'
                                "
                                :icon="CalendarDays"
                            />

                            <DetailItem
                                label="Notice Period"
                                :value="
                                    offer.notice_period_days
                                        ? `${offer.notice_period_days} days`
                                        : '—'
                                "
                                :icon="CalendarDays"
                            />

                            <DetailItem
                                label="Offer Valid Till"
                                :value="
                                    formatDate(
                                        offer.valid_till,
                                    )
                                "
                                :icon="Clock3"
                            />
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="border-b border-slate-200 px-5 py-4 sm:px-6"
                        >
                            <div
                                class="flex items-start gap-3"
                            >
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"
                                >
                                    <WalletCards
                                        class="h-5 w-5"
                                    />
                                </div>

                                <div>
                                    <h2
                                        class="text-lg font-bold text-slate-900"
                                    >
                                        Compensation
                                        Structure
                                    </h2>

                                    <p
                                        class="mt-1 text-sm text-slate-500"
                                    >
                                        Detailed monthly and
                                        annual compensation.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid gap-4 border-b border-slate-200 bg-slate-50 px-5 py-5 sm:grid-cols-2 lg:grid-cols-3 sm:px-6"
                        >
                            <SummaryCard
                                label="Monthly Gross"
                                :value="
                                    money(
                                        offer
                                            .salary_summary
                                            .monthly_gross,
                                    )
                                "
                            />

                            <SummaryCard
                                label="Estimated In-Hand"
                                :value="
                                    money(
                                        offer
                                            .salary_summary
                                            .monthly_in_hand,
                                    )
                                "
                            />

                            <SummaryCard
                                label="Annual CTC"
                                :value="
                                    money(
                                        offer
                                            .salary_summary
                                            .annual_ctc,
                                    )
                                "
                                prominent
                            />
                        </div>

                        <SalaryGroup
                            v-if="earnings.length"
                            title="Earnings"
                            :items="earnings"
                            :currency="
                                offer.currency
                            "
                        />

                        <SalaryGroup
                            v-if="deductions.length"
                            title="Deductions"
                            :items="deductions"
                            :currency="
                                offer.currency
                            "
                        />

                        <SalaryGroup
                            v-if="
                                employerContributions.length
                            "
                            title="Employer Contributions"
                            :items="
                                employerContributions
                            "
                            :currency="
                                offer.currency
                            "
                        />
                    </section>

                    <section
                        v-if="offer.salary_annexure"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="border-b border-slate-200 px-5 py-4 sm:px-6"
                        >
                            <h2
                                class="text-lg font-bold text-slate-900"
                            >
                                Compensation Notes
                            </h2>
                        </div>

                        <div
                            class="whitespace-pre-line px-5 py-5 text-sm leading-7 text-slate-700 sm:px-6"
                        >
                            {{
                                offer.salary_annexure
                            }}
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="border-b border-slate-200 px-5 py-4 sm:px-6"
                        >
                            <div
                                class="flex items-center gap-3"
                            >
                                <FileText
                                    class="h-5 w-5 text-indigo-600"
                                />

                                <h2
                                    class="text-lg font-bold text-slate-900"
                                >
                                    Terms and Conditions
                                </h2>
                            </div>
                        </div>

                        <div
                            v-if="offer.offer_terms"
                            class="whitespace-pre-line px-5 py-5 text-sm leading-7 text-slate-700 sm:px-6"
                        >
                            {{ offer.offer_terms }}
                        </div>

                        <div
                            v-else
                            class="px-5 py-5 text-sm leading-7 text-slate-700 sm:px-6"
                        >
                            Please review the attached offer
                            letter for the complete terms and
                            conditions applicable to your
                            employment.
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:sticky xl:top-6"
                    >
                        <div
                            class="border-b border-slate-200 px-5 py-4"
                        >
                            <div
                                class="flex items-center gap-3"
                            >
                                <ShieldCheck
                                    class="h-5 w-5 text-indigo-600"
                                />

                                <h2
                                    class="font-bold text-slate-900"
                                >
                                    Your Response
                                </h2>
                            </div>
                        </div>

                        <div class="space-y-4 p-5">
                            <template
                                v-if="
                                    portal.can_respond
                                "
                            >
                                <p
                                    class="text-sm leading-6 text-slate-600"
                                >
                                    Review all offer details
                                    carefully before recording
                                    your final response.
                                </p>

                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                                    @click="
                                        showAcceptModal =
                                            true
                                    "
                                >
                                    <CheckCircle2
                                        class="h-5 w-5"
                                    />

                                    Accept Offer
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-5 py-3 text-sm font-bold text-red-600 transition hover:bg-red-50"
                                    @click="
                                        showDeclineModal =
                                            true
                                    "
                                >
                                    <ThumbsDown
                                        class="h-5 w-5"
                                    />

                                    Decline Offer
                                </button>
                            </template>

                            <template v-else>
                                <div
                                    class="rounded-xl bg-slate-50 p-4 text-center"
                                >
                                    <FileCheck2
                                        class="mx-auto h-8 w-8 text-slate-400"
                                    />

                                    <p
                                        class="mt-3 font-semibold text-slate-900"
                                    >
                                        {{
                                            portal.has_responded
                                                ? "Response completed"
                                                : "Response unavailable"
                                        }}
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-5 text-slate-500"
                                    >
                                        {{
                                            portal.has_responded
                                                ? "Your response has already been recorded."
                                                : "Please contact HR for assistance."
                                        }}
                                    </p>
                                </div>
                            </template>

                            <a
                                v-if="
                                    portal.routes
                                        .download_pdf
                                "
                                :href="
                                    portal.routes
                                        .download_pdf
                                "
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                <Download
                                    class="h-4 w-4"
                                />

                                Download Offer Letter
                            </a>

                            <div
                                class="border-t border-slate-200 pt-4"
                            >
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                                >
                                    Need assistance?
                                </p>

                                <p
                                    v-if="company.email"
                                    class="mt-2 break-all text-sm text-slate-700"
                                >
                                    {{ company.email }}
                                </p>

                                <p
                                    v-if="company.phone"
                                    class="mt-1 text-sm text-slate-700"
                                >
                                    {{ company.phone }}
                                </p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </main>

        <ResponseModal
            :show="showAcceptModal"
            title="Accept employment offer"
            description="Please confirm your name and acceptance of the offer terms."
            confirm-text="Accept Offer"
            confirm-classes="bg-emerald-600 hover:bg-emerald-700"
            :processing="acceptForm.processing"
            @close="
                showAcceptModal = false
            "
            @confirm="acceptOffer"
        >
            <div class="space-y-4">
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Full Name
                    </label>

                    <input
                        v-model="
                            acceptForm.candidate_name
                        "
                        type="text"
                        class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />

                    <p
                        v-if="
                            acceptForm.errors
                                .candidate_name
                        "
                        class="mt-1.5 text-sm text-red-600"
                    >
                        {{
                            acceptForm.errors
                                .candidate_name
                        }}
                    </p>
                </div>

                <label
                    class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"
                >
                    <input
                        v-model="
                            acceptForm.consent
                        "
                        type="checkbox"
                        class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />

                    <span
                        class="text-sm leading-6 text-slate-700"
                    >
                        I confirm that I have read,
                        understood and accept the terms
                        and conditions of this employment
                        offer.
                    </span>
                </label>

                <p
                    v-if="
                        acceptForm.errors.consent
                    "
                    class="text-sm text-red-600"
                >
                    {{
                        acceptForm.errors.consent
                    }}
                </p>
            </div>
        </ResponseModal>

        <ResponseModal
            :show="showDeclineModal"
            title="Decline employment offer"
            description="Please confirm your decision and provide a reason."
            confirm-text="Decline Offer"
            confirm-classes="bg-red-600 hover:bg-red-700"
            :processing="declineForm.processing"
            @close="
                showDeclineModal = false
            "
            @confirm="declineOffer"
        >
            <div class="space-y-4">
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Full Name
                    </label>

                    <input
                        v-model="
                            declineForm.candidate_name
                        "
                        type="text"
                        class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />

                    <p
                        v-if="
                            declineForm.errors
                                .candidate_name
                        "
                        class="mt-1.5 text-sm text-red-600"
                    >
                        {{
                            declineForm.errors
                                .candidate_name
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Reason for declining
                    </label>

                    <textarea
                        v-model="
                            declineForm.reason
                        "
                        rows="5"
                        class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />

                    <p
                        v-if="
                            declineForm.errors.reason
                        "
                        class="mt-1.5 text-sm text-red-600"
                    >
                        {{
                            declineForm.errors.reason
                        }}
                    </p>
                </div>

                <label
                    class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"
                >
                    <input
                        v-model="
                            declineForm.consent
                        "
                        type="checkbox"
                        class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />

                    <span
                        class="text-sm leading-6 text-slate-700"
                    >
                        I confirm that I am declining this
                        offer and understand that this
                        response will be recorded.
                    </span>
                </label>

                <p
                    v-if="
                        declineForm.errors.consent
                    "
                    class="text-sm text-red-600"
                >
                    {{
                        declineForm.errors.consent
                    }}
                </p>
            </div>
        </ResponseModal>
    </div>
</template>