<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ balances: Object, clients: Object, users: Object, filters: Object })

const clientId = ref(props.filters?.client_id ?? '')
const userId = ref(props.filters?.user_id ?? '')
const year = ref(props.filters?.year ?? new Date().getFullYear())

watch([clientId, userId, year], () => {
    router.get(route('hr.leave.balances'), {
        client_id: clientId.value,
        user_id: userId.value,
        year: year.value,
    }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Leave Balances</h1>
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
                <input v-model="year" type="number" class="input-field w-32" min="2020" max="2030" />
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Leave Type</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Year</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Available</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Used</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="b in balances.data" :key="b.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ b.user?.first_name }} {{ b.user?.last_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ b.user?.employee_id }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ b.client?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ b.leaveType?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ b.year }}</td>
                            <td class="px-5 py-4 text-center font-semibold text-green-600">{{ b.available_balance?.toFixed(1) || '0.0' }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ b.used_balance }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ b.pending_balance }}</td>
                        </tr>
                        <tr v-if="!balances.data?.length">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400">No leave balances found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="balances.last_page > 1" :links="balances.links" :current-page="balances.current_page" />
        </div>
    </AppLayout>
</template>
