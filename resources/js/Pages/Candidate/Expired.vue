<script setup>
// Candidate/Expired.vue
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'
defineProps({ message: String })
</script>

<template>
    <GuestLayout>

        <Head title="QR Code Expired – HRMS" />
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center px-4">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-10">
                <div class="text-6xl mb-4">⏰</div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">QR Code Invalid</h1>
                <p class="text-gray-500">{{ message ?? 'This QR code is no longer valid. Please contact the HR team for assistance.' }}</p>
            </div>
        </div>
    </GuestLayout>
</template>