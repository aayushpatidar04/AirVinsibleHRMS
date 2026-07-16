<script setup>
import InterviewerLayout from '@/Layouts/InterviewerLayout.vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import Modal from '@/Components/Common/Modal.vue'
import { ref, computed, watch } from 'vue'

const props = defineProps({
  candidate: Object,
  interview: Object,
  questions: Array,
  previous_feedback: Array,
  next_rounds: Array,
  salary_logic: Object,
  registration_form: Object, // NEW
})

const started = ref(props.interview.status === 'in_progress' || props.interview.status === 'completed')
const showReject = ref(false)
const showComplete = ref(false)
const showRegPanel = ref(false) // NEW: Registration panel visibility

// Per-question response forms (saved individually)
const saving = ref({})
const responseData = ref({})
props.questions.forEach(q => {
  responseData.value[q.id] = {
    response_text: q.response?.response_text ?? '',
    rating_value: q.response?.rating_value ?? null,
    yes_no_value: q.response?.yes_no_value ?? null,
    selected_options: q.response?.selected_options ?? [],
    interviewer_notes: q.response?.interviewer_notes ?? '',
    question_rating: q.response?.question_rating ?? null,
  }
})

const saveResponse = async (questionId) => {
  saving.value[questionId] = true
  router.post(
    route('interviewer.interviews.response.save', { candidate: props.candidate.id, round: props.interview.round_id }),
    { question_id: questionId, ...responseData.value[questionId] },
    { preserveState: true, onFinish: () => (saving.value[questionId] = false) }
  )
}

// Complete form
const completeForm = useForm({
  overall_feedback: props.interview.overall_feedback ?? '',
  overall_rating: props.interview.overall_rating ?? 3,
  next_round_id: '',
  next_interviewer_id: '',
  salary_offer_min: props.interview.salary_offer_min ?? '',
  salary_offer_max: props.interview.salary_offer_max ?? '',
  offered_designation: props.interview.offered_designation ?? '',
  salary_offer_status: props.interview.salary_offer_status ?? 'pending',
})

// Fetch interviewers for selected round
const available_interviewers = ref([])
const loading_interviewers = ref(false)

const fetchInterviewers = async (roundId) => {
  if (!roundId) {
    available_interviewers.value = []
    completeForm.next_interviewer_id = ''
    return
  }

  loading_interviewers.value = true
  try {
    const response = await fetch(
      route('interviewer.interviews.get-interviewers', { 
        candidate: props.candidate.id, 
        round: roundId 
      })
    )
    const data = await response.json()
    available_interviewers.value = data.interviewers || []
    completeForm.next_interviewer_id = ''
  } catch (error) {
    console.error('Failed to fetch interviewers:', error)
    available_interviewers.value = []
  } finally {
    loading_interviewers.value = false
  }
}

// Watch for changes in next_round_id and fetch interviewers
watch(() => completeForm.next_round_id, (newRoundId) => {
  fetchInterviewers(newRoundId)
})

const submitComplete = () => {
  completeForm.post(
    route('interviewer.interviews.complete', { candidate: props.candidate.id, round: props.interview.round_id }),
    { onSuccess: () => (showComplete.value = false) }
  )
}

// Reject form
const rejectForm = useForm({ rejection_reason: '', overall_feedback: '' })
const submitReject = () => {
  rejectForm.post(
    route('interviewer.interviews.reject', { candidate: props.candidate.id, round: props.interview.round_id }),
    { onSuccess: () => (showReject.value = false) }
  )
}

// Custom question
const showCustomQ = ref(false)
const customQForm = useForm({ question_text: '', question_type: 'short_answer' })
const submitCustomQ = () => {
  customQForm.post(
    route('interviewer.interviews.add-question', { candidate: props.candidate.id, round: props.interview.round_id }),
    {
      onSuccess: () => {
        showCustomQ.value = false;
        customQForm.reset()
      }
    }
  )
}

const startInterview = () => {
  router.post(
    route('interviewer.interviews.start', { candidate: props.candidate.id, round: props.interview.round_id }),
    {}, { onSuccess: () => (started.value = true) }
  )
}

const salaryLabel = computed(() => {
  if (props.salary_logic.is_mandatory) {
    return '💰 Final Salary Range (required in this HR round)'
  }

  if (props.salary_logic.is_post_ops) return '📊 Salary Range (OPS discussion)'
  if (props.salary_logic.is_optional_hr) return '💰 Salary Range (optional)'
  return ''
})

// NEW: Format registration form value for display
const formatRegValue = (field) => {
  const val = field.value

  if (val === null || val === undefined || val === '') return '—'

  if (field.field_type === 'checkbox' && Array.isArray(val)) {
    return val.join(', ') || '—'
  }

  if (field.field_type === 'radio' || field.field_type === 'dropdown') {
    return val || '—'
  }

  if (field.field_type === 'date' && val) {
    return new Date(val).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
  }

  return val
}

// NEW: Get field type icon
const fieldTypeIcon = (type) => {
  const icons = {
    text: '📝',
    email: '📧',
    phone: '📱',
    number: '🔢',
    date: '📅',
    textarea: '📋',
    dropdown: '🔽',
    radio: '⭕',
    checkbox: '☑️',
    file: '📎',
  }
  return icons[type] || '📝'
}
</script>

<template>
  <InterviewerLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
          <Link :href="route('interviewer.candidates.show', candidate.id)" class="text-gray-400 hover:text-gray-600">←
          </Link>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-2xl font-bold text-gray-900">{{ interview.round_name }}</h1>
              <span v-if="interview.is_hr_round"
                class="text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">HR Round</span>
              <span v-if="interview.is_ops_round" class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">OPS
                Round</span>
            </div>
            <p class="text-sm text-gray-500">{{ candidate.name }} · {{ candidate.position }} · {{
              candidate.profile_label }}</p>
          </div>
        </div>
        <div class="flex gap-2 flex-wrap">
          <span v-if="interview.status === 'in_progress'"
            class="text-xs bg-yellow-100 text-yellow-700 px-3 py-3 rounded-full font-medium">In Progress</span>

          <!-- NEW: View Registration Form Button -->
          <button v-if="registration_form" @click="showRegPanel = true"
            class="btn-secondary text-sm bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100">
            📝 View Registration
          </button>

          <a v-if="candidate.resume_path" :href="`/storage/${candidate.resume_path}`" target="_blank"
            class="btn-secondary text-sm text-indigo-600 border border-indigo-200 rounded-lg py-2 hover:bg-indigo-100 transition">
            📄 View Resume
          </a>
          <button v-if="!started" @click="startInterview" class="btn-primary">▶ Start Interview</button>
          <template v-if="started && interview.status !== 'completed'">
            <button @click="showCustomQ = true" class="btn-secondary text-sm">+ Custom Question</button>
            <button @click="showComplete = true" class="btn-primary  text-sm">✓ Complete Round</button>
            <button @click="showReject = true" class="btn-danger   text-sm">✕ Reject</button>
          </template>
        </div>
      </div>

      <!-- Salary Notice Banner -->
      <div v-if="salary_logic.show_salary_section && salary_logic.salary_hint" :class="[
        'rounded-xl border p-4 text-sm',
        salary_logic.is_mandatory ? 'bg-orange-50 border-orange-200 text-orange-800' :
          salary_logic.is_post_ops ? 'bg-blue-50   border-blue-200   text-blue-800' :
            'bg-yellow-50  border-yellow-200  text-yellow-800'
      ]">
        {{ salary_logic.salary_hint }}
      </div>

      <!-- Previous Feedback -->
      <div v-if="previous_feedback.length" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 mb-3">Previous Round Feedback</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="fb in previous_feedback" :key="fb.round"
            class="bg-gray-50 rounded-lg p-3 text-sm border border-gray-100">
            <div class="flex items-center justify-between mb-1">
              <span class="font-medium text-gray-700">{{ fb.round }}</span>
              <span v-if="fb.rating" class="text-yellow-600 font-semibold">{{ fb.rating }} ★</span>
            </div>
            <p class="text-gray-600 text-xs">By {{ fb.interviewer }} on {{ fb.date }}</p>
            <p class="text-gray-700 mt-1">{{ fb.feedback }}</p>
          </div>
        </div>
      </div>

      <!-- Questions -->
      <div v-if="started" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-800">Interview Questions ({{ questions.length }})</h2>
          <span class="text-xs text-gray-400">{{questions.filter(q => q.is_mandatory).length}} mandatory</span>
        </div>

        <div v-for="(q, idx) in questions" :key="q.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-start gap-3 mb-4">
            <span
              class="h-7 w-7 rounded-full bg-teal-100 text-teal-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">
              {{ idx + 1 }}
            </span>
            <div class="flex-1">
              <p class="font-medium text-gray-800">{{ q.question_text }}</p>
              <div class="flex gap-2 mt-1 flex-wrap">
                <span class="text-xs text-gray-400">{{ q.question_type.replace('_', ' ') }}</span>
                <span v-if="q.is_mandatory"
                  class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Required</span>
                <span v-if="q.is_custom"
                  class="text-xs bg-purple-100 text-purple-600 px-1.5 py-0.5 rounded">Custom</span>
              </div>
            </div>
          </div>

          <!-- Candidate Response Input -->
          <div class="space-y-3 pl-10">
            <!-- Short / Long Answer -->
            <div v-if="['short_answer', 'long_answer'].includes(q.question_type)">
              <label class="label">Candidate's Answer</label>
              <component :is="q.question_type === 'long_answer' ? 'textarea' : 'input'"
                v-model="responseData[q.id].response_text" :rows="4" class="input-field"
                placeholder="Record candidate's response..." />
            </div>

            <!-- Rating Scale -->
            <div v-if="q.question_type === 'rating_scale'">
              <label class="label">Rating (1–5)</label>
              <div class="flex gap-2">
                <button v-for="n in 5" :key="n" type="button" @click="responseData[q.id].rating_value = n" :class="[
                  'h-10 w-10 rounded-xl font-bold text-sm transition border',
                  responseData[q.id].rating_value === n
                    ? 'bg-teal-600 text-white border-teal-600'
                    : 'bg-white text-gray-600 border-gray-300 hover:border-teal-400'
                ]">
                  {{ n }}
                </button>
              </div>
            </div>

            <!-- Yes / No -->
            <div v-if="q.question_type === 'yes_no'">
              <label class="label">Answer</label>
              <div class="flex gap-3">
                <button type="button" @click="responseData[q.id].yes_no_value = true"
                  :class="['px-5 py-2 rounded-lg font-medium text-sm border transition',
                    responseData[q.id].yes_no_value === true ? 'bg-green-600 text-white border-green-600' : 'bg-white border-gray-300 hover:border-green-400']">
                  Yes
                </button>
                <button type="button" @click="responseData[q.id].yes_no_value = false"
                  :class="['px-5 py-2 rounded-lg font-medium text-sm border transition',
                    responseData[q.id].yes_no_value === false ? 'bg-red-500 text-white border-red-500' : 'bg-white border-gray-300 hover:border-red-400']">
                  No
                </button>
              </div>
            </div>

            <!-- Multiple Choice -->
            <div v-if="q.question_type === 'multiple_choice' && q.options">
              <label class="label">Select Option</label>
              <div class="space-y-2">
                <label v-for="opt in q.options" :key="opt" class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" :value="opt" v-model="responseData[q.id].selected_options"
                    class="rounded text-teal-600" />
                  <span class="text-sm text-gray-700">{{ opt }}</span>
                </label>
              </div>
            </div>

            <!-- Interviewer Notes -->
            <div>
              <label class="label">Interviewer Notes</label>
              <input v-model="responseData[q.id].interviewer_notes" type="text" class="input-field"
                placeholder="Your observations about this answer..." />
            </div>

            <!-- Question Rating -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Answer Quality:</label>
                <div class="flex gap-1">
                  <button v-for="n in 5" :key="n" type="button" @click="responseData[q.id].question_rating = n"
                    :class="['text-xl transition', n <= (responseData[q.id].question_rating ?? 0) ? 'text-yellow-400' : 'text-gray-200']">
                    ★
                  </button>
                </div>
              </div>
              <button type="button" @click="saveResponse(q.id)" :disabled="saving[q.id]"
                class="text-xs bg-teal-50 text-teal-700 border border-teal-200 px-3 py-1.5 rounded-lg hover:bg-teal-100 transition disabled:opacity-50">
                {{ saving[q.id] ? 'Saving...' : '💾 Save Response' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="bg-gray-50 rounded-xl border border-dashed border-gray-300 p-12 text-center">
        <p class="text-gray-500 text-lg mb-4">Click <strong>Start Interview</strong> to begin recording responses.</p>
      </div>
    </div>

    <!-- ============================================ -->
    <!-- NEW: Registration Form Side Panel -->
    <!-- ============================================ -->
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="translate-x-full opacity-0"
      enter-to-class="translate-x-0 opacity-100" leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-x-0 opacity-100" leave-to-class="translate-x-full opacity-0">
      <div v-if="showRegPanel"
        class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl border-l border-gray-200 flex flex-col">
        <!-- Panel Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-indigo-50">
          <div>
            <h2 class="text-lg font-bold text-gray-900">📝 Registration Details</h2>
            <p v-if="registration_form.submitted_at" class="text-xs text-gray-500">
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
          <!-- No data state -->
          <div v-if="!registration_form || !registration_form.fields" class="text-center py-12 text-gray-400">
            <p class="text-4xl mb-3">📭</p>
            <p>No registration form data available.</p>
          </div>

          <!-- Form Fields -->
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
                  <p class="text-sm text-gray-800 mt-1 break-words">
                    {{ formatRegValue(field) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Candidate Basic Info Summary -->
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

    <!-- Backdrop overlay -->
    <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="showRegPanel" @click="showRegPanel = false" class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm">
      </div>
    </Transition>

    <!-- Complete Round Modal -->
    <Modal :show="showComplete" title="Complete Interview Round" max-width="2xl" @close="showComplete = false">
      <div class="space-y-5">
        <!-- Overall Rating -->
        <div class="max-h-[90vh] overflow-y-auto hide-scrollbar">
          <label class="label">Overall Rating <span class="text-red-500">*</span></label>
          <div class="flex gap-2">
            <button v-for="n in 5" :key="n" type="button" @click="completeForm.overall_rating = n"
              :class="['text-3xl transition', n <= completeForm.overall_rating ? 'text-yellow-400' : 'text-gray-200']">
              ★
            </button>
          </div>
        </div>

        <!-- Feedback -->
        <div>
          <label class="label">Overall Feedback <span class="text-red-500">*</span></label>
          <textarea v-model="completeForm.overall_feedback" rows="4" class="input-field"
            placeholder="Summarise the candidate's performance in this round..." />
          <p v-if="completeForm.errors.overall_feedback" class="error">{{ completeForm.errors.overall_feedback }}</p>
        </div>

        <!-- Salary Offer Section -->
        <div v-if="salary_logic.show_salary_section" :class="[
          'rounded-xl border p-4 space-y-3',
          salary_logic.is_mandatory ? 'bg-orange-50 border-orange-200' : 'bg-yellow-50 border-yellow-200'
        ]">
          <p class="text-sm font-semibold" :class="salary_logic.is_mandatory ? 'text-orange-800' : 'text-yellow-800'">
            {{ salaryLabel }}
          </p>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">
                Salary Offer Minimum (₹ / year)
                <span v-if="salary_logic.is_mandatory" class="text-red-500">*</span>
              </label>
              <input v-model="completeForm.salary_offer_min" type="number" class="input-field"
                :placeholder="salary_logic.is_mandatory ? 'Required for this profile' : 'Optional'" min="0"
                step="1000" />
              <p v-if="completeForm.errors.salary_offer_min" class="error">{{ completeForm.errors.salary_offer_min }}</p>
            </div>
            <div>
              <label class="label">
                Salary Offer Maximum (₹ / year)
                <span v-if="salary_logic.is_mandatory" class="text-red-500">*</span>
              </label>
              <input v-model="completeForm.salary_offer_max" type="number" class="input-field"
                :placeholder="salary_logic.is_mandatory ? 'Required for this profile' : 'Optional'" min="0"
                step="1000" />
              <p v-if="completeForm.errors.salary_offer_max" class="error">{{ completeForm.errors.salary_offer_max }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Offered Designation</label>
              <input v-model="completeForm.offered_designation" type="text" class="input-field"
                placeholder="e.g. Senior Advisor" />
            </div>
            <div v-if="completeForm.salary_offer_min || completeForm.salary_offer_max">
              <label class="label">Salary Offer Status</label>
              <select v-model="completeForm.salary_offer_status" class="input-field">
                <option value="pending">Pending Candidate Response</option>
                <option value="accepted">Accepted</option>
                <option value="negotiating">Negotiating</option>
                <option value="declined">Declined</option>
              </select>
            </div>
          </div>
        </div>

        <p v-if="salary_logic.is_post_ops && !salary_logic.is_ops_round"
          class="text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-3">
          ℹ️ Salary for this profile will be discussed in the OPS round. No offer required here.
        </p>

        <!-- Next Round -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Move to Next Round</label>
            <select v-model="completeForm.next_round_id" class="input-field">
              <option value="">— Final round (no next round) —</option>
              <option v-for="r in next_rounds" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
          </div>
          <div v-if="completeForm.next_round_id">
            <label class="label">Assign Interviewer <span class="text-red-500">*</span></label>
            <select v-model="completeForm.next_interviewer_id" class="input-field" :disabled="loading_interviewers">
              <option value="">{{ loading_interviewers ? 'Loading interviewers...' : 'Select Interviewer' }}</option>
              <option v-for="i in available_interviewers" :key="i.id" :value="i.id">
                {{ i.name }} {{ i.emp ? `(${i.emp})` : '' }}
              </option>
            </select>
            <p v-if="completeForm.errors.next_interviewer_id" class="error">{{ completeForm.errors.next_interviewer_id
            }}
            </p>
          </div>
        </div>

        <p v-if="completeForm.errors.questions" class="error">{{ completeForm.errors.questions }}</p>
      </div>
      <template #footer>
        <button @click="showComplete = false" class="btn-secondary">Cancel</button>
        <button @click="submitComplete" :disabled="completeForm.processing" class="btn-primary">
          {{ completeForm.processing ? 'Completing...' : '✓ Complete Round' }}
        </button>
      </template>
    </Modal>


    <!-- Reject Modal -->
    <Modal :show="showReject" title="Reject Candidate" @close="showReject = false">
      <div class="space-y-4">
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
          This will mark the candidate as <strong>Rejected</strong> and end the process.
        </div>
        <div>
          <label class="label">Rejection Reason <span class="text-red-500">*</span></label>
          <textarea v-model="rejectForm.rejection_reason" rows="3" class="input-field border-red-200 focus:ring-red-400"
            placeholder="Why is this candidate being rejected?" />
          <p v-if="rejectForm.errors.rejection_reason" class="error">{{ rejectForm.errors.rejection_reason }}</p>
        </div>
        <div>
          <label class="label">Additional Feedback</label>
          <textarea v-model="rejectForm.overall_feedback" rows="2" class="input-field"
            placeholder="Optional notes..." />
        </div>
      </div>
      <template #footer>
        <button @click="showReject = false" class="btn-secondary">Cancel</button>
        <button @click="submitReject" :disabled="rejectForm.processing"
          class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-medium transition disabled:opacity-50">
          {{ rejectForm.processing ? 'Rejecting...' : '✕ Confirm Reject' }}
        </button>
      </template>
    </Modal>

    <!-- Custom Question Modal -->
    <Modal :show="showCustomQ" title="Add Custom Question" @close="showCustomQ = false">
      <div class="space-y-4">
        <div>
          <label class="label">Question <span class="text-red-500">*</span></label>
          <textarea v-model="customQForm.question_text" rows="3" class="input-field"
            placeholder="Type your question..." />
          <p v-if="customQForm.errors.question_text" class="error">{{ customQForm.errors.question_text }}</p>
        </div>
        <div>
          <label class="label">Type</label>
          <select v-model="customQForm.question_type" class="input-field">
            <option value="short_answer">Short Answer</option>
            <option value="long_answer">Long Answer</option>
            <option value="rating_scale">Rating Scale</option>
            <option value="yes_no">Yes / No</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button @click="showCustomQ = false" class="btn-secondary">Cancel</button>
        <button @click="submitCustomQ" :disabled="customQForm.processing" class="btn-primary">
          {{ customQForm.processing ? 'Adding...' : 'Add Question' }}
        </button>
      </template>
    </Modal>
  </InterviewerLayout>
</template>