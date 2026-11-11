<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({ shift: Object })

const deleteShift = () => {
    if (confirm('Delete this shift? This cannot be undone.'))
        router.delete(route('admin.shifts.destroy', props.shift.id))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.shifts.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ shift.name }}</h1>
                        <p class="text-sm text-gray-500">{{ shift.client?.name }} · {{ shift.start_time }} – {{ shift.end_time }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="shift.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                        class="text-xs font-semibold px-3 py-1 rounded-full">
                        {{ shift.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <Link :href="route('admin.shifts.edit', shift.id)" class="btn-secondary text-sm">Edit</Link>
                    <button @click="deleteShift" class="btn-danger text-sm">Delete</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Shift Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Client</dt>
                            <dd class="text-gray-800">{{ shift.client?.name }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Shift Code</dt>
                            <dd class="text-gray-800 font-mono">{{ shift.code }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Start Time</dt>
                            <dd class="text-gray-800">{{ shift.start_time }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">End Time</dt>
                            <dd class="text-gray-800">{{ shift.end_time }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Working Days</dt>
                            <dd class="text-gray-800">{{ shift.working_days?.join(', ') || '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Rotational</dt>
                            <dd class="text-gray-800">{{ shift.is_rotational ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Overnight</dt>
                            <dd class="text-gray-800">{{ shift.is_overnight ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Break Rules</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Break Start</dt>
                            <dd class="text-gray-800">{{ shift.break_start_time || '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Break End</dt>
                            <dd class="text-gray-800">{{ shift.break_end_time || '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Break Duration</dt>
                            <dd class="text-gray-800">{{ shift.break_duration_minutes }} minutes</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Grace Period</dt>
                            <dd class="text-gray-800">{{ shift.grace_period_minutes }} minutes</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
