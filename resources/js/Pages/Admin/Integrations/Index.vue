<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ integrations: Object, clients: Array, types: Array, filters: Object })

const search = ref(props.filters?.search ?? '')
const clientId = ref(props.filters?.client_id ?? '')
const type = ref(props.filters?.type ?? '')
const isActive = ref(props.filters?.is_active ?? '')

watch([search, clientId, type, isActive], () => {
    router.get(route('admin.integrations.index'), {
        search: search.value,
        client_id: clientId.value,
        type: type.value,
        is_active: isActive.value,
    }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Integrations</h1>
                <Link :href="route('admin.integrations.create')" class="btn-primary">+ New Integration</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name, provider..." class="input-field w-64" />
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in props.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="type" class="input-field w-32">
                    <option value="">All Types</option>
                    <option v-for="t in props.types" :key="t" :value="t">{{ t }}</option>
                </select>
                <select v-model="isActive" class="input-field w-32">
                    <option value="">All Status</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Name</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Type</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Provider</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="i in integrations.data" :key="i.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ i.name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ i.client?.name || 'Global' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ i.type }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ i.provider }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="i.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ i.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-3">
                                <Link :href="route('admin.integrations.show', i.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                <Link :href="route('admin.integrations.edit', i.id)" class="text-gray-600 hover:underline text-xs">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!integrations.data?.length">
                            <td colspan="6" class="px-5 py-10 text-center text-gray-400">No integrations found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="integrations.links" />
        </div>
    </AppLayout>
</template>
