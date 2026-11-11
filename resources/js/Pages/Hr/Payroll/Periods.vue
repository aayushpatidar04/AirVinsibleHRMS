<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ periods: Object, clients: Object, filters: Object })

const clientId = ref(props.filters?.client_id ?? '')

watch(clientId, () => {
    router.get(route('hr.payroll.periods'), { client_id: clientId.value }, { preserveState: true, replace: true })
})

const form = useForm({
    client_id: '',
    name: '',
    start_date: '',
    end_date: '',
    attendance_cutoff_date: '',
    salary_processing_date: '',
    notes: '',
})

const submit = () => {
    form.post(route('hr.payroll.periods.store'), {
        onSuccess: () => window.location.reload(),
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Payroll Periods</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Name</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Start Date</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">End Date</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Cutoff Date</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Processing Date</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in periods.data" :key="p.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ p.name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ p.client?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ p.start_date }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ p.end_date }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ p.attendance_cutoff_date }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ p.salary_processing_date }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="p.status === 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ p.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right"></td>
                        </tr>
                        <tr v-if="!periods.data?.length">
                            <td colspan="8" class="px-5 py-10 text-center text-gray-400">No payroll periods found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="periods.last_page > 1" :links="periods.links" :current-page="periods.current_page" />

            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Create Payroll Period</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                        <select v-model="form.client_id" class="input-field w-full">
                            <option value="">Select client</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="form.errors.client_id" class="text-xs text-red-500 mt-1">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field w-full" placeholder="e.g. November 2026" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                        <input v-model="form.start_date" type="date" class="input-field w-full" />
                        <p v-if="form.errors.start_date" class="text-xs text-red-500 mt-1">{{ form.errors.start_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
                        <input v-model="form.end_date" type="date" class="input-field w-full" />
                        <p v-if="form.errors.end_date" class="text-xs text-red-500 mt-1">{{ form.errors.end_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Attendance Cutoff Date <span class="text-red-500">*</span></label>
                        <input v-model="form.attendance_cutoff_date" type="date" class="input-field w-full" />
                        <p v-if="form.errors.attendance_cutoff_date" class="text-xs text-red-500 mt-1">{{ form.errors.attendance_cutoff_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Salary Processing Date <span class="text-red-500">*</span></label>
                        <input v-model="form.salary_processing_date" type="date" class="input-field w-full" />
                        <p v-if="form.errors.salary_processing_date" class="text-xs text-red-500 mt-1">{{ form.errors.salary_processing_date }}</p>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="input-field w-full"></textarea>
                        <p v-if="form.errors.notes" class="text-xs text-red-500 mt-1">{{ form.errors.notes }}</p>
                    </div>
                    <div class="md:col-span-3 flex justify-end pt-2">
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            {{ form.processing ? 'Creating...' : 'Create Period' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
