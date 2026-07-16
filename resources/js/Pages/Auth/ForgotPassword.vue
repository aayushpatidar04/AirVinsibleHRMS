<script setup>
// ForgotPassword.vue
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'

defineProps({ status: String })
const form = useForm({ email: '' })
const submit = () => form.post(route('password.email'))
</script>

<template>
    <GuestLayout>

        <Head title="Forgot Password — HRMS" />
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-indigo-600 text-white text-3xl mb-4">
                    ⚡</div>
                <h1 class="text-2xl font-bold text-gray-900">Forgot Password</h1>
                <p class="mt-1 text-sm text-gray-500">We'll email you a reset link.</p>
            </div>

            <div class="bg-white py-8 px-8 shadow-xl rounded-2xl border border-gray-100">
                <div v-if="status"
                    class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    {{ status }}
                </div>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="label">Email address</label>
                        <input v-model="form.email" type="email" class="input-field" placeholder="you@company.com"
                            required />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition disabled:opacity-60">
                        {{ form.processing ? 'Sending...' : 'Send Reset Link' }}
                    </button>
                </form>
                <p class="mt-4 text-center text-sm text-gray-500">
                    <Link :href="route('login')" class="text-indigo-600 hover:underline">Back to login</Link>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>