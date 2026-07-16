<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Common/Modal.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ interviewer: Object, stats: Object, recent_interviews: Array })

const showResetModal = ref(false)
const resetForm = useForm({ password: '', password_confirmation: '' })

const submitReset = () => {
    resetForm.post(route('admin.interviewers.reset-password', props.interviewer.id), {
        onSuccess: () => { showResetModal.value = false; resetForm.reset() }
    })
}

const removeInterviewer = () => {
    if (!confirm('Delete this interviewer? This cannot be undone.')) return
    router.delete(route('admin.interviewers.destroy', props.interviewer.id))
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Interviewer</h1>
                    <p class="text-sm text-gray-500 mt-1">View interviewer details and recent activity</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('admin.interviewers.edit', props.interviewer.id)" class="btn-secondary">Edit</Link>
                    <button @click="showResetModal = true" class="btn-secondary">Reset Password</button>
                    <button @click="removeInterviewer" class="btn-danger">Delete</button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-indigo-100 text-indigo-700 font-bold text-xl flex items-center justify-center">{{ props.interviewer.name?.split(' ').map(n => n[0]).join('').slice(0,2) }}</div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">{{ props.interviewer.name }}</h2>
                        <p class="text-sm text-gray-500">{{ props.interviewer.email }}</p>
                        <p class="text-xs text-gray-400">{{ props.interviewer.phone ?? '—' }} · {{ props.interviewer.branch ?? '—' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-gray-800">{{ props.stats.total ?? 0 }}</div>
                        <div class="text-xs text-gray-500">Total Interviews</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ props.stats.pending ?? 0 }}</div>
                        <div class="text-xs text-gray-500">Pending</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ props.stats.completed ?? 0 }}</div>
                        <div class="text-xs text-gray-500">Completed</div>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="font-semibold text-gray-800 mb-2">Recent Interviews</h3>
                    <div class="divide-y divide-gray-50">
                        <div v-for="i in props.recent_interviews" :key="i.id" class="py-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ i.candidate }}</p>
                                <p class="text-xs text-gray-400">{{ i.round }} · {{ i.date ?? '—' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm">{{ i.status }}</p>
                            </div>
                        </div>
                        <p v-if="!props.recent_interviews?.length" class="py-6 text-center text-gray-400">No recent interviews.</p>
                    </div>
                </div>
            </div>

            <!-- Reset Password Modal -->
            <Modal :show="showResetModal" title="Reset Password" @close="showResetModal = false">
                <div class="space-y-4">
                    <div>
                        <label class="label">New Password</label>
                        <input v-model="resetForm.password" type="password" class="input-field" placeholder="Min 8 characters" />
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
        </div>
    </AdminLayout>
</template>
