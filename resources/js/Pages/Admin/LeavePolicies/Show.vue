<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({ policy: Object })

const deletePolicy = () => {
    if (confirm('Delete this leave policy? This cannot be undone.'))
        router.delete(route('admin.leave-policies.destroy', props.policy.id))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.leave-policies.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ policy.leaveType?.name }} Policy</h1>
                        <p class="text-sm text-gray-500">{{ policy.client?.name }} · {{ policy.employee_category }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="policy.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                        class="text-xs font-semibold px-3 py-1 rounded-full">
                        {{ policy.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <Link :href="route('admin.leave-policies.edit', policy.id)" class="btn-secondary text-sm">Edit</Link>
                    <button @click="deletePolicy" class="btn-danger text-sm">Delete</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Policy Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Client</dt>
                            <dd class="text-gray-800">{{ policy.client?.name }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Leave Type</dt>
                            <dd class="text-gray-800">{{ policy.leaveType?.name }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Employee Category</dt>
                            <dd class="text-gray-800">{{ policy.employee_category }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Annual Entitlement</dt>
                            <dd class="text-gray-800">{{ policy.annual_entitlement }} days</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Monthly Entitlement</dt>
                            <dd class="text-gray-800">{{ policy.monthly_entitlement || '—' }} days</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Accrual Frequency</dt>
                            <dd class="text-gray-800">{{ policy.accrual_frequency }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Carry Forward & Encashment</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Allow Carry Forward</dt>
                            <dd class="text-gray-800">{{ policy.allow_carry_forward ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Max Carry Forward Days</dt>
                            <dd class="text-gray-800">{{ policy.max_carry_forward_days }} days</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Allow Encashment</dt>
                            <dd class="text-gray-800">{{ policy.allow_encashment ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Rules & Restrictions</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Max Consecutive Days</dt>
                            <dd class="text-gray-800">{{ policy.max_consecutive_days }} days</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Notice Period</dt>
                            <dd class="text-gray-800">{{ policy.notice_period_days }} days</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Allow Half Day</dt>
                            <dd class="text-gray-800">{{ policy.allow_half_day ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Allow During Probation</dt>
                            <dd class="text-gray-800">{{ policy.allow_during_probation ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Allow Negative Balance</dt>
                            <dd class="text-gray-800">{{ policy.allow_negative_balance ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Expiry Days</dt>
                            <dd class="text-gray-800">{{ policy.expiry_days }} days</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Counting Rules</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Count Weekly Offs</dt>
                            <dd class="text-gray-800">{{ policy.count_weekly_offs ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Count Public Holidays</dt>
                            <dd class="text-gray-800">{{ policy.count_public_holidays ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Approval Hierarchy</dt>
                            <dd class="text-gray-800">{{ policy.approval_hierarchy }}</dd>
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
                    </dl>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
