<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({ run: Object, calculations: Object })
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Payroll Calculations</h1>
                <Link :href="route('hr.payroll.index')" class="btn-secondary">Back to Payroll Runs</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Run Number</h2>
                        <p class="text-lg font-bold text-gray-900">{{ run.run_number }}</p>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Client</h2>
                        <p class="text-lg font-bold text-gray-900">{{ run.client?.name }}</p>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Pay Date</h2>
                        <p class="text-lg font-bold text-gray-900">{{ run.pay_date }}</p>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Status</h2>
                        <p :class="{
                            'text-yellow-600': run.status === 'draft',
                            'text-blue-600': run.status === 'calculated',
                            'text-orange-600': run.status === 'pending_review',
                            'text-indigo-600': run.status === 'pending_approval',
                            'text-green-600': run.status === 'approved',
                            'text-purple-600': run.status === 'payslips_published',
                            'text-gray-600': run.status === 'closed',
                        }" class="font-bold capitalize">{{ run.status }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Total Gross</h2>
                            <p class="text-lg font-bold text-gray-900">{{ parseFloat(run.total_gross || 0).toFixed(2) }}</p>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Total Net</h2>
                            <p class="text-lg font-bold text-gray-900">{{ parseFloat(run.total_net || 0).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Payable Days</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Present</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Working Hours</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Overtime</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Gross</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Deductions</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Net Pay</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="c in calculations.data" :key="c.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ c.user?.first_name }} {{ c.user?.last_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ c.user?.employee_id }}</div>
                            </td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ c.payable_days }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ c.present_days }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ c.total_working_hours }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ c.overtime_hours }}</td>
                            <td class="px-5 py-4 text-right text-gray-600">{{ parseFloat(c.gross_earnings || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-right text-gray-600">{{ parseFloat(c.total_deductions || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-gray-900">{{ parseFloat(c.net_pay || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="c.status === 'calculated' ? 'text-blue-600' : 'text-green-600'" class="text-xs font-medium">
                                    {{ c.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!calculations.data?.length">
                            <td colspan="9" class="px-5 py-10 text-center text-gray-400">No calculations found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
