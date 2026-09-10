<script setup>
// Admin/QRCodes/Index.vue
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ qrCodes: Object, branches: Array, filters: Object })
const branchId = ref(props.filters?.branch_id ?? '')
const status = ref(props.filters?.status ?? '')

watch([branchId, status], () => {
    router.get(route('admin.qrcodes.index'), { branch_id: branchId.value, status: status.value },
        { preserveState: true, replace: true })
})

const toggle = (qr) => router.post(route('admin.qrcodes.toggle', qr.id))
const del = (qr) => { if (confirm('Delete QR code?')) router.delete(route('admin.qrcodes.destroy', qr.id)) }
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">QR Codes</h1>
                <Link :href="route('admin.qrcodes.create')" class="btn-primary">+ Generate QR Code</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
                <select v-model="branchId" class="input-field w-48">
                    <option value="">All Branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="status" class="input-field w-36">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="qr in qrCodes.data" :key="qr.id"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900">{{ qr.label }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ qr.branch }} · {{ qr.form }}</p>
                        </div>
                        <span
                            :class="qr.is_active && !qr.is_expired ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                            class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0">
                            {{ qr.is_expired ? 'Expired' : qr.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                        <p>🔗 <span class="font-mono text-gray-700">{{ qr.uuid?.slice(0, 18) }}...</span></p>
                        <p>👥 Used {{ qr.usage_count }} times</p>
                        <p v-if="qr.expiry">⏰ Expires {{ qr.expiry }}</p>
                    </div>
                    <div class="flex gap-2 pt-1 border-t border-gray-50">
                        <Link :href="route('admin.qrcodes.show', qr.id)"
                            class="text-indigo-600 text-xs hover:underline">View QR</Link>
                        <button @click="toggle(qr)" class="text-xs"
                            :class="qr.is_active ? 'text-yellow-600' : 'text-green-600'">
                            {{ qr.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button @click="del(qr)" class="text-red-400 text-xs hover:text-red-600 ml-auto">Delete</button>
                    </div>
                </div>
                <p v-if="!qrCodes.data?.length" class="col-span-3 text-center text-gray-400 py-10">No QR codes generated
                    yet.</p>
            </div>
            <Pagination :links="qrCodes.links" />
        </div>
    </AppLayout>
</template>