<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AuthField from '@/Components/Auth/AuthField.vue'
import { useForm, Head } from '@inertiajs/vue3'

const props = defineProps({ token: String, email: String })

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})
const submit = () => form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
})
</script>

<template>
    <Head title="Reset password — Airvincible HRMS" />

    <AuthLayout title="Choose a new password" subtitle="Your new password must be at least 8 characters." icon="key">
        <form @submit.prevent="submit" class="space-y-5">
            <AuthField v-model="form.email" label="Email address" type="email" autocomplete="username"
                :error="form.errors.email" required />
            <AuthField v-model="form.password" label="New password" type="password" autocomplete="new-password"
                placeholder="At least 8 characters" :error="form.errors.password" required autofocus />
            <AuthField v-model="form.password_confirmation" label="Confirm new password" type="password"
                autocomplete="new-password" placeholder="Re-enter your new password"
                :error="form.errors.password_confirmation" required />

            <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-3">
                {{ form.processing ? 'Saving…' : 'Reset password' }}
            </button>
        </form>
    </AuthLayout>
</template>