<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({ branch: Object, stats: Object, interviewers: Array, recent_candidates: Array })

const deleteBranch = () => {
    if (confirm('Delete this branch? This cannot be undone.'))
        router.delete(route('admin.branches.destroy', props.branch.id))
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.branches.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ branch.name }}</h1>
                        <p class="text-sm text-gray-500">{{ branch.code }} · {{ branch.city }}, {{ branch.state }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="branch.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                        class="text-xs font-semibold px-3 py-1 rounded-full">
                        {{ branch.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <Link :href="route('admin.branches.edit', branch.id)" class="btn-secondary text-sm">Edit</Link>
                    <button @click="deleteBranch" class="btn-danger text-sm">Delete</button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600">{{ stats.total_candidates }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Candidates</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ stats.active_candidates }}</p>
                    <p class="text-xs text-gray-500 mt-1">Active</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ stats.selected }}</p>
                    <p class="text-xs text-gray-500 mt-1">Selected</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                    <p class="text-2xl font-bold text-teal-600">{{ stats.total_interviewers }}</p>
                    <p class="text-xs text-gray-500 mt-1">Interviewers</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Branch Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Branch Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-28 text-gray-500 font-medium">Address</dt>
                            <dd class="text-gray-800">{{ branch.address }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-28 text-gray-500 font-medium">City</dt>
                            <dd class="text-gray-800">{{ branch.city }}, {{ branch.state }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-28 text-gray-500 font-medium">Postal Code</dt>
                            <dd class="text-gray-800">{{ branch.postal_code }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-28 text-gray-500 font-medium">Phone</dt>
                            <dd class="text-gray-800">{{ branch.phone }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-28 text-gray-500 font-medium">Email</dt>
                            <dd class="text-gray-800">{{ branch.email }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Interviewers -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-gray-800">Interviewers</h2>
                        <Link :href="route('admin.interviewers.create')"
                            class="text-xs text-indigo-600 hover:underline">+ Add</Link>
                    </div>
                    <div class="space-y-2">
                        <div v-for="iv in interviewers" :key="iv.id"
                            class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ iv.name }}</p>
                                <p class="text-xs text-gray-400">{{ iv.email }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">{{
                                    iv.pending }} pending</span>
                            </div>
                        </div>
                        <p v-if="!interviewers?.length" class="text-sm text-gray-400 text-center py-4">No interviewers
                            assigned.</p>
                    </div>
                </div>
            </div>

            <!-- Recent Candidates -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Recent Candidates</h2>
                    <Link :href="route('admin.candidates.index', { branch_id: branch.id })"
                        class="text-xs text-indigo-600 hover:underline">View all →</Link>
                </div>
                <div class="space-y-2">
                    <div v-for="c in recent_candidates" :key="c.id"
                        class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                        <div>
                            <Link :href="route('admin.candidates.show', c.id)"
                                class="text-sm font-medium text-indigo-600 hover:underline">{{ c.name }}</Link>
                            <p class="text-xs text-gray-400">{{ c.position }} · {{ c.profile }}</p>
                        </div>
                        <StatusBadge :status="c.status" />
                    </div>
                    <p v-if="!recent_candidates?.length" class="text-sm text-gray-400 text-center py-4">No candidates
                        yet.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>