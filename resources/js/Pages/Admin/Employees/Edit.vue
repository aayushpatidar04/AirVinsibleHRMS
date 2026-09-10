<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    employee: Object, branches: Array, roles: Array,
    managers: Array, employmentTypes: Object,
})

const form = useForm({
    first_name: props.employee.first_name,
    last_name: props.employee.last_name,
    email: props.employee.email,
    phone: props.employee.phone ?? '',
    employee_id: props.employee.employee_id ?? '',
    designation: props.employee.designation ?? '',
    department: props.employee.department ?? '',
    branch_id: props.employee.branch_id ?? '',
    reporting_to: props.employee.reporting_to ?? '',
    date_of_joining: props.employee.date_of_joining ?? '',
    date_of_birth: props.employee.date_of_birth ?? '',
    employment_type: props.employee.employment_type ?? 'full_time',
    employment_status: props.employee.employment_status ?? 'active',
    address: props.employee.address ?? '',
    emergency_contact_name: props.employee.emergency_contact_name ?? '',
    emergency_contact_phone: props.employee.emergency_contact_phone ?? '',
    roles: props.employee.roles ?? ['employee'],
    can_interview: props.employee.can_interview ?? false,
    is_active: props.employee.is_active ?? true,
})

const submit = () => form.put(route('admin.employees.update', props.employee.id))

const toggleRole = (r) => {
    const idx = form.roles.indexOf(r)
    if (idx >= 0) {
        if (form.roles.length === 1) return // keep at least one
        form.roles.splice(idx, 1)
    } else {
        form.roles.push(r)
    }
    if (form.roles.includes('interviewer')) form.can_interview = true
}
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.employees.show', employee.id)" class="text-gray-400 hover:text-gray-600">←
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Employee</h1>
                    <p class="text-sm text-gray-500">{{ employee.name }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Personal Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Personal Information</h2>
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
                            <label class="label">Date of Birth</label>
                            <input v-model="form.date_of_birth" type="date" class="input-field" />
                        </div>
                        <div>
                            <label class="label">Address</label>
                            <input v-model="form.address" type="text" class="input-field" />
                        </div>
                    </div>
                </div>

                <!-- Employment -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Employment Details</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Employee ID</label>
                            <input v-model="form.employee_id" type="text" class="input-field" />
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
                            <input v-model="form.designation" type="text" class="input-field" />
                        </div>
                        <div>
                            <label class="label">Department</label>
                            <input v-model="form.department" type="text" class="input-field" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Branch</label>
                            <select v-model="form.branch_id" class="input-field">
                                <option value="">No Branch</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Reports To</label>
                            <select v-model="form.reporting_to" class="input-field">
                                <option value="">None</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Date of Joining</label>
                            <input v-model="form.date_of_joining" type="date" class="input-field" />
                        </div>
                        <div>
                            <label class="label">Employment Status <span class="text-red-500">*</span></label>
                            <select v-model="form.employment_status" class="input-field">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="resigned">Resigned</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Roles -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Roles & Access</h2>
                    <p class="text-sm text-gray-500">Toggle roles — employees can hold multiple roles simultaneously.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button v-for="r in roles" :key="r" type="button" @click="toggleRole(r)" :class="['px-4 py-2 rounded-lg text-sm font-medium border transition', form.roles.includes(r)
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400']">
                            {{ r.charAt(0).toUpperCase() + r.slice(1) }}
                        </button>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.can_interview" type="checkbox" class="rounded text-teal-600" />
                        <span class="text-sm text-gray-700">Allow conducting interviews</span>
                    </label>
                    <p v-if="form.errors.roles" class="error">{{ form.errors.roles }}</p>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-800">Emergency Contact</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Contact Name</label>
                            <input v-model="form.emergency_contact_name" type="text" class="input-field" />
                        </div>
                        <div>
                            <label class="label">Contact Phone</label>
                            <input v-model="form.emergency_contact_phone" type="tel" class="input-field" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.employees.show', employee.id)" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>