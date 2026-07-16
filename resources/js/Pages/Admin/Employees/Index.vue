<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import Modal from '@/Components/Common/Modal.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
    employees: Object, branches: Array, roles: Array,
    departments: Array, filters: Object, employmentTypes: Object,
})

const search = ref(props.filters?.search ?? '')
const branchId = ref(props.filters?.branch_id ?? '')
const department = ref(props.filters?.department ?? '')
const role = ref(props.filters?.role ?? '')
const status = ref(props.filters?.status ?? '')
const canInterview = ref(props.filters?.can_interview ?? '')

const showImportModal = ref(false)
const importForm = useForm({
    file: null,
})

watch([search, branchId, department, role, status, canInterview], () => {
    router.get(route('admin.employees.index'), {
        search: search.value, branch_id: branchId.value,
        department: department.value, role: role.value,
        status: status.value, can_interview: canInterview.value,
    }, { preserveState: true, replace: true })
})

const roleBadge = (roles) => {
    const colors = {
        admin: 'bg-red-100 text-red-700', interviewer: 'bg-teal-100 text-teal-700',
        hr: 'bg-purple-100 text-purple-700', employee: 'bg-gray-100 text-gray-600'
    }
    return roles?.map(r => ({ label: r, color: colors[r] ?? 'bg-gray-100 text-gray-600' })) ?? []
}

const handleFileChange = (event) => {
    importForm.file = event.target.files[0]
}

const submitImport = () => {
    const formData = new FormData()
    formData.append('file', importForm.file)

    router.post(route('admin.employees.import'), formData, {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false
            importForm.reset()
        },
    })
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Employees</h1>
                    <p class="text-sm text-gray-500 mt-1">All employees — assign roles (interviewer, HR, admin) to
                        control access</p>
                </div>
                <div class="flex gap-2">
                    <button @click="showImportModal = true" class="btn-secondary">
                        ↓ Import
                    </button>
                    <Link :href="route('admin.employees.create')" class="btn-primary">+ Add Employee</Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name, email, ID, designation..."
                    class="input-field w-64" />
                <select v-model="branchId" class="input-field w-40">
                    <option value="">All Branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="department" class="input-field w-40">
                    <option value="">All Departments</option>
                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                </select>
                <select v-model="role" class="input-field w-36">
                    <option value="">All Roles</option>
                    <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                </select>
                <select v-model="canInterview" class="input-field w-40">
                    <option value="">All Employees</option>
                    <option value="yes">Interviewers Only</option>
                </select>
                <select v-model="status" class="input-field w-36">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div v-if="$page.props.flash.importErrors?.length" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-red-800 mb-2">⚠️ Import Errors ({{
                    $page.props.flash.importErrors.length }} rows failed)</h3>
                <div class="max-h-48 overflow-y-auto space-y-1">
                    <div v-for="error in $page.props.flash.importErrors" :key="error.row" class="text-xs text-red-700">
                        Row {{ error.row }} ({{ error.email }}): {{ error.error }}
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Designation / Dept</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Branch</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Roles</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Interviews</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center flex-shrink-0">
                                        {{emp.name?.split(' ').map(n => n[0]).join('').slice(0, 2)}}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ emp.name }}</p>
                                        <p class="text-xs text-gray-400">{{ emp.email }}</p>
                                        <p v-if="emp.employee_id" class="text-xs text-gray-400">{{ emp.employee_id }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-gray-700">{{ emp.designation ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ emp.department ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-gray-600 text-sm">{{ emp.branch ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="b in roleBadge(emp.roles)" :key="b.label"
                                        :class="['text-xs font-medium px-2 py-0.5 rounded-full', b.color]">
                                        {{ b.label }}
                                    </span>
                                </div>
                                <div v-if="emp.can_interview" class="mt-1">
                                    <span class="text-xs bg-teal-50 text-teal-600 px-1.5 py-0.5 rounded">🎙 Can
                                        interview</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span v-if="emp.total_interviews > 0" class="text-indigo-600 font-semibold">{{
                                    emp.total_interviews }}</span>
                                <span v-else class="text-gray-300">—</span>
                                <span v-if="emp.pending_count > 0" class="ml-1 text-xs text-yellow-600">({{
                                    emp.pending_count }} pending)</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span
                                    :class="emp.employment_status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                    class="text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                                    {{ emp.employment_status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-3">
                                <Link :href="route('admin.employees.show', emp.id)"
                                    class="text-indigo-600 hover:underline text-xs">View</Link>
                                <Link :href="route('admin.employees.edit', emp.id)"
                                    class="text-gray-500 hover:underline text-xs">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!employees.data?.length">
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">No employees found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="employees.links" />
        </div>

        <Modal :show="showImportModal" title="Import Employees from Excel" @close="showImportModal = false">
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                    <p class="font-semibold mb-1">📋 Instructions</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Download the <a :href="route('admin.employees.import.sample')"
                                class="underline font-medium">sample template</a> to see the format</li>
                        <li>Required columns: <strong>first_name, last_name, email, phone, employee_id, designation,
                                department, branch_name, date_of_birth, employment_type</strong></li>
                        <li><strong>branch_name</strong>: Enter branch name (case-insensitive). If not found, assigns to
                            first branch</li>
                        <li><strong>roles</strong>: comma-separated (e.g., <code>employee,interviewer</code>)</li>
                        <li><strong>reporting_to_email</strong>: email of existing manager</li>
                        <li><strong>can_interview</strong>: <code>yes</code> or <code>no</code></li>
                        <li>Dates format: <code>YYYY-MM-DD</code></li>
                        <li>Supported formats: <code>.xlsx</code>, <code>.xls</code>, <code>.csv</code></li>
                    </ul>
                </div>

                <div>
                    <label class="label">Upload Excel File <span class="text-red-500">*</span></label>
                    <div class="flex items-center justify-center w-full">
                        <label
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <p class="text-3xl mb-2">📊</p>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium text-indigo-600">Click to upload</span> or drag & drop
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Excel file (.xlsx, .xls, .csv) max 5 MB</p>
                                <p v-if="importForm.file" class="text-xs text-green-600 font-medium mt-1">
                                    ✓ {{ importForm.file.name }}
                                </p>
                            </div>
                            <input type="file" class="hidden" accept=".xlsx,.xls,.csv" @change="handleFileChange" />
                        </label>
                    </div>
                    <p v-if="importForm.errors.file" class="error">{{ importForm.errors.file }}</p>
                </div>
            </div>
            <template #footer>
                <button @click="showImportModal = false" class="btn-secondary">Cancel</button>
                <a :href="route('admin.employees.import.sample')" class="btn-secondary text-sm">
                    ↓ Download Template
                </a>
                <button @click="submitImport" :disabled="!importForm.file || importForm.processing" class="btn-primary">
                    {{ importForm.processing ? 'Importing...' : 'Import Employees' }}
                </button>
            </template>
        </Modal>

        <!-- Import Debug Panel (temporary, remove after fixing) -->
        <div v-if="$page.props.flash.importDebug?.length"
            class="mb-6 bg-gray-900 text-green-400 rounded-xl p-4 font-mono text-xs overflow-auto max-h-96">
            <div class="flex items-center justify-between mb-2">
                <p class="font-bold text-white">🔧 Import Debug Log</p>
                <button @click="$page.props.flash.importDebug = []" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <div v-for="(log, i) in $page.props.flash.importDebug" :key="i"
                class="py-0.5 border-b border-gray-800 last:border-0">
                {{ log }}
            </div>
        </div>

        <!-- Import Errors -->
        <div v-if="$page.props.flash.importErrors?.length" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="font-semibold text-red-800 mb-2">⚠️ Import Errors ({{ $page.props.flash.importErrors.length }})
            </p>
            <div class="overflow-auto max-h-64">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-red-600 border-b border-red-200">
                            <th class="py-1 pr-4">Row</th>
                            <th class="py-1 pr-4">Email</th>
                            <th class="py-1">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="err in $page.props.flash.importErrors" :key="err.row + err.error"
                            class="border-b border-red-100 last:border-0">
                            <td class="py-1 pr-4 text-red-700 font-mono">{{ err.row }}</td>
                            <td class="py-1 pr-4 text-red-700">{{ err.email }}</td>
                            <td class="py-1 text-red-600">{{ err.error }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>