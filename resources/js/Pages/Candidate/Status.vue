<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    candidate: Object,
    progress_history: Array,
    status_labels: Object,
    final_labels: Object,
})

const statusColor = {
    new: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    round_completed: 'bg-purple-100 text-purple-700',
    all_rounds_cleared: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-600',
}

const finalColor = {
    pending: 'bg-gray-100 text-gray-600',
    selected: 'bg-emerald-100 text-emerald-700',
    not_selected: 'bg-red-100 text-red-600',
}
</script>

<template>
    <GuestLayout>

        <Head :title="`Status – ${candidate.name}`" />
        <div class="sm:mx-auto sm:w-full sm:max-w-xl px-4">
            <!-- Header -->
            <div class="text-center mb-6">
                <div
                    class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-indigo-600 text-white text-2xl mb-3">
                    ⚡</div>
                <h1 class="text-2xl font-bold text-gray-900">Interview Status</h1>
                <p class="text-sm text-gray-500 mt-1">{{ candidate.name }}</p>
            </div>

            <!-- Candidate Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-5">
                <div class="bg-indigo-600 px-6 py-4 text-white">
                    <p class="font-bold text-lg">{{ candidate.name }}</p>
                    <p class="text-indigo-200 text-sm">{{ candidate.position }} · {{ candidate.branch }}</p>
                    <p class="text-indigo-200 text-xs mt-1">{{ candidate.profile }} · Registered {{
                        candidate.registered_at }}</p>
                </div>

                <div class="p-6 grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <p class="text-xs text-gray-500 mb-1">Current Status</p>
                        <span
                            :class="['inline-block px-3 py-1 rounded-full text-xs font-semibold', statusColor[candidate.current_status] ?? 'bg-gray-100 text-gray-600']">
                            {{ status_labels[candidate.current_status] ?? candidate.current_status }}
                        </span>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <p class="text-xs text-gray-500 mb-1">Final Decision</p>
                        <span
                            :class="['inline-block px-3 py-1 rounded-full text-xs font-semibold', finalColor[candidate.final_status] ?? 'bg-gray-100 text-gray-600']">
                            {{ final_labels[candidate.final_status] ?? candidate.final_status }}
                        </span>
                    </div>
                    <div v-if="candidate.average_rating" class="text-center p-3 bg-yellow-50 rounded-xl col-span-2">
                        <p class="text-xs text-gray-500 mb-1">Average Rating</p>
                        <p class="font-bold text-yellow-600 text-xl">{{ candidate.average_rating }} ★</p>
                    </div>
                </div>

                <!-- Selected Message -->
                <div v-if="candidate.final_status === 'selected'"
                    class="mx-6 mb-6 bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                    <p class="text-2xl mb-2">🎊</p>
                    <p class="font-bold text-emerald-800">Congratulations! You've been selected.</p>
                    <p class="text-emerald-600 text-sm mt-1">Our HR team will contact you with the offer details.</p>
                </div>

                <!-- Rejected Message -->
                <div v-if="candidate.final_status === 'not_selected'"
                    class="mx-6 mb-6 bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                    <p class="text-2xl mb-2">🙏</p>
                    <p class="font-bold text-red-800">Thank you for your interest.</p>
                    <p class="text-red-600 text-sm mt-1">We appreciate your time. Better opportunities may come your
                        way.</p>
                </div>
            </div>

            <!-- Round History -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Interview Rounds</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="(p, i) in progress_history" :key="i" class="px-6 py-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div :class="['h-8 w-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0',
                                    p.status === 'completed' ? 'bg-green-100 text-green-700' :
                                        p.status === 'rejected' ? 'bg-red-100   text-red-600' : 'bg-yellow-100 text-yellow-700']">
                                    {{ i + 1 }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ p.round }}</p>
                                    <p class="text-xs text-gray-400">{{ p.date ?? 'Scheduled' }}</p>
                                </div>
                            </div>
                            <span :class="['text-xs font-medium px-2.5 py-1 rounded-full',
                                p.status === 'completed' ? 'bg-green-100 text-green-700' :
                                    p.status === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700']">
                                {{ p.status.charAt(0).toUpperCase() + p.status.slice(1) }}
                            </span>
                        </div>
                        <div v-if="p.status === 'completed' && p.rating" class="ml-11">
                            <div class="flex gap-1">
                                <span v-for="n in 5" :key="n"
                                    :class="n <= p.rating ? 'text-yellow-400' : 'text-gray-200'"
                                    class="text-lg">★</span>
                            </div>
                        </div>
                        <div v-if="p.status === 'rejected' && p.feedback" class="ml-11 text-xs text-gray-500 mt-1">
                            {{ p.feedback }}
                        </div>
                    </div>
                    <p v-if="!progress_history?.length" class="px-6 py-8 text-center text-gray-400 text-sm">
                        No interview rounds completed yet.
                    </p>
                </div>
            </div>

            <p class="text-center text-xs text-gray-400 mt-6 px-4">
                For any queries please contact your recruiter or visit the branch.
            </p>
        </div>
    </GuestLayout>
</template>