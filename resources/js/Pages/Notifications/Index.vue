<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'

defineProps({ notifications: Object })

const markRead = (id) => {
    // best-effort: call named route if available; fallback to direct endpoint
    try {
        router.post(route('notifications.read', id))
    } catch (e) {
        router.post(`/notifications/${id}/read`)
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Notifications" />

        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold mb-4">Notifications</h1>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="divide-y divide-gray-50">
                    <div v-for="n in notifications.data" :key="n.id" class="p-4 flex items-start justify-between gap-4">
                        <div>
                            <p :class="['text-sm', n.read_at ? 'text-gray-600' : 'text-gray-800 font-medium']">{{ n.data?.message ?? n.type }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ n.created_at }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <button v-if="!n.read_at" @click="markRead(n.id)" class="text-sm text-indigo-600 hover:underline">Mark read</button>
                        </div>
                    </div>
                    <p v-if="!notifications.data?.length" class="p-8 text-center text-gray-400">No notifications.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
