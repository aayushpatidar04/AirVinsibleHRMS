<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({ user: Object, todayPunches: Array, recentPunches: Array, currentStatus: String })

const statusLabel = {
    'in': 'Checked In',
    'out': 'Checked Out',
    'break_start': 'On Break',
    'break_end': 'Break Ended',
    null: 'Not Punched In Today',
}

const statusColor = {
    'in': 'bg-green-100 text-green-700',
    'out': 'bg-red-100 text-red-700',
    'break_start': 'bg-yellow-100 text-yellow-700',
    'break_end': 'bg-gray-100 text-gray-700',
    null: 'bg-gray-100 text-gray-500',
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Employee Dashboard</h1>
                <Link :href="route('employee.punch')" class="btn-primary">Punch In/Out</Link>
            </div>

            <!-- Current Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase">Current Status</p>
                        <p class="text-2xl font-bold text-gray-900">{{ statusLabel[currentStatus] }}</p>
                    </div>
                    <span :class="statusColor[currentStatus]" class="text-xs font-semibold px-3 py-1 rounded-full">
                        {{ statusLabel[currentStatus] }}
                    </span>
                </div>
            </div>

            <!-- Today's Punches -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Today's Punches</h2>
                </div>

                <div v-if="todayPunches.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium text-gray-500">Time</th>
                                <th class="px-5 py-3 text-left font-medium text-gray-500">Type</th>
                                <th class="px-5 py-3 text-left font-medium text-gray-500">Source</th>
                                <th class="px-5 py-3 text-right font-medium text-gray-500">Device</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="p in todayPunches" :key="p.id" class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-gray-900">{{ p.punch_time }}</td>
                                <td class="px-5 py-4">
                                    <span :class="p.punch_type === 'in' ? 'text-green-600' : p.punch_type === 'out' ? 'text-red-600' : 'text-yellow-600'" class="font-medium text-sm uppercase">
                                        {{ p.punch_type }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ p.source }}</td>
                                <td class="px-5 py-4 text-right text-gray-600">{{ p.device_id || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-10 text-gray-400">
                    No punches recorded today.
                </div>
            </div>

            <!-- Recent Punches -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Recent Activity</h2>
                </div>

                <div v-if="recentPunches.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium text-gray-500">Date</th>
                                <th class="px-5 py-3 text-left font-medium text-gray-500">Type</th>
                                <th class="px-5 py-3 text-left font-medium text-gray-500">Source</th>
                                <th class="px-5 py-3 text-right font-medium text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="p in recentPunches" :key="p.id" class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-gray-900">{{ p.punch_time }}</td>
                                <td class="px-5 py-4">
                                    <span :class="p.punch_type === 'in' ? 'text-green-600' : p.punch_type === 'out' ? 'text-red-600' : 'text-yellow-600'" class="font-medium text-sm uppercase">
                                        {{ p.punch_type }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ p.source }}</td>
                                <td class="px-5 py-4 text-right text-gray-400 text-xs">{{ p.device_id || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-10 text-gray-400">
                    No recent activity.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
