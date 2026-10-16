<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AuthField from '@/Components/Auth/AuthField.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' })
const submit = () => form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
})
</script>

<template>
    <Head title="Create account — Airvincible HRMS" />

    <AuthLayout title="Create your account" subtitle="Enter your details to get started." icon="user" aside>
        <form @submit.prevent="submit" class="space-y-5">
            <AuthField v-model="form.name" label="Full name" autocomplete="name" placeholder="Your full name"
                :error="form.errors.name" required autofocus />
            <AuthField v-model="form.email" label="Email address" type="email" autocomplete="username"
                placeholder="you@company.com" :error="form.errors.email" required />
            <AuthField v-model="form.password" label="Password" type="password" autocomplete="new-password"
                placeholder="At least 8 characters" :error="form.errors.password" required />
            <AuthField v-model="form.password_confirmation" label="Confirm password" type="password"
                autocomplete="new-password" placeholder="Re-enter your password"
                :error="form.errors.password_confirmation" required />

            <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-3">
                {{ form.processing ? 'Creating account…' : 'Create account' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-soft">
            Already have an account?
            <Link :href="route('login')" class="font-semibold text-indigo-600 hover:text-indigo-800">Sign in</Link>
        </p>
    </AuthLayout>
</template>