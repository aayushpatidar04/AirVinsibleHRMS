<script setup>
// Interviewers/Edit.vue
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ interviewer: Object, branches: Array })

const form = useForm({
    first_name: props.interviewer.first_name,
    last_name: props.interviewer.last_name,
    email: props.interviewer.email,
    phone: props.interviewer.phone,
    employee_id: props.interviewer.employee_id ?? '',
    branch_id: props.interviewer.branch_id,
    is_active: props.interviewer.is_active,
})

const submit = () => form.put(route('admin.interviewers.update', props.interviewer.id))
</script>

<template>
    <AdminLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.interviewers.show', interviewer.id)"
                    class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">Edit Interviewer</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">First Name <span class="text-red-500">*</span></label>
                        <input v-model="form.first_name" type="text" class="input-field" />
                        <p v-if="form.errors.first_name" class="error">{{ form.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="label">Last Name <span class="text-red-500">*</span></label>
                        <input v-model="form.last_name" type="text" class="input-field" />
                        <p v-if="form.errors.last_name" class="error">{{ form.errors.last_name }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Email <span class="text-red-500">*</span></label>
                        <input v-model="form.email" type="email" class="input-field" />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="label">Phone <span class="text-red-500">*</span></label>
                        <input v-model="form.phone" type="tel" class="input-field" />
                        <p v-if="form.errors.phone" class="error">{{ form.errors.phone }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Employee ID</label>
                        <input v-model="form.employee_id" type="text" class="input-field" />
                    </div>
                    <div>
                        <label class="label">Branch <span class="text-red-500">*</span></label>
                        <select v-model="form.branch_id" class="input-field">
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <p v-if="form.errors.branch_id" class="error">{{ form.errors.branch_id }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded" />
                    <label for="is_active" class="text-sm text-gray-700">Account is active</label>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.interviewers.show', interviewer.id)" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>