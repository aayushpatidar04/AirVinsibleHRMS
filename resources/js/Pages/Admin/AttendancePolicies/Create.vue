<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ clients: Array })

const form = useForm({
    client_id: '',
    name: '',
    description: '',
    shift_start_time: '09:00',
    shift_end_time: '18:00',
    grace_period_start: '',
    grace_period_end: '',
    grace_period_minutes: 15,
    max_late_arrivals: 3,
    allow_early_departure: false,
    min_required_hours: 8,
    min_session_duration_minutes: 30,
    mandatory_portal_punch: false,
    mandatory_machine_punch: false,
    break_duration_minutes: 30,
    max_break_minutes: 60,
    track_idle_time: false,
    overtime_eligible: true,
    overtime_multiplier: 1.5,
    weekly_offs: [],
    public_holidays: [],
    half_day_threshold_hours: 4.0,
    full_day_threshold_hours: 8.0,
    allow_negative_leave_balance: false,
    deduct_late_arrival: true,
    deduct_early_departure: true,
    effective_from: '',
    effective_to: '',
    is_active: true,
})

const submit = () => form.post(route('admin.attendance-policies.store'))
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.attendance-policies.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">New Attendance Policy</h1>
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
                        <label class="label">Policy Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field" placeholder="Standard Office Policy" />
                        <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="2" class="input-field" placeholder="Describe this attendance policy..." />
                    <p v-if="form.errors.description" class="error">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Shift Start Time <span class="text-red-500">*</span></label>
                        <input v-model="form.shift_start_time" type="time" class="input-field" />
                        <p v-if="form.errors.shift_start_time" class="error">{{ form.errors.shift_start_time }}</p>
                    </div>
                    <div>
                        <label class="label">Shift End Time <span class="text-red-500">*</span></label>
                        <input v-model="form.shift_end_time" type="time" class="input-field" />
                        <p v-if="form.errors.shift_end_time" class="error">{{ form.errors.shift_end_time }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Grace Period Start</label>
                        <input v-model="form.grace_period_start" type="time" class="input-field" />
                        <p v-if="form.errors.grace_period_start" class="error">{{ form.errors.grace_period_start }}</p>
                    </div>
                    <div>
                        <label class="label">Grace Period End</label>
                        <input v-model="form.grace_period_end" type="time" class="input-field" />
                        <p v-if="form.errors.grace_period_end" class="error">{{ form.errors.grace_period_end }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="label">Grace Period (minutes)</label>
                        <input v-model="form.grace_period_minutes" type="number" class="input-field" />
                        <p v-if="form.errors.grace_period_minutes" class="error">{{ form.errors.grace_period_minutes }}</p>
                    </div>
                    <div>
                        <label class="label">Max Late Arrivals</label>
                        <input v-model="form.max_late_arrivals" type="number" class="input-field" />
                        <p v-if="form.errors.max_late_arrivals" class="error">{{ form.errors.max_late_arrivals }}</p>
                    </div>
                    <div>
                        <label class="label">Min Required Hours</label>
                        <input v-model="form.min_required_hours" type="number" class="input-field" />
                        <p v-if="form.errors.min_required_hours" class="error">{{ form.errors.min_required_hours }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="flex items-center gap-2">
                        <input id="allow_early_departure" v-model="form.allow_early_departure" type="checkbox" class="rounded" />
                        <label for="allow_early_departure" class="text-sm text-gray-700">Allow Early Departure</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="mandatory_portal_punch" v-model="form.mandatory_portal_punch" type="checkbox" class="rounded" />
                        <label for="mandatory_portal_punch" class="text-sm text-gray-700">Mandatory Portal Punch</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="mandatory_machine_punch" v-model="form.mandatory_machine_punch" type="checkbox" class="rounded" />
                        <label for="mandatory_machine_punch" class="text-sm text-gray-700">Mandatory Machine Punch</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="label">Break Duration (minutes)</label>
                        <input v-model="form.break_duration_minutes" type="number" class="input-field" />
                        <p v-if="form.errors.break_duration_minutes" class="error">{{ form.errors.break_duration_minutes }}</p>
                    </div>
                    <div>
                        <label class="label">Max Break (minutes)</label>
                        <input v-model="form.max_break_minutes" type="number" class="input-field" />
                        <p v-if="form.errors.max_break_minutes" class="error">{{ form.errors.max_break_minutes }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="track_idle_time" v-model="form.track_idle_time" type="checkbox" class="rounded" />
                        <label for="track_idle_time" class="text-sm text-gray-700">Track Idle Time</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="flex items-center gap-2">
                        <input id="overtime_eligible" v-model="form.overtime_eligible" type="checkbox" class="rounded" />
                        <label for="overtime_eligible" class="text-sm text-gray-700">Overtime Eligible</label>
                    </div>
                    <div>
                        <label class="label">Overtime Multiplier</label>
                        <input v-model="form.overtime_multiplier" type="number" step="0.1" class="input-field" />
                        <p v-if="form.errors.overtime_multiplier" class="error">{{ form.errors.overtime_multiplier }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="deduct_late_arrival" v-model="form.deduct_late_arrival" type="checkbox" class="rounded" />
                        <label for="deduct_late_arrival" class="text-sm text-gray-700">Deduct Late Arrival</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="label">Half Day Threshold (hours)</label>
                        <input v-model="form.half_day_threshold_hours" type="number" step="0.1" class="input-field" />
                        <p v-if="form.errors.half_day_threshold_hours" class="error">{{ form.errors.half_day_threshold_hours }}</p>
                    </div>
                    <div>
                        <label class="label">Full Day Threshold (hours)</label>
                        <input v-model="form.full_day_threshold_hours" type="number" step="0.1" class="input-field" />
                        <p v-if="form.errors.full_day_threshold_hours" class="error">{{ form.errors.full_day_threshold_hours }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="deduct_early_departure" v-model="form.deduct_early_departure" type="checkbox" class="rounded" />
                        <label for="deduct_early_departure" class="text-sm text-gray-700">Deduct Early Departure</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="flex items-center gap-2">
                        <input id="allow_negative_leave_balance" v-model="form.allow_negative_leave_balance" type="checkbox" class="rounded" />
                        <label for="allow_negative_leave_balance" class="text-sm text-gray-700">Allow Negative Leave Balance</label>
                    </div>
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
                    <Link :href="route('admin.attendance-policies.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create Policy' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
