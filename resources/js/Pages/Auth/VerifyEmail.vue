<script setup>
// VerifyEmail.vue
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'
defineProps({ status: String })
const form = useForm({})
const submit = () => form.post(route('verification.send'))
const handleLogout = () => useForm({}).post(route('logout'))
</script>

<template>
    <GuestLayout>

        <Head title="Verify Email — HRMS" />
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-8 shadow-xl rounded-2xl border border-gray-100 text-center">
                <div class="text-5xl mb-4">📧</div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Verify your email</h2>
                <p class="text-sm text-gray-500 mb-6">
                    We sent a verification link to your email address. Please check your inbox.
                </p>
                <div v-if="status === 'verification-link-sent'"
                    class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    A new verification link has been sent.
                </div>
                <div class="flex flex-col gap-3">
                    <button @click="submit" :disabled="form.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition disabled:opacity-60 text-sm">
                        {{ form.processing ? 'Sending...' : 'Resend Verification Email' }}
                    </button>
                    <button @click="handleLogout" class="text-sm text-gray-500 hover:text-gray-700">Sign out</button>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>