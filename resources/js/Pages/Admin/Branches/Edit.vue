<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ branch: Object })

const form = useForm({
    name: props.branch.name,
    code: props.branch.code,
    address: props.branch.address,
    city: props.branch.city,
    state: props.branch.state,
    postal_code: props.branch.postal_code,
    country: props.branch.country,
    phone: props.branch.phone,
    email: props.branch.email,
    is_active: props.branch.is_active,
})

const submit = () => form.put(route('admin.branches.update', props.branch.id))
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.branches.show', branch.id)" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">Edit Branch</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Branch Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field" />
                        <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label">Branch Code <span class="text-red-500">*</span></label>
                        <input v-model="form.code" type="text" class="input-field uppercase" maxlength="10" />
                        <p v-if="form.errors.code" class="error">{{ form.errors.code }}</p>
                    </div>
                </div>

                <div>
                    <label class="label">Address <span class="text-red-500">*</span></label>
                    <input v-model="form.address" type="text" class="input-field" />
                    <p v-if="form.errors.address" class="error">{{ form.errors.address }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">City <span class="text-red-500">*</span></label>
                        <input v-model="form.city" type="text" class="input-field" />
                    </div>
                    <div>
                        <label class="label">State <span class="text-red-500">*</span></label>
                        <input v-model="form.state" type="text" class="input-field" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Postal Code <span class="text-red-500">*</span></label>
                        <input v-model="form.postal_code" type="text" class="input-field" />
                    </div>
                    <div>
                        <label class="label">Country</label>
                        <input v-model="form.country" type="text" class="input-field" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Phone <span class="text-red-500">*</span></label>
                        <input v-model="form.phone" type="tel" class="input-field" />
                        <p v-if="form.errors.phone" class="error">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="label">Email <span class="text-red-500">*</span></label>
                        <input v-model="form.email" type="email" class="input-field" />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded" />
                    <label for="is_active" class="text-sm text-gray-700">Branch is active</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.branches.show', branch.id)" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>