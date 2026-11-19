<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    user: Object,
    todayPunches: Array,
    currentStatus: String,
    canPunchIn: Boolean,
    canPunchOut: Boolean,
    canBreakStart: Boolean,
    canBreakEnd: Boolean,
    flash: Object,
})

const processing = ref(false)

const punchTypes = [
    { value: 'in', label: 'Punch In', disabled: !props.canPunchIn, base: 'bg-green-600 text-white border-green-600' },
    { value: 'out', label: 'Punch Out', disabled: !props.canPunchOut, base: 'bg-red-600 text-white border-red-600' },
    { value: 'break_start', label: 'Start Break', disabled: !props.canBreakStart, base: 'bg-yellow-600 text-white border-yellow-600' },
    { value: 'break_end', label: 'End Break', disabled: !props.canBreakEnd, base: 'bg-indigo-600 text-white border-indigo-600' },
]

const recordPunch = (type) => {
    if (type.disabled || processing.value) return

    processing.value = true

    router.post(route('employee.punch.store'), {
        punch_type: type.value,
        source: 'portal',
        notes: null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            processing.value = false
        },
        onError: () => {
            processing.value = false
        },
    })
}

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
                <h1 class="text-2xl font-bold text-gray-900">Punch In / Out</h1>
            </div>

            <!-- Flash Message -->
            <div v-if="props.flash.success" class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm">
                {{ props.flash.success }}
            </div>

            <!-- Current Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase">Your Status Today</p>
                        <p class="text-2xl font-bold text-gray-900">{{ statusLabel[currentStatus] }}</p>
                    </div>
                    <span :class="statusColor[currentStatus]" class="text-xs font-semibold px-3 py-1 rounded-full">
                        {{ statusLabel[currentStatus] }}
                    </span>
                </div>

                <!-- Punch Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <button
                        v-for="pt in punchTypes"
                        :key="pt.value"
                        type="button"
                        @click="recordPunch(pt)"
                        :disabled="pt.disabled || processing"
                        :class="
                            'w-full py-4 rounded-lg font-medium border-2 transition-all duration-200 flex flex-col items-center justify-center gap-2 ' +
                            (processing ? 'opacity-60 cursor-not-allowed' : pt.base + ' hover:bg-gray-100 text-gray-600 border-gray-300')
                        "
                    >
                        <span class="text-lg font-bold">{{ pt.label }}</span>
                        <span v-if="pt.disabled" class="text-xs opacity-50">Not available</span>
                    </button>
                </div>
            </div>

            <!-- Today's Punch History -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Today's Punches</h2>

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
        </div>
    </AppLayout>
</template>
