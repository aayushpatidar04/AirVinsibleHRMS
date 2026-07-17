<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Common/Modal.vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ round: Object, questions: Array, questionTypes: Object })

const showAddQ = ref(false)
const editQ = ref(null)

const qForm = useForm({
    question_text: '',
    question_type: 'short_answer',
    is_mandatory: false,
    order: (props.questions?.length ?? 0) + 1,
    options: [],
})

const resetQuestionForm = () => {
    qForm.reset()
    qForm.question_type = 'short_answer'
    qForm.is_mandatory = false
    qForm.order = (props.questions?.length ?? 0) + 1
    qForm.options = []
    optionInput.value = ''
}

const openAddQuestion = () => {
    editQ.value = null
    resetQuestionForm()
    showAddQ.value = true
}

const openEdit = (q) => {
    editQ.value = q
    qForm.question_text = q.question_text
    qForm.question_type = q.question_type
    qForm.is_mandatory = q.is_mandatory
    qForm.order = q.order
    qForm.options = q.options ?? []
    optionInput.value = ''
    showAddQ.value = true
}

const closeModal = () => { showAddQ.value = false; editQ.value = null; resetQuestionForm() }

const submitQ = () => {
    if (editQ.value) {
        qForm.put(route('admin.rounds.questions.update', [props.round.id, editQ.value.id]), { onSuccess: closeModal })
    } else {
        qForm.post(route('admin.rounds.questions.store', props.round.id), { onSuccess: closeModal })
    }
}

const deleteQ = (q) => {
    if (confirm('Remove this question?'))
        router.delete(route('admin.rounds.questions.destroy', [props.round.id, q.id]))
}

const deleteRound = () => {
    if (confirm('Delete this round?'))
        router.delete(route('admin.rounds.destroy', props.round.id))
}

const needsOptions = (type) => ['multiple_choice', 'dropdown', 'radio'].includes(type)
const optionInput = ref('')
const addOption = () => { if (optionInput.value.trim()) { qForm.options.push(optionInput.value.trim()); optionInput.value = '' } }
const removeOption = (i) => qForm.options.splice(i, 1)
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.rounds.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-2xl font-bold text-gray-900">{{ round.name }}</h1>
                            <span v-if="round.is_hr_round"
                                class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">HR Round</span>
                            <span v-if="round.is_ops_round"
                                class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">OPS Round</span>
                            <span v-if="round.is_mandatory"
                                class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Mandatory</span>
                        </div>
                        <p class="text-sm text-gray-500">Sequence #{{ round.sequence }} · {{ questions.length }}
                            questions</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="openAddQuestion" class="btn-primary text-sm">+ Add Question</button>
                    <Link :href="route('admin.rounds.edit', round.id)" class="btn-secondary text-sm">Edit</Link>
                    <button @click="deleteRound" class="btn-danger text-sm">Delete</button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div v-for="(val, key) in { Total: round.stats.total, Pending: round.stats.pending, 'In Progress': round.stats.in_progress, Completed: round.stats.completed, Rejected: round.stats.rejected }"
                    :key="key" class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-xl font-bold text-gray-800">{{ val }}</p>
                    <p class="text-xs text-gray-500">{{ key }}</p>
                </div>
            </div>

            <!-- Salary Logic Notice -->
            <div v-if="round.is_hr_round || round.is_ops_round"
                :class="round.is_hr_round ? 'bg-orange-50 border-orange-200 text-orange-800' : 'bg-blue-50 border-blue-200 text-blue-800'"
                class="rounded-xl border p-4 text-sm">
                <p v-if="round.is_hr_round">
                    <strong>💰 Salary Offer Rules for this HR Round:</strong><br />
                        • <strong>TL / QA / AM / OM</strong> and <strong>Other</strong> profiles — salary offer is
                        <strong>MANDATORY</strong>. Round cannot be completed without it.<br />
                        • <strong>Advisor / Executive</strong> profiles — salary discussion happens in the OPS round.
                </p>
                <p v-if="round.is_ops_round">
                    <strong>📊 Salary Discussion Rules for this OPS Round:</strong><br />
                    • <strong>Advisor / Executive</strong> profiles — record a salary range in this OPS round if available.
                </p>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">Questions</h2>
                    <span class="text-xs text-gray-400">{{questions.filter(q => q.is_mandatory).length}}
                        mandatory</span>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="(q, idx) in questions" :key="q.id"
                        class="px-5 py-4 flex items-start justify-between hover:bg-gray-50">
                        <div class="flex gap-3">
                            <span class="text-sm font-bold text-gray-400 w-5">{{ idx + 1 }}</span>
                            <div>
                                <p class="text-sm text-gray-800">{{ q.question_text }}</p>
                                <div class="flex gap-2 mt-1 flex-wrap">
                                    <span class="text-xs text-gray-400">{{ questionTypes[q.question_type] }}</span>
                                    <span v-if="q.is_mandatory"
                                        class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Required</span>
                                    <span v-if="q.is_custom"
                                        class="text-xs bg-purple-100 text-purple-600 px-1.5 py-0.5 rounded">Custom</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 ml-4 flex-shrink-0">
                            <button @click="openEdit(q)" class="text-xs text-gray-400 hover:text-gray-700">Edit</button>
                            <button @click="deleteQ(q)" class="text-xs text-red-400 hover:text-red-600">Remove</button>
                        </div>
                    </div>
                    <p v-if="!questions?.length" class="px-5 py-10 text-center text-gray-400 text-sm">No questions yet.
                        Add your first question.</p>
                </div>
            </div>
        </div>

        <!-- Add / Edit Question Modal -->
        <Modal :show="showAddQ" :title="editQ ? 'Edit Question' : 'Add Question'" max-width="lg" @close="closeModal">
            <div class="space-y-4">
                <div>
                    <label class="label">Question <span class="text-red-500">*</span></label>
                    <textarea v-model="qForm.question_text" rows="3" class="input-field"
                        placeholder="Enter your question..." />
                    <p v-if="qForm.errors.question_text" class="error">{{ qForm.errors.question_text }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Type <span class="text-red-500">*</span></label>
                        <select v-model="qForm.question_type" class="input-field">
                            <option v-for="(label, val) in questionTypes" :key="val" :value="val">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Order</label>
                        <input v-model.number="qForm.order" type="number" class="input-field" min="1" />
                    </div>
                </div>

                <!-- Options for multiple choice -->
                <div v-if="needsOptions(qForm.question_type)">
                    <label class="label">Options</label>
                    <div class="flex gap-2 mb-2">
                        <input v-model="optionInput" type="text" class="input-field flex-1" placeholder="Add option"
                            @keydown.enter.prevent="addOption" />
                        <button type="button" @click="addOption" class="btn-secondary text-sm">Add</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="(opt, i) in qForm.options" :key="i"
                            class="flex items-center gap-1 bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                            {{ opt }}
                            <button @click="removeOption(i)" class="text-red-400 hover:text-red-600 ml-1">×</button>
                        </span>
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="qForm.is_mandatory" type="checkbox" class="rounded text-red-500" />
                    <span class="text-sm text-gray-700">This question is mandatory (must be answered to close the
                        round)</span>
                </label>
            </div>
            <template #footer>
                <button @click="closeModal" class="btn-secondary">Cancel</button>
                <button @click="submitQ" :disabled="qForm.processing" class="btn-primary">
                    {{ qForm.processing ? 'Saving...' : (editQ ? 'Update Question' : 'Add Question') }}
                </button>
            </template>
        </Modal>
    </AdminLayout>
</template>