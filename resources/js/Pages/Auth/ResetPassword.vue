<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { useForm, Head } from '@inertiajs/vue3'

const props = defineProps({ token: String, email: String })

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})
const submit = () => form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
    <GuestLayout>

        <Head title="Reset Password — HRMS" />
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-indigo-600 text-white text-3xl mb-4">
                    ⚡</div>
                <h1 class="text-2xl font-bold text-gray-900">Reset Password</h1>
            </div>
            <div class="bg-white py-8 px-8 shadow-xl rounded-2xl border border-gray-100">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="label">Email</label>
                        <input v-model="form.email" type="email" class="input-field" autocomplete="username" />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="label">New Password</label>
                        <input v-model="form.password" type="password" class="input-field"
                            placeholder="Min 8 characters" autocomplete="new-password" />
                        <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" class="input-field"
                            autocomplete="new-password" />
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition disabled:opacity-60">
                        {{ form.processing ? 'Resetting...' : 'Reset Password' }}
                    </button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>