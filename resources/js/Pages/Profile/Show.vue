<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import InterviewerLayout from '@/Layouts/InterviewerLayout.vue'
import { usePage, Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const userProp = defineProps({ user: Object, branch: Object })
const isAdmin = computed(() => page.props.auth?.roles?.includes('admin'))
const Layout = computed(() => isAdmin.value ? AdminLayout : InterviewerLayout)
</script>

<template>
    <component :is="Layout">
        <Head title="Profile" />

        <div class="max-w-2xl mx-auto space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">Profile</h1>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="font-semibold">{{ userProp.user.full_name ?? (userProp.user.first_name + ' ' + userProp.user.last_name) }}</p>
                <p class="text-sm text-gray-500">{{ userProp.user.email }}</p>
                <p class="text-xs text-gray-400 mt-2">Branch: {{ userProp.branch?.name ?? '—' }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-2">Contact</h2>
                <p class="text-sm text-gray-700">Phone: {{ userProp.user.phone ?? '—' }}</p>
                <p class="text-sm text-gray-700">Employee ID: {{ userProp.user.employee_id ?? '—' }}</p>
            </div>
        </div>
    </component>
</template>
