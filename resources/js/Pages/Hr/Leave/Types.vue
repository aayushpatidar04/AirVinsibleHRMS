<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ types: Object, clients: Object, filters: Object })

const clientId = ref(props.filters?.client_id ?? '')

watch(clientId, () => {
    router.get(route('hr.leave.types'), { client_id: clientId.value }, { preserveState: true, replace: true })
})

const form = useForm({
    client_id: '',
    name: '',
    code: '',
    description: '',
    is_paid: true,
    allow_half_day: false,
    allow_during_probation: true,
    annual_entitlement: 0,
    monthly_entitlement: 0,
    accrual_frequency: 'monthly',
    max_consecutive_days: 3,
    notice_period_days: 1,
    allow_carry_forward: false,
    max_carry_forward_days: 0,
    allow_encashment: false,
    allow_negative_balance: false,
    is_active: true,
})

const submit = () => {
    form.post(route('hr.leave.types.store'), {
        onSuccess: () => window.location.reload(),
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Leave Types</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Name</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Code</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Paid</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Half Day</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Annual Entitlement</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="t in types.data" :key="t.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ t.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600 font-mono">{{ t.code }}</td>
                            <td class="px-5 py-4 text-center">{{ t.is_paid ? 'Yes' : 'No' }}</td>
                            <td class="px-5 py-4 text-center">{{ t.allow_half_day ? 'Yes' : 'No' }}</td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ t.annual_entitlement }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="t.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ t.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ t.client?.name }}</td>
                        </tr>
                        <tr v-if="!types.data?.length">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400">No leave types found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="types.last_page > 1" :links="types.links" :current-page="types.current_page" />

            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Create Leave Type</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                        <select v-model="form.client_id" class="input-field w-full">
                            <option value="">Select client</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="form.errors.client_id" class="text-xs text-red-500 mt-1">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field w-full" placeholder="e.g. Casual Leave" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span></label>
                        <input v-model="form.code" type="text" class="input-field w-full" placeholder="e.g. CL" />
                        <p v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{ form.errors.code }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <input v-model="form.description" type="text" class="input-field w-full" />
                        <p v-if="form.errors.description" class="text-xs text-red-500 mt-1">{{ form.errors.description }}</p>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        <input type="checkbox" v-model="form.is_paid" class="rounded" />
                        <label class="text-sm text-gray-700">Paid Leave</label>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        <input type="checkbox" v-model="form.allow_half_day" class="rounded" />
                        <label class="text-sm text-gray-700">Allow Half Day</label>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        <input type="checkbox" v-model="form.allow_during_probation" class="rounded" />
                        <label class="text-sm text-gray-700">Allow During Probation</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Annual Entitlement</label>
                        <input v-model="form.annual_entitlement" type="number" class="input-field w-full" min="0" />
                        <p v-if="form.errors.annual_entitlement" class="text-xs text-red-500 mt-1">{{ form.errors.annual_entitlement }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Consecutive Days</label>
                        <input v-model="form.max_consecutive_days" type="number" class="input-field w-full" min="0" />
                        <p v-if="form.errors.max_consecutive_days" class="text-xs text-red-500 mt-1">{{ form.errors.max_consecutive_days }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notice Period (Days)</label>
                        <input v-model="form.notice_period_days" type="number" class="input-field w-full" min="0" />
                        <p v-if="form.errors.notice_period_days" class="text-xs text-red-500 mt-1">{{ form.errors.notice_period_days }}</p>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        <input type="checkbox" v-model="form.allow_carry_forward" class="rounded" />
                        <label class="text-sm text-gray-700">Allow Carry Forward</label>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        <input type="checkbox" v-model="form.is_active" class="rounded" />
                        <label class="text-sm text-gray-700">Is Active</label>
                    </div>
                    <div class="md:col-span-3 flex justify-end pt-2">
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            {{ form.processing ? 'Creating...' : 'Create Leave Type' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
