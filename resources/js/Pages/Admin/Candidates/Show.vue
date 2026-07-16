<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import Modal from '@/Components/Common/Modal.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import RichTextEditor from '@/Components/Common/RichTextEditor.vue'

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
})

const expanded = ref(null)
const showStatusModal = ref(false)
const showApprovalModal = ref(false)
const showReassignModal = ref(false)
const showRegPanel = ref(false)
const selectedRound = ref(null)

const statusForm = useForm({
    final_status: props.candidate.final_status,
    hiring_notes: props.candidate.hiring_notes ?? '',
    final_ctc: props.candidate.final_ctc ?? '',
    final_in_hand: props.candidate.final_in_hand ?? '',
    final_pf_allowed: props.candidate.final_pf_allowed ?? false,
    final_designation: props.candidate.final_designation ?? '',
    rejection_reason: '',
    hold_reason: '',
    process_name: '',
    remarks: '',
    salary_annexure: props.candidate.salary_annexure ?? '',
})

const resetDynamicFields = () => {
    statusForm.rejection_reason = ''
    statusForm.hold_reason = ''
    statusForm.process_name = ''
    statusForm.remarks = ''
    statusForm.final_ctc = ''
    statusForm.final_in_hand = ''
    statusForm.final_pf_allowed = false
    statusForm.final_designation = ''
}

const reassignForm = useForm({
    new_interviewer_id: ''
})

const approvalForm = useForm({
    approval_status: props.candidate.approval_status ?? 'pending',
    approval_notes: props.candidate.approval_notes ?? '',
})

const approvalStatusOptions = {
    pending: 'Pending Approval',
    approved: 'Approved',
    rejected: 'Rejected',
}

const submitApproval = () => {
    approvalForm.put(route('admin.candidates.update-approval', props.candidate.id), {
        onSuccess: () => (showApprovalModal.value = false),
    })
}

const submitStatus = () => {
    statusForm.put(route('admin.candidates.update-status', props.candidate.id), {
        onSuccess: () => {
            showStatusModal.value = false
            resetDynamicFields()
        }
    })
}

const isSelected = computed(() => statusForm.final_status === 'selected')
const isNotSelected = computed(() => statusForm.final_status === 'not_selected')
const isPending = computed(() => statusForm.final_status === 'pending')

const canSubmitStatus = computed(() => {
    if (statusForm.processing) return false
    if (isSelected.value && (!statusForm.process_name || !statusForm.final_ctc || !statusForm.final_in_hand)) return false
    if (isNotSelected.value && !statusForm.rejection_reason) return false
    if (isPending.value && !statusForm.hold_reason) return false
    return true
})

// Open reassign modal for a specific round
const openReassignModal = (round) => {
    selectedRound.value = round
    reassignForm.new_interviewer_id = round.interviewer_id ?? ''
    showReassignModal.value = true
}

// Submit reassign
const submitReassign = () => {
    if (!selectedRound.value || !reassignForm.new_interviewer_id) return

    reassignForm.post(
        route('interviewer.interviews.reassign', {
            candidate: props.candidate.id,
            round: selectedRound.value.round_id,
        }),
        {
            onSuccess: () => {
                showReassignModal.value = false
                selectedRound.value = null
                reassignForm.reset()
            },
        }
    )
}

// Admin can reassign any round that is pending or in_progress
const canReassign = (round) => {
    return ['pending', 'in_progress'].includes(round.status)
}

// Filter interviewers eligible for selected round
const eligibleInterviewers = computed(() => {
    if (!selectedRound.value) return []
    return props.available_interviewers.filter(i =>
        i.can_interview_rounds.includes(selectedRound.value.round_id)
    )
})

// Format registration form value
const formatRegValue = (field) => {
    const val = field.value
    if (val === null || val === undefined || val === '') return '—'
    if (field.field_type === 'checkbox' && Array.isArray(val)) {
        return val.join(', ') || '—'
    }
    if (field.field_type === 'date' && val) {
        return new Date(val).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
    }
    if (field.field_type === 'file' && typeof val === 'object' && val?.name) {
        return val
    }
    return val
}

const fieldTypeIcon = (type) => {
    const icons = {
        text: '📝', email: '📧', phone: '📱', number: '🔢',
        date: '📅', textarea: '📋', dropdown: '🔽',
        radio: '⭕', checkbox: '☑️', file: '📎',
    }
    return icons[type] || '📝'
}

const roundStatusColor = (status) => {
    return {
        not_started: 'bg-gray-100 text-gray-600',
        pending: 'bg-yellow-100 text-yellow-700',
        in_progress: 'bg-blue-100 text-blue-700',
        completed: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    }[status] ?? 'bg-gray-100 text-gray-600'
}

// Schedule-related state
const showScheduleModal = ref(false)
const scheduleForm = useForm({
    scheduled_at: '',
    gmeet_link: '',
    notes: '',
    interviewer_id: '',
})

// Open schedule modal for a round
const openScheduleModal = (round) => {
    selectedRound.value = round

    // Pre-fill if schedule exists
    const existing = props.schedules?.find(s => s.round_id === round.round_id)
    if (existing) {
        scheduleForm.scheduled_at = existing.scheduled_at_raw?.slice(0, 16) ?? ''
        scheduleForm.gmeet_link = existing.gmeet_link ?? ''
        scheduleForm.notes = existing.notes ?? ''
        scheduleForm.interviewer_id = round.interviewer_id ?? ''
    } else {
        scheduleForm.reset()
        scheduleForm.interviewer_id = round.interviewer_id ?? ''
    }

    showScheduleModal.value = true
}

// Submit schedule
const submitSchedule = () => {
    if (!selectedRound.value) return

    scheduleForm.post(
        route('admin.candidates.rounds.schedule', {
            candidate: props.candidate.id,
            round: selectedRound.value.round_id,
        }),
        {
            onSuccess: () => {
                showScheduleModal.value = false
                selectedRound.value = null
                scheduleForm.reset()
            },
        }
    )
}

// Get schedule for a round
const getRoundSchedule = (roundId) => {
    return props.schedules?.find(s => s.round_id === roundId)
}

// Format datetime for input (min value = now)
const minDateTime = computed(() => {
    const now = new Date()
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
    return now.toISOString().slice(0, 16)
})
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.candidates.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ candidate.name }}</h1>
                        <p class="text-sm text-gray-500">{{ candidate.position }} · {{ candidate.profile_label }} · {{
                            candidate.branch }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <StatusBadge :status="candidate.current_status" />
                    <StatusBadge :status="candidate.final_status" type="final" />
                    <button v-if="registration_form" @click="showRegPanel = true"
                        class="btn-secondary text-sm bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100">
                        📝 View Registration
                    </button>
                    <button v-if="candidate.requires_approval" @click="showApprovalModal = true"
                        class="btn-secondary text-sm bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100">
                        ⚠️ Approval
                    </button>
                    <button @click="showStatusModal = true" class="btn-primary text-sm">Update Status</button>
                </div>
            </div>

            <!-- Salary Logic Banner -->
            <div v-if="candidate.requires_salary_hr"
                class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-800">
                <strong>💰 TL/QA/AM/OM or Other Profile:</strong> Salary offer is <strong>mandatory</strong> in the HR
                round for this candidate.
                <span v-if="candidate.latest_salary_offer"> Latest offer: <strong>{{ candidate.latest_salary_offer
                        }}</strong></span>
            </div>
            <div v-if="candidate.requires_approval"
                :class="['rounded-xl p-4 text-sm', candidate.approval_status === 'approved' ? 'bg-green-50 border border-green-200 text-green-800' : candidate.approval_status === 'rejected' ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-amber-50 border border-amber-200 text-amber-800']">
                <strong>⚠️ Rejoining approval required:</strong>
                <span v-if="candidate.approval_status === 'approved'">Approval granted.</span>
                <span v-else-if="candidate.approval_status === 'rejected'">Approval rejected.</span>
                <span v-else>Approval is pending.</span>
                <div class="mt-2 text-xs">
                    <span class="font-semibold">Status:</span> {{ candidate.approval_status_label || 'Pending Approval'
                    }}
                    <span v-if="candidate.old_employee"> · Previous employee: {{ candidate.old_employee.name }}</span>
                </div>
            </div>
            <div v-else-if="candidate.salary_post_ops"
                class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
                <strong>📊 Advisor/Executive Profile:</strong> Salary discussion takes place during the OPS round.
                <span v-if="candidate.latest_salary_offer"> Latest offer: <strong>{{ candidate.latest_salary_offer
                        }}</strong></span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Candidate Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
                    <h2 class="font-semibold text-gray-800">Candidate Info</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Email</dt>
                            <dd class="text-gray-800 break-all">{{ candidate.email }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Phone</dt>
                            <dd class="text-gray-800">{{ candidate.phone }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Branch</dt>
                            <dd class="text-gray-800">{{ candidate.branch }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Rounds Done</dt>
                            <dd class="text-gray-800 font-semibold">{{ candidate.completed_rounds }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Avg Rating</dt>
                            <dd class="text-yellow-600 font-semibold">{{ candidate.average_rating ?
                                candidate.average_rating + ' ★' : '—' }}</dd>
                        </div>
                        <div v-if="candidate.final_ctc" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Final CTC</dt>
                            <dd class="text-green-700 font-semibold">₹{{
                                Number(candidate.final_ctc).toLocaleString('en-IN') }}</dd>
                        </div>
                        <div v-if="candidate.final_in_hand" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Final In-Hand</dt>
                            <dd class="text-green-700 font-semibold">₹{{
                                Number(candidate.final_in_hand).toLocaleString('en-IN') }}</dd>
                        </div>
                        <div v-if="candidate.final_pf_allowed !== null" class="flex gap-2">
                            <dt class="w-24 text-gray-500">PF Allowed</dt>
                            <dd class="text-gray-800">{{ candidate.final_pf_allowed ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div v-if="candidate.final_designation" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Designation</dt>
                            <dd class="text-gray-800">{{ candidate.final_designation }}</dd>
                        </div>
                        <div v-if="candidate.hiring_notes" class="pt-2">
                            <p class="text-xs text-gray-500 font-medium mb-1">Hiring Notes</p>
                            <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-2">{{ candidate.hiring_notes }}</p>
                        </div>
                        <div v-if="candidate.process_name" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Process</dt>
                            <dd class="text-green-700 font-semibold">{{ candidate.process_name }}</dd>
                        </div>

                        <div v-if="candidate.rejection_reason" class="flex gap-2">
                            <dt class="w-24 text-gray-500">Rejected</dt>
                            <dd class="text-red-700">
                                {{ candidate.rejection_reason }}
                                <p v-if="candidate.rejection_remarks" class="text-xs text-gray-500 mt-1">{{
                                    candidate.rejection_remarks }}</p>
                            </dd>
                        </div>

                        <div v-if="candidate.hold_reason" class="flex gap-2">
                            <dt class="w-24 text-gray-500">On Hold</dt>
                            <dd class="text-amber-700">
                                {{ candidate.hold_reason }}
                                <p v-if="candidate.hold_remarks" class="text-xs text-gray-500 mt-1">{{
                                    candidate.hold_remarks }}</p>
                            </dd>
                        </div>
                        <div v-if="candidate.salary_annexure" class="pt-2">
                            <p class="text-xs text-gray-500 font-medium mb-1">Salary Annexure</p>
                            <div class="text-sm text-gray-700 bg-white rounded-lg p-3 border border-gray-200 prose prose-sm max-w-none"
                                v-html="candidate.salary_annexure">
                            </div>
                        </div>
                    </dl>
                    <a v-if="candidate.resume_path" :href="`/storage/${candidate.resume_path}`" target="_blank"
                        class="block mt-2 text-center text-sm text-indigo-600 border border-indigo-200 rounded-lg py-2 hover:bg-indigo-50 transition">
                        📄 View Resume
                    </a>
                </div>

                <!-- Round Progress Timeline -->
                <div class="lg:col-span-2 space-y-4">
                    <h2 class="font-semibold text-gray-800">Interview History</h2>
                    <div v-for="(p, i) in progress_history" :key="p.id"
                        class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Round Header -->
                        <button @click="expanded = expanded === i ? null : i"
                            class="w-full px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold',
                                    p.status === 'completed' ? 'bg-green-100 text-green-700' : p.status === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700'
                                ]">{{ i + 1 }}</div>
                                <div class="text-left">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-800">{{ p.round_name }}</p>
                                        <span v-if="p.is_hr_round"
                                            class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded">HR</span>
                                        <span v-if="p.is_ops_round"
                                            class="text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded">OPS</span>
                                        <StatusBadge :status="p.status" type="progress" />
                                    </div>
                                    <p class="text-xs text-gray-400">{{ p.interviewer }} · {{ p.started_at ?? 'Not started' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="p.salary_offer_min || p.salary_offer_max"
                                    class="text-xs font-semibold text-green-600">
                                    💰
                                    <template v-if="p.salary_offer_min && p.salary_offer_max">
                                        ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }} - ₹{{
                                            Number(p.salary_offer_max).toLocaleString('en-IN') }}
                                    </template>
                                    <template v-else-if="p.salary_offer_min">
                                        From ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }}
                                    </template>
                                    <template v-else>
                                        Up to ₹{{ Number(p.salary_offer_max).toLocaleString('en-IN') }}
                                    </template>
                                </span>
                                <!-- Reassign Button (for pending/in_progress rounds) -->
                                <div v-if="canReassign(p) || p.status === 'pending'"
                                    class="flex justify-end gap-2 pb-2 border-b border-gray-100">
                                    <button @click="openReassignModal(p)"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition">
                                        🔄 Reassign
                                    </button>
                                    <button @click="openScheduleModal(p)"
                                        class="text-sm text-green-600 hover:text-green-800 font-medium px-3 py-1.5 rounded-lg hover:bg-green-50 transition">
                                        📅 {{ getRoundSchedule(p.round_id) ? 'Edit Schedule' : 'Schedule Interview' }}
                                    </button>
                                </div>
                                <span class="text-gray-300 text-sm">{{ expanded === i ? '▲' : '▼' }}</span>
                            </div>
                        </button>

                        <!-- Expanded Details -->
                        <div v-if="expanded === i" class="border-t border-gray-100 px-5 py-4 space-y-4">
                            <div v-if="getRoundSchedule(p.round_id)"
                                :class="['rounded-lg p-3 text-sm mb-3',
                                    getRoundSchedule(p.round_id).is_upcoming ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200']">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold"
                                            :class="getRoundSchedule(p.round_id).is_upcoming ? 'text-green-800' : 'text-gray-700'">
                                            📅 Scheduled: {{ getRoundSchedule(p.round_id).scheduled_at }}
                                            <span v-if="getRoundSchedule(p.round_id).is_upcoming"
                                                class="text-xs font-normal text-green-600 ml-2">
                                                ({{ getRoundSchedule(p.round_id).time_until }})
                                            </span>
                                        </p>
                                        <a :href="getRoundSchedule(p.round_id).gmeet_link" target="_blank"
                                            class="text-indigo-600 hover:underline text-xs mt-1 inline-block">
                                            🔗 {{ getRoundSchedule(p.round_id).gmeet_link }}
                                        </a>
                                        <p v-if="getRoundSchedule(p.round_id).notes" class="text-gray-600 text-xs mt-1">
                                            📝 {{ getRoundSchedule(p.round_id).notes }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <span v-if="getRoundSchedule(p.round_id).sent_at"
                                            class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                            ✉️ Sent
                                        </span>
                                        <button @click="openScheduleModal(p)"
                                            class="text-xs text-gray-500 hover:text-gray-700 underline">
                                            Edit
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Salary Offer -->
                            <div v-if="p.salary_offer_min || p.salary_offer_max"
                                class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm">
                                <p class="font-semibold text-green-800">Salary Offer Details</p>
                                <p class="text-green-700" v-if="p.salary_offer_min && p.salary_offer_max">
                                    Range: ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }} - ₹{{
                                        Number(p.salary_offer_max).toLocaleString('en-IN') }} / year
                                </p>
                                <p class="text-green-700" v-else-if="p.salary_offer_min">
                                    Minimum: ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }} / year
                                </p>
                                <p class="text-green-700" v-else>
                                    Maximum: ₹{{ Number(p.salary_offer_max).toLocaleString('en-IN') }} / year
                                </p>
                                <p v-if="p.offered_designation" class="text-green-700">Designation: {{
                                    p.offered_designation }}</p>
                                <p v-if="p.salary_offer_status" class="text-green-600 capitalize">Status: {{
                                    p.salary_offer_status }}</p>
                            </div>

                            <!-- Feedback -->
                            <div v-if="p.feedback" class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs font-medium text-gray-500 mb-1">Feedback</p>
                                <p class="text-sm text-gray-700">{{ p.feedback }}</p>
                                <p v-if="p.rating" class="text-yellow-600 font-semibold mt-1">{{ p.rating }} ★ · {{
                                    p.duration }}</p>
                            </div>

                            <!-- Rejection Reason -->
                            <div v-if="p.rejection_reason" class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p class="text-xs font-medium text-red-600 mb-1">Rejection Reason</p>
                                <p class="text-sm text-red-700">{{ p.rejection_reason }}</p>
                            </div>

                            <!-- Responses -->
                            <div v-if="p.responses?.length">
                                <p class="text-xs font-medium text-gray-500 mb-2">Responses ({{ p.responses.length }})
                                </p>
                                <div class="space-y-2">
                                    <div v-for="r in p.responses" :key="r.question"
                                        class="bg-gray-50 rounded-lg p-3 text-sm">
                                        <p class="font-medium text-gray-700">
                                            {{ r.question }}
                                            <span v-if="r.is_mandatory" class="text-red-400 text-xs ml-1">*</span>
                                        </p>
                                        <p class="text-gray-600 mt-1">
                                            <span v-if="r.response_text">{{ r.response_text }}</span>
                                            <span v-else-if="r.rating_value">Rating: {{ r.rating_value }}/5</span>
                                            <span v-else-if="r.yes_no_value !== null">{{ r.yes_no_value ? 'Yes' : 'No'
                                            }}</span>
                                            <span v-else class="text-gray-400 italic">No answer recorded</span>
                                        </p>
                                        <p v-if="r.interviewer_notes" class="text-indigo-600 text-xs mt-1">Note: {{
                                            r.interviewer_notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!progress_history?.length"
                        class="text-center text-gray-400 py-10 bg-white rounded-xl border border-gray-100">
                        No interview rounds completed yet.
                    </p>
                </div>
            </div>
        </div>

        <!-- Approval Status Modal -->
        <Modal :show="showApprovalModal" title="Update Rejoin Approval" max-width="lg"
            @close="showApprovalModal = false">
            <div class="space-y-4">
                <div>
                    <label class="label">Approval Status</label>
                    <select v-model="approvalForm.approval_status" class="input-field">
                        <option v-for="(label, key) in approvalStatusOptions" :key="key" :value="key">{{ label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="label">Approval Notes</label>
                    <textarea v-model="approvalForm.approval_notes" rows="3" class="input-field"
                        placeholder="Enter approval notes or reason for rejection..." />
                </div>
            </div>
            <template #footer>
                <button @click="showApprovalModal = false" class="btn-secondary">Cancel</button>
                <button @click="submitApproval" :disabled="approvalForm.processing" class="btn-primary">
                    {{ approvalForm.processing ? 'Saving...' : 'Save Approval' }}
                </button>
            </template>
        </Modal>

        <!-- Update Status Modal -->
        <Modal :show="showStatusModal" title="Update Candidate Status" max-width="lg" @close="showStatusModal = false">
            <div class="space-y-5">
                <!-- Final Decision -->
                <div>
                    <label class="label">Final Decision <span class="text-red-500">*</span></label>
                    <select v-model="statusForm.final_status" @change="resetDynamicFields" class="input-field"
                        :class="{ 'border-red-300': statusForm.errors.final_status }">
                        <option value="">Select decision...</option>
                        <option v-for="(label, key) in finalStatusOptions" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <p v-if="statusForm.errors.final_status" class="text-xs text-red-500 mt-1">{{
                        statusForm.errors.final_status
                    }}</p>
                </div>

                <!-- ─── SELECTED → Process Dropdown ─── -->
                <div v-if="isSelected" class="space-y-4 animate-fade-in">
                    <div>
                        <label class="label">Assigned Process / Project <span class="text-red-500">*</span></label>
                        <select v-model="statusForm.process_name" class="input-field"
                            :class="{ 'border-red-300': statusForm.errors.process_name }">
                            <option value="">Select process...</option>
                            <option v-for="process in processOptions" :key="process" :value="process">{{ process }}
                            </option>
                        </select>
                        <p v-if="statusForm.errors.process_name" class="text-xs text-red-500 mt-1">{{
                            statusForm.errors.process_name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Final CTC Offered (₹)</label>
                            <input v-model="statusForm.final_ctc" type="number" class="input-field"
                                placeholder="e.g. 600000" />
                        </div>
                        <div>
                            <label class="label">Final In-Hand Offered (₹)</label>
                            <input v-model="statusForm.final_in_hand" type="number" class="input-field"
                                placeholder="e.g. 450000" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">PF Allowed</label>
                            <select v-model="statusForm.final_pf_allowed" class="input-field">
                                <option :value="true">Yes</option>
                                <option :value="false">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Final Designation</label>
                            <input v-model="statusForm.final_designation" type="text" class="input-field"
                                placeholder="e.g. Senior Advisor" />
                        </div>
                    </div>
                </div>

                <!-- ─── NOT SELECTED → Rejection Reason ─── -->
                <div v-else-if="isNotSelected" class="space-y-4 animate-fade-in">
                    <div>
                        <label class="label">Rejection Reason <span class="text-red-500">*</span></label>
                        <select v-model="statusForm.rejection_reason" class="input-field"
                            :class="{ 'border-red-300': statusForm.errors.rejection_reason }">
                            <option value="">Select reason...</option>
                            <option v-for="reason in rejectionReasons" :key="reason" :value="reason">{{ reason }}
                            </option>
                        </select>
                        <p v-if="statusForm.errors.rejection_reason" class="text-xs text-red-500 mt-1">{{
                            statusForm.errors.rejection_reason }}</p>
                    </div>

                    <div>
                        <label class="label">Remarks <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <textarea v-model="statusForm.remarks" rows="3" class="input-field"
                            placeholder="Additional remarks..." />
                    </div>
                </div>

                <!-- ─── DECISION PENDING → Hold Reason ─── -->
                <div v-else-if="isPending" class="space-y-4 animate-fade-in">
                    <div>
                        <label class="label">Hold Reason <span class="text-red-500">*</span></label>
                        <select v-model="statusForm.hold_reason" class="input-field"
                            :class="{ 'border-red-300': statusForm.errors.hold_reason }">
                            <option value="">Select reason...</option>
                            <option v-for="reason in holdReasons" :key="reason" :value="reason">{{ reason }}</option>
                        </select>
                        <p v-if="statusForm.errors.hold_reason" class="text-xs text-red-500 mt-1">{{
                            statusForm.errors.hold_reason }}</p>
                    </div>

                    <div>
                        <label class="label">Remarks <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <textarea v-model="statusForm.remarks" rows="3" class="input-field"
                            placeholder="Additional remarks..." />
                    </div>
                </div>

                <!-- ─── RICH TEXT EDITOR (Always visible) ─── -->
                <div>
                    <label class="label">Salary Annexure <span class="text-gray-400 font-normal">(Paste images, tables,
                            formatted text)</span></label>
                    <RichTextEditor v-model="statusForm.salary_annexure"
                        placeholder="Paste Excel tables, images, screenshots, or type detailed notes here..." />
                    <p v-if="statusForm.errors.salary_annexure" class="text-xs text-red-500 mt-1">{{
                        statusForm.errors.salary_annexure }}</p>
                </div>

                <!-- Hiring Notes (always visible) -->
                <div>
                    <label class="label">Internal Hiring Notes</label>
                    <textarea v-model="statusForm.hiring_notes" rows="3" class="input-field"
                        placeholder="Internal notes for team reference..." />
                </div>
            </div>

            <template #footer>
                <button @click="showStatusModal = false" class="btn-secondary">Cancel</button>
                <button @click="submitStatus" :disabled="!canSubmitStatus" class="btn-primary"
                    :class="isNotSelected ? 'bg-red-600 hover:bg-red-700' : isSelected ? 'bg-green-600 hover:bg-green-700' : ''">
                    {{ statusForm.processing ? 'Saving...' : 'Update Status' }}
                </button>
            </template>
        </Modal>

        <!-- Reassign Interviewer Modal -->
        <Modal :show="showReassignModal" title="Reassign Interviewer" @close="showReassignModal = false">
            <div class="space-y-4">
                <div v-if="selectedRound" class="bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-500">Round</p>
                    <p class="font-medium text-gray-800">{{ selectedRound.round_name }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        Current: {{ selectedRound.interviewer ?? 'Not assigned' }}
                    </p>
                </div>

                <div>
                    <label class="label">Select New Interviewer <span class="text-red-500">*</span></label>
                    <select v-model="reassignForm.new_interviewer_id" class="input-field">
                        <option value="">Choose interviewer...</option>
                        <option v-for="i in eligibleInterviewers" :key="i.id" :value="i.id">
                            {{ i.name }} {{ i.emp ? `(${i.emp})` : '' }}
                        </option>
                    </select>
                    <p v-if="eligibleInterviewers.length === 0" class="text-xs text-red-500 mt-1">
                        No eligible interviewers found for this round. Assign round permissions in employee settings
                        first.
                    </p>
                    <p v-if="reassignForm.errors.new_interviewer_id" class="error">{{
                        reassignForm.errors.new_interviewer_id }}
                    </p>
                </div>
            </div>
            <template #footer>
                <button @click="showReassignModal = false" class="btn-secondary">Cancel</button>
                <button @click="submitReassign" :disabled="!reassignForm.new_interviewer_id || reassignForm.processing"
                    class="btn-primary">
                    {{ reassignForm.processing ? 'Reassigning...' : 'Confirm Reassign' }}
                </button>
            </template>
        </Modal>

        <!-- Registration Form Side Panel -->
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100" leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100" leave-to-class="translate-x-full opacity-0">
            <div v-if="showRegPanel"
                class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl border-l border-gray-200 flex flex-col">
                <!-- Panel Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-indigo-50">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">📝 Registration Details</h2>
                        <p v-if="registration_form?.submitted_at" class="text-xs text-gray-500">
                            Submitted on {{ registration_form.submitted_at }}
                        </p>
                    </div>
                    <button @click="showRegPanel = false"
                        class="h-8 w-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition">
                        ✕
                    </button>
                </div>

                <!-- Panel Content -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div v-if="!registration_form || !registration_form.fields" class="text-center py-12 text-gray-400">
                        <p class="text-4xl mb-3">📭</p>
                        <p>No registration form data available.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="field in registration_form.fields" :key="field.field_label"
                            class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                            <div class="flex items-start gap-3">
                                <span class="text-lg">{{ fieldTypeIcon(field.field_type) }}</span>
                                <div class="flex-1 min-w-0">
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ field.field_label }}
                                        <span v-if="field.is_mandatory" class="text-red-400">*</span>
                                    </label>

                                    <!-- File value -->
                                    <div v-if="field.field_type === 'file' && formatRegValue(field)?.name">
                                        <a :href="formatRegValue(field).url" target="_blank"
                                            class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium mt-1">
                                            <span>📎</span>
                                            {{ formatRegValue(field).name }}
                                        </a>
                                    </div>
                                    <!-- Default value -->
                                    <p v-else class="text-sm text-gray-800 mt-1 break-words">
                                        {{ formatRegValue(field) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Candidate Summary -->
                    <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100 mt-6">
                        <h3 class="text-sm font-semibold text-indigo-900 mb-3">Candidate Summary</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Name</span>
                                <span class="text-gray-800 font-medium">{{ candidate.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Email</span>
                                <span class="text-gray-800 break-all">{{ candidate.email }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Phone</span>
                                <span class="text-gray-800">{{ candidate.phone }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Position</span>
                                <span class="text-gray-800">{{ candidate.position }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Profile</span>
                                <span class="text-gray-800">{{ candidate.profile_label }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-indigo-600">Branch</span>
                                <span class="text-gray-800">{{ candidate.branch }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <button @click="showRegPanel = false"
                        class="w-full py-2.5 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 transition">
                        Close Panel
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Backdrop for registration panel -->
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showRegPanel" @click="showRegPanel = false"
                class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm">
            </div>
        </Transition>

        <!-- NEW: Schedule Interview Modal -->
        <Modal :show="showScheduleModal"
            :title="getRoundSchedule(selectedRound?.round_id) ? 'Edit Schedule' : 'Schedule Interview'" max-width="lg"
            @close="showScheduleModal = false">
            <div class="space-y-4">
                <div v-if="selectedRound" class="bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-500">Round</p>
                    <p class="font-medium text-gray-800">{{ selectedRound.round_name }}</p>
                    <p class="text-xs text-gray-400">Candidate: {{ candidate.name }}</p>
                </div>

                <div>
                    <label class="label">Interviewer <span class="text-red-500">*</span></label>
                    <select v-model="scheduleForm.interviewer_id" class="input-field" required>
                        <option value="">Select interviewer</option>
                        <option v-for="i in eligibleInterviewers" :key="i.id" :value="i.id">
                            {{ i.name }} {{ i.emp ? `(${i.emp})` : '' }}
                        </option>
                    </select>
                    <p v-if="scheduleForm.errors.interviewer_id" class="error">{{ scheduleForm.errors.interviewer_id }}
                    </p>
                </div>

                <div>
                    <label class="label">Date & Time <span class="text-red-500">*</span></label>
                    <input v-model="scheduleForm.scheduled_at" type="datetime-local" :min="minDateTime"
                        class="input-field" required />
                    <p v-if="scheduleForm.errors.scheduled_at" class="error">{{ scheduleForm.errors.scheduled_at }}</p>
                </div>

                <div>
                    <label class="label">Google Meet Link <span class="text-red-500">*</span></label>
                    <input v-model="scheduleForm.gmeet_link" type="url"
                        placeholder="https://meet.google.com/xxx-xxxx-xxx" class="input-field" required />
                    <p class="text-xs text-gray-400 mt-1">Paste the Google Meet link here</p>
                    <p v-if="scheduleForm.errors.gmeet_link" class="error">{{ scheduleForm.errors.gmeet_link }}</p>
                </div>

                <div>
                    <label class="label">Notes (Optional)</label>
                    <textarea v-model="scheduleForm.notes" rows="2" class="input-field"
                        placeholder="Any special instructions for the candidate..." />
                </div>
            </div>
            <template #footer>
                <button @click="showScheduleModal = false" class="btn-secondary">Cancel</button>
                <button @click="submitSchedule"
                    :disabled="scheduleForm.processing || !scheduleForm.scheduled_at || !scheduleForm.gmeet_link"
                    class="btn-primary">
                    {{ scheduleForm.processing ? 'Saving...' : (getRoundSchedule(selectedRound?.round_id) ?
                        'Update & Notify' : 'Schedule & Send Email') }}
                </button>
            </template>
        </Modal>
    </AdminLayout>
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