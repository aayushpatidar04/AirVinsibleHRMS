<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'

defineProps({ canResetPassword: Boolean, status: String })

const form = useForm({ email: '', password: '', remember: false })
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') })
</script>

<template>
    <GuestLayout>

        <Head title="Sign In — HRMS" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo / Brand -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-indigo-600 text-white text-3xl mb-4 shadow-lg">
                    ⚡
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900">HRMS</h1>
                <p class="mt-1 text-sm text-gray-500">Human Resource Management System</p>
            </div>

            <div class="bg-white py-8 px-8 shadow-xl rounded-2xl border border-gray-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Sign in to your account</h2>

                <!-- Status / flash -->
                <div v-if="status"
                    class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label class="label">Email address</label>
                        <input v-model="form.email" type="email" autocomplete="email" class="input-field"
                            :class="{ 'border-red-400 focus:ring-red-400': form.errors.email }"
                            placeholder="you@company.com" required />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="label !mb-0">Password</label>
                            <Link v-if="canResetPassword" :href="route('password.request')"
                                class="text-xs text-indigo-600 hover:text-indigo-800">Forgot password?</Link>
                        </div>
                        <input v-model="form.password" type="password" autocomplete="current-password"
                            class="input-field" :class="{ 'border-red-400 focus:ring-red-400': form.errors.password }"
                            placeholder="••••••••" required />
                        <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.remember" type="checkbox"
                                class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full flex justify-center items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl transition disabled:opacity-60 shadow-sm">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? 'Signing in...' : 'Sign in' }}
                    </button>
                </form>
            </div>

            <!-- Demo credentials helper -->
            <!-- <div class="mt-6 bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-xs text-indigo-800">
                <p class="font-semibold mb-2">🔑 Demo Credentials</p>
                <p><strong>Admin:</strong> admin@hrms.com / password</p>
                <p><strong>Interviewer:</strong> rahul@hrms.com / password</p>
            </div> -->
        </div>
    </GuestLayout>
</template>