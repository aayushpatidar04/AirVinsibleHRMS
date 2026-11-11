<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({ client: Object })
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">{{ client.name }}</h1>
                <div class="flex gap-2">
                    <Link :href="route('admin.clients.edit', client.id)" class="btn-secondary">Edit</Link>
                    <Link :href="route('admin.clients.index')" class="btn-secondary">Back</Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Client Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Code</dt>
                            <dd class="font-mono text-gray-900">{{ client.code }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Contact Name</dt>
                            <dd class="text-gray-900">{{ client.contact_name || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Email</dt>
                            <dd class="text-gray-900">{{ client.contact_email || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Phone</dt>
                            <dd class="text-gray-900">{{ client.contact_phone || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Address</dt>
                            <dd class="text-gray-900">{{ client.address || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">City, State</dt>
                            <dd class="text-gray-900">{{ [client.city, client.state].filter(Boolean).join(', ') || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Country</dt>
                            <dd class="text-gray-900">{{ client.country || '—' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                <span :class="client.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ client.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase">Employees</div>
                            <div class="text-2xl font-bold text-gray-900">{{ client.users_count }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="client.attendance_policies?.length || client.shifts?.length || client.leave_types?.length">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Configurations</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-if="client.attendance_policies?.length" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Attendance Policies</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li v-for="policy in client.attendance_policies" :key="policy.id">{{ policy.name }}</li>
                        </ul>
                    </div>
                    <div v-if="client.shifts?.length" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Shifts</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li v-for="shift in client.shifts" :key="shift.id">{{ shift.name }}</li>
                        </ul>
                    </div>
                    <div v-if="client.leave_types?.length" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Leave Types</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li v-for="type in client.leave_types" :key="type.id">{{ type.name }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
