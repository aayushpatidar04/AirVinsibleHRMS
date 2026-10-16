<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AuthField from '@/Components/Auth/AuthField.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'

defineProps({ canResetPassword: Boolean, status: String })

const form = useForm({ email: '', password: '', remember: false })
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') })
</script>

<template>
    <Head title="Sign in — Airvincible HRMS" />

    <AuthLayout title="Sign in to your account" subtitle="Use your work email to open the dashboard." icon="lock" aside>
        <div v-if="status" class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-5">
            <AuthField v-model="form.email" label="Email address" type="email" autocomplete="email"
                placeholder="you@company.com" :error="form.errors.email" required autofocus />

            <AuthField v-model="form.password" label="Password" type="password" autocomplete="current-password"
                placeholder="Enter your password" :error="form.errors.password" required>
                <template #hint>
                    <Link v-if="canResetPassword" :href="route('password.request')"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Forgot password?</Link>
                </template>
            </AuthField>

            <label class="flex cursor-pointer items-center gap-2">
                <input v-model="form.remember" type="checkbox" class="rounded border-indigo-200 text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-ink-soft">Keep me signed in</span>
            </label>

            <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-3">
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>
    </AuthLayout>
</template>