<script setup>
import StatusBadge from "@/Components/Common/StatusBadge.vue";
import { ref } from "vue";

const props = defineProps({
    history: {
        type: Array,
        default: () => [],
    },

    schedules: {
        type: Array,
        default: () => [],
    },

    showMineBadge: {
        type: Boolean,
        default: false,
    },

    showAdminActions: {
        type: Boolean,
        default: false,
    },

    emptyMessage: {
        type: String,
        default: "No interview rounds yet.",
    },
});

const emit = defineEmits(["reassign", "schedule"]);

const expandedProgressId = ref(null);

const toggleRound = (progressId) => {
    expandedProgressId.value =
        expandedProgressId.value === progressId ? null : progressId;
};

const isExpanded = (progressId) => {
    return expandedProgressId.value === progressId;
};

const canReassign = (progress) => {
    return ["pending", "in_progress"].includes(progress.status);
};

const getRoundSchedule = (roundId) => {
    return (
        props.schedules?.find(
            (schedule) => Number(schedule.round_id) === Number(roundId),
        ) ?? null
    );
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === "") {
        return null;
    }

    return `₹${Number(value).toLocaleString("en-IN")}`;
};

const formatSalaryRange = (progress) => {
    const minimum = formatCurrency(progress.salary_offer_min);
    const maximum = formatCurrency(progress.salary_offer_max);

    if (minimum && maximum) {
        return `${minimum} - ${maximum}`;
    }

    if (minimum) {
        return `From ${minimum}`;
    }

    if (maximum) {
        return `Up to ${maximum}`;
    }

    return null;
};

const getAnswerText = (item) => {
    if (
        item.response_text !== null &&
        item.response_text !== undefined &&
        String(item.response_text).trim() !== ""
    ) {
        return item.response_text;
    }

    if (item.rating_value !== null && item.rating_value !== undefined) {
        return `Rating: ${item.rating_value}/5`;
    }

    if (item.yes_no_value !== null && item.yes_no_value !== undefined) {
        return item.yes_no_value ? "Yes" : "No";
    }

    if (Array.isArray(item.selected_options) && item.selected_options.length) {
        return item.selected_options.join(", ");
    }

    return null;
};

const getStatusIcon = (status, index) => {
    if (status === "completed") return "✓";
    if (status === "rejected") return "✕";
    if (status === "in_progress") return "▶";

    return index + 1;
};

const getStatusCircleClass = (status) => {
    const classes = {
        completed: "bg-green-500 text-white ring-green-100",

        rejected: "bg-red-500 text-white ring-red-100",

        in_progress: "bg-blue-500 text-white ring-blue-100",

        pending: "bg-yellow-400 text-yellow-900 ring-yellow-100",

        not_started: "bg-gray-300 text-gray-700 ring-gray-100",
    };

    return classes[status] ?? "bg-gray-300 text-gray-700 ring-gray-100";
};

const openReassign = (progress) => {
    emit("reassign", progress);
};

const openSchedule = (progress) => {
    emit("schedule", progress);
};
</script>

<template>
    <div class="space-y-4">
        <!-- Timeline heading -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">Interview History</h2>

                <p v-if="history.length" class="mt-0.5 text-xs text-gray-400">
                    {{ history.length }}
                    {{ history.length === 1 ? "round" : "rounds" }}
                </p>
            </div>
        </div>

        <!-- Timeline -->
        <div v-if="history.length" class="relative">
            <div
                v-for="(progress, index) in history"
                :key="progress.id"
                class="relative pb-6 pl-11 last:pb-0"
            >
                <!-- Vertical connector -->
                <div
                    v-if="index < history.length - 1"
                    class="absolute bottom-0 left-[15px] top-8 w-px bg-gray-200"
                />

                <!-- Timeline status circle -->
                <div
                    :class="[
                        'absolute left-0 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ring-4',
                        getStatusCircleClass(progress.status),
                    ]"
                >
                    {{ getStatusIcon(progress.status, index) }}
                </div>

                <!-- Round card -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                >
                    <!-- Round header -->
                    <button
                        type="button"
                        class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left transition hover:bg-gray-50"
                        @click="toggleRound(progress.id)"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-800">
                                    {{ progress.round_name }}
                                </p>

                                <span
                                    v-if="progress.is_hr_round"
                                    class="rounded bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-600"
                                >
                                    HR
                                </span>

                                <span
                                    v-if="progress.is_ops_round"
                                    class="rounded bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-600"
                                >
                                    OPS
                                </span>

                                <span
                                    v-if="showMineBadge && progress.is_mine"
                                    class="rounded bg-teal-100 px-1.5 py-0.5 text-xs font-medium text-teal-700"
                                >
                                    Mine
                                </span>

                                <StatusBadge
                                    :status="progress.status"
                                    type="progress"
                                />
                            </div>

                            <div
                                class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-400"
                            >
                                <span>
                                    {{ progress.interviewer || "Not assigned" }}
                                </span>

                                <span>·</span>

                                <span>
                                    {{ progress.started_at || "Not started" }}
                                </span>

                                <template v-if="progress.duration">
                                    <span>·</span>
                                    <span>{{ progress.duration }}</span>
                                </template>
                            </div>

                            <div
                                v-if="progress.question_counts?.total"
                                class="mt-2 flex flex-wrap items-center gap-2"
                            >
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
                                >
                                    {{ progress.question_counts.template }}
                                    template
                                </span>

                                <span
                                    v-if="progress.question_counts.custom"
                                    class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700"
                                >
                                    {{ progress.question_counts.custom }}
                                    custom
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-shrink-0 items-center gap-3">
                            <span
                                v-if="
                                    progress.salary_offer_min ||
                                    progress.salary_offer_max
                                "
                                class="hidden text-xs font-semibold text-green-600 sm:inline"
                            >
                                💰
                                {{ formatSalaryRange(progress) }}
                            </span>

                            <!-- Admin/HR actions -->
                            <div
                                v-if="showAdminActions && canReassign(progress)"
                                class="hidden items-center gap-1 md:flex"
                            >
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-800"
                                    @click.stop="openReassign(progress)"
                                >
                                    🔄 Reassign
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-green-600 transition hover:bg-green-50 hover:text-green-800"
                                    @click.stop="openSchedule(progress)"
                                >
                                    📅
                                    {{
                                        getRoundSchedule(progress.round_id)
                                            ? "Edit Schedule"
                                            : "Schedule"
                                    }}
                                </button>
                            </div>

                            <span class="text-sm text-gray-300">
                                {{ isExpanded(progress.id) ? "▲" : "▼" }}
                            </span>
                        </div>
                    </button>

                    <!-- Expanded content -->
                    <div
                        v-if="isExpanded(progress.id)"
                        class="space-y-5 border-t border-gray-100 px-5 py-5"
                    >
                        <!-- Mobile actions -->
                        <div
                            v-if="showAdminActions && canReassign(progress)"
                            class="flex flex-wrap gap-2 md:hidden"
                        >
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700"
                                @click="openReassign(progress)"
                            >
                                🔄 Reassign
                            </button>

                            <button
                                type="button"
                                class="rounded-lg bg-green-50 px-3 py-2 text-xs font-medium text-green-700"
                                @click="openSchedule(progress)"
                            >
                                📅
                                {{
                                    getRoundSchedule(progress.round_id)
                                        ? "Edit Schedule"
                                        : "Schedule Interview"
                                }}
                            </button>
                        </div>

                        <!-- Schedule -->
                        <div
                            v-if="getRoundSchedule(progress.round_id)"
                            :class="[
                                'rounded-xl border p-4 text-sm',

                                getRoundSchedule(progress.round_id).is_upcoming
                                    ? 'border-green-200 bg-green-50'
                                    : 'border-gray-200 bg-gray-50',
                            ]"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <p
                                        :class="[
                                            'font-semibold',

                                            getRoundSchedule(progress.round_id)
                                                .is_upcoming
                                                ? 'text-green-800'
                                                : 'text-gray-700',
                                        ]"
                                    >
                                        📅 Scheduled:
                                        {{
                                            getRoundSchedule(progress.round_id)
                                                .scheduled_at
                                        }}

                                        <span
                                            v-if="
                                                getRoundSchedule(
                                                    progress.round_id,
                                                ).is_upcoming
                                            "
                                            class="ml-1 text-xs font-normal text-green-600"
                                        >
                                            ({{
                                                getRoundSchedule(
                                                    progress.round_id,
                                                ).time_until
                                            }})
                                        </span>
                                    </p>

                                    <a
                                        v-if="
                                            getRoundSchedule(progress.round_id)
                                                .gmeet_link
                                        "
                                        :href="
                                            getRoundSchedule(progress.round_id)
                                                .gmeet_link
                                        "
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 inline-block break-all text-xs text-indigo-600 hover:underline"
                                    >
                                        🔗
                                        {{
                                            getRoundSchedule(progress.round_id)
                                                .gmeet_link
                                        }}
                                    </a>

                                    <p
                                        v-if="
                                            getRoundSchedule(progress.round_id)
                                                .notes
                                        "
                                        class="mt-1 text-xs text-gray-600"
                                    >
                                        📝
                                        {{
                                            getRoundSchedule(progress.round_id)
                                                .notes
                                        }}
                                    </p>
                                </div>

                                <span
                                    v-if="
                                        getRoundSchedule(progress.round_id)
                                            .sent_at
                                    "
                                    class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700"
                                >
                                    ✉️ Notification sent
                                </span>
                            </div>
                        </div>

                        <!-- Round overview -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-lg border border-gray-100 bg-gray-50 p-3"
                            >
                                <p class="text-xs font-medium text-gray-400">
                                    Started
                                </p>

                                <p
                                    class="mt-1 text-sm font-medium text-gray-700"
                                >
                                    {{ progress.started_at || "—" }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-100 bg-gray-50 p-3"
                            >
                                <p class="text-xs font-medium text-gray-400">
                                    Completed
                                </p>

                                <p
                                    class="mt-1 text-sm font-medium text-gray-700"
                                >
                                    {{ progress.ended_at || "—" }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-100 bg-gray-50 p-3"
                            >
                                <p class="text-xs font-medium text-gray-400">
                                    Overall Rating
                                </p>

                                <p
                                    v-if="
                                        progress.rating !== null &&
                                        progress.rating !== undefined
                                    "
                                    class="mt-1 text-sm font-semibold text-yellow-600"
                                >
                                    {{ progress.rating }} ★
                                </p>

                                <p v-else class="mt-1 text-sm text-gray-400">
                                    —
                                </p>
                            </div>
                        </div>

                        <!-- Salary offer -->
                        <div
                            v-if="
                                progress.salary_offer_min ||
                                progress.salary_offer_max ||
                                progress.offered_designation
                            "
                            class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <p class="font-semibold text-green-800">
                                    Salary Offer Details
                                </p>

                                <span
                                    v-if="progress.salary_offer_status"
                                    class="rounded-full bg-white px-2 py-1 text-xs font-medium capitalize text-green-700"
                                >
                                    {{ progress.salary_offer_status }}
                                </span>
                            </div>

                            <p
                                v-if="
                                    progress.salary_offer_min ||
                                    progress.salary_offer_max
                                "
                                class="mt-1 text-green-700"
                            >
                                Range:
                                {{ formatSalaryRange(progress) }}
                                / year
                            </p>

                            <p
                                v-if="progress.offered_designation"
                                class="mt-1 text-green-700"
                            >
                                Designation:
                                {{ progress.offered_designation }}
                            </p>
                        </div>

                        <!-- Feedback -->
                        <div
                            v-if="progress.feedback"
                            class="rounded-xl border border-gray-100 bg-gray-50 p-4"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Overall Feedback
                            </p>

                            <p
                                class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700"
                            >
                                {{ progress.feedback }}
                            </p>
                        </div>

                        <!-- Rejection -->
                        <div
                            v-if="progress.rejection_reason"
                            class="rounded-xl border border-red-200 bg-red-50 p-4"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-red-600"
                            >
                                Rejection Reason
                            </p>

                            <p
                                class="mt-2 whitespace-pre-line text-sm text-red-700"
                            >
                                {{ progress.rejection_reason }}
                            </p>
                        </div>

                        <!-- Template questions -->
                        <div v-if="progress.template_responses?.length">
                            <div
                                class="mb-3 flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-gray-600"
                                    >
                                        Template Questions
                                    </p>

                                    <p class="mt-0.5 text-xs text-gray-400">
                                        Questions configured for this interview
                                        round
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600"
                                >
                                    {{ progress.template_responses.length }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(
                                        response, responseIndex
                                    ) in progress.template_responses"
                                    :key="
                                        response.key ||
                                        `template-${responseIndex}`
                                    "
                                    class="rounded-xl border border-gray-100 bg-gray-50 p-4"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="font-medium text-gray-800"
                                            >
                                                {{ response.question }}

                                                <span
                                                    v-if="response.is_mandatory"
                                                    class="ml-1 text-red-400"
                                                >
                                                    *
                                                </span>
                                            </p>

                                            <p
                                                class="mt-1 text-xs capitalize text-gray-400"
                                            >
                                                {{
                                                    response.type?.replaceAll(
                                                        "_",
                                                        " ",
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <span
                                            v-if="response.question_rating"
                                            class="flex-shrink-0 text-sm font-semibold text-yellow-600"
                                        >
                                            {{ response.question_rating }}
                                            ★
                                        </span>
                                    </div>

                                    <p
                                        v-if="getAnswerText(response)"
                                        class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700"
                                    >
                                        {{ getAnswerText(response) }}
                                    </p>

                                    <p
                                        v-else
                                        class="mt-3 text-sm italic text-gray-400"
                                    >
                                        No answer recorded
                                    </p>

                                    <div
                                        v-if="response.interviewer_notes"
                                        class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2"
                                    >
                                        <p
                                            class="text-xs font-medium text-indigo-500"
                                        >
                                            Interviewer Note
                                        </p>

                                        <p
                                            class="mt-1 whitespace-pre-line text-xs text-indigo-700"
                                        >
                                            {{ response.interviewer_notes }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom questions -->
                        <div v-if="progress.custom_questions?.length">
                            <div
                                class="mb-3 flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-purple-600"
                                    >
                                        Interview-Specific Questions
                                    </p>

                                    <p class="mt-0.5 text-xs text-gray-400">
                                        Added during this candidate's interview
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-purple-100 px-2 py-1 text-xs text-purple-700"
                                >
                                    {{ progress.custom_questions.length }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(
                                        question, questionIndex
                                    ) in progress.custom_questions"
                                    :key="
                                        question.key ||
                                        `custom-${questionIndex}`
                                    "
                                    class="rounded-xl border border-purple-100 bg-purple-50/40 p-4"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <p
                                                    class="font-medium text-gray-800"
                                                >
                                                    {{ question.question }}
                                                </p>

                                                <span
                                                    class="rounded bg-purple-100 px-1.5 py-0.5 text-xs font-medium text-purple-700"
                                                >
                                                    Custom
                                                </span>
                                            </div>

                                            <p
                                                class="mt-1 text-xs capitalize text-gray-400"
                                            >
                                                {{
                                                    question.type?.replaceAll(
                                                        "_",
                                                        " ",
                                                    )
                                                }}

                                                <template
                                                    v-if="question.added_by"
                                                >
                                                    · Added by
                                                    {{ question.added_by }}
                                                </template>
                                            </p>
                                        </div>

                                        <span
                                            v-if="question.question_rating"
                                            class="flex-shrink-0 text-sm font-semibold text-yellow-600"
                                        >
                                            {{ question.question_rating }}
                                            ★
                                        </span>
                                    </div>

                                    <p
                                        v-if="getAnswerText(question)"
                                        class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700"
                                    >
                                        {{ getAnswerText(question) }}
                                    </p>

                                    <p
                                        v-else
                                        class="mt-3 text-sm italic text-gray-400"
                                    >
                                        No answer recorded
                                    </p>

                                    <div
                                        v-if="question.interviewer_notes"
                                        class="mt-3 rounded-lg border border-purple-100 bg-white px-3 py-2"
                                    >
                                        <p
                                            class="text-xs font-medium text-purple-500"
                                        >
                                            Interviewer Note
                                        </p>

                                        <p
                                            class="mt-1 whitespace-pre-line text-xs text-purple-700"
                                        >
                                            {{ question.interviewer_notes }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No responses -->
                        <div
                            v-if="
                                !progress.template_responses?.length &&
                                !progress.custom_questions?.length
                            "
                            class="rounded-xl border border-dashed border-gray-200 p-6 text-center"
                        >
                            <p class="text-sm text-gray-400">
                                No question responses recorded for this round.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else
            class="rounded-xl border border-gray-100 bg-white py-12 text-center shadow-sm"
        >
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-xl"
            >
                📋
            </div>

            <p class="mt-3 text-sm text-gray-400">
                {{ emptyMessage }}
            </p>
        </div>
    </div>
</template>
