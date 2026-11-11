<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({ policy: Object })

const deletePolicy = () => {
    if (confirm('Delete this attendance policy? This cannot be undone.'))
        router.delete(route('admin.attendance-policies.destroy', props.policy.id))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.attendance-policies.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ policy.name }}</h1>
                        <p class="text-sm text-gray-500">{{ policy.client?.name }} · {{ policy.shift_start_time }} – {{ policy.shift_end_time }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="policy.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                        class="text-xs font-semibold px-3 py-1 rounded-full">
                        {{ policy.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <Link :href="route('admin.attendance-policies.edit', policy.id)" class="btn-secondary text-sm">Edit</Link>
                    <button @click="deletePolicy" class="btn-danger text-sm">Delete</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Shift Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Client</dt>
                            <dd class="text-gray-800">{{ policy.client?.name }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Shift Start</dt>
                            <dd class="text-gray-800">{{ policy.shift_start_time }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Shift End</dt>
                            <dd class="text-gray-800">{{ policy.shift_end_time }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Grace Period</dt>
                            <dd class="text-gray-800">{{ policy.grace_period_minutes }} minutes</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Max Late Arrivals</dt>
                            <dd class="text-gray-800">{{ policy.max_late_arrivals }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Min Required Hours</dt>
                            <dd class="text-gray-800">{{ policy.min_required_hours }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Break Rules</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Break Duration</dt>
                            <dd class="text-gray-800">{{ policy.break_duration_minutes }} minutes</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Max Break</dt>
                            <dd class="text-gray-800">{{ policy.max_break_minutes }} minutes</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Track Idle Time</dt>
                            <dd class="text-gray-800">{{ policy.track_idle_time ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Overtime & Deductions</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Overtime Eligible</dt>
                            <dd class="text-gray-800">{{ policy.overtime_eligible ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">OT Multiplier</dt>
                            <dd class="text-gray-800">{{ policy.overtime_multiplier }}x</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Deduct Late</dt>
                            <dd class="text-gray-800">{{ policy.deduct_late_arrival ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Deduct Early Departure</dt>
                            <dd class="text-gray-800">{{ policy.deduct_early_departure ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Thresholds</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Half Day Threshold</dt>
                            <dd class="text-gray-800">{{ policy.half_day_threshold_hours }} hours</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Full Day Threshold</dt>
                            <dd class="text-gray-800">{{ policy.full_day_threshold_hours }} hours</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Negative Leave Balance</dt>
                            <dd class="text-gray-800">{{ policy.allow_negative_leave_balance ? 'Allowed' : 'Not Allowed' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Effective Period</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Effective From</dt>
                            <dd class="text-gray-800">{{ policy.effective_from }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Effective To</dt>
                            <dd class="text-gray-800">{{ policy.effective_to || '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Weekly Offs</dt>
                            <dd class="text-gray-800">{{ policy.weekly_offs?.join(', ') || '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Public Holidays</dt>
                            <dd class="text-gray-800">{{ policy.public_holidays?.join(', ') || '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
