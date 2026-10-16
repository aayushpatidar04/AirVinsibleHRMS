<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm, Head } from '@inertiajs/vue3'

defineProps({ status: String })

const form = useForm({})
const logoutForm = useForm({})
const resend = () => form.post(route('verification.send'))
const logout = () => logoutForm.post(route('logout'))
</script>

<template>
    <Head title="Verify email — Airvincible HRMS" />

    <AuthLayout title="Check your inbox" subtitle="We sent a verification link to your email address. Open it to activate your account." icon="mail">
        <div v-if="status === 'verification-link-sent'" class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            A new verification link has been sent to your email address.
        </div>

        <div class="space-y-3">
            <button type="button" :disabled="form.processing" class="btn-primary w-full !py-3" @click="resend">
                {{ form.processing ? 'Sending…' : 'Resend verification email' }}
            </button>
            <button type="button" :disabled="logoutForm.processing" class="btn-secondary w-full !py-3" @click="logout">Sign out</button>
        </div>
    </AuthLayout>
</template>