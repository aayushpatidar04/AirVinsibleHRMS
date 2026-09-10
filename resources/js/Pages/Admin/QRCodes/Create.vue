<script setup>
// Admin/QRCodes/Create.vue
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ branches: Array, forms: Array })

const form = useForm({
    label: '',
    branch_id: '',
    form_id: '',
    expiry_date: '',
})

const submit = () => form.post(route('admin.qrcodes.store'))
</script>

<template>
    <AppLayout>
        <div class="max-w-lg mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.qrcodes.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">Generate QR Code</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div>
                    <label class="label">Label / Name <span class="text-red-500">*</span></label>
                    <input v-model="form.label" type="text" class="input-field"
                        placeholder="e.g. Mumbai Walk-In – Jan 2025" />
                    <p v-if="form.errors.label" class="error">{{ form.errors.label }}</p>
                </div>
                <div>
                    <label class="label">Branch <span class="text-red-500">*</span></label>
                    <select v-model="form.branch_id" class="input-field">
                        <option value="">Select Branch</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <p v-if="form.errors.branch_id" class="error">{{ form.errors.branch_id }}</p>
                </div>
                <div>
                    <label class="label">Registration Form <span class="text-red-500">*</span></label>
                    <select v-model="form.form_id" class="input-field">
                        <option value="">Select Form</option>
                        <option v-for="f in forms" :key="f.id" :value="f.id">{{ f.name }}</option>
                    </select>
                    <p v-if="form.errors.form_id" class="error">{{ form.errors.form_id }}</p>
                </div>
                <div>
                    <label class="label">Expiry Date <span class="text-gray-400 text-xs">(optional — leave blank for no
                            expiry)</span></label>
                    <input v-model="form.expiry_date" type="datetime-local" class="input-field" />
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.qrcodes.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Generating...' : '🔲 Generate QR Code' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>