<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const isAdmin = computed(() => page.props.auth.roles?.includes('admin'))
const Layout = computed(() => isAdmin.value ? AppLayout : AppLayout)

defineProps({ mustVerifyEmail: Boolean, status: String })

const profileForm = useForm({
    first_name: user.value.first_name,
    last_name: user.value.last_name,
    email: user.value.email,
    phone: user.value.phone ?? '',
})
const submitProfile = () => profileForm.patch(route('profile.update'))

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' })
const submitPassword = () => passwordForm.put(route('password.update'), {
    onSuccess: () => passwordForm.reset(),
})

const deleteForm = useForm({ password: '' })
const submitDelete = () => {
    if (confirm('Are you sure? This action is permanent.'))
        deleteForm.delete(route('profile.destroy'))
}
</script>

<template>
    <component :is="Layout">

        <Head title="Profile — HRMS" />
        <div class="max-w-2xl mx-auto space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>

            <!-- Profile Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Profile Information</h2>
                <form @submit.prevent="submitProfile" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">First Name</label>
                            <input v-model="profileForm.first_name" type="text" class="input-field" />
                            <p v-if="profileForm.errors.first_name" class="error">{{ profileForm.errors.first_name }}
                            </p>
                        </div>
                        <div>
                            <label class="label">Last Name</label>
                            <input v-model="profileForm.last_name" type="text" class="input-field" />
                            <p v-if="profileForm.errors.last_name" class="error">{{ profileForm.errors.last_name }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input v-model="profileForm.email" type="email" class="input-field" />
                        <p v-if="profileForm.errors.email" class="error">{{ profileForm.errors.email }}</p>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input v-model="profileForm.phone" type="tel" class="input-field" />
                        <p v-if="profileForm.errors.phone" class="error">{{ profileForm.errors.phone }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="profileForm.processing" class="btn-primary">
                            {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                        <span v-if="profileForm.recentlySuccessful" class="text-sm text-green-600">Saved!</span>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Change Password</h2>
                <form @submit.prevent="submitPassword" class="space-y-4">
                    <div>
                        <label class="label">Current Password</label>
                        <input v-model="passwordForm.current_password" type="password" class="input-field"
                            autocomplete="current-password" />
                        <p v-if="passwordForm.errors.current_password" class="error">{{
                            passwordForm.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="label">New Password</label>
                        <input v-model="passwordForm.password" type="password" class="input-field"
                            autocomplete="new-password" />
                        <p v-if="passwordForm.errors.password" class="error">{{ passwordForm.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">Confirm New Password</label>
                        <input v-model="passwordForm.password_confirmation" type="password" class="input-field" />
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="passwordForm.processing" class="btn-primary">
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                        </button>
                        <span v-if="passwordForm.recentlySuccessful" class="text-sm text-green-600">Updated!</span>
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6">
                <h2 class="font-semibold text-red-700 mb-2">Delete Account</h2>
                <p class="text-sm text-gray-500 mb-4">Once deleted, all data will be permanently removed.</p>
                <form @submit.prevent="submitDelete" class="space-y-4">
                    <div>
                        <label class="label">Confirm your password</label>
                        <input v-model="deleteForm.password" type="password" class="input-field border-red-200" />
                        <p v-if="deleteForm.errors.password" class="error">{{ deleteForm.errors.password }}</p>
                    </div>
                    <button type="submit" :disabled="deleteForm.processing"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition disabled:opacity-50">
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Account' }}
                    </button>
                </form>
            </div>
        </div>
    </component>
</template>