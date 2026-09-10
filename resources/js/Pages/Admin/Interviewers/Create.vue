<script setup>
// This file is Interviewers/Create.vue
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ branches: Array })

const form = useForm({
    first_name: '', last_name: '', email: '', phone: '',
    employee_id: '', branch_id: '', password: '', password_confirmation: '',
})

const submit = () => form.post(route('admin.interviewers.store'))
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.interviewers.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">New Interviewer</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">First Name <span class="text-red-500">*</span></label>
                        <input v-model="form.first_name" type="text" class="input-field" placeholder="Rahul" />
                        <p v-if="form.errors.first_name" class="error">{{ form.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="label">Last Name <span class="text-red-500">*</span></label>
                        <input v-model="form.last_name" type="text" class="input-field" placeholder="Sharma" />
                        <p v-if="form.errors.last_name" class="error">{{ form.errors.last_name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Email <span class="text-red-500">*</span></label>
                        <input v-model="form.email" type="email" class="input-field" placeholder="rahul@company.com" />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="label">Phone <span class="text-red-500">*</span></label>
                        <input v-model="form.phone" type="tel" class="input-field" placeholder="9876543210" />
                        <p v-if="form.errors.phone" class="error">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Employee ID</label>
                        <input v-model="form.employee_id" type="text" class="input-field" placeholder="INT001" />
                    </div>
                    <div>
                        <label class="label">Branch <span class="text-red-500">*</span></label>
                        <select v-model="form.branch_id" class="input-field">
                            <option value="">Select Branch</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <p v-if="form.errors.branch_id" class="error">{{ form.errors.branch_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Password <span class="text-red-500">*</span></label>
                        <input v-model="form.password" type="password" class="input-field"
                            placeholder="Min 8 characters" />
                        <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">Confirm Password <span class="text-red-500">*</span></label>
                        <input v-model="form.password_confirmation" type="password" class="input-field" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.interviewers.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create Interviewer' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>