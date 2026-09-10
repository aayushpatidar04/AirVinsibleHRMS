<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import Modal from "@/Components/Common/Modal.vue";
import { ref, watch, reactive } from "vue";

const props = defineProps({
    candidate: Object,
    interview: Object,
    ops_salary_recommendation: {
        type: Object,
        default: null,
    },
    questions: Array,
    previous_feedback: Array,
    next_rounds: Array,
    registration_form: Object,
    scoreCategories: Object,
});

const started = ref(
    props.interview.status === "in_progress" ||
        props.interview.status === "completed",
);
const showReject = ref(false);
const showComplete = ref(false);
const showRegPanel = ref(false);

// BUG FIX: Use reactive() instead of ref() for nested reactivity
// This ensures Vue tracks changes to nested properties
const responseData = reactive({});

// BUG FIX: Use reactive for saving state too
const saving = reactive({});

const initializeQuestionResponse = (question) => {
    // Skip if already initialized
    if (responseData[question.key]) {
        return;
    }

    // BUG FIX: Properly check if response exists and has data
    const savedResponse = question.response || {};

    responseData[question.key] = {
        response_text: savedResponse.response_text ?? "",
        rating_value: savedResponse.rating_value ?? null,
        yes_no_value: savedResponse.yes_no_value ?? null,
        selected_options: savedResponse.selected_options ?? [],
        interviewer_notes: savedResponse.interviewer_notes ?? "",
        question_rating: savedResponse.question_rating ?? null,
    };

    // Initialize saving state
    saving[question.key] = false;
};

// Initialize all questions
props.questions.forEach(initializeQuestionResponse);

// Watch for new questions (e.g., after adding custom question)
watch(
    () => props.questions,
    (newQuestions) => {
        newQuestions.forEach(initializeQuestionResponse);
    },
    { deep: true },
);

const saveResponse = (question) => {
    // BUG FIX: Set saving state properly
    saving[question.key] = true;

    const payload = {
        question_id: question.id,
        question_source: question.source,
        ...responseData[question.key],
    };

    router.post(
        route("interviewer.interviews.response.save", {
            candidate: props.candidate.id,
            round: props.interview.round_id,
        }),
        payload,
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {},
            onError: (errors) => {
                console.error("Save failed:", errors);
            },
            onFinish: () => {
                saving[question.key] = false;
            },
        },
    );
};

// Complete form
const completeForm = useForm({
    overall_feedback: props.interview.overall_feedback ?? "",
    overall_rating: props.interview.overall_rating ?? 3,
    next_round_id: "",
    next_interviewer_id: "",
    salary_offer_min: props.interview.salary_offer_min ?? "",
    salary_offer_max: props.interview.salary_offer_max ?? "",
    offered_designation: props.interview.offered_designation ?? "",
    salary_offer_status: props.interview.salary_offer_status ?? "",
    completion_action: props.interview.is_hr_round
        ? "mark_all_rounds_cleared"
        : "continue_to_next_round",
});

const salaryResponseOptions = [
    {
        value: "accepted",
        label: "Accepted",
        description: "Candidate accepted the discussed salary range.",
        activeClass: "border-green-500 bg-green-50 ring-1 ring-green-500",
    },
    {
        value: "negotiating",
        label: "Negotiating",
        description: "The salary discussion is still under negotiation.",
        activeClass: "border-orange-500 bg-orange-50 ring-1 ring-orange-500",
    },
    {
        value: "pending",
        label: "Needs Time",
        description: "Candidate needs time before confirming the range.",
        activeClass: "border-blue-500 bg-blue-50 ring-1 ring-blue-500",
    },
    {
        value: "declined",
        label: "Declined",
        description: "Candidate declined discussed salary range.",
        activeClass: "border-red-500 bg-red-50 ring-1 ring-red-500",
    },
];

const formatSalary = (value) => {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    return `₹${Number(value).toLocaleString("en-IN")}`;
};

// Fetch interviewers for selected round
const available_interviewers = ref([]);
const loading_interviewers = ref(false);

const fetchInterviewers = async (roundId) => {
    if (!roundId) {
        available_interviewers.value = [];
        completeForm.next_interviewer_id = "";
        return;
    }

    loading_interviewers.value = true;
    try {
        const response = await fetch(
            route("interviewer.interviews.get-interviewers", {
                candidate: props.candidate.id,
                round: roundId,
            }),
        );
        const data = await response.json();
        available_interviewers.value = data.interviewers || [];
        completeForm.next_interviewer_id = "";
    } catch (error) {
        console.error("Failed to fetch interviewers:", error);
        available_interviewers.value = [];
    } finally {
        loading_interviewers.value = false;
    }
};

watch(
    () => completeForm.next_round_id,
    (newRoundId) => {
        completeForm.next_interviewer_id = "";
        available_interviewers.value = [];

        if (newRoundId) {
            fetchInterviewers(newRoundId);
        }
    },
);

watch(
    () => completeForm.completion_action,
    (action) => {
        if (action === "mark_all_rounds_cleared") {
            completeForm.next_round_id = "";
            completeForm.next_interviewer_id = "";
            available_interviewers.value = [];
        }
    },
);

const submitComplete = () => {
    completeForm.clearErrors();

    if (
        completeForm.completion_action === "continue_to_next_round" &&
        !completeForm.next_round_id
    ) {
        completeForm.setError(
            "next_round_id",
            "Please select the next interview round.",
        );

        return;
    }

    if (
        completeForm.completion_action === "continue_to_next_round" &&
        !completeForm.next_interviewer_id
    ) {
        completeForm.setError(
            "next_interviewer_id",
            "Please select an interviewer for the next round.",
        );

        return;
    }

    if (props.interview.is_ops_round) {
        if (
            completeForm.salary_offer_min === "" ||
            completeForm.salary_offer_min === null
        ) {
            completeForm.setError(
                "salary_offer_min",
                "Minimum suggested salary is required in the OPS round.",
            );

            return;
        }

        if (
            completeForm.salary_offer_max === "" ||
            completeForm.salary_offer_max === null
        ) {
            completeForm.setError(
                "salary_offer_max",
                "Maximum suggested salary is required in the OPS round.",
            );

            return;
        }

        if (
            Number(completeForm.salary_offer_max) <
            Number(completeForm.salary_offer_min)
        ) {
            completeForm.setError(
                "salary_offer_max",
                "Maximum salary must be greater than or equal to minimum salary.",
            );

            return;
        }
    }

    completeForm.post(
        route("interviewer.interviews.complete", {
            candidate: props.candidate.id,
            round: props.interview.round_id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                showComplete.value = false;
            },
        },
    );
};

// Reject form
const rejectForm = useForm({ rejection_reason: "", overall_feedback: "" });
const submitReject = () => {
    rejectForm.post(
        route("interviewer.interviews.reject", {
            candidate: props.candidate.id,
            round: props.interview.round_id,
        }),
        { onSuccess: () => (showReject.value = false) },
    );
};

// Custom question
const showCustomQ = ref(false);
const customQForm = useForm({
    question_text: "",
    question_type: "short_answer",
    score_category: "",
});
const submitCustomQ = () => {
    customQForm.post(
        route("interviewer.interviews.add-question", {
            candidate: props.candidate.id,
            round: props.interview.round_id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showCustomQ.value = false;
                customQForm.reset();
            },
        },
    );
};

const startInterview = () => {
    router.post(
        route("interviewer.interviews.start", {
            candidate: props.candidate.id,
            round: props.interview.round_id,
        }),
        {},
        { onSuccess: () => (started.value = true) },
    );
};

// Registration form helpers
const formatRegValue = (field) => {
    const val = field.value;
    if (val === null || val === undefined || val === "") return "—";
    if (field.field_type === "checkbox" && Array.isArray(val)) {
        return val.join(", ") || "—";
    }
    if (field.field_type === "radio" || field.field_type === "dropdown") {
        return val || "—";
    }
    if (field.field_type === "date" && val) {
        return new Date(val).toLocaleDateString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
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
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="
                            route('interviewer.candidates.show', candidate.id)
                        "
                        class="text-gray-400 hover:text-gray-600"
                        >←
                    </Link>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ interview.round_name }}
                            </h1>
                            <span
                                v-if="interview.is_hr_round"
                                class="text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full"
                                >HR Round</span
                            >
                            <span
                                v-if="interview.is_ops_round"
                                class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full"
                                >OPS Round</span
                            >
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ candidate.name }} · {{ candidate.position }} ·
                            {{ candidate.profile_label }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <span
                        v-if="interview.status === 'in_progress'"
                        class="text-xs bg-yellow-100 text-yellow-700 px-3 py-3 rounded-full font-medium"
                        >In Progress</span
                    >

                    <!-- NEW: View Registration Form Button -->
                    <button
                        v-if="registration_form"
                        @click="showRegPanel = true"
                        class="btn-secondary text-sm bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100"
                    >
                        📝 View Registration
                    </button>

                    <a
                        v-if="candidate.resume_path"
                        :href="`/storage/${candidate.resume_path}`"
                        target="_blank"
                        class="btn-secondary text-sm text-indigo-600 border border-indigo-200 rounded-lg py-2 hover:bg-indigo-100 transition"
                    >
                        📄 View Resume
                    </a>
                    <button
                        v-if="!started"
                        @click="startInterview"
                        class="btn-primary"
                    >
                        ▶ Start Interview
                    </button>
                    <template
                        v-if="started && interview.status !== 'completed'"
                    >
                        <button
                            @click="showCustomQ = true"
                            class="btn-secondary text-sm"
                        >
                            + Custom Question
                        </button>
                        <button
                            @click="showComplete = true"
                            class="btn-primary text-sm"
                        >
                            ✓ Complete Round
                        </button>
                        <button
                            @click="showReject = true"
                            class="btn-danger text-sm"
                        >
                            ✕ Reject
                        </button>
                    </template>
                </div>
            </div>

            <!-- Previous Feedback -->
            <div
                v-if="previous_feedback.length"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
            >
                <h2 class="font-semibold text-gray-800 mb-3">
                    Previous Round Feedback
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div
                        v-for="fb in previous_feedback"
                        :key="fb.round"
                        class="bg-gray-50 rounded-lg p-3 text-sm border border-gray-100"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-700">{{
                                fb.round
                            }}</span>
                            <span
                                v-if="fb.rating"
                                class="text-yellow-600 font-semibold"
                                >{{ fb.rating }} ★</span
                            >
                        </div>
                        <p class="text-gray-600 text-xs">
                            By {{ fb.interviewer }} on {{ fb.date }}
                        </p>
                        <p class="text-gray-700 mt-1">{{ fb.feedback }}</p>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div v-if="started" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">
                        Interview Questions ({{ questions.length }})
                    </h2>
                    <span class="text-xs text-gray-400"
                        >{{
                            questions.filter((q) => q.is_mandatory).length
                        }}
                        mandatory</span
                    >
                </div>

                <div
                    v-for="(q, idx) in questions"
                    :key="q.key"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="flex items-start gap-3 mb-4">
                        <span
                            class="h-7 w-7 rounded-full bg-teal-100 text-teal-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5"
                        >
                            {{ idx + 1 }}
                        </span>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800">
                                {{ q.question_text }}
                            </p>
                            <div class="flex gap-2 mt-1 flex-wrap">
                                <span class="text-xs text-gray-400">{{
                                    q.question_type.replace("_", " ")
                                }}</span>
                                <span
                                    v-if="q.is_mandatory"
                                    class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded"
                                    >Required</span
                                >
                                <span
                                    v-if="q.is_custom"
                                    class="text-xs bg-purple-100 text-purple-600 px-1.5 py-0.5 rounded"
                                    >Custom</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Candidate Response Input -->
                    <div class="space-y-3 pl-10">
                        <!-- Short / Long Answer -->
                        <!-- Short / Long Answer -->
                        <div
                            v-if="
                                ['short_answer', 'long_answer'].includes(
                                    q.question_type,
                                )
                            "
                        >
                            <label class="label">Candidate's Answer</label>
                            <component
                                :is="
                                    q.question_type === 'long_answer'
                                        ? 'textarea'
                                        : 'input'
                                "
                                :value="responseData[q.key]?.response_text"
                                @input="
                                    (e) => {
                                        responseData[q.key].response_text =
                                            e.target.value;
                                    }
                                "
                                :rows="4"
                                class="input-field"
                                placeholder="Record candidate's response..."
                            />
                        </div>

                        <!-- Rating Scale -->
                        <div v-if="q.question_type === 'rating_scale'">
                            <label class="label">Rating (1–5)</label>
                            <div class="flex gap-2">
                                <button
                                    v-for="n in 5"
                                    :key="n"
                                    type="button"
                                    @click="
                                        responseData[q.key].rating_value = n
                                    "
                                    :class="[
                                        'h-10 w-10 rounded-xl font-bold text-sm transition border',
                                        responseData[q.key].rating_value === n
                                            ? 'bg-teal-600 text-white border-teal-600'
                                            : 'bg-white text-gray-600 border-gray-300 hover:border-teal-400',
                                    ]"
                                >
                                    {{ n }}
                                </button>
                            </div>
                        </div>

                        <!-- Yes / No -->
                        <div v-if="q.question_type === 'yes_no'">
                            <label class="label">Answer</label>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="
                                        responseData[q.key].yes_no_value = true
                                    "
                                    :class="[
                                        'px-5 py-2 rounded-lg font-medium text-sm border transition',
                                        responseData[q.key].yes_no_value ===
                                        true
                                            ? 'bg-green-600 text-white border-green-600'
                                            : 'bg-white border-gray-300 hover:border-green-400',
                                    ]"
                                >
                                    Yes
                                </button>
                                <button
                                    type="button"
                                    @click="
                                        responseData[q.key].yes_no_value = false
                                    "
                                    :class="[
                                        'px-5 py-2 rounded-lg font-medium text-sm border transition',
                                        responseData[q.key].yes_no_value ===
                                        false
                                            ? 'bg-red-500 text-white border-red-500'
                                            : 'bg-white border-gray-300 hover:border-red-400',
                                    ]"
                                >
                                    No
                                </button>
                            </div>
                        </div>

                        <!-- Multiple Choice -->
                        <div
                            v-if="
                                q.question_type === 'multiple_choice' &&
                                q.options
                            "
                        >
                            <label class="label">Select Option</label>
                            <div class="space-y-2">
                                <label
                                    v-for="opt in q.options"
                                    :key="opt"
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        v-model="
                                            responseData[q.key].selected_options
                                        "
                                        class="rounded text-teal-600"
                                    />
                                    <span class="text-sm text-gray-700">{{
                                        opt
                                    }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Interviewer Notes -->
                        <div>
                            <label class="label">Interviewer Notes</label>
                            <input
                                v-model="responseData[q.key].interviewer_notes"
                                type="text"
                                class="input-field"
                                placeholder="Your observations about this answer..."
                            />
                        </div>

                        <!-- Question Rating -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-gray-500"
                                    >Answer Quality:</label
                                >
                                <div class="flex gap-1">
                                    <button
                                        v-for="n in 5"
                                        :key="n"
                                        type="button"
                                        @click="
                                            responseData[
                                                q.key
                                            ].question_rating = n
                                        "
                                        :class="[
                                            'text-xl transition',
                                            n <=
                                            (responseData[q.key]
                                                .question_rating ?? 0)
                                                ? 'text-yellow-400'
                                                : 'text-gray-200',
                                        ]"
                                    >
                                        ★
                                    </button>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="saveResponse(q)"
                                :disabled="saving[q.key]"
                                class="text-xs bg-teal-50 text-teal-700 border border-teal-200 px-3 py-1.5 rounded-lg hover:bg-teal-100 transition disabled:opacity-50"
                            >
                                {{
                                    saving[q.key]
                                        ? "Saving..."
                                        : "💾 Save Response"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="bg-gray-50 rounded-xl border border-dashed border-gray-300 p-12 text-center"
            >
                <p class="text-gray-500 text-lg mb-4">
                    Click <strong>Start Interview</strong> to begin recording
                    responses.
                </p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- NEW: Registration Form Side Panel -->
        <!-- ============================================ -->
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
                            v-if="registration_form.submitted_at"
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
                    <!-- No data state -->
                    <div
                        v-if="!registration_form || !registration_form.fields"
                        class="text-center py-12 text-gray-400"
                    >
                        <p class="text-4xl mb-3">📭</p>
                        <p>No registration form data available.</p>
                    </div>

                    <!-- Form Fields -->
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
                                    <p
                                        class="text-sm text-gray-800 mt-1 break-words"
                                    >
                                        {{ formatRegValue(field) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Candidate Basic Info Summary -->
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

        <!-- Backdrop overlay -->
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

        <!-- Complete Round Modal -->
        <Modal
            :show="showComplete"
            title="Complete Interview Round"
            max-width="2xl"
            @close="showComplete = false"
        >
            <div
                class="max-h-[75vh] space-y-6 overflow-y-auto px-1 hide-scrollbar"
            >
                <!-- Current Round Information -->
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                Completing Round
                            </p>

                            <p class="mt-1 font-semibold text-gray-900">
                                {{ interview.round_name }}
                            </p>
                        </div>

                        <span
                            v-if="interview.is_ops_round"
                            class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700"
                        >
                            OPS Round
                        </span>

                        <span
                            v-else-if="interview.is_hr_round"
                            class="rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-700"
                        >
                            HR Round
                        </span>
                    </div>
                </div>

                <!-- Overall Rating -->
                <div>
                    <label class="label">
                        Overall Rating
                        <span class="text-red-500">*</span>
                    </label>

                    <div class="mt-2 flex gap-2">
                        <button
                            v-for="n in 5"
                            :key="n"
                            type="button"
                            @click="completeForm.overall_rating = n"
                            :class="[
                                'text-3xl transition',
                                n <= completeForm.overall_rating
                                    ? 'text-yellow-400'
                                    : 'text-gray-200',
                            ]"
                        >
                            ★
                        </button>
                    </div>

                    <p v-if="completeForm.errors.overall_rating" class="error">
                        {{ completeForm.errors.overall_rating }}
                    </p>
                </div>

                <!-- Overall Feedback -->
                <div>
                    <label class="label">
                        Overall Feedback
                        <span class="text-red-500">*</span>
                    </label>

                    <textarea
                        v-model="completeForm.overall_feedback"
                        rows="4"
                        class="input-field"
                        placeholder="Summarise the candidate's performance in this round..."
                    />

                    <p
                        v-if="completeForm.errors.overall_feedback"
                        class="error"
                    >
                        {{ completeForm.errors.overall_feedback }}
                    </p>
                </div>

                <!-- OPS Salary Recommendation -->
                <div
                    v-if="interview.is_ops_round"
                    class="space-y-4 rounded-xl border border-blue-200 bg-blue-50 p-5"
                >
                    <div>
                        <h3 class="font-semibold text-blue-900">
                            OPS Salary Recommendation
                        </h3>

                        <p class="mt-1 text-sm text-blue-700">
                            Enter the monthly salary range recommended by the
                            OPS interviewer. This is an internal recommendation
                            and not a final salary offer.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- OPS Minimum -->
                        <div>
                            <label class="label">
                                Minimum Suggested Salary
                                <span class="text-red-500"> * </span>
                            </label>

                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"
                                >
                                    ₹
                                </span>

                                <input
                                    v-model="completeForm.salary_offer_min"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="input-field pl-8"
                                    placeholder="Example: 25000"
                                />
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Recommended monthly minimum.
                            </p>

                            <p
                                v-if="completeForm.errors.salary_offer_min"
                                class="error"
                            >
                                {{ completeForm.errors.salary_offer_min }}
                            </p>
                        </div>

                        <!-- OPS Maximum -->
                        <div>
                            <label class="label">
                                Maximum Suggested Salary
                                <span class="text-red-500"> * </span>
                            </label>

                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"
                                >
                                    ₹
                                </span>

                                <input
                                    v-model="completeForm.salary_offer_max"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="input-field pl-8"
                                    placeholder="Example: 28000"
                                />
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Recommended monthly maximum.
                            </p>

                            <p
                                v-if="completeForm.errors.salary_offer_max"
                                class="error"
                            >
                                {{ completeForm.errors.salary_offer_max }}
                            </p>
                        </div>
                    </div>

                    <!-- OPS Designation -->
                    <div>
                        <label class="label"> Suggested Designation </label>

                        <input
                            v-model="completeForm.offered_designation"
                            type="text"
                            class="input-field"
                            placeholder="Example: Senior Advisor"
                        />

                        <p
                            v-if="completeForm.errors.offered_designation"
                            class="error"
                        >
                            {{ completeForm.errors.offered_designation }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-blue-200 bg-white/70 p-3 text-xs leading-5 text-blue-800"
                    >
                        HR will review this recommendation during the HR round.
                        Final CTC, in-hand salary, PF and designation will be
                        approved separately after the interviews are completed.
                    </div>
                </div>

                <!-- OPS Recommendation shown in HR round -->
                <div
                    v-if="interview.is_hr_round && ops_salary_recommendation"
                    class="space-y-4 rounded-xl border border-blue-200 bg-blue-50 p-5"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h3 class="font-semibold text-blue-900">
                                OPS Salary Recommendation
                            </h3>

                            <p class="mt-1 text-sm text-blue-700">
                                Review the OPS recommendation before discussing
                                salary with the candidate.
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700"
                        >
                            Read Only
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 rounded-xl border border-blue-100 bg-white/80 p-4 sm:grid-cols-2"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                Suggested Salary Range
                            </p>

                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{
                                    formatSalary(
                                        ops_salary_recommendation.minimum,
                                    )
                                }}
                                –
                                {{
                                    formatSalary(
                                        ops_salary_recommendation.maximum,
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                Suggested Designation
                            </p>

                            <p class="mt-1 font-semibold text-gray-900">
                                {{
                                    ops_salary_recommendation.designation || "—"
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                Recommended By
                            </p>

                            <p class="mt-1 font-semibold text-gray-900">
                                {{
                                    ops_salary_recommendation.interviewer || "—"
                                }}
                            </p>

                            <p
                                v-if="
                                    ops_salary_recommendation.interviewer_employee_id
                                "
                                class="mt-1 text-xs text-gray-500"
                            >
                                {{
                                    ops_salary_recommendation.interviewer_employee_id
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                Completed On
                            </p>

                            <p class="mt-1 font-semibold text-gray-900">
                                {{
                                    ops_salary_recommendation.completed_at ||
                                    "—"
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="ops_salary_recommendation.feedback"
                        class="rounded-lg border border-blue-100 bg-white/70 p-4"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-gray-500"
                        >
                            OPS Feedback
                        </p>

                        <p
                            class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700"
                        >
                            {{ ops_salary_recommendation.feedback }}
                        </p>
                    </div>
                </div>

                <!-- No OPS Recommendation Warning -->
                <div
                    v-if="interview.is_hr_round && !ops_salary_recommendation"
                    class="rounded-xl border border-yellow-200 bg-yellow-50 p-4"
                >
                    <p class="font-medium text-yellow-900">
                        OPS recommendation not available
                    </p>

                    <p class="mt-1 text-sm text-yellow-700">
                        No completed OPS round containing a salary
                        recommendation was found for this candidate. HR can
                        still record the salary discussed with the candidate.
                    </p>
                </div>

                <!-- HR Salary Discussion -->
                <div
                    v-if="interview.is_hr_round"
                    class="space-y-5 rounded-xl border border-orange-200 bg-orange-50 p-5"
                >
                    <div>
                        <h3 class="font-semibold text-orange-900">
                            HR Salary Discussion
                        </h3>

                        <p class="mt-1 text-sm text-orange-700">
                            Record the monthly salary range and designation
                            discussed with the candidate. These details are not
                            the final approved offer.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- HR Discussed Minimum -->
                        <div>
                            <label class="label">
                                Discussed Minimum Salary
                                <span class="text-red-500"> * </span>
                            </label>

                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"
                                >
                                    ₹
                                </span>

                                <input
                                    v-model="completeForm.salary_offer_min"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="input-field pl-8"
                                    placeholder="Example: 25000"
                                />
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Minimum monthly amount discussed with the
                                candidate.
                            </p>

                            <p
                                v-if="completeForm.errors.salary_offer_min"
                                class="error"
                            >
                                {{ completeForm.errors.salary_offer_min }}
                            </p>
                        </div>

                        <!-- HR Discussed Maximum -->
                        <div>
                            <label class="label">
                                Discussed Maximum Salary
                                <span class="text-red-500"> * </span>
                            </label>

                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"
                                >
                                    ₹
                                </span>

                                <input
                                    v-model="completeForm.salary_offer_max"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="input-field pl-8"
                                    placeholder="Example: 26000"
                                />
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Maximum monthly amount discussed with the
                                candidate.
                            </p>

                            <p
                                v-if="completeForm.errors.salary_offer_max"
                                class="error"
                            >
                                {{ completeForm.errors.salary_offer_max }}
                            </p>
                        </div>
                    </div>

                    <!-- Discussed Designation -->
                    <div>
                        <label class="label"> Discussed Designation </label>

                        <input
                            v-model="completeForm.offered_designation"
                            type="text"
                            class="input-field"
                            placeholder="Example: Senior Advisor"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Designation discussed with the candidate during the
                            HR round.
                        </p>

                        <p
                            v-if="completeForm.errors.offered_designation"
                            class="error"
                        >
                            {{ completeForm.errors.offered_designation }}
                        </p>
                    </div>

                    <!-- Candidate Response -->
                    <div>
                        <label class="label">
                            Candidate Response
                            <span class="text-red-500"> * </span>
                        </label>

                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label
                                v-for="option in salaryResponseOptions"
                                :key="option.value"
                                :class="[
                                    'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition',
                                    completeForm.salary_offer_status ===
                                    option.value
                                        ? option.activeClass
                                        : 'border-orange-200 bg-white/60 hover:bg-white',
                                ]"
                            >
                                <input
                                    v-model="completeForm.salary_offer_status"
                                    type="radio"
                                    :value="option.value"
                                    class="mt-1"
                                />

                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ option.label }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs leading-5 text-gray-500"
                                    >
                                        {{ option.description }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <p
                            v-if="completeForm.errors.salary_offer_status"
                            class="error"
                        >
                            {{ completeForm.errors.salary_offer_status }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-orange-200 bg-white/70 p-3 text-xs leading-5 text-orange-800"
                    >
                        HR/Admin will review both the OPS recommendation and the
                        HR salary discussion before approving the final CTC,
                        in-hand salary, PF and designation.
                    </div>
                </div>

                <!-- HR Completion Decision -->
                <div
                    v-if="interview.is_hr_round"
                    class="space-y-3 rounded-xl border border-orange-200 bg-orange-50 p-5"
                >
                    <div>
                        <h3 class="font-semibold text-orange-900">
                            HR Round Decision
                        </h3>

                        <p class="mt-1 text-sm text-orange-700">
                            Continue the candidate to another round or mark the
                            complete interview process as cleared.
                        </p>
                    </div>

                    <label
                        :class="[
                            'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition',
                            completeForm.completion_action ===
                            'continue_to_next_round'
                                ? 'border-orange-500 bg-white ring-1 ring-orange-500'
                                : 'border-orange-200 bg-white/60 hover:bg-white',
                        ]"
                    >
                        <input
                            v-model="completeForm.completion_action"
                            type="radio"
                            value="continue_to_next_round"
                            class="mt-1"
                        />

                        <div>
                            <p class="font-medium text-gray-900">
                                Continue to Next Round
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
                                Complete the HR round and assign another
                                interview round.
                            </p>
                        </div>
                    </label>

                    <label
                        :class="[
                            'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition',
                            completeForm.completion_action ===
                            'mark_all_rounds_cleared'
                                ? 'border-green-500 bg-green-50 ring-1 ring-green-500'
                                : 'border-orange-200 bg-white/60 hover:bg-white',
                        ]"
                    >
                        <input
                            v-model="completeForm.completion_action"
                            type="radio"
                            value="mark_all_rounds_cleared"
                            class="mt-1"
                        />

                        <div>
                            <p class="font-medium text-gray-900">
                                Mark All Rounds Cleared
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
                                Complete the HR round and close the interview
                                process. Final salary will be approved
                                separately.
                            </p>
                        </div>
                    </label>

                    <p
                        v-if="completeForm.errors.completion_action"
                        class="error"
                    >
                        {{ completeForm.errors.completion_action }}
                    </p>
                </div>

                <!-- Non-HR Information -->
                <div
                    v-else
                    class="rounded-xl border border-indigo-200 bg-indigo-50 p-4"
                >
                    <p class="text-sm text-indigo-800">
                        After completing this round, select the next interview
                        round and assign an interviewer.
                    </p>
                </div>

                <!-- Next Round Selection -->
                <div
                    v-if="
                        !interview.is_hr_round ||
                        completeForm.completion_action ===
                            'continue_to_next_round'
                    "
                    class="space-y-4 rounded-xl border border-gray-200 bg-white p-5"
                >
                    <div>
                        <h3 class="font-semibold text-gray-900">
                            Next Interview Round
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Select the next round and assign the appropriate
                            interviewer.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">
                                Next Round
                                <span class="text-red-500"> * </span>
                            </label>

                            <select
                                v-model="completeForm.next_round_id"
                                class="input-field"
                            >
                                <option value="">Select next round</option>

                                <option
                                    v-for="r in next_rounds"
                                    :key="r.id"
                                    :value="r.id"
                                >
                                    {{ r.name }}
                                </option>
                            </select>

                            <p
                                v-if="completeForm.errors.next_round_id"
                                class="error"
                            >
                                {{ completeForm.errors.next_round_id }}
                            </p>
                        </div>

                        <div>
                            <label class="label">
                                Assign Interviewer
                                <span class="text-red-500"> * </span>
                            </label>

                            <select
                                v-model="completeForm.next_interviewer_id"
                                class="input-field"
                                :disabled="
                                    !completeForm.next_round_id ||
                                    loading_interviewers
                                "
                            >
                                <option value="">
                                    {{
                                        !completeForm.next_round_id
                                            ? "Select a round first"
                                            : loading_interviewers
                                              ? "Loading interviewers..."
                                              : "Select interviewer"
                                    }}
                                </option>

                                <option
                                    v-for="i in available_interviewers"
                                    :key="i.id"
                                    :value="i.id"
                                >
                                    {{ i.name }}
                                    {{ i.emp ? `(${i.emp})` : "" }}
                                </option>
                            </select>

                            <p
                                v-if="completeForm.errors.next_interviewer_id"
                                class="error"
                            >
                                {{ completeForm.errors.next_interviewer_id }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mandatory Questions Error -->
                <p
                    v-if="completeForm.errors.questions"
                    class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                >
                    {{ completeForm.errors.questions }}
                </p>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="showComplete = false"
                    class="btn-secondary"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="submitComplete"
                    :disabled="completeForm.processing"
                    :class="[
                        'btn-primary',
                        completeForm.processing
                            ? 'cursor-not-allowed opacity-50'
                            : '',
                    ]"
                >
                    {{
                        completeForm.processing
                            ? "Completing..."
                            : interview.is_hr_round &&
                                completeForm.completion_action ===
                                    "mark_all_rounds_cleared"
                              ? "✓ Complete All Rounds"
                              : "✓ Complete & Assign Next Round"
                    }}
                </button>
            </template>
        </Modal>

        <!-- Reject Modal -->
        <Modal
            :show="showReject"
            title="Reject Candidate"
            @close="showReject = false"
        >
            <div class="space-y-4">
                <div
                    class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700"
                >
                    This will mark the candidate as
                    <strong>Rejected</strong> and end the process.
                </div>
                <div>
                    <label class="label"
                        >Rejection Reason
                        <span class="text-red-500">*</span></label
                    >
                    <textarea
                        v-model="rejectForm.rejection_reason"
                        rows="3"
                        class="input-field border-red-200 focus:ring-red-400"
                        placeholder="Why is this candidate being rejected?"
                    />
                    <p v-if="rejectForm.errors.rejection_reason" class="error">
                        {{ rejectForm.errors.rejection_reason }}
                    </p>
                </div>
                <div>
                    <label class="label">Additional Feedback</label>
                    <textarea
                        v-model="rejectForm.overall_feedback"
                        rows="2"
                        class="input-field"
                        placeholder="Optional notes..."
                    />
                </div>
            </div>
            <template #footer>
                <button @click="showReject = false" class="btn-secondary">
                    Cancel
                </button>
                <button
                    @click="submitReject"
                    :disabled="rejectForm.processing"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-medium transition disabled:opacity-50"
                >
                    {{
                        rejectForm.processing
                            ? "Rejecting..."
                            : "✕ Confirm Reject"
                    }}
                </button>
            </template>
        </Modal>

        <!-- Custom Question Modal -->
        <Modal
            :show="showCustomQ"
            title="Add Custom Question"
            @close="showCustomQ = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="label"
                        >Question <span class="text-red-500">*</span></label
                    >
                    <textarea
                        v-model="customQForm.question_text"
                        rows="3"
                        class="input-field"
                        placeholder="Type your question..."
                    />
                    <p v-if="customQForm.errors.question_text" class="error">
                        {{ customQForm.errors.question_text }}
                    </p>
                </div>
                <div>
                    <label class="label">Type</label>
                    <select
                        v-model="customQForm.question_type"
                        class="input-field"
                    >
                        <option value="short_answer">Short Answer</option>
                        <option value="long_answer">Long Answer</option>
                        <option value="rating_scale">Rating Scale</option>
                        <option value="yes_no">Yes / No</option>
                    </select>
                </div>
                <div>
                    <label class="label"> Score Category </label>

                    <select
                        v-model="customQForm.score_category"
                        class="input-field"
                    >
                        <option value="">Not included in scorecard</option>

                        <option
                            v-for="(label, key) in scoreCategories"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option>
                    </select>

                    <p v-if="customQForm.errors.score_category" class="error">
                        {{ customQForm.errors.score_category }}
                    </p>
                </div>
            </div>
            <template #footer>
                <button @click="showCustomQ = false" class="btn-secondary">
                    Cancel
                </button>
                <button
                    @click="submitCustomQ"
                    :disabled="customQForm.processing"
                    class="btn-primary"
                >
                    {{ customQForm.processing ? "Adding..." : "Add Question" }}
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
