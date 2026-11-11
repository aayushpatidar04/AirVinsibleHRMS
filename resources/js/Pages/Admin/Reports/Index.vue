<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ clients: Array, selectedClientId: [Number, String], stats: Object })

const clientId = ref(props.selectedClientId ?? '')

watch(clientId, () => {
    router.get(route('admin.reports.index'), {
        client_id: clientId.value || null,
    }, { preserveState: true, replace: true })
})

const statCards = [
    { label: 'Total Candidates', value: props.stats?.total_candidates ?? 0, color: 'text-indigo-600', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { label: 'Selected Candidates', value: props.stats?.selected_candidates ?? 0, color: 'text-green-600', icon: 'M5 13l4 4L19 7' },
    { label: 'Converted to Employee', value: props.stats?.converted_candidates ?? 0, color: 'text-teal-600', icon: 'M9 19V6l12-3v13' },
    { label: 'Total Employees', value: props.stats?.total_employees ?? 0, color: 'text-blue-600', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { label: 'Attendance - Present', value: props.stats?.attendance_present ?? 0, color: 'text-emerald-600', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Attendance - Absent', value: props.stats?.attendance_absent ?? 0, color: 'text-rose-600', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Leave - Pending', value: props.stats?.leave_pending ?? 0, color: 'text-amber-600', icon: 'M19 14l-7 7v-4a6 6 0 00-6-6V3m6 11V5a6 6 0 016 6v8m0 0l-7-7' },
    { label: 'Leave - Approved', value: props.stats?.leave_approved ?? 0, color: 'text-emerald-600', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Payslips Published', value: props.stats?.published_payslips ?? 0, color: 'text-purple-600', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Total Payroll Amount', value: props.stats?.total_payroll_amount ?? 0, color: 'text-indigo-600', prefix: '₹', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
]
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Consolidated Reports</h1>
                <div class="flex items-center gap-3">
                    <select v-model="clientId" class="input-field w-64">
                        <option value="">All Clients</option>
                        <option v-for="c in props.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <Link :href="route('admin.reports.index')" class="text-xs text-gray-500 hover:underline">Reset</Link>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="card in statCards" :key="card.label"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">{{ card.label }}</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ card.prefix }}{{ card.value }}
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                            <svg class="w-5 h-5" :class="card.color" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Filters Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Report Filters</h2>
                <p class="text-sm text-gray-600">
                    Filter reports by client using the dropdown above. All statistics update dynamically based on the selected client.
                </p>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase">Candidates</p>
                        <p class="text-sm font-semibold text-gray-900">{{ stats.total_candidates }} total · {{ stats.selected_candidates }} selected · {{ stats.converted_candidates }} converted</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase">Attendance</p>
                        <p class="text-sm font-semibold text-gray-900">{{ stats.attendance_present }} present · {{ stats.attendance_absent }} absent · {{ stats.attendance_late }} late</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase">Leave</p>
                        <p class="text-sm font-semibold text-gray-900">{{ stats.leave_pending }} pending · {{ stats.leave_approved }} approved · {{ stats.leave_rejected }} rejected</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
