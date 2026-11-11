<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ applications: Object, clients: Object, users: Object, leaveTypes: Object, filters: Object })

const status = ref(props.filters?.status ?? '')
const userId = ref(props.filters?.user_id ?? '')
const clientId = ref(props.filters?.client_id ?? '')
const leaveTypeId = ref(props.filters?.leave_type_id ?? '')

watch([status, userId, clientId, leaveTypeId], () => {
    router.get(route('hr.leave.index'), {
        status: status.value,
        user_id: userId.value,
        client_id: clientId.value,
        leave_type_id: leaveTypeId.value,
    }, { preserveState: true, replace: true })
})

const approveLeave = (application, approved) => {
    const reason = approved ? '' : (prompt('Rejection reason:') || '')
    router.post(route('hr.leave.approve', application.id), {
        approved: approved,
        rejection_reason: reason,
    }, {
        onSuccess: () => window.location.reload(),
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Leave Applications</h1>
                <Link :href="route('hr.leave.index')" class="btn-secondary">Refresh</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="status" class="input-field w-48">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select v-model="userId" class="input-field w-48">
                    <option value="">All Employees</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.first_name }} {{ u.last_name }}</option>
                </select>
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="leaveTypeId" class="input-field w-48">
                    <option value="">All Leave Types</option>
                    <option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Leave Type</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">From</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">To</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Days</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="a in applications.data" :key="a.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ a.user?.first_name }} {{ a.user?.last_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ a.user?.employee_id }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ a.leaveType?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ a.start_date }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ a.end_date }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ a.total_days }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="{
                                    'bg-yellow-100 text-yellow-700': a.status === 'pending',
                                    'bg-green-100 text-green-700': a.status === 'approved',
                                    'bg-red-100 text-red-700': a.status === 'rejected',
                                }" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ a.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-2">
                                <span v-if="a.status === 'pending'" class="text-xs">
                                    <button @click="approveLeave(a, true)" class="text-green-600 hover:underline">Approve</button>
                                    <button @click="approveLeave(a, false)" class="text-red-600 hover:underline">Reject</button>
                                </span>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                        </tr>
                        <tr v-if="!applications.data?.length">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400">No leave applications found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="applications.last_page > 1" :links="applications.links" :current-page="applications.current_page" />
        </div>
    </AppLayout>
</template>
