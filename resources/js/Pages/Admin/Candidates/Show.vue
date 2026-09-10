<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import StatusBadge from "@/Components/Common/StatusBadge.vue";
import Modal from "@/Components/Common/Modal.vue";
import { useForm, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import RichTextEditor from "@/Components/Common/RichTextEditor.vue";
import InterviewTimeline from "@/Components/Recruitment/InterviewTimeline.vue";
import CandidateScorecard from "@/Components/Recruitment/CandidateScorecard.vue";

const props = defineProps({
    candidate: Object,
    progress_history: Array,
    finalStatusOptions: Object,
    available_interviewers: Array,
    registration_form: Object,
    schedules: Array,
    rejectionReasons: Array,
    holdReasons: Array,
    processOptions: Array,
    scorecard: Object,
    ops_recommendation: {
        type: Object,
        default: null,
    },

    hr_discussion: {
        type: Object,
        default: null,
    },
});

const showStatusModal = ref(false);
const showApprovalModal = ref(false);
const showReassignModal = ref(false);
const showRegPanel = ref(false);
const selectedRound = ref(null);

const statusForm = useForm({
    final_status: props.candidate.final_status,
    hiring_notes: props.candidate.hiring_notes ?? "",
    final_ctc: props.candidate.final_ctc ?? "",
    final_in_hand: props.candidate.final_in_hand ?? "",
    final_pf_allowed: props.candidate.final_pf_allowed ?? false,
    final_designation: props.candidate.final_designation ?? "",
    rejection_reason: props.candidate.rejection_reason ?? "",
    hold_reason: props.candidate.hold_reason ?? "",
    process_name: props.candidate.process_name ?? "",
    remarks:
        props.candidate.rejection_remarks ?? props.candidate.hold_remarks ?? "",
    salary_annexure: props.candidate.salary_annexure ?? "",
});

const resetDynamicFields = () => {
    statusForm.clearErrors();
    statusForm.rejection_reason = "";
    statusForm.hold_reason = "";
    statusForm.remarks = "";
    if (!isSelected.value) {
        statusForm.process_name = "";
        statusForm.final_ctc = "";
        statusForm.final_in_hand = "";
        statusForm.final_pf_allowed = false;
        statusForm.final_designation = "";
    }
    if (isSelected.value && !statusForm.final_designation) {
        statusForm.final_designation =
            props.hr_discussion?.designation ??
            props.ops_recommendation?.designation ??
            "";
    }
    if (isSelected.value && !statusForm.final_in_hand) {
        statusForm.final_in_hand = props.hr_discussion?.salary_max ?? "";
    }
};

const reassignForm = useForm({
    new_interviewer_id: "",
});

const approvalForm = useForm({
    approval_status: props.candidate.approval_status ?? "pending",
    approval_notes: props.candidate.approval_notes ?? "",
});

const approvalStatusOptions = {
    pending: "Pending Approval",
    approved: "Approved",
    rejected: "Rejected",
};

const submitApproval = () => {
    approvalForm.put(
        route("recruitment.candidates.update-approval", props.candidate.id),
        {
            onSuccess: () => (showApprovalModal.value = false),
        },
    );
};

const submitStatus = () => {
    statusForm.put(
        route("recruitment.candidates.update-status", props.candidate.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                showStatusModal.value = false;
                resetDynamicFields();
            },
        },
    );
};

const isSelected = computed(() => statusForm.final_status === "selected");
const isNotSelected = computed(
    () => statusForm.final_status === "not_selected",
);
const isPending = computed(() => statusForm.final_status === "pending");

const formatSalary = (value) => {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    return `₹${Number(value).toLocaleString("en-IN")}`;
};

const formatSalaryRange = (minimum, maximum) => {
    if (!minimum && !maximum) {
        return "—";
    }

    if (minimum && maximum) {
        return `${formatSalary(minimum)} – ${formatSalary(maximum)}`;
    }

    return formatSalary(minimum || maximum);
};

const formatCandidateResponse = (value) => {
    const options = {
        accepted: "Accepted",
        negotiating: "Negotiating",
        pending: "Needs Time",
        declined: "Declined",
    };

    return options[value] ?? "—";
};

const salaryResponseClass = (value) => {
    const classes = {
        accepted: "border-green-200 bg-green-50 text-green-700",

        negotiating: "border-orange-200 bg-orange-50 text-orange-700",

        pending: "border-blue-200 bg-blue-50 text-blue-700",

        declined: "border-red-200 bg-red-50 text-red-700",
    };

    return classes[value] ?? "border-gray-200 bg-gray-50 text-gray-600";
};

const canSubmitStatus = computed(() => {
    if (statusForm.processing) {
        return false;
    }
    if (!statusForm.final_status) {
        return false;
    }
    if (
        isSelected.value &&
        (!statusForm.process_name ||
            !statusForm.final_ctc ||
            !statusForm.final_in_hand ||
            !statusForm.final_designation)
    ) {
        return false;
    }
    if (isNotSelected.value && !statusForm.rejection_reason) {
        return false;
    }
    if (isPending.value && !statusForm.hold_reason) {
        return false;
    }
    return true;
});

// Open reassign modal for a specific round
const openReassignModal = (round) => {
    selectedRound.value = round;
    reassignForm.new_interviewer_id = round.interviewer_id ?? "";
    showReassignModal.value = true;
};

// Submit reassign
const submitReassign = () => {
    if (!selectedRound.value || !reassignForm.new_interviewer_id) return;

    reassignForm.post(
        route("interviewer.interviews.reassign", {
            candidate: props.candidate.id,
            round: selectedRound.value.round_id,
        }),
        {
            onSuccess: () => {
                showReassignModal.value = false;
                selectedRound.value = null;
                reassignForm.reset();
            },
        },
    );
};

// Admin can reassign any round that is pending or in_progress
const canReassign = (round) => {
    return ["pending", "in_progress"].includes(round.status);
};

// Filter interviewers eligible for selected round
const eligibleInterviewers = computed(() => {
    if (!selectedRound.value) return [];
    return props.available_interviewers.filter((i) =>
        i.can_interview_rounds.includes(selectedRound.value.round_id),
    );
});

// Format registration form value
const formatRegValue = (field) => {
    const val = field.value;
    if (val === null || val === undefined || val === "") return "—";
    if (field.field_type === "checkbox" && Array.isArray(val)) {
        return val.join(", ") || "—";
    }
    if (field.field_type === "date" && val) {
        return new Date(val).toLocaleDateString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
    }
    if (field.field_type === "file" && typeof val === "object" && val?.name) {
        return val;
    }
    return val;
};

const fieldTypeIcon = (type) => {
    const icons = {
        text: "📝",
        email: "📧",
        phone: "📱",
        number: "🔢",
        date: "📅",
        textarea: "📋",
        dropdown: "🔽",
        radio: "⭕",
        checkbox: "☑️",
        file: "📎",
    };
    return icons[type] || "📝";
};

const roundStatusColor = (status) => {
    return (
        {
            not_started: "bg-gray-100 text-gray-600",
            pending: "bg-yellow-100 text-yellow-700",
            in_progress: "bg-blue-100 text-blue-700",
            completed: "bg-green-100 text-green-700",
            rejected: "bg-red-100 text-red-700",
        }[status] ?? "bg-gray-100 text-gray-600"
    );
};

// Schedule-related state
const showScheduleModal = ref(false);
const scheduleForm = useForm({
    scheduled_at: "",
    gmeet_link: "",
    notes: "",
    interviewer_id: "",
});

// Open schedule modal for a round
const openScheduleModal = (round) => {
    selectedRound.value = round;

    // Pre-fill if schedule exists
    const existing = props.schedules?.find(
        (s) => s.round_id === round.round_id,
    );
    if (existing) {
        scheduleForm.scheduled_at =
            existing.scheduled_at_raw?.slice(0, 16) ?? "";
        scheduleForm.gmeet_link = existing.gmeet_link ?? "";
        scheduleForm.notes = existing.notes ?? "";
        scheduleForm.interviewer_id = round.interviewer_id ?? "";
    } else {
        scheduleForm.reset();
        scheduleForm.interviewer_id = round.interviewer_id ?? "";
    }

    showScheduleModal.value = true;
};

// Submit schedule
const submitSchedule = () => {
    if (!selectedRound.value) return;

    scheduleForm.post(
        route("recruitment.candidates.rounds.schedule", {
            candidate: props.candidate.id,
            round: selectedRound.value.round_id,
        }),
        {
            onSuccess: () => {
                showScheduleModal.value = false;
                selectedRound.value = null;
                scheduleForm.reset();
            },
        },
    );
};

// Get schedule for a round
const getRoundSchedule = (roundId) => {
    return props.schedules?.find((s) => s.round_id === roundId);
};

// Format datetime for input (min value = now)
const minDateTime = computed(() => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
});

</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('recruitment.candidates.index')"
                        class="text-gray-400 hover:text-gray-600"
                        >←</Link
                    >
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ candidate.name }}
                        </h1>
                        <p class="text-sm text-gray-500">
                            {{ candidate.position }} ·
                            {{ candidate.profile_label }} ·
                            {{ candidate.branch }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <StatusBadge :status="candidate.current_status" />
                    <StatusBadge
                        :status="candidate.final_status"
                        type="final"
                    />
                    <button
                        v-if="registration_form"
                        @click="showRegPanel = true"
                        class="btn-secondary text-sm bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100"
                    >
                        📝 View Registration
                    </button>
                    <button
                        v-if="candidate.requires_approval"
                        @click="showApprovalModal = true"
                        class="btn-secondary text-sm bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100"
                    >
                        ⚠️ Approval
                    </button>
                    <button
                        @click="showStatusModal = true"
                        class="btn-primary text-sm"
                    >
                        Update Status
                    </button>
                    <Link
                        v-if="
                            candidate.final_status === 'selected' &&
                            !candidate.active_offer
                        "
                        :href="
                            route(
                                'recruitment.candidates.offers.create',
                                candidate.id,
                            )
                        "
                        class="btn-primary bg-purple-600 hover:bg-purple-700"
                    >
                        Generate Offer
                    </Link>
                    <Link
                        v-if="candidate.active_offer"
                        :href="
                            route(
                                'recruitment.offers.show',
                                candidate.active_offer.id,
                            )
                        "
                        class="btn-primary bg-purple-600 hover:bg-purple-700"
                    >
                        View Offer
                    </Link>
                </div>
            </div>

            <!-- Salary Logic Banner -->
            <div
                v-if="candidate.requires_salary_hr"
                class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-800"
            >
                <strong>💰 TL/QA/AM/OM or Other Profile:</strong> Salary offer
                is <strong>mandatory</strong> in the HR round for this
                candidate.
                <span v-if="candidate.latest_salary_offer">
                    Latest offer:
                    <strong>{{ candidate.latest_salary_offer }}</strong></span
                >
            </div>
            <div
                v-if="candidate.requires_approval"
                :class="[
                    'rounded-xl p-4 text-sm',
                    candidate.approval_status === 'approved'
                        ? 'bg-green-50 border border-green-200 text-green-800'
                        : candidate.approval_status === 'rejected'
                          ? 'bg-red-50 border border-red-200 text-red-800'
                          : 'bg-amber-50 border border-amber-200 text-amber-800',
                ]"
            >
                <strong>⚠️ Rejoining approval required:</strong>
                <span v-if="candidate.approval_status === 'approved'"
                    >Approval granted.</span
                >
                <span v-else-if="candidate.approval_status === 'rejected'"
                    >Approval rejected.</span
                >
                <span v-else>Approval is pending.</span>
                <div class="mt-2 text-xs">
                    <span class="font-semibold">Status:</span>
                    {{ candidate.approval_status_label || "Pending Approval" }}
                    <span v-if="candidate.old_employee">
                        · Previous employee:
                        {{ candidate.old_employee.name }}</span
                    >
                </div>
            </div>
            <div
                v-else-if="candidate.salary_post_ops"
                class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800"
            >
                <strong>📊 Advisor/Executive Profile:</strong> Salary discussion
                takes place during the OPS round.
                <span v-if="candidate.latest_salary_offer">
                    Latest offer:
                    <strong>{{ candidate.latest_salary_offer }}</strong></span
                >
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Candidate Info -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3"
                >
                    <h2 class="font-semibold text-gray-800">Candidate Info</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Email</dt>
                            <dd class="text-gray-800 break-all">
                                {{ candidate.email }}
                            </dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Phone</dt>
                            <dd class="text-gray-800">{{ candidate.phone }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Branch</dt>
                            <dd class="text-gray-800">
                                {{ candidate.branch }}
                            </dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Rounds Done</dt>
                            <dd class="text-gray-800 font-semibold">
                                {{ candidate.completed_rounds }}
                            </dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Avg Rating</dt>
                            <dd class="text-yellow-600 font-semibold">
                                {{
                                    candidate.average_rating
                                        ? candidate.average_rating + " ★"
                                        : "—"
                                }}
                            </dd>
                        </div>
                        <div v-if="candidate.final_ctc" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Final CTC</dt>
                            <dd class="text-green-700 font-semibold">
                                ₹{{
                                    Number(candidate.final_ctc).toLocaleString(
                                        "en-IN",
                                    )
                                }}
                            </dd>
                        </div>
                        <div v-if="candidate.final_in_hand" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Final In-Hand</dt>
                            <dd class="text-green-700 font-semibold">
                                ₹{{
                                    Number(
                                        candidate.final_in_hand,
                                    ).toLocaleString("en-IN")
                                }}
                            </dd>
                        </div>
                        <div
                            v-if="candidate.final_pf_allowed !== null"
                            class="flex gap-2"
                        >
                            <dt class="w-24 text-gray-500">PF Allowed</dt>
                            <dd class="text-gray-800">
                                {{ candidate.final_pf_allowed ? "Yes" : "No" }}
                            </dd>
                        </div>
                        <div
                            v-if="candidate.final_designation"
                            class="flex gap-2"
                        >
                            <dt class="w-24 text-gray-500">Designation</dt>
                            <dd class="text-gray-800">
                                {{ candidate.final_designation }}
                            </dd>
                        </div>
                        <div v-if="candidate.hiring_notes" class="pt-2">
                            <p class="text-xs text-gray-500 font-medium mb-1">
                                Hiring Notes
                            </p>
                            <p
                                class="text-sm text-gray-700 bg-gray-50 rounded-lg p-2"
                            >
                                {{ candidate.hiring_notes }}
                            </p>
                        </div>
                        <div v-if="candidate.process_name" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Process</dt>
                            <dd class="text-green-700 font-semibold">
                                {{ candidate.process_name }}
                            </dd>
                        </div>

                        <div
                            v-if="candidate.rejection_reason"
                            class="flex gap-2"
                        >
                            <dt class="w-24 text-gray-500">Rejected</dt>
                            <dd class="text-red-700">
                                {{ candidate.rejection_reason }}
                                <p
                                    v-if="candidate.rejection_remarks"
                                    class="text-xs text-gray-500 mt-1"
                                >
                                    {{ candidate.rejection_remarks }}
                                </p>
                            </dd>
                        </div>

                        <div v-if="candidate.hold_reason" class="flex gap-2">
                            <dt class="w-24 text-gray-500">On Hold</dt>
                            <dd class="text-amber-700">
                                {{ candidate.hold_reason }}
                                <p
                                    v-if="candidate.hold_remarks"
                                    class="text-xs text-gray-500 mt-1"
                                >
                                    {{ candidate.hold_remarks }}
                                </p>
                            </dd>
                        </div>
                        <div v-if="candidate.salary_annexure" class="pt-2">
                            <p class="text-xs text-gray-500 font-medium mb-1">
                                Salary Annexure
                            </p>
                            <div
                                class="text-sm text-gray-700 bg-white rounded-lg p-3 border border-gray-200 prose prose-sm max-w-none"
                                v-html="candidate.salary_annexure"
                            ></div>
                        </div>
                    </dl>
                    <a
                        v-if="candidate.resume_path"
                        :href="`/storage/${candidate.resume_path}`"
                        target="_blank"
                        class="block mt-2 text-center text-sm text-indigo-600 border border-indigo-200 rounded-lg py-2 hover:bg-indigo-50 transition"
                    >
                        📄 View Resume
                    </a>
                </div>

                <!-- Round Progress Timeline -->
                <div class="lg:col-span-2 space-y-6">
                    <CandidateScorecard :scorecard="scorecard" />
                    <InterviewTimeline
                        :history="progress_history"
                        :schedules="schedules"
                        :show-admin-actions="true"
                        @reassign="openReassignModal"
                        @schedule="openScheduleModal"
                    />
                </div>
            </div>
        </div>

        <!-- Approval Status Modal -->
        <Modal
            :show="showApprovalModal"
            title="Update Rejoin Approval"
            max-width="lg"
            @close="showApprovalModal = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="label">Approval Status</label>
                    <select
                        v-model="approvalForm.approval_status"
                        class="input-field"
                    >
                        <option
                            v-for="(label, key) in approvalStatusOptions"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="label">Approval Notes</label>
                    <textarea
                        v-model="approvalForm.approval_notes"
                        rows="3"
                        class="input-field"
                        placeholder="Enter approval notes or reason for rejection..."
                    />
                </div>
            </div>
            <template #footer>
                <button
                    @click="showApprovalModal = false"
                    class="btn-secondary"
                >
                    Cancel
                </button>
                <button
                    @click="submitApproval"
                    :disabled="approvalForm.processing"
                    class="btn-primary"
                >
                    {{
                        approvalForm.processing ? "Saving..." : "Save Approval"
                    }}
                </button>
            </template>
        </Modal>

        <!-- Update Status Modal -->
        <Modal
            :show="showStatusModal"
            title="Hiring Decision Center"
            max-width="5xl"
            @close="showStatusModal = false"
        >
            <div
                class="max-h-[78vh] space-y-6 overflow-y-auto px-1 hide-scrollbar"
            >
                <!-- Introduction -->
                <div
                    class="rounded-xl border border-indigo-200 bg-indigo-50 p-4"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="font-semibold text-indigo-900">
                                Final Recruitment Review
                            </p>

                            <p class="mt-1 text-sm text-indigo-700">
                                Review interview results, OPS recommendations
                                and HR salary discussions before finalizing the
                                candidate's hiring decision.
                            </p>
                        </div>

                        <span
                            class="w-fit rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-medium text-indigo-700"
                        >
                            Final Stage
                        </span>
                    </div>
                </div>

                <!-- Candidate Summary -->
                <section class="rounded-xl border border-gray-200 bg-white p-5">
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                Candidate
                            </p>

                            <h3
                                class="mt-1 text-xl font-semibold text-gray-900"
                            >
                                {{ candidate.name }}
                            </h3>

                            <div
                                class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500"
                            >
                                <span>
                                    {{ candidate.email || "—" }}
                                </span>

                                <span>
                                    {{ candidate.phone || "—" }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <p
                                    class="text-xs uppercase tracking-wide text-gray-500"
                                >
                                    Position
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-900"
                                >
                                    {{ candidate.position || "—" }}
                                </p>
                            </div>

                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <p
                                    class="text-xs uppercase tracking-wide text-gray-500"
                                >
                                    Profile
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-900"
                                >
                                    {{ candidate.profile_label || "—" }}
                                </p>
                            </div>

                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <p
                                    class="text-xs uppercase tracking-wide text-gray-500"
                                >
                                    Average Rating
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-900"
                                >
                                    {{
                                        candidate.average_rating
                                            ? `${candidate.average_rating}/5`
                                            : "—"
                                    }}
                                </p>
                            </div>

                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <p
                                    class="text-xs uppercase tracking-wide text-gray-500"
                                >
                                    Rounds Completed
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-900"
                                >
                                    {{ candidate.completed_rounds ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Salary Journey -->
                <section
                    class="rounded-xl border border-gray-200 bg-gray-50 p-5"
                >
                    <div>
                        <h3 class="font-semibold text-gray-900">
                            Salary Decision Journey
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Compare the OPS recommendation, HR discussion and
                            final approved offer.
                        </p>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <!-- OPS Recommendation -->
                        <div
                            class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <p class="font-semibold text-blue-900">
                                    OPS Recommendation
                                </p>

                                <span
                                    class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                >
                                    Step 1
                                </span>
                            </div>

                            <template v-if="ops_recommendation">
                                <div class="mt-4">
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-blue-600"
                                    >
                                        Suggested Salary
                                    </p>

                                    <p
                                        class="mt-1 text-lg font-semibold text-gray-900"
                                    >
                                        {{
                                            formatSalaryRange(
                                                ops_recommendation.salary_min,
                                                ops_recommendation.salary_max,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-blue-600"
                                    >
                                        Suggested Designation
                                    </p>

                                    <p class="mt-1 font-medium text-gray-900">
                                        {{
                                            ops_recommendation.designation ||
                                            "—"
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-if="ops_recommendation.interviewer"
                                    class="mt-4"
                                >
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-blue-600"
                                    >
                                        Recommended By
                                    </p>

                                    <p class="mt-1 text-sm text-gray-700">
                                        {{ ops_recommendation.interviewer }}
                                    </p>
                                </div>

                                <div
                                    v-if="ops_recommendation.feedback"
                                    class="mt-4 rounded-lg border border-blue-100 bg-white/70 p-3"
                                >
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                    >
                                        OPS Feedback
                                    </p>

                                    <p
                                        class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700"
                                    >
                                        {{ ops_recommendation.feedback }}
                                    </p>
                                </div>
                            </template>

                            <div
                                v-else
                                class="mt-4 rounded-lg border border-dashed border-blue-200 p-4 text-sm text-blue-700"
                            >
                                No OPS salary recommendation was recorded.
                            </div>
                        </div>

                        <!-- HR Discussion -->
                        <div
                            class="rounded-xl border border-orange-200 bg-orange-50 p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <p class="font-semibold text-orange-900">
                                    HR Discussion
                                </p>

                                <span
                                    class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-medium text-orange-700"
                                >
                                    Step 2
                                </span>
                            </div>

                            <template v-if="hr_discussion">
                                <div class="mt-4">
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-orange-600"
                                    >
                                        Discussed Salary
                                    </p>

                                    <p
                                        class="mt-1 text-lg font-semibold text-gray-900"
                                    >
                                        {{
                                            formatSalaryRange(
                                                hr_discussion.salary_min,
                                                hr_discussion.salary_max,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-orange-600"
                                    >
                                        Discussed Designation
                                    </p>

                                    <p class="mt-1 font-medium text-gray-900">
                                        {{ hr_discussion.designation || "—" }}
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-orange-600"
                                    >
                                        Candidate Response
                                    </p>

                                    <span
                                        :class="[
                                            'mt-2 inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                            salaryResponseClass(
                                                hr_discussion.candidate_response,
                                            ),
                                        ]"
                                    >
                                        {{
                                            formatCandidateResponse(
                                                hr_discussion.candidate_response,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    v-if="hr_discussion.feedback"
                                    class="mt-4 rounded-lg border border-orange-100 bg-white/70 p-3"
                                >
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                    >
                                        HR Feedback
                                    </p>

                                    <p
                                        class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700"
                                    >
                                        {{ hr_discussion.feedback }}
                                    </p>
                                </div>
                            </template>

                            <div
                                v-else
                                class="mt-4 rounded-lg border border-dashed border-orange-200 p-4 text-sm text-orange-700"
                            >
                                No completed HR salary discussion was found.
                            </div>
                        </div>

                        <!-- Final Approved Offer Preview -->
                        <div
                            :class="[
                                'rounded-xl border p-4',
                                isSelected
                                    ? 'border-green-200 bg-green-50'
                                    : 'border-gray-200 bg-white',
                            ]"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <p
                                    :class="[
                                        'font-semibold',
                                        isSelected
                                            ? 'text-green-900'
                                            : 'text-gray-900',
                                    ]"
                                >
                                    Final Approved Offer
                                </p>

                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-medium',
                                        isSelected
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    Step 3
                                </span>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Final CTC
                                </p>

                                <p
                                    class="mt-1 text-lg font-semibold text-gray-900"
                                >
                                    {{ formatSalary(statusForm.final_ctc) }}
                                </p>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Final In-Hand
                                </p>

                                <p class="mt-1 font-medium text-gray-900">
                                    {{ formatSalary(statusForm.final_in_hand) }}
                                </p>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Final Designation
                                </p>

                                <p class="mt-1 font-medium text-gray-900">
                                    {{ statusForm.final_designation || "—" }}
                                </p>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    PF
                                </p>

                                <p class="mt-1 font-medium text-gray-900">
                                    {{
                                        statusForm.final_pf_allowed
                                            ? "Allowed"
                                            : "Not Allowed"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Hiring Decision -->
                <section class="rounded-xl border border-gray-200 bg-white p-5">
                    <div>
                        <h3 class="font-semibold text-gray-900">
                            Hiring Decision
                            <span class="text-red-500">*</span>
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Select the final recruitment outcome for this
                            candidate.
                        </p>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <!-- Selected -->
                        <label
                            :class="[
                                'cursor-pointer rounded-xl border p-4 transition',
                                isSelected
                                    ? 'border-green-500 bg-green-50 ring-1 ring-green-500'
                                    : 'border-gray-200 bg-white hover:border-green-300 hover:bg-green-50/40',
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="statusForm.final_status"
                                    type="radio"
                                    value="selected"
                                    class="mt-1"
                                    @change="resetDynamicFields"
                                />

                                <div>
                                    <p class="font-semibold text-gray-900">
                                        Selected
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-5 text-gray-500"
                                    >
                                        Approve the candidate and finalize the
                                        employment offer.
                                    </p>
                                </div>
                            </div>
                        </label>

                        <!-- Not Selected -->
                        <label
                            :class="[
                                'cursor-pointer rounded-xl border p-4 transition',
                                isNotSelected
                                    ? 'border-red-500 bg-red-50 ring-1 ring-red-500'
                                    : 'border-gray-200 bg-white hover:border-red-300 hover:bg-red-50/40',
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="statusForm.final_status"
                                    type="radio"
                                    value="not_selected"
                                    class="mt-1"
                                    @change="resetDynamicFields"
                                />

                                <div>
                                    <p class="font-semibold text-gray-900">
                                        Not Selected
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-5 text-gray-500"
                                    >
                                        Reject the candidate and record the
                                        appropriate reason.
                                    </p>
                                </div>
                            </div>
                        </label>

                        <!-- Pending -->
                        <label
                            :class="[
                                'cursor-pointer rounded-xl border p-4 transition',
                                isPending
                                    ? 'border-yellow-500 bg-yellow-50 ring-1 ring-yellow-500'
                                    : 'border-gray-200 bg-white hover:border-yellow-300 hover:bg-yellow-50/40',
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="statusForm.final_status"
                                    type="radio"
                                    value="pending"
                                    class="mt-1"
                                    @change="resetDynamicFields"
                                />

                                <div>
                                    <p class="font-semibold text-gray-900">
                                        Keep On Hold
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-5 text-gray-500"
                                    >
                                        Keep the hiring decision pending for
                                        further review.
                                    </p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="statusForm.errors.final_status"
                        class="mt-2 text-xs text-red-500"
                    >
                        {{ statusForm.errors.final_status }}
                    </p>
                </section>

                <!-- Selected -->
                <section
                    v-if="isSelected"
                    class="space-y-5 rounded-xl border border-green-200 bg-green-50 p-5 animate-fade-in"
                >
                    <div>
                        <h3 class="font-semibold text-green-900">
                            Employment and Offer Details
                        </h3>

                        <p class="mt-1 text-sm text-green-700">
                            Enter the final approved employment and compensation
                            details.
                        </p>
                    </div>

                    <div>
                        <label class="label">
                            Assigned Process / Project
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="statusForm.process_name"
                            class="input-field"
                            :class="{
                                'border-red-300':
                                    statusForm.errors.process_name,
                            }"
                        >
                            <option value="">Select process...</option>

                            <option
                                v-for="process in processOptions"
                                :key="process"
                                :value="process"
                            >
                                {{ process }}
                            </option>
                        </select>

                        <p
                            v-if="statusForm.errors.process_name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ statusForm.errors.process_name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">
                                Final CTC Offered
                                <span class="text-red-500"> * </span>
                            </label>

                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"
                                >
                                    ₹
                                </span>

                                <input
                                    v-model="statusForm.final_ctc"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="input-field pl-8"
                                    placeholder="Example: 30000"
                                />
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Enter the final approved CTC.
                            </p>

                            <p
                                v-if="statusForm.errors.final_ctc"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ statusForm.errors.final_ctc }}
                            </p>
                        </div>

                        <div>
                            <label class="label">
                                Final In-Hand Offered
                                <span class="text-red-500"> * </span>
                            </label>

                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"
                                >
                                    ₹
                                </span>

                                <input
                                    v-model="statusForm.final_in_hand"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="input-field pl-8"
                                    placeholder="Example: 26000"
                                />
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Expected employee take-home salary.
                            </p>

                            <p
                                v-if="statusForm.errors.final_in_hand"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ statusForm.errors.final_in_hand }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">
                                Final Designation
                                <span class="text-red-500"> * </span>
                            </label>

                            <input
                                v-model="statusForm.final_designation"
                                type="text"
                                class="input-field"
                                placeholder="Example: Senior Advisor"
                            />

                            <p
                                v-if="statusForm.errors.final_designation"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ statusForm.errors.final_designation }}
                            </p>
                        </div>

                        <div>
                            <label class="label"> PF Allowed </label>

                            <select
                                v-model="statusForm.final_pf_allowed"
                                class="input-field"
                            >
                                <option :value="true">Yes</option>

                                <option :value="false">No</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Not Selected -->
                <section
                    v-else-if="isNotSelected"
                    class="space-y-4 rounded-xl border border-red-200 bg-red-50 p-5 animate-fade-in"
                >
                    <div>
                        <h3 class="font-semibold text-red-900">
                            Rejection Details
                        </h3>

                        <p class="mt-1 text-sm text-red-700">
                            Record the primary reason for not selecting this
                            candidate.
                        </p>
                    </div>

                    <div>
                        <label class="label">
                            Rejection Reason
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="statusForm.rejection_reason"
                            class="input-field"
                            :class="{
                                'border-red-300':
                                    statusForm.errors.rejection_reason,
                            }"
                        >
                            <option value="">Select reason...</option>

                            <option
                                v-for="reason in rejectionReasons"
                                :key="reason"
                                :value="reason"
                            >
                                {{ reason }}
                            </option>
                        </select>

                        <p
                            v-if="statusForm.errors.rejection_reason"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ statusForm.errors.rejection_reason }}
                        </p>
                    </div>

                    <div>
                        <label class="label">
                            Rejection Remarks
                            <span class="font-normal text-gray-400">
                                (Optional)
                            </span>
                        </label>

                        <textarea
                            v-model="statusForm.remarks"
                            rows="3"
                            class="input-field"
                            placeholder="Additional details regarding the rejection..."
                        />

                        <p
                            v-if="statusForm.errors.remarks"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ statusForm.errors.remarks }}
                        </p>
                    </div>
                </section>

                <!-- On Hold -->
                <section
                    v-else-if="isPending"
                    class="space-y-4 rounded-xl border border-yellow-200 bg-yellow-50 p-5 animate-fade-in"
                >
                    <div>
                        <h3 class="font-semibold text-yellow-900">
                            Hold Details
                        </h3>

                        <p class="mt-1 text-sm text-yellow-700">
                            Record why the hiring decision requires additional
                            review.
                        </p>
                    </div>

                    <div>
                        <label class="label">
                            Hold Reason
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="statusForm.hold_reason"
                            class="input-field"
                            :class="{
                                'border-red-300': statusForm.errors.hold_reason,
                            }"
                        >
                            <option value="">Select reason...</option>

                            <option
                                v-for="reason in holdReasons"
                                :key="reason"
                                :value="reason"
                            >
                                {{ reason }}
                            </option>
                        </select>

                        <p
                            v-if="statusForm.errors.hold_reason"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ statusForm.errors.hold_reason }}
                        </p>
                    </div>

                    <div>
                        <label class="label">
                            Hold Remarks
                            <span class="font-normal text-gray-400">
                                (Optional)
                            </span>
                        </label>

                        <textarea
                            v-model="statusForm.remarks"
                            rows="3"
                            class="input-field"
                            placeholder="Explain what needs to be reviewed or confirmed..."
                        />

                        <p
                            v-if="statusForm.errors.remarks"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ statusForm.errors.remarks }}
                        </p>
                    </div>
                </section>

                <!-- Salary Annexure -->
                <section class="rounded-xl border border-gray-200 bg-white p-5">
                    <div>
                        <label class="label"> Salary Annexure </label>

                        <p class="mb-3 mt-1 text-sm text-gray-500">
                            Paste formatted salary structures, Excel tables,
                            screenshots, images or detailed compensation notes.
                        </p>

                        <RichTextEditor
                            v-model="statusForm.salary_annexure"
                            placeholder="Paste salary breakup, tables, screenshots or detailed notes here..."
                        />

                        <p
                            v-if="statusForm.errors.salary_annexure"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ statusForm.errors.salary_annexure }}
                        </p>
                    </div>
                </section>

                <!-- Internal Notes -->
                <section class="rounded-xl border border-gray-200 bg-white p-5">
                    <label class="label"> Internal Hiring Notes </label>

                    <p class="mb-3 mt-1 text-sm text-gray-500">
                        These notes are visible internally and are not part of
                        the candidate-facing offer.
                    </p>

                    <textarea
                        v-model="statusForm.hiring_notes"
                        rows="4"
                        class="input-field"
                        placeholder="Add final approval comments, joining conditions, document requirements or other internal notes..."
                    />

                    <p
                        v-if="statusForm.errors.hiring_notes"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ statusForm.errors.hiring_notes }}
                    </p>
                </section>

                <!-- Next Stage Preview -->
                <section
                    v-if="isSelected"
                    class="rounded-xl border border-purple-200 bg-purple-50 p-5"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h3 class="font-semibold text-purple-900">
                                Next Stage: Offer and Onboarding
                            </h3>

                            <p class="mt-1 text-sm text-purple-700">
                                After selection, the candidate can move to offer
                                acceptance, document collection, joining
                                confirmation and employee creation.
                            </p>
                        </div>

                        <span
                            class="w-fit rounded-full border border-purple-200 bg-white px-3 py-1 text-xs font-medium text-purple-700"
                        >
                            Upcoming
                        </span>
                    </div>

                    <div
                        class="mt-4 grid grid-cols-2 gap-3 text-sm md:grid-cols-4"
                    >
                        <div
                            class="rounded-lg border border-purple-100 bg-white/70 p-3 text-center text-purple-800"
                        >
                            Offer Generated
                        </div>

                        <div
                            class="rounded-lg border border-purple-100 bg-white/70 p-3 text-center text-purple-800"
                        >
                            Offer Accepted
                        </div>

                        <div
                            class="rounded-lg border border-purple-100 bg-white/70 p-3 text-center text-purple-800"
                        >
                            Joining Confirmed
                        </div>

                        <div
                            class="rounded-lg border border-purple-100 bg-white/70 p-3 text-center text-purple-800"
                        >
                            Employee Created
                        </div>
                    </div>
                </section>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="showStatusModal = false"
                    class="btn-secondary"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="submitStatus"
                    :disabled="!canSubmitStatus"
                    :class="[
                        'btn-primary',
                        !canSubmitStatus ? 'cursor-not-allowed opacity-50' : '',
                        isNotSelected
                            ? 'bg-red-600 hover:bg-red-700'
                            : isSelected
                              ? 'bg-green-600 hover:bg-green-700'
                              : isPending
                                ? 'bg-yellow-600 hover:bg-yellow-700'
                                : '',
                    ]"
                >
                    {{
                        statusForm.processing
                            ? "Saving Decision..."
                            : isSelected
                              ? "Finalize Selection"
                              : isNotSelected
                                ? "Confirm Rejection"
                                : isPending
                                  ? "Place Candidate On Hold"
                                  : "Save Hiring Decision"
                    }}
                </button>
            </template>
        </Modal>

        <!-- Reassign Interviewer Modal -->
        <Modal
            :show="showReassignModal"
            title="Reassign Interviewer"
            @close="showReassignModal = false"
        >
            <div class="space-y-4">
                <div v-if="selectedRound" class="bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-500">Round</p>
                    <p class="font-medium text-gray-800">
                        {{ selectedRound.round_name }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Current:
                        {{ selectedRound.interviewer ?? "Not assigned" }}
                    </p>
                </div>

                <div>
                    <label class="label"
                        >Select New Interviewer
                        <span class="text-red-500">*</span></label
                    >
                    <select
                        v-model="reassignForm.new_interviewer_id"
                        class="input-field"
                    >
                        <option value="">Choose interviewer...</option>
                        <option
                            v-for="i in eligibleInterviewers"
                            :key="i.id"
                            :value="i.id"
                        >
                            {{ i.name }} {{ i.emp ? `(${i.emp})` : "" }}
                        </option>
                    </select>
                    <p
                        v-if="eligibleInterviewers.length === 0"
                        class="text-xs text-red-500 mt-1"
                    >
                        No eligible interviewers found for this round. Assign
                        round permissions in employee settings first.
                    </p>
                    <p
                        v-if="reassignForm.errors.new_interviewer_id"
                        class="error"
                    >
                        {{ reassignForm.errors.new_interviewer_id }}
                    </p>
                </div>
            </div>
            <template #footer>
                <button
                    @click="showReassignModal = false"
                    class="btn-secondary"
                >
                    Cancel
                </button>
                <button
                    @click="submitReassign"
                    :disabled="
                        !reassignForm.new_interviewer_id ||
                        reassignForm.processing
                    "
                    class="btn-primary"
                >
                    {{
                        reassignForm.processing
                            ? "Reassigning..."
                            : "Confirm Reassign"
                    }}
                </button>
            </template>
        </Modal>

        <!-- Registration Form Side Panel -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div
                v-if="showRegPanel"
                class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl border-l border-gray-200 flex flex-col"
            >
                <!-- Panel Header -->
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-indigo-50"
                >
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            📝 Registration Details
                        </h2>
                        <p
                            v-if="registration_form?.submitted_at"
                            class="text-xs text-gray-500"
                        >
                            Submitted on {{ registration_form.submitted_at }}
                        </p>
                    </div>
                    <button
                        @click="showRegPanel = false"
                        class="h-8 w-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition"
                    >
                        ✕
                    </button>
                </div>

                <!-- Panel Content -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div
                        v-if="!registration_form || !registration_form.fields"
                        class="text-center py-12 text-gray-400"
                    >
                        <p class="text-4xl mb-3">📭</p>
                        <p>No registration form data available.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="field in registration_form.fields"
                            :key="field.field_label"
                            class="bg-gray-50 rounded-lg p-4 border border-gray-100"
                        >
                            <div class="flex items-start gap-3">
                                <span class="text-lg">{{
                                    fieldTypeIcon(field.field_type)
                                }}</span>
                                <div class="flex-1 min-w-0">
                                    <label
                                        class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
                                    >
                                        {{ field.field_label }}
                                        <span
                                            v-if="field.is_mandatory"
                                            class="text-red-400"
                                            >*</span
                                        >
                                    </label>

                                    <!-- File value -->
                                    <div
                                        v-if="
                                            field.field_type === 'file' &&
                                            formatRegValue(field)?.name
                                        "
                                    >
                                        <a
                                            :href="formatRegValue(field).url"
                                            target="_blank"
                                            class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium mt-1"
                                        >
                                            <span>📎</span>
                                            {{ formatRegValue(field).name }}
                                        </a>
                                    </div>
                                    <!-- Default value -->
                                    <p
                                        v-else
                                        class="text-sm text-gray-800 mt-1 break-words"
                                    >
                                        {{ formatRegValue(field) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Candidate Summary -->
                    <div
                        class="bg-indigo-50 rounded-xl p-4 border border-indigo-100 mt-6"
                    >
                        <h3 class="text-sm font-semibold text-indigo-900 mb-3">
                            Candidate Summary
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Name</span>
                                <span class="text-gray-800 font-medium">{{
                                    candidate.name
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Email</span>
                                <span class="text-gray-800 break-all">{{
                                    candidate.email
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Phone</span>
                                <span class="text-gray-800">{{
                                    candidate.phone
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Position</span>
                                <span class="text-gray-800">{{
                                    candidate.position
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Profile</span>
                                <span class="text-gray-800">{{
                                    candidate.profile_label
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Branch</span>
                                <span class="text-gray-800">{{
                                    candidate.branch
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <button
                        @click="showRegPanel = false"
                        class="w-full py-2.5 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 transition"
                    >
                        Close Panel
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Backdrop for registration panel -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showRegPanel"
                @click="showRegPanel = false"
                class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm"
            ></div>
        </Transition>

        <!-- NEW: Schedule Interview Modal -->
        <Modal
            :show="showScheduleModal"
            :title="
                getRoundSchedule(selectedRound?.round_id)
                    ? 'Edit Schedule'
                    : 'Schedule Interview'
            "
            max-width="lg"
            @close="showScheduleModal = false"
        >
            <div class="space-y-4">
                <div v-if="selectedRound" class="bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-500">Round</p>
                    <p class="font-medium text-gray-800">
                        {{ selectedRound.round_name }}
                    </p>
                    <p class="text-xs text-gray-400">
                        Candidate: {{ candidate.name }}
                    </p>
                </div>

                <div>
                    <label class="label"
                        >Interviewer <span class="text-red-500">*</span></label
                    >
                    <select
                        v-model="scheduleForm.interviewer_id"
                        class="input-field"
                        required
                    >
                        <option value="">Select interviewer</option>
                        <option
                            v-for="i in eligibleInterviewers"
                            :key="i.id"
                            :value="i.id"
                        >
                            {{ i.name }} {{ i.emp ? `(${i.emp})` : "" }}
                        </option>
                    </select>
                    <p v-if="scheduleForm.errors.interviewer_id" class="error">
                        {{ scheduleForm.errors.interviewer_id }}
                    </p>
                </div>

                <div>
                    <label class="label"
                        >Date & Time <span class="text-red-500">*</span></label
                    >
                    <input
                        v-model="scheduleForm.scheduled_at"
                        type="datetime-local"
                        :min="minDateTime"
                        class="input-field"
                        required
                    />
                    <p v-if="scheduleForm.errors.scheduled_at" class="error">
                        {{ scheduleForm.errors.scheduled_at }}
                    </p>
                </div>

                <div>
                    <label class="label"
                        >Google Meet Link
                        <span class="text-red-500">*</span></label
                    >
                    <input
                        v-model="scheduleForm.gmeet_link"
                        type="url"
                        placeholder="https://meet.google.com/xxx-xxxx-xxx"
                        class="input-field"
                        required
                    />
                    <p class="text-xs text-gray-400 mt-1">
                        Paste the Google Meet link here
                    </p>
                    <p v-if="scheduleForm.errors.gmeet_link" class="error">
                        {{ scheduleForm.errors.gmeet_link }}
                    </p>
                </div>

                <div>
                    <label class="label">Notes (Optional)</label>
                    <textarea
                        v-model="scheduleForm.notes"
                        rows="2"
                        class="input-field"
                        placeholder="Any special instructions for the candidate..."
                    />
                </div>
            </div>
            <template #footer>
                <button
                    @click="showScheduleModal = false"
                    class="btn-secondary"
                >
                    Cancel
                </button>
                <button
                    @click="submitSchedule"
                    :disabled="
                        scheduleForm.processing ||
                        !scheduleForm.scheduled_at ||
                        !scheduleForm.gmeet_link
                    "
                    class="btn-primary"
                >
                    {{
                        scheduleForm.processing
                            ? "Saving..."
                            : getRoundSchedule(selectedRound?.round_id)
                              ? "Update & Notify"
                              : "Schedule & Send Email"
                    }}
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
