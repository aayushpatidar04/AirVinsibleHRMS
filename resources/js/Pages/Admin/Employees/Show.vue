<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import Modal from '@/Components/Common/Modal.vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    employee: Object,
    interview_stats: Object,
    recent_interviews: Array,
    all_roles: Array,
    branches: Array,
    interview_rounds: Array, // NEW
})

const showResetModal = ref(false)
const showRoleModal = ref(false)
const showRoundModal = ref(false) // NEW
const selectedRole = ref('')
const selectedRoundIds = ref([]) // NEW

const resetForm = useForm({ password: '', password_confirmation: '' })
const roundForm = useForm({ round_ids: [] }) // NEW

const submitReset = () => {
    resetForm.post(route('admin.employees.reset-password', props.employee.id), {
        onSuccess: () => { showResetModal.value = false; resetForm.reset() }
    })
}

const toggleInterviewer = () => {
    router.post(route('admin.employees.toggle-interviewer', props.employee.id), {}, {
        onSuccess: () => { }
    })
}

// NEW: Open round modal with pre-selected values
const openRoundModal = () => {
    selectedRoundIds.value = [...props.employee.interview_round_ids]
    showRoundModal.value = true
}

// NEW: Submit round assignment
const submitRoundAssignment = () => {
    roundForm.round_ids = selectedRoundIds.value
    roundForm.post(route('admin.employees.sync-interview-rounds', props.employee.id), {
        onSuccess: () => { showRoundModal.value = false; roundForm.reset() }
    })
}

const assignRole = () => {
    if (!selectedRole.value) return
    router.post(route('admin.employees.assign-role', props.employee.id),
        { role: selectedRole.value },
        { onSuccess: () => { showRoleModal.value = false; selectedRole.value = '' } }
    )
}

const removeRole = (role) => {
    if (confirm(`Remove role "${role}" from ${props.employee.name}?`)) {
        router.post(route('admin.employees.remove-role', props.employee.id), { role })
    }
}

const toggleEmployee = () => {
    if (props.employee.deleted_at) {
        if (confirm('Restore this employee?')) {
            router.post(route('admin.employees.restore', props.employee.id))
        }
    } else {
        if (confirm('Deactivate this employee?')) {
            router.delete(route('admin.employees.destroy', props.employee.id))
        }
    }
}

const roleColors = {
    admin: 'bg-red-100 text-red-700 border-red-200',
    interviewer: 'bg-teal-100 text-teal-700 border-teal-200',
    hr: 'bg-purple-100 text-purple-700 border-purple-200',
    employee: 'bg-gray-100 text-gray-600 border-gray-200',
}

const availableRoles = props.all_roles.filter(r => !props.employee.roles?.includes(r))

// NEW: Check if employee has specific round assigned
const hasRound = (roundId) => props.employee.interview_round_ids?.includes(roundId)

// NEW: Toggle round selection
const toggleRound = (roundId) => {
    const idx = selectedRoundIds.value.indexOf(roundId)
    if (idx > -1) {
        selectedRoundIds.value.splice(idx, 1)
    } else {
        selectedRoundIds.value.push(roundId)
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.employees.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div
                        class="h-14 w-14 rounded-2xl bg-indigo-100 text-indigo-700 font-bold text-xl flex items-center justify-center flex-shrink-0">
                        {{employee.name?.split(' ').map(n => n[0]).join('').slice(0, 2)}}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ employee.name }}</h1>
                        <p class="text-sm text-gray-500">{{ employee.designation ?? 'No designation' }} · {{
                            employee.department ?? '—' }} · {{ employee.branch ?? 'No branch' }}</p>
                        <p class="text-xs text-gray-400">{{ employee.employee_id }} · Joined {{ employee.date_of_joining
                            ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button @click="toggleInterviewer" :class="employee.can_interview ? 'btn-secondary' : 'btn-teal'"
                        class="text-sm">
                        {{ employee.can_interview ? '🔕 Remove Interviewer' : '🎙 Make Interviewer' }}
                    </button>
                    <Link :href="route('admin.employees.edit', employee.id)" class="btn-secondary text-sm">Edit</Link>
                    <button @click="showResetModal = true" class="btn-secondary text-sm">Reset Password</button>
                    <button @click="toggleEmployee"
                        :class="employee.deleted_at ? 'btn-success text-sm' : 'btn-danger text-sm'">
                        {{ employee.deleted_at ? 'Restore' : 'Deactivate' }}
                    </button>
                </div>
            </div>

            <!-- Status Badges -->
            <div class="flex flex-wrap gap-2">
                <span v-for="role in employee.roles" :key="role"
                    :class="['inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium border', roleColors[role] ?? 'bg-gray-100 text-gray-600 border-gray-200']">
                    {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                    <button v-if="employee.roles.length > 1" @click="removeRole(role)"
                        class="ml-1 opacity-60 hover:opacity-100 text-base leading-none">×</button>
                </span>
                <button @click="showRoleModal = true" v-if="availableRoles.length > 0"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 transition">
                    + Add Role
                </button>
                <span v-if="employee.can_interview"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-teal-50 text-teal-700 border border-teal-200">
                    🎙 Can conduct interviews
                </span>
            </div>

            <!-- NEW: Interview Rounds Badge Section -->
            <div v-if="employee.can_interview" class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 text-sm">Assigned Interview Rounds</h3>
                    <button @click="openRoundModal" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                        {{ employee.interview_round_ids?.length > 0 ? 'Edit Rounds' : 'Assign Rounds' }}
                    </button>
                </div>
                <div v-if="employee.interview_round_ids?.length > 0" class="flex flex-wrap gap-2">
                    <span v-for="round in interview_rounds.filter(r => hasRound(r.id))" :key="round.id"
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-blue-50 text-blue-700 border border-blue-200">
                        {{ round.name }}
                    </span>
                </div>
                <p v-else class="text-sm text-gray-400">
                    No rounds assigned.
                    <button @click="openRoundModal" class="text-indigo-600 hover:underline">Assign rounds →</button>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Employee Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
                    <h2 class="font-semibold text-gray-800">Details</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 flex-shrink-0">Email</dt>
                            <dd class="text-gray-800 break-all">{{ employee.email }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 flex-shrink-0">Phone</dt>
                            <dd class="text-gray-800">{{ employee.phone ?? '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 flex-shrink-0">Employee ID</dt>
                            <dd class="text-gray-800">{{ employee.employee_id ?? '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 flex-shrink-0">Date of Birth</dt>
                            <dd class="text-gray-800">{{ employee.date_of_birth ?? '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 flex-shrink-0">Emp. Type</dt>
                            <dd class="text-gray-800 capitalize">{{ employee.employment_type?.replace('_', ' ') ?? '—'
                                }}
                            </dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 flex-shrink-0">Status</dt>
                            <dd class="capitalize font-medium"
                                :class="employee.employment_status === 'active' ? 'text-green-600' : 'text-red-500'">
                                {{ employee.employment_status }}
                            </dd>
                        </div>
                        <div v-if="employee.address" class="flex gap-2">
                            <dt class="w-32 text-gray-500 flex-shrink-0">Address</dt>
                            <dd class="text-gray-700">{{ employee.address }}</dd>
                        </div>
                        <div v-if="employee.emergency_contact_name" class="pt-2 border-t">
                            <p class="text-xs text-gray-500 font-medium mb-1">Emergency Contact</p>
                            <p class="text-gray-700">{{ employee.emergency_contact_name }}</p>
                            <p class="text-gray-600">{{ employee.emergency_contact_phone }}</p>
                        </div>
                    </dl>
                </div>

                <!-- Interview Stats + Recent -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Stats -->
                    <div class="grid grid-cols-4 gap-3">
                        <div v-for="(val, key) in { Total: interview_stats.total, Pending: interview_stats.pending, Completed: interview_stats.completed, Rejected: interview_stats.rejected }"
                            :key="key" class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                            <p class="text-2xl font-bold text-gray-800">{{ val }}</p>
                            <p class="text-xs text-gray-500">{{ key }}</p>
                        </div>
                    </div>

                    <!-- Recent Interviews -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h2 class="font-semibold text-gray-800">Recent Interviews</h2>
                        </div>
                        <div class="divide-y divide-gray-50">
                            <div v-for="i in recent_interviews" :key="i.id"
                                class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ i.candidate }}</p>
                                    <p class="text-xs text-gray-400">{{ i.position }} · {{ i.round }}</p>
                                </div>
                                <div class="text-right">
                                    <StatusBadge :status="i.status" type="progress" />
                                    <p v-if="i.rating" class="text-xs text-yellow-600 mt-0.5">{{ i.rating }} ★</p>
                                    <p class="text-xs text-gray-400">{{ i.date }}</p>
                                </div>
                            </div>
                            <p v-if="!recent_interviews?.length" class="px-5 py-8 text-center text-gray-400 text-sm">
                                No interviews conducted yet.
                                <span v-if="!employee.can_interview" class="block text-xs mt-1 text-teal-600">Click
                                    "Make Interviewer" to enable interview access.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reset Password Modal -->
        <Modal :show="showResetModal" title="Reset Password" @close="showResetModal = false">
            <div class="space-y-4">
                <div>
                    <label class="label">New Password</label>
                    <input v-model="resetForm.password" type="password" class="input-field"
                        placeholder="Min 8 characters" />
                    <p v-if="resetForm.errors.password" class="error">{{ resetForm.errors.password }}</p>
                </div>
                <div>
                    <label class="label">Confirm Password</label>
                    <input v-model="resetForm.password_confirmation" type="password" class="input-field" />
                </div>
            </div>
            <template #footer>
                <button @click="showResetModal = false" class="btn-secondary">Cancel</button>
                <button @click="submitReset" :disabled="resetForm.processing" class="btn-primary">Reset</button>
            </template>
        </Modal>

        <!-- Assign Role Modal -->
        <Modal :show="showRoleModal" title="Assign Additional Role" @close="showRoleModal = false">
            <div class="space-y-4">
                <p class="text-sm text-gray-500">Current roles: <strong>{{ employee.roles?.join(', ') }}</strong></p>
                <div>
                    <label class="label">Select Role to Add</label>
                    <select v-model="selectedRole" class="input-field">
                        <option value="">Choose role...</option>
                        <option v-for="r in availableRoles" :key="r" :value="r">{{ r.charAt(0).toUpperCase() +
                            r.slice(1) }}
                        </option>
                    </select>
                </div>
                <div v-if="selectedRole === 'interviewer'"
                    class="bg-teal-50 border border-teal-200 rounded-lg p-3 text-sm text-teal-700">
                    🎙 This will allow <strong>{{ employee.name }}</strong> to conduct interviews.
                </div>
                <div v-if="selectedRole === 'admin'"
                    class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                    ⚠️ Admin role grants full system access. Use with caution.
                </div>
            </div>
            <template #footer>
                <button @click="showRoleModal = false" class="btn-secondary">Cancel</button>
                <button @click="assignRole" :disabled="!selectedRole" class="btn-primary">Assign Role</button>
            </template>
        </Modal>

        <!-- NEW: Assign Interview Rounds Modal -->
        <Modal :show="showRoundModal" title="Assign Interview Rounds" @close="showRoundModal = false">
            <div class="space-y-4">
                <p class="text-sm text-gray-500">
                    Select which interview rounds <strong>{{ employee.name }}</strong> can conduct.
                </p>

                <div v-if="!employee.can_interview"
                    class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-700">
                    ⚠️ This employee is not marked as an interviewer yet.
                    <button @click="toggleInterviewer" class="underline font-medium">Make Interviewer first</button>
                </div>

                <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                    <div v-for="round in interview_rounds" :key="round.id" @click="toggleRound(round.id)" :class="[
                        'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition',
                        selectedRoundIds.includes(round.id)
                            ? 'bg-blue-50 border-blue-300'
                            : 'bg-white border-gray-200 hover:border-gray-300'
                    ]">
                        <div :class="[
                            'w-5 h-5 rounded border flex items-center justify-center flex-shrink-0 transition',
                            selectedRoundIds.includes(round.id)
                                ? 'bg-blue-600 border-blue-600'
                                : 'border-gray-300'
                        ]">
                            <svg v-if="selectedRoundIds.includes(round.id)" class="w-3 h-3 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ round.name }}</p>
                            <p v-if="round.description" class="text-xs text-gray-500">{{ round.description }}</p>
                        </div>
                    </div>
                </div>

                <p v-if="roundForm.errors.round_ids" class="error">{{ roundForm.errors.round_ids }}</p>
            </div>
            <template #footer>
                <button @click="showRoundModal = false" class="btn-secondary">Cancel</button>
                <button @click="submitRoundAssignment" :disabled="roundForm.processing || !employee.can_interview"
                    class="btn-primary">
                    {{ roundForm.processing ? 'Saving...' : 'Save Rounds' }}
                </button>
            </template>
        </Modal>
    </AdminLayout>
</template>