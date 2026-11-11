<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ corrections: Object, clients: Object, filters: Object })

const status = ref(props.filters?.status ?? '')
const clientId = ref(props.filters?.client_id ?? '')

watch([status, clientId], () => {
    router.get(route('hr.attendance.corrections'), {
        status: status.value,
        client_id: clientId.value,
    }, { preserveState: true, replace: true })
})

const approveCorrection = (correction, approved) => {
    router.post(route('hr.attendance.corrections.approve', correction.id), {
        approved: approved,
        rejection_reason: approved ? '' : prompt('Rejection reason:') || '',
    }, {
        onSuccess: () => window.location.reload(),
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Attendance Corrections</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="status" class="input-field w-48">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Date</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Original</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Corrected</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="c in corrections.data" :key="c.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ c.user?.first_name }} {{ c.user?.last_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ c.user?.employee_id }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ c.client?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ c.correction_date }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">
                                {{ c.original_punch_in || '—' }} / {{ c.original_punch_out || '—' }}
                            </td>
                            <td class="px-5 py-4 text-center text-gray-600">
                                {{ c.corrected_punch_in || '—' }} / {{ c.corrected_punch_out || '—' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span :class="{
                                    'bg-yellow-100 text-yellow-700': c.status === 'pending',
                                    'bg-green-100 text-green-700': c.status === 'approved',
                                    'bg-red-100 text-red-700': c.status === 'rejected',
                                }" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ c.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-2">
                                <span v-if="c.status === 'pending'" class="text-xs">
                                    <button @click="approveCorrection(c, true)" class="text-green-600 hover:underline">Approve</button>
                                    <button @click="approveCorrection(c, false)" class="text-red-600 hover:underline">Reject</button>
                                </span>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                        </tr>
                        <tr v-if="!corrections.data?.length">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400">No corrections found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="corrections.last_page > 1" :links="corrections.links" :current-page="corrections.current_page" />
        </div>
    </AppLayout>
</template>
