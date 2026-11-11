<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ policy: Object, clients: Array, leaveTypes: Array })

const form = useForm({
    client_id: props.policy.client_id,
    leave_type_id: props.policy.leave_type_id,
    employee_category: props.policy.employee_category,
    annual_entitlement: props.policy.annual_entitlement,
    monthly_entitlement: props.policy.monthly_entitlement,
    accrual_frequency: props.policy.accrual_frequency,
    credit_date: props.policy.credit_date,
    eligibility_service_days: props.policy.eligibility_service_days,
    allow_carry_forward: props.policy.allow_carry_forward,
    max_carry_forward_days: props.policy.max_carry_forward_days,
    allow_encashment: props.policy.allow_encashment,
    max_consecutive_days: props.policy.max_consecutive_days,
    notice_period_days: props.policy.notice_period_days,
    allow_half_day: props.policy.allow_half_day,
    allow_during_probation: props.policy.allow_during_probation,
    allow_negative_balance: props.policy.allow_negative_balance,
    expiry_days: props.policy.expiry_days,
    approval_hierarchy: props.policy.approval_hierarchy,
    count_weekly_offs: props.policy.count_weekly_offs,
    count_public_holidays: props.policy.count_public_holidays,
    effective_from: props.policy.effective_from,
    effective_to: props.policy.effective_to,
    is_active: props.policy.is_active,
})

const submit = () => form.put(route('admin.leave-policies.update', props.policy.id))
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.leave-policies.show', props.policy.id)" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">Edit Leave Policy</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Client <span class="text-red-500">*</span></label>
                        <select v-model="form.client_id" class="input-field">
                            <option value="">Select client</option>
                            <option v-for="c in props.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="form.errors.client_id" class="error">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <label class="label">Leave Type <span class="text-red-500">*</span></label>
                        <select v-model="form.leave_type_id" class="input-field">
                            <option value="">Select leave type</option>
                            <option v-for="lt in props.leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option>
                        </select>
                        <p v-if="form.errors.leave_type_id" class="error">{{ form.errors.leave_type_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Employee Category</label>
                        <input v-model="form.employee_category" type="text" class="input-field" />
                        <p v-if="form.errors.employee_category" class="error">{{ form.errors.employee_category }}</p>
                    </div>
                    <div>
                        <label class="label">Annual Entitlement <span class="text-red-500">*</span></label>
                        <input v-model="form.annual_entitlement" type="number" class="input-field" />
                        <p v-if="form.errors.annual_entitlement" class="error">{{ form.errors.annual_entitlement }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="label">Monthly Entitlement</label>
                        <input v-model="form.monthly_entitlement" type="number" class="input-field" />
                        <p v-if="form.errors.monthly_entitlement" class="error">{{ form.errors.monthly_entitlement }}</p>
                    </div>
                    <div>
                        <label class="label">Accrual Frequency</label>
                        <select v-model="form.accrual_frequency" class="input-field">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="upon_joining">Upon Joining</option>
                        </select>
                        <p v-if="form.errors.accrual_frequency" class="error">{{ form.errors.accrual_frequency }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="allow_carry_forward" v-model="form.allow_carry_forward" type="checkbox" class="rounded" />
                        <label for="allow_carry_forward" class="text-sm text-gray-700">Allow Carry Forward</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="label">Max Carry Forward Days</label>
                        <input v-model="form.max_carry_forward_days" type="number" class="input-field" />
                        <p v-if="form.errors.max_carry_forward_days" class="error">{{ form.errors.max_carry_forward_days }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="allow_encashment" v-model="form.allow_encashment" type="checkbox" class="rounded" />
                        <label for="allow_encashment" class="text-sm text-gray-700">Allow Encashment</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="allow_half_day" v-model="form.allow_half_day" type="checkbox" class="rounded" />
                        <label for="allow_half_day" class="text-sm text-gray-700">Allow Half Day</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="flex items-center gap-2">
                        <input id="allow_during_probation" v-model="form.allow_during_probation" type="checkbox" class="rounded" />
                        <label for="allow_during_probation" class="text-sm text-gray-700">Allow During Probation</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="allow_negative_balance" v-model="form.allow_negative_balance" type="checkbox" class="rounded" />
                        <label for="allow_negative_balance" class="text-sm text-gray-700">Allow Negative Balance</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="count_weekly_offs" v-model="form.count_weekly_offs" type="checkbox" class="rounded" />
                        <label for="count_weekly_offs" class="text-sm text-gray-700">Count Weekly Offs</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="flex items-center gap-2">
                        <input id="count_public_holidays" v-model="form.count_public_holidays" type="checkbox" class="rounded" />
                        <label for="count_public_holidays" class="text-sm text-gray-700">Count Public Holidays</label>
                    </div>
                    <div>
                        <label class="label">Max Consecutive Days</label>
                        <input v-model="form.max_consecutive_days" type="number" class="input-field" />
                        <p v-if="form.errors.max_consecutive_days" class="error">{{ form.errors.max_consecutive_days }}</p>
                    </div>
                    <div>
                        <label class="label">Notice Period (days)</label>
                        <input v-model="form.notice_period_days" type="number" class="input-field" />
                        <p v-if="form.errors.notice_period_days" class="error">{{ form.errors.notice_period_days }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Expiry Days</label>
                        <input v-model="form.expiry_days" type="number" class="input-field" />
                        <p v-if="form.errors.expiry_days" class="error">{{ form.errors.expiry_days }}</p>
                    </div>
                    <div>
                        <label class="label">Approval Hierarchy</label>
                        <input v-model="form.approval_hierarchy" type="text" class="input-field" />
                        <p v-if="form.errors.approval_hierarchy" class="error">{{ form.errors.approval_hierarchy }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Effective From <span class="text-red-500">*</span></label>
                        <input v-model="form.effective_from" type="date" class="input-field" />
                        <p v-if="form.errors.effective_from" class="error">{{ form.errors.effective_from }}</p>
                    </div>
                    <div>
                        <label class="label">Effective To</label>
                        <input v-model="form.effective_to" type="date" class="input-field" />
                        <p v-if="form.errors.effective_to" class="error">{{ form.errors.effective_to }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded" />
                    <label for="is_active" class="text-sm text-gray-700">Policy is active</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.leave-policies.show', props.policy.id)" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
