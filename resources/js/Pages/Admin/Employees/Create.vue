<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ branches: Array, roles: Array, managers: Array, employmentTypes: Object })

const form = useForm({
    first_name: '', last_name: '', email: '', phone: '',
    employee_id: '', designation: '', department: '',
    branch_id: '', reporting_to: '', date_of_joining: '',
    date_of_birth: '', employment_type: 'full_time',
    address: '', emergency_contact_name: '', emergency_contact_phone: '',
    password: '', password_confirmation: '',
    roles: ['employee'],
    can_interview: false,
})

const submit = () => form.post(route('admin.employees.store'))

const toggleRole = (r) => {
    const idx = form.roles.indexOf(r)
    if (idx >= 0) form.roles.splice(idx, 1)
    else form.roles.push(r)
    if (form.roles.includes('interviewer')) form.can_interview = true
}
</script>

<template>
    <AdminLayout>
        <div class="max-w-3xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.employees.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Add Employee</h1>
                    <p class="text-sm text-gray-500">New employees can later be promoted to interviewers, HR, or admin
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Personal Information</h2>
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
                            <input v-model="form.email" type="email" class="input-field"
                                placeholder="rahul@company.com" />
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
                            <label class="label">Date of Birth</label>
                            <input v-model="form.date_of_birth" type="date" class="input-field" />
                        </div>
                        <div>
                            <label class="label">Address</label>
                            <input v-model="form.address" type="text" class="input-field"
                                placeholder="Current address" />
                        </div>
                    </div>
                </div>

                <!-- Employment Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Employment Details</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Employee ID</label>
                            <input v-model="form.employee_id" type="text" class="input-field" placeholder="EMP001" />
                            <p v-if="form.errors.employee_id" class="error">{{ form.errors.employee_id }}</p>
                        </div>
                        <div>
                            <label class="label">Employment Type <span class="text-red-500">*</span></label>
                            <select v-model="form.employment_type" class="input-field">
                                <option v-for="(l, k) in employmentTypes" :key="k" :value="k">{{ l }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Designation</label>
                            <input v-model="form.designation" type="text" class="input-field"
                                placeholder="e.g. Senior Advisor" />
                        </div>
                        <div>
                            <label class="label">Department</label>
                            <input v-model="form.department" type="text" class="input-field"
                                placeholder="e.g. Sales, Operations, HR" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Branch</label>
                            <select v-model="form.branch_id" class="input-field">
                                <option value="">Select Branch</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p v-if="form.errors.branch_id" class="error">{{ form.errors.branch_id }}</p>
                        </div>
                        <div>
                            <label class="label">Reports To</label>
                            <select v-model="form.reporting_to" class="input-field">
                                <option value="">None (Top Level)</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }} {{ m.designation ?
                                    `— ${m.designation}` : '' }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="label">Date of Joining</label>
                        <input v-model="form.date_of_joining" type="date" class="input-field w-56" />
                    </div>
                </div>

                <!-- Roles & Permissions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Roles & Permissions</h2>
                    <p class="text-sm text-gray-500">Assign roles to control what this employee can access. Multiple
                        roles are allowed.</p>
                    <div class="flex flex-wrap gap-3">
                        <button v-for="r in roles" :key="r" type="button" @click="toggleRole(r)" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium border transition',
                            form.roles.includes(r)
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400'
                        ]">
                            {{ r.charAt(0).toUpperCase() + r.slice(1) }}
                        </button>
                    </div>
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 text-sm text-indigo-700">
                        <strong>Selected roles:</strong> {{ form.roles.length ? form.roles.join(', ') : 'None' }}<br />
                        <span v-if="form.roles.includes('interviewer')" class="text-teal-700">
                            🎙 This employee will be able to conduct interviews.
                        </span>
                        <span v-if="form.roles.includes('admin')" class="text-red-700">
                            ⚠️ Admin role grants full system access.
                        </span>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.can_interview" type="checkbox" class="rounded text-teal-600" />
                        <span class="text-sm text-gray-700">
                            Allow conducting interviews
                            <span class="text-xs text-gray-400">(auto-set when interviewer role is assigned)</span>
                        </span>
                    </label>
                    <p v-if="form.errors.roles" class="error">{{ form.errors.roles }}</p>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Emergency Contact</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Contact Name</label>
                            <input v-model="form.emergency_contact_name" type="text" class="input-field"
                                placeholder="Parent / Spouse name" />
                        </div>
                        <div>
                            <label class="label">Contact Phone</label>
                            <input v-model="form.emergency_contact_phone" type="tel" class="input-field"
                                placeholder="Emergency phone number" />
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Account Password</h2>
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
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.employees.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create Employee' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>