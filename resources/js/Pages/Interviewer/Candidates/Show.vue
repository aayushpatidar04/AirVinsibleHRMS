<script setup>
import InterviewerLayout from '@/Layouts/InterviewerLayout.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import Modal from '@/Components/Common/Modal.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ candidate: Object, progress_history: Array, my_active_progress: Object, available_interviewers: Array, schedules: Array })
const expanded = ref(null)
const showReassign = ref(false)
const reassignForm = useForm({ new_interviewer_id: '' })

const submitReassign = () => {
  reassignForm.post(
    route('interviewer.interviews.reassign', { candidate: props.candidate.id, round: props.my_active_progress.round_id }),
    { onSuccess: () => (showReassign.value = false) }
  )
}

const getRoundSchedule = (roundId) => {
    return props.schedules?.find(s => s.round_id === roundId)
}
</script>

<template>
    <InterviewerLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('interviewer.candidates.index')" class="text-gray-400 hover:text-gray-600">←
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ candidate.name }}</h1>
                        <p class="text-sm text-gray-500">{{ candidate.position }} · {{ candidate.profile_label }} · {{
                            candidate.branch }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <StatusBadge :status="candidate.current_status" />
                    <Link v-if="my_active_progress"
                        :href="route('interviewer.interviews.show', { candidate: candidate.id, round: my_active_progress.round_id })"
                        class="btn-primary text-sm">
                        {{ my_active_progress.status === 'pending' ? 'Start Interview' : 'Continue Interview' }}
                    </Link>
                    <button v-if="my_active_progress" @click="showReassign = true" class="btn-secondary text-sm">
                        🔁 Reassign Interviewer
                    </button>
                </div>
            </div>

            <!-- Salary Notice -->
            <div v-if="candidate.requires_salary_hr"
                class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-800">
                <strong>💰 TL/QA/AM/OM or Other Profile:</strong> Salary offer is <strong>mandatory</strong> when
                completing the HR round.
            </div>
            <div v-else-if="candidate.salary_post_ops"
                class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
                <strong>📊 Advisor/Executive Profile:</strong> Salary discussion takes place during the OPS round.
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
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
                            <dt class="w-24 text-gray-500">Registered</dt>
                            <dd class="text-gray-800">{{ candidate.registered_at }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Rounds Done</dt>
                            <dd class="font-semibold text-gray-800">{{ candidate.completed_rounds }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-500">Avg Rating</dt>
                            <dd class="text-yellow-600 font-semibold">{{ candidate.average_rating ?
                                candidate.average_rating + ' ★' : '—' }}</dd>
                        </div>
                    </dl>
                    <a v-if="candidate.resume_path" :href="`/storage/${candidate.resume_path}`" target="_blank"
                        class="block text-center text-sm text-teal-600 border border-teal-200 rounded-lg py-2 hover:bg-teal-50 transition mt-2">
                        📄 View Resume
                    </a>
                </div>

                <!-- History -->
                <div class="lg:col-span-2 space-y-3">
                    <h2 class="font-semibold text-gray-800">Interview History</h2>
                    <div v-for="(p, i) in progress_history" :key="p.id"
                        class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <button @click="expanded = expanded === i ? null : i"
                            class="w-full px-5 py-4 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'h-7 w-7 rounded-full text-xs font-bold flex items-center justify-center',
                                    p.status === 'completed' ? 'bg-green-100 text-green-700' : p.status === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700'
                                ]">{{ i + 1 }}</div>
                                <div class="text-left">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-800">{{ p.round_name }}</span>
                                        <span v-if="p.is_hr_round"
                                            class="text-xs bg-orange-100 text-orange-600 px-1.5 rounded">HR</span>
                                        <span v-if="p.is_ops_round"
                                            class="text-xs bg-blue-100 text-blue-600 px-1.5 rounded">OPS</span>
                                        <span v-if="p.is_mine"
                                            class="text-xs bg-teal-100 text-teal-600 px-1.5 rounded">Mine</span>
                                    </div>
                                    <p class="text-xs text-gray-400">{{ p.interviewer }} · {{ p.started_at ?? 'Pending'
                                        }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="p.salary_offer_min || p.salary_offer_max" class="text-xs font-semibold text-green-600">
                                    💰
                                    <template v-if="p.salary_offer_min && p.salary_offer_max">
                                        ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }} - ₹{{ Number(p.salary_offer_max).toLocaleString('en-IN') }}
                                    </template>
                                    <template v-else-if="p.salary_offer_min">
                                        From ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }}
                                    </template>
                                    <template v-else>
                                        Up to ₹{{ Number(p.salary_offer_max).toLocaleString('en-IN') }}
                                    </template>
                                </span>
                                <StatusBadge :status="p.status" type="progress" />
                                <span class="text-gray-300">{{ expanded === i ? '▲' : '▼' }}</span>
                            </div>
                        </button>

                        <div v-if="expanded === i" class="border-t border-gray-100 px-5 py-4 space-y-3">
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
                                </div>
                            </div>
                            <div v-if="p.salary_offer_min || p.salary_offer_max"
                                class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm">
                                <p class="font-semibold text-green-800">Salary Offer</p>
                                <p class="text-green-700" v-if="p.salary_offer_min && p.salary_offer_max">
                                    Range: ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }} - ₹{{ Number(p.salary_offer_max).toLocaleString('en-IN') }} / year
                                </p>
                                <p class="text-green-700" v-else-if="p.salary_offer_min">
                                    Minimum: ₹{{ Number(p.salary_offer_min).toLocaleString('en-IN') }} / year
                                </p>
                                <p class="text-green-700" v-else>
                                    Maximum: ₹{{ Number(p.salary_offer_max).toLocaleString('en-IN') }} / year
                                </p>
                                <p v-if="p.offered_designation" class="text-green-700">Designation: {{ p.offered_designation }}</p>
                            </div>
                            <div v-if="p.feedback" class="bg-gray-50 rounded-lg p-3 text-sm">
                                <p class="text-xs text-gray-500 mb-1 font-medium">Feedback ({{ p.rating }} ★)</p>
                                <p class="text-gray-700">{{ p.feedback }}</p>
                            </div>
                            <div v-if="p.rejection_reason" class="bg-red-50 rounded-lg p-3 text-sm">
                                <p class="text-xs text-red-500 mb-1 font-medium">Rejection Reason</p>
                                <p class="text-red-700">{{ p.rejection_reason }}</p>
                            </div>
                            <div v-if="p.responses?.length">
                                <p class="text-xs text-gray-500 font-medium mb-2">Q&A ({{ p.responses.length }})</p>
                                <div class="space-y-2">
                                    <div v-for="r in p.responses" :key="r.question"
                                        class="bg-gray-50 rounded-lg p-3 text-sm">
                                        <p class="font-medium text-gray-700">{{ r.question }}</p>
                                        <p class="text-gray-600 mt-0.5">
                                            <span v-if="r.response_text">{{ r.response_text }}</span>
                                            <span v-else-if="r.rating_value">{{ r.rating_value }}/5</span>
                                            <span v-else-if="r.yes_no_value !== null">{{ r.yes_no_value ? 'Yes' : 'No'
                                                }}</span>
                                            <span v-else class="italic text-gray-400">No answer</span>
                                        </p>
                                        <p v-if="r.interviewer_notes" class="text-teal-600 text-xs mt-1">Note: {{
                                            r.interviewer_notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!progress_history?.length"
                        class="text-center text-gray-400 py-10 bg-white rounded-xl border border-gray-100 text-sm">No
                        interview rounds yet.</p>
                </div>
            </div>
        </div>

        <Modal :show="showReassign" title="Reassign Interviewer" @close="showReassign = false">
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Assign this round to another interviewer who is eligible for the round.
                </p>
                <div>
                    <label class="label">Select Interviewer</label>
                    <select v-model="reassignForm.new_interviewer_id" class="input-field w-full">
                        <option value="">Select interviewer</option>
                        <option v-for="interviewer in available_interviewers" :key="interviewer.id" :value="interviewer.id">
                            {{ interviewer.name }}{{ interviewer.emp ? ` (${interviewer.emp})` : '' }}
                        </option>
                    </select>
                    <p v-if="reassignForm.errors.new_interviewer_id" class="error">
                        {{ reassignForm.errors.new_interviewer_id }}
                    </p>
                </div>
            </div>
            <template #footer>
                <button @click="showReassign = false" class="btn-secondary">Cancel</button>
                <button @click="submitReassign" :disabled="reassignForm.processing" class="btn-primary">
                    {{ reassignForm.processing ? 'Reassigning...' : 'Reassign Interviewer' }}
                </button>
            </template>
        </Modal>
    </InterviewerLayout>
</template>