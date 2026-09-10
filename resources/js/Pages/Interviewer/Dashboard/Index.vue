<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import StatCard from '@/Components/Common/StatCard.vue'
import { Link } from '@inertiajs/vue3'

defineProps({ stats: Object, pending_interviews: Array, active_interviews: Array })
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">My Dashboard</h1>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard title="Pending" :value="stats.pending" icon="⏳" color="yellow" />
                <StatCard title="In Progress" :value="stats.in_progress" icon="🎙️" color="blue" />
                <StatCard title="Done Today" :value="stats.completed_today" icon="✅" color="green" />
                <StatCard title="Total Completed" :value="stats.total_completed" icon="📊" color="indigo" />
            </div>

            <!-- Pending Interviews -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Pending Interviews ({{ pending_interviews.length }})</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="p in pending_interviews" :key="p.id"
                        class="px-5 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-900">{{ p.candidate_name }}</p>
                                <span v-if="p.is_hr_round"
                                    class="text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">HR
                                    Round</span>
                                <span v-if="p.requires_salary"
                                    class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">💰 Salary
                                    Required</span>
                                <span v-else-if="p.salary_post_ops"
                                    class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">Salary
                                    Post-OPS</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-0.5">{{ p.position }} · {{ p.profile }} · {{ p.round_name
                                }}</p>
                        </div>
                        <Link
                            :href="route('interviewer.interviews.show', { candidate: p.candidate_id, round: p.round_id })"
                            class="btn-primary text-sm flex-shrink-0">
                            Start Interview
                        </Link>
                    </div>
                    <p v-if="!pending_interviews.length" class="px-5 py-8 text-center text-gray-400 text-sm">No pending
                        interviews.</p>
                </div>
            </div>

            <!-- In Progress -->
            <div v-if="active_interviews.length"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">In Progress ({{ active_interviews.length }})</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="p in active_interviews" :key="p.id"
                        class="px-5 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <p class="font-medium text-gray-900">{{ p.candidate_name }}</p>
                            <p class="text-sm text-gray-500">{{ p.position }} · {{ p.round_name }} · Started {{
                                p.started_at }}</p>
                        </div>
                        <Link
                            :href="route('interviewer.interviews.show', { candidate: p.candidate_id, round: p.round_id })"
                            class="btn-secondary text-sm">Continue</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>