<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ runs: Object, clients: Object, statuses: Object, filters: Object })

const clientId = ref(props.filters?.client_id ?? '')
const status = ref(props.filters?.status ?? '')

watch([clientId, status], () => {
    router.get(route('hr.payroll.index'), {
        client_id: clientId.value,
        status: status.value,
    }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Payroll Runs</h1>
                <Link :href="route('hr.payroll.index')" class="btn-secondary">Refresh</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="status" class="input-field w-48">
                    <option value="">All Status</option>
                    <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Run Number</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Pay Date</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Employees</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Gross</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Deductions</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Net</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="r in runs.data" :key="r.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ r.run_number }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ r.client?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ r.pay_date }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="{
                                    'bg-yellow-100 text-yellow-700': r.status === 'draft',
                                    'bg-blue-100 text-blue-700': r.status === 'calculated',
                                    'bg-orange-100 text-orange-700': r.status === 'pending_review',
                                    'bg-indigo-100 text-indigo-700': r.status === 'pending_approval',
                                    'bg-green-100 text-green-700': r.status === 'approved',
                                    'bg-purple-100 text-purple-700': r.status === 'payslips_published',
                                    'bg-gray-100 text-gray-600': r.status === 'closed',
                                }" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ r.status.replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ r.employee_count }}</td>
                            <td class="px-5 py-4 text-right text-gray-600">{{ parseFloat(r.total_gross || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-right text-gray-600">{{ parseFloat(r.total_deductions || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-gray-900">{{ parseFloat(r.total_net || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-right space-x-2">
                                <Link :href="route('hr.payroll.calculations', r.id)" class="text-indigo-600 hover:underline text-xs">View Details</Link>
                            </td>
                        </tr>
                        <tr v-if="!runs.data?.length">
                            <td colspan="9" class="px-5 py-10 text-center text-gray-400">No payroll runs found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="runs.last_page > 1" :links="runs.links" :current-page="runs.current_page" />
        </div>
    </AppLayout>
</template>
