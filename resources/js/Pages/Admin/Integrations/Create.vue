<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ clients: Array })

const integrationTypes = [
    'email', 'sms', 'biometric', 'payroll', 'accounting', 'attendance', 'leave', 'other'
]

const form = useForm({
    client_id: '',
    name: '',
    type: 'email',
    provider: '',
    config: {},
    api_key: '',
    api_secret: '',
    webhook_url: '',
    is_active: false,
})

const submit = () => form.post(route('admin.integrations.store'))
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.integrations.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">New Integration</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Client</label>
                        <select v-model="form.client_id" class="input-field">
                            <option value="">Global (all clients)</option>
                            <option v-for="c in props.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="form.errors.client_id" class="error">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <label class="label">Integration Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field" placeholder="SendGrid Email" />
                        <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Type <span class="text-red-500">*</span></label>
                        <select v-model="form.type" class="input-field">
                            <option v-for="t in integrationTypes" :key="t" :value="t">{{ t }}</option>
                        </select>
                        <p v-if="form.errors.type" class="error">{{ form.errors.type }}</p>
                    </div>
                    <div>
                        <label class="label">Provider <span class="text-red-500">*</span></label>
                        <input v-model="form.provider" type="text" class="input-field" placeholder="SendGrid" />
                        <p v-if="form.errors.provider" class="error">{{ form.errors.provider }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">API Key</label>
                        <input v-model="form.api_key" type="text" class="input-field" placeholder="API key..." />
                        <p v-if="form.errors.api_key" class="error">{{ form.errors.api_key }}</p>
                    </div>
                    <div>
                        <label class="label">API Secret</label>
                        <input v-model="form.api_secret" type="password" class="input-field" placeholder="API secret..." />
                        <p v-if="form.errors.api_secret" class="error">{{ form.errors.api_secret }}</p>
                    </div>
                </div>

                <div>
                    <label class="label">Webhook URL</label>
                    <input v-model="form.webhook_url" type="url" class="input-field" placeholder="https://example.com/webhook" />
                    <p v-if="form.errors.webhook_url" class="error">{{ form.errors.webhook_url }}</p>
                </div>

                <div>
                    <label class="label">Configuration (JSON)</label>
                    <textarea v-model="form.config" rows="4" class="input-field font-mono text-sm" placeholder='{"key": "value"}' />
                    <p v-if="form.errors.config" class="error">{{ form.errors.config }}</p>
                    <p class="text-xs text-gray-500 mt-1">Optional JSON configuration for the integration.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded" />
                    <label for="is_active" class="text-sm text-gray-700">Integration is active</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.integrations.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create Integration' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
