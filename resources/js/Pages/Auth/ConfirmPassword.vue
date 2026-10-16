<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AuthField from '@/Components/Auth/AuthField.vue'
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm({ password: '' })
const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() })
</script>

<template>
    <Head title="Confirm password — Airvincible HRMS" />

    <AuthLayout title="Confirm your password" subtitle="This area is protected. Enter your password to continue." icon="shield">
        <form @submit.prevent="submit" class="space-y-5">
            <AuthField v-model="form.password" label="Password" type="password" autocomplete="current-password"
                placeholder="Enter your password" :error="form.errors.password" required autofocus />

            <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-3">
                {{ form.processing ? 'Confirming…' : 'Confirm' }}
            </button>
        </form>
    </AuthLayout>
</template>