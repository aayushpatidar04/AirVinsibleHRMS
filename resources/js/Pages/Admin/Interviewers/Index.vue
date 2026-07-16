<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ interviewers: Object, branches: Array, filters: Object })

const search = ref(props.filters?.search ?? '')
const branchId = ref(props.filters?.branch_id ?? '')
const status = ref(props.filters?.status ?? '')

watch([search, branchId, status], () => {
    router.get(route('admin.interviewers.index'), {
        search: search.value, branch_id: branchId.value, status: status.value
    }, { preserveState: true, replace: true })
})
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Interviewers</h1>
                <Link :href="route('admin.interviewers.create')" class="btn-primary">+ New Interviewer</Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name, email, ID..." class="input-field w-64" />
                <select v-model="branchId" class="input-field w-44">
                    <option value="">All Branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="status" class="input-field w-36">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Interviewer</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Branch</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Total</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Pending</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Done</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="iv in interviewers.data" :key="iv.id" class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ iv.first_name }} {{ iv.last_name }}</p>
                                <p class="text-xs text-gray-400">{{ iv.email }}</p>
                                <p v-if="iv.employee_id" class="text-xs text-gray-400">{{ iv.employee_id }}</p>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ iv.branch?.name ?? '—' }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ iv.total_interviews }}</td>
                            <td class="px-5 py-4 text-center font-medium text-yellow-600">{{ iv.pending_count }}</td>
                            <td class="px-5 py-4 text-center font-medium text-green-600">{{ iv.completed_count }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="iv.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                    class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ iv.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-3">
                                <Link :href="route('admin.interviewers.show', iv.id)"
                                    class="text-indigo-600 hover:underline text-xs">View</Link>
                                <Link :href="route('admin.interviewers.edit', iv.id)"
                                    class="text-gray-500 hover:underline text-xs">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!interviewers.data?.length">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400">No interviewers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="interviewers.links" />
        </div>
    </AdminLayout>
</template>