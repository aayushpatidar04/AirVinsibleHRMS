<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ clients: Array, leaveTypes: Array })

const form = useForm({
    client_id: '',
    leave_type_id: '',
    employee_category: 'Regular',
    annual_entitlement: 12,
    monthly_entitlement: 1,
    accrual_frequency: 'monthly',
    credit_date: 1,
    eligibility_service_days: 0,
    allow_carry_forward: true,
    max_carry_forward_days: 5,
    allow_encashment: false,
    max_consecutive_days: 10,
    notice_period_days: 3,
    allow_half_day: true,
    allow_during_probation: false,
    allow_negative_balance: false,
    expiry_days: 90,
    approval_hierarchy: 'manager',
    count_weekly_offs: true,
    count_public_holidays: true,
    effective_from: '',
    effective_to: '',
    is_active: true,
})

const submit = () => form.post(route('admin.leave-policies.store'))
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.leave-policies.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">New Leave Policy</h1>
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
                        <input v-model="form.employee_category" type="text" class="input-field" placeholder="Regular" />
                        <p v-if="form.errors.employee_category" class="error">{{ form.errors.employee_category }}</p>
                    </div>
                    <div>
                        <label class="label">Annual Entitlement <span class="text-red-500">*</span></label>
                        <input v-model="form.annual_entitlement" type="number" class="input-field" />
                        <p v-if="form.errors.annual_entitlement" class="error">{{ form.errors.annual_entitlement }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
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
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="label">Credit Date (day of month)</label>
                        <input v-model="form.credit_date" type="number" min="1" max="31" class="input-field" />
                        <p v-if="form.errors.credit_date" class="error">{{ form.errors.credit_date }}</p>
                    </div>
                    <div>
                        <label class="label">Eligibility (service days)</label>
                        <input v-model="form.eligibility_service_days" type="number" class="input-field" />
                        <p v-if="form.errors.eligibility_service_days" class="error">{{ form.errors.eligibility_service_days }}</p>
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
                        <input v-model="form.approval_hierarchy" type="text" class="input-field" placeholder="manager" />
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
                    <Link :href="route('admin.leave-policies.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create Policy' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
