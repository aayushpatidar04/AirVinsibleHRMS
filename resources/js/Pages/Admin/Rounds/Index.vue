<script setup>
// Rounds/Index.vue
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({ rounds: Array })
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Interview Rounds</h1>
                <Link :href="route('admin.rounds.create')" class="btn-primary">+ New Round</Link>
            </div>

            <div class="space-y-4">
                <div v-for="round in rounds" :key="round.id"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-start justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="h-10 w-10 rounded-xl bg-indigo-100 text-indigo-700 font-bold text-lg flex items-center justify-center flex-shrink-0">
                            {{ round.sequence }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-semibold text-gray-900">{{ round.name }}</h3>
                                <span v-if="round.is_hr_round"
                                    class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-medium">HR
                                    Round</span>
                                <span v-if="round.is_ops_round"
                                    class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">OPS
                                    Round</span>
                                <span v-if="round.is_mandatory"
                                    class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Mandatory</span>
                                <span v-if="!round.is_active"
                                    class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                            </div>
                            <p v-if="round.description" class="text-sm text-gray-500 mt-1">{{ round.description }}</p>
                            <div class="flex gap-4 mt-2 text-xs text-gray-500">
                                <span>{{ round.questions_count }} questions</span>
                                <span>{{ round.stats.total }} candidates</span>
                                <span class="text-green-600">{{ round.stats.completed }} completed</span>
                                <span class="text-red-500">{{ round.stats.rejected }} rejected</span>
                                <span v-if="round.stats.avg_rating > 0" class="text-yellow-600">★ {{
                                    round.stats.avg_rating }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 ml-4 flex-shrink-0">
                        <Link :href="route('admin.rounds.show', round.id)" class="btn-secondary text-xs">Manage</Link>
                        <Link :href="route('admin.rounds.edit', round.id)"
                            class="text-white text-xs px-2 btn-primary">Edit</Link>
                    </div>
                </div>
                <p v-if="!rounds?.length" class="text-center text-gray-400 py-10">No rounds created yet.</p>
            </div>
        </div>
    </AdminLayout>
</template>