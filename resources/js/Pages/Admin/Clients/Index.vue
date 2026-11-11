<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ clients: Object, filters: Object })

const search = ref(props.filters?.search ?? '')
const isActive = ref(props.filters?.is_active ?? '')

watch([search, isActive], () => {
    router.get(route('admin.clients.index'), { search: search.value, is_active: isActive.value }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
                <Link :href="route('admin.clients.create')" class="btn-primary">+ New Client</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name, code..." class="input-field w-64" />
                <select v-model="isActive" class="input-field w-40">
                    <option value="">All Status</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Code</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Contact</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Employees</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ client.name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ client.contact_email || 'No email' }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600 font-mono">{{ client.code }}</td>
                            <td class="px-5 py-4">
                                <div class="text-gray-600">{{ client.contact_name || '—' }}</div>
                                <div class="text-xs text-gray-400">{{ client.contact_phone || '—' }}</div>
                            </td>
                            <td class="px-5 py-4 text-center font-semibold text-indigo-600">{{ client.users_count }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="client.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ client.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-3">
                                <Link :href="route('admin.clients.show', client.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                <Link :href="route('admin.clients.edit', client.id)" class="text-gray-600 hover:underline text-xs">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!clients.data?.length">
                            <td colspan="6" class="px-5 py-10 text-center text-gray-400">No clients found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="clients.last_page > 1" :links="clients.links" :current-page="clients.current_page" />
        </div>
    </AppLayout>
</template>
