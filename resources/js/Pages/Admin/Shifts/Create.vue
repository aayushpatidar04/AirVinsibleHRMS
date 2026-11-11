<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ clients: Array })

const workingDaysOptions = [
    { value: 'monday', label: 'Monday' },
    { value: 'tuesday', label: 'Tuesday' },
    { value: 'wednesday', label: 'Wednesday' },
    { value: 'thursday', label: 'Thursday' },
    { value: 'friday', label: 'Friday' },
    { value: 'saturday', label: 'Saturday' },
    { value: 'sunday', label: 'Sunday' },
]

const form = useForm({
    client_id: '',
    name: '',
    code: '',
    start_time: '09:00',
    end_time: '18:00',
    break_start_time: '',
    break_end_time: '',
    break_duration_minutes: 30,
    grace_period_minutes: 15,
    is_rotational: false,
    is_overnight: false,
    working_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    is_active: true,
})

const submit = () => form.post(route('admin.shifts.store'))
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.shifts.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">New Shift</h1>
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
                        <label class="label">Shift Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field" placeholder="Day Shift" />
                        <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Shift Code <span class="text-red-500">*</span></label>
                        <input v-model="form.code" type="text" class="input-field uppercase" placeholder="DAY" maxlength="20" />
                        <p v-if="form.errors.code" class="error">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label class="label">Start Time <span class="text-red-500">*</span></label>
                        <input v-model="form.start_time" type="time" class="input-field" />
                        <p v-if="form.errors.start_time" class="error">{{ form.errors.start_time }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">End Time <span class="text-red-500">*</span></label>
                        <input v-model="form.end_time" type="time" class="input-field" />
                        <p v-if="form.errors.end_time" class="error">{{ form.errors.end_time }}</p>
                    </div>
                    <div>
                        <label class="label">Break Start Time</label>
                        <input v-model="form.break_start_time" type="time" class="input-field" />
                        <p v-if="form.errors.break_start_time" class="error">{{ form.errors.break_start_time }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Break End Time</label>
                        <input v-model="form.break_end_time" type="time" class="input-field" />
                        <p v-if="form.errors.break_end_time" class="error">{{ form.errors.break_end_time }}</p>
                    </div>
                    <div>
                        <label class="label">Break Duration (minutes)</label>
                        <input v-model="form.break_duration_minutes" type="number" class="input-field" />
                        <p v-if="form.errors.break_duration_minutes" class="error">{{ form.errors.break_duration_minutes }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Grace Period (minutes)</label>
                        <input v-model="form.grace_period_minutes" type="number" class="input-field" />
                        <p v-if="form.errors.grace_period_minutes" class="error">{{ form.errors.grace_period_minutes }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="is_rotational" v-model="form.is_rotational" type="checkbox" class="rounded" />
                        <label for="is_rotational" class="text-sm text-gray-700">Rotational Shift</label>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_overnight" v-model="form.is_overnight" type="checkbox" class="rounded" />
                    <label for="is_overnight" class="text-sm text-gray-700">Overnight Shift</label>
                </div>

                <div>
                    <label class="label">Working Days</label>
                    <div class="flex flex-wrap gap-2">
                        <label v-for="d in workingDaysOptions" :key="d.value" class="flex items-center gap-1 cursor-pointer whitespace-nowrap hover:bg-gray-50 rounded px-2 py-1">
                            <input type="checkbox" :value="d.value" v-model="form.working_days" class="rounded" />
                            <span class="text-sm text-gray-700">{{ d.label }}</span>
                        </label>
                    </div>
                    <p v-if="form.errors.working_days" class="error mt-1">{{ form.errors.working_days }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded" />
                    <label for="is_active" class="text-sm text-gray-700">Shift is active</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.shifts.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create Shift' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
