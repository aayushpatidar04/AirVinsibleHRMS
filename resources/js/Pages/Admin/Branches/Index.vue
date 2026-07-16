<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ branches: Object, filters: Object })

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

watch([search, status], () => {
    router.get(route('admin.branches.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true })
})
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Branches</h1>
                <Link :href="route('admin.branches.create')" class="btn-primary">+ New Branch</Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name, city..." class="input-field w-64" />
                <select v-model="status" class="input-field w-40">
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
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Branch</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">City</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Phone / Email</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Candidates</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Users</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="branch in branches.data" :key="branch.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ branch.name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ branch.code }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ branch.city }}, {{ branch.state }}</td>
                            <td class="px-5 py-4">
                                <div class="text-gray-600">{{ branch.phone }}</div>
                                <div class="text-xs text-gray-400">{{ branch.email }}</div>
                            </td>
                            <td class="px-5 py-4 text-center font-semibold text-indigo-600">{{ branch.candidates_count
                                }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ branch.users_count }}</td>
                            <td class="px-5 py-4 text-center">
                                <span
                                    :class="branch.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                    class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ branch.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-3">
                                <Link :href="route('admin.branches.show', branch.id)"
                                    class="text-indigo-600 hover:underline text-xs">View</Link>
                                <Link :href="route('admin.branches.edit', branch.id)"
                                    class="text-gray-600 hover:underline text-xs">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!branches.data?.length">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400">No branches found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="branches.links" />
        </div>
    </AdminLayout>
</template>