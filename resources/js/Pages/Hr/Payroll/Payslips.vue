<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ payslips: Object, clients: Object, users: Object, filters: Object })

const clientId = ref(props.filters?.client_id ?? '')
const userId = ref(props.filters?.user_id ?? '')
const isPublished = ref(props.filters?.is_published ?? '')

watch([clientId, userId, isPublished], () => {
    router.get(route('hr.payroll.payslips'), {
        client_id: clientId.value,
        user_id: userId.value,
        is_published: isPublished.value,
    }, { preserveState: true, replace: true })
})

const publishPayslip = (payslip, publish) => {
    router.post(route('hr.payroll.payslips.publish', payslip.id), {
        publish: publish,
    }, {
        onSuccess: () => window.location.reload(),
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Payslips</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="userId" class="input-field w-48">
                    <option value="">All Employees</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.first_name }} {{ u.last_name }}</option>
                </select>
                <select v-model="isPublished" class="input-field w-40">
                    <option value="">All</option>
                    <option value="true">Published</option>
                    <option value="false">Unpublished</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Payslip Number</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Period</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Net Pay</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Published</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in payslips.data" :key="p.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ p.payslip_number }}</td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ p.user?.first_name }} {{ p.user?.last_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ p.user?.employee_id }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ p.client?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ p.period_start }} to {{ p.period_end }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-gray-900">{{ parseFloat(p.net_pay || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="p.is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ p.is_published ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-2">
                                <button @click="publishPayslip(p, !p.is_published)" class="text-xs px-3 py-1 rounded border transition"
                                    :class="p.is_published ? 'text-red-600 border-red-200 hover:bg-red-50' : 'text-green-600 border-green-200 hover:bg-green-50'">
                                    {{ p.is_published ? 'Unpublish' : 'Publish' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!payslips.data?.length">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400">No payslips found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="payslips.last_page > 1" :links="payslips.links" :current-page="payslips.current_page" />
        </div>
    </AppLayout>
</template>
