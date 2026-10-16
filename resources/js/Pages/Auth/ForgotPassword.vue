<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AuthField from '@/Components/Auth/AuthField.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'

defineProps({ status: String })

const form = useForm({ email: '' })
const submit = () => form.post(route('password.email'))
</script>

<template>
    <Head title="Forgot password — Airvincible HRMS" />

    <AuthLayout title="Forgot your password?" subtitle="Enter your email and we will send you a link to choose a new one." icon="key">
        <div v-if="status" class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-5">
            <AuthField v-model="form.email" label="Email address" type="email" autocomplete="email"
                placeholder="you@company.com" :error="form.errors.email" required autofocus />

            <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-3">
                {{ form.processing ? 'Sending…' : 'Send reset link' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm">
            <Link :href="route('login')" class="font-semibold text-indigo-600 hover:text-indigo-800">Back to sign in</Link>
        </p>
    </AuthLayout>
</template>