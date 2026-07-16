<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({
    name: '', code: '', address: '', city: '', state: '',
    postal_code: '', country: 'India', phone: '', email: '',
})
const submit = () => form.post(route('admin.branches.store'))
</script>

<template>
    <AdminLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.branches.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">New Branch</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Branch Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field" placeholder="Mumbai Head Office" />
                        <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label">Branch Code <span class="text-red-500">*</span></label>
                        <input v-model="form.code" type="text" class="input-field uppercase" placeholder="MUM01"
                            maxlength="10" />
                        <p v-if="form.errors.code" class="error">{{ form.errors.code }}</p>
                    </div>
                </div>

                <div>
                    <label class="label">Address <span class="text-red-500">*</span></label>
                    <input v-model="form.address" type="text" class="input-field" placeholder="123 Business Park" />
                    <p v-if="form.errors.address" class="error">{{ form.errors.address }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">City <span class="text-red-500">*</span></label>
                        <input v-model="form.city" type="text" class="input-field" placeholder="Mumbai" />
                        <p v-if="form.errors.city" class="error">{{ form.errors.city }}</p>
                    </div>
                    <div>
                        <label class="label">State <span class="text-red-500">*</span></label>
                        <input v-model="form.state" type="text" class="input-field" placeholder="Maharashtra" />
                        <p v-if="form.errors.state" class="error">{{ form.errors.state }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Postal Code <span class="text-red-500">*</span></label>
                        <input v-model="form.postal_code" type="text" class="input-field" placeholder="400001" />
                        <p v-if="form.errors.postal_code" class="error">{{ form.errors.postal_code }}</p>
                    </div>
                    <div>
                        <label class="label">Country</label>
                        <input v-model="form.country" type="text" class="input-field" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Phone <span class="text-red-500">*</span></label>
                        <input v-model="form.phone" type="tel" class="input-field" placeholder="022-12345678" />
                        <p v-if="form.errors.phone" class="error">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="label">Email <span class="text-red-500">*</span></label>
                        <input v-model="form.email" type="email" class="input-field" placeholder="mumbai@company.com" />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.branches.index')" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create Branch' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>