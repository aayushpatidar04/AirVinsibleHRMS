<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ policies: Object, clients: Array, leaveTypes: Array, filters: Object })

const search = ref(props.filters?.search ?? '')
const clientId = ref(props.filters?.client_id ?? '')
const isActive = ref(props.filters?.is_active ?? '')

watch([search, clientId, isActive], () => {
    router.get(route('admin.leave-policies.index'), {
        search: search.value,
        client_id: clientId.value,
        is_active: isActive.value,
    }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Leave Policies</h1>
                <Link :href="route('admin.leave-policies.create')" class="btn-primary">+ New Policy</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search policy..." class="input-field w-64" />
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in props.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
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
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Leave Type</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Employee Category</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Annual Entitlement</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Carry Forward</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Encashment</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in policies.data" :key="p.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ p.leaveType?.name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ p.client?.name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ p.employee_category }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ p.annual_entitlement }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="p.allow_carry_forward ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ p.allow_carry_forward ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span :class="p.allow_encashment ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ p.allow_encashment ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ p.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-3">
                                <Link :href="route('admin.leave-policies.show', p.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                <Link :href="route('admin.leave-policies.edit', p.id)" class="text-gray-600 hover:underline text-xs">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!policies.data?.length">
                            <td colspan="8" class="px-5 py-10 text-center text-gray-400">No leave policies found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="policies.links" />
        </div>
    </AppLayout>
</template>
