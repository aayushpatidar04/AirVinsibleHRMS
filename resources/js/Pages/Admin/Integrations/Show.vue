<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({ integration: Object })

const deleteIntegration = () => {
    if (confirm('Delete this integration? This cannot be undone.'))
        router.delete(route('admin.integrations.destroy', props.integration.id))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.integrations.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ integration.name }}</h1>
                        <p class="text-sm text-gray-500">{{ integration.type }} · {{ integration.provider }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="integration.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                        class="text-xs font-semibold px-3 py-1 rounded-full">
                        {{ integration.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <Link :href="route('admin.integrations.edit', integration.id)" class="btn-secondary text-sm">Edit</Link>
                    <button @click="deleteIntegration" class="btn-danger text-sm">Delete</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Integration Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Client</dt>
                            <dd class="text-gray-800">{{ integration.client?.name || 'Global' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Type</dt>
                            <dd class="text-gray-800">{{ integration.type }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Provider</dt>
                            <dd class="text-gray-800">{{ integration.provider }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Webhook URL</dt>
                            <dd class="text-gray-800 break-all">{{ integration.webhook_url || '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Last Sync</dt>
                            <dd class="text-gray-800">{{ integration.last_sync_at || 'Never' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">Status Message</dt>
                            <dd class="text-gray-800">{{ integration.status_message || '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Configuration</h2>
                    <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <pre class="text-gray-700">{{ integration.config ? JSON.stringify(integration.config, null, 2) : '{}' }}</pre>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Credentials</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">API Key</dt>
                            <dd class="text-gray-800 break-all">{{ integration.api_key || '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-32 text-gray-500 font-medium">API Secret</dt>
                            <dd class="text-gray-800 break-all">{{ integration.api_secret ? '••••••••' : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
