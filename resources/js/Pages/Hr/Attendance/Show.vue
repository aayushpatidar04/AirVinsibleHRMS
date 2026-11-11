<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ attendanceRecord: Object })
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Attendance Detail</h1>
                <Link :href="route('hr.attendance.index')" class="btn-secondary">Back</Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Record Summary</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Employee</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.user?.first_name }} {{ attendanceRecord.user?.last_name }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Employee ID</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.user?.employee_id }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Client</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.client?.name }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Date</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.attendance_date }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Shift</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.shift?.name || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                <span :class="{
                                    'bg-green-100 text-green-700': attendanceRecord.status === 'present',
                                    'bg-red-100 text-red-700': attendanceRecord.status === 'absent',
                                    'bg-orange-100 text-orange-700': attendanceRecord.status === 'late',
                                    'bg-yellow-100 text-yellow-700': attendanceRecord.status === 'half_day',
                                }" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ attendanceRecord.status }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Punch Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Scheduled Start</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.scheduled_start || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Scheduled End</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.scheduled_end || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">First Punch In</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.first_punch_in || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Last Punch Out</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.last_punch_out || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Total Working Hours</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.total_working_hours || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">System Session Hours</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.system_session_hours || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Break Duration</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.break_duration || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Overtime Hours</dt>
                            <dd class="text-gray-900">{{ attendanceRecord.overtime_hours || '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div v-if="attendanceRecord.punches?.length" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Punch History</h2>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Time</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Type</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Source</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Device</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Manual Correction</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="punch in attendanceRecord.punches" :key="punch.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 text-gray-600">{{ punch.punch_time }}</td>
                            <td class="px-5 py-4">{{ punch.punch_type }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ punch.source }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ punch.device_id || '—' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span v-if="punch.is_manual_correction" class="text-xs text-blue-600">Yes</span>
                                <span v-else class="text-xs text-gray-400">No</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="attendanceRecord.notes" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-yellow-900 mb-2">Notes</h3>
                <p class="text-sm text-yellow-800">{{ attendanceRecord.notes }}</p>
            </div>
        </div>
    </AppLayout>
</template>
