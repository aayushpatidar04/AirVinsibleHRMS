<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ attendanceRecords: Object, clients: Object, users: Object, filters: Object })

const search = ref('')
const clientId = ref(props.filters?.client_id ?? '')
const status = ref(props.filters?.status ?? '')
const startDate = ref(props.filters?.start_date ?? '')
const endDate = ref(props.filters?.end_date ?? '')

watch([search, clientId, status, startDate, endDate], () => {
    router.get(route('hr.attendance.index'), {
        client_id: clientId.value,
        status: status.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Attendance Records</h1>
                <Link :href="route('hr.attendance.index')" class="btn-secondary">Refresh</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search employee name..." class="input-field w-64" />
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="status" class="input-field w-40">
                    <option value="">All Status</option>
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="half_day">Half Day</option>
                    <option value="weekly_off">Weekly Off</option>
                    <option value="holiday">Holiday</option>
                    <option value="leave">Leave</option>
                </select>
                <input v-model="startDate" type="date" class="input-field w-40" />
                <input v-model="endDate" type="date" class="input-field w-40" />
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Date</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Check-in</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Check-out</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Hours</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Late</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="record in attendanceRecords.data" :key="record.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ record.user?.first_name }} {{ record.user?.last_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ record.user?.employee_id }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ record.client?.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ record.attendance_date }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="record.status === 'late' ? 'text-red-600' : 'text-gray-600'" class="text-xs font-medium">
                                    {{ record.status }}
                                </span>
                                <span v-if="record.is_late" class="text-red-500 text-xs ml-1">● {{ record.late_minutes }}min</span>
                            </td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ record.first_punch_in || '—' }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ record.last_punch_out || '—' }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ record.total_working_hours || '—' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span v-if="record.missing_punch" class="text-xs text-red-500 font-medium">Missing Punch</span>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-3">
                                <Link :href="route('hr.attendance.show', record.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                            </td>
                        </tr>
                        <tr v-if="!attendanceRecords.data?.length">
                            <td colspan="9" class="px-5 py-10 text-center text-gray-400">No attendance records found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="attendanceRecords.last_page > 1" :links="attendanceRecords.links" :current-page="attendanceRecords.current_page" />
        </div>
    </AppLayout>
</template>
