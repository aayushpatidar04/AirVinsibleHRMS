<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ components: Object, clients: Object, filters: Object })

const clientId = ref(props.filters?.client_id ?? '')
const type = ref(props.filters?.type ?? '')

watch([clientId, type], () => {
    router.get(route('hr.payroll.components'), {
        client_id: clientId.value,
        type: type.value,
    }, { preserveState: true, replace: true })
})

const form = useForm({
    client_id: '',
    name: '',
    code: '',
    type: 'earning',
    calculation_method: 'fixed',
    default_value: 0,
    percentage_base: '',
    is_statutory: false,
    is_active: true,
    formula: '',
})

const submit = () => {
    form.post(route('hr.payroll.components.store'), {
        onSuccess: () => window.location.reload(),
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Payroll Components</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="clientId" class="input-field w-48">
                    <option value="">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="type" class="input-field w-40">
                    <option value="">All Types</option>
                    <option value="earning">Earnings</option>
                    <option value="deduction">Deductions</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Name</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Code</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Type</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Method</th>
                            <th class="px-5 py-3 text-right font-medium text-gray-500">Default Value</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Statutory</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Client</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="c in components.data" :key="c.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ c.name }}</td>
                            <td class="px-5 py-4 text-center text-gray-600 font-mono">{{ c.code }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="c.type === 'earning' ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
                                    {{ c.type }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center text-gray-600">{{ c.calculation_method }}</td>
                            <td class="px-5 py-4 text-right text-gray-600">{{ parseFloat(c.default_value || 0).toFixed(2) }}</td>
                            <td class="px-5 py-4 text-center">{{ c.is_statutory ? 'Yes' : 'No' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ c.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ c.client?.name }}</td>
                        </tr>
                        <tr v-if="!components.data?.length">
                            <td colspan="8" class="px-5 py-10 text-center text-gray-400">No payroll components found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="components.last_page > 1" :links="components.links" :current-page="components.current_page" />

            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Create Payroll Component</h2>
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
                        <input v-model="form.name" type="text" class="input-field w-full" placeholder="e.g. Basic Salary" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span></label>
                        <input v-model="form.code" type="text" class="input-field w-full" placeholder="e.g. BASIC" />
                        <p v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                        <select v-model="form.type" class="input-field w-full">
                            <option value="earning">Earning</option>
                            <option value="deduction">Deduction</option>
                        </select>
                        <p v-if="form.errors.type" class="text-xs text-red-500 mt-1">{{ form.errors.type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Calculation Method</label>
                        <input v-model="form.calculation_method" type="text" class="input-field w-full" placeholder="e.g. fixed" />
                        <p v-if="form.errors.calculation_method" class="text-xs text-red-500 mt-1">{{ form.errors.calculation_method }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Value</label>
                        <input v-model="form.default_value" type="number" class="input-field w-full" min="0" step="0.01" />
                        <p v-if="form.errors.default_value" class="text-xs text-red-500 mt-1">{{ form.errors.default_value }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Percentage Base</label>
                        <input v-model="form.percentage_base" type="text" class="input-field w-full" placeholder="e.g. basic" />
                        <p v-if="form.errors.percentage_base" class="text-xs text-red-500 mt-1">{{ form.errors.percentage_base }}</p>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        <input type="checkbox" v-model="form.is_statutory" class="rounded" />
                        <label class="text-sm text-gray-700">Statutory Component</label>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        <input type="checkbox" v-model="form.is_active" class="rounded" />
                        <label class="text-sm text-gray-700">Is Active</label>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Formula</label>
                        <input v-model="form.formula" type="text" class="input-field w-full" placeholder="Calculation formula" />
                        <p v-if="form.errors.formula" class="text-xs text-red-500 mt-1">{{ form.errors.formula }}</p>
                    </div>
                    <div class="md:col-span-3 flex justify-end pt-2">
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            {{ form.processing ? 'Creating...' : 'Create Component' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
