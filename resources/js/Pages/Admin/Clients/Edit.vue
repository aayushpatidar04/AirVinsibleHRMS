<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({ client: Object })

const form = useForm({
    name: props.client.name,
    code: props.client.code,
    contact_name: props.client.contact_name,
    contact_email: props.client.contact_email,
    contact_phone: props.client.contact_phone,
    address: props.client.address,
    city: props.client.city,
    state: props.client.state,
    postal_code: props.client.postal_code,
    country: props.client.country,
    notes: props.client.notes,
    is_active: props.client.is_active,
    effective_from: props.client.effective_from,
    effective_to: props.client.effective_to,
})

const submit = () => {
    form.put(route('admin.clients.update', client.id), {
        onSuccess: () => router.route('admin.clients.show', client.id),
    })
}
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Client</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="input-field w-full" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client Code <span class="text-red-500">*</span></label>
                            <input v-model="form.code" type="text" class="input-field w-full" />
                            <p v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{ form.errors.code }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                            <input v-model="form.contact_name" type="text" class="input-field w-full" />
                            <p v-if="form.errors.contact_name" class="text-xs text-red-500 mt-1">{{ form.errors.contact_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                            <input v-model="form.contact_email" type="email" class="input-field w-full" />
                            <p v-if="form.errors.contact_email" class="text-xs text-red-500 mt-1">{{ form.errors.contact_email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                            <input v-model="form.contact_phone" type="text" class="input-field w-full" />
                            <p v-if="form.errors.contact_phone" class="text-xs text-red-500 mt-1">{{ form.errors.contact_phone }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input v-model="form.address" type="text" class="input-field w-full" />
                            <p v-if="form.errors.address" class="text-xs text-red-500 mt-1">{{ form.errors.address }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input v-model="form.city" type="text" class="input-field w-full" />
                            <p v-if="form.errors.city" class="text-xs text-red-500 mt-1">{{ form.errors.city }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <input v-model="form.state" type="text" class="input-field w-full" />
                            <p v-if="form.errors.state" class="text-xs text-red-500 mt-1">{{ form.errors.state }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                            <input v-model="form.postal_code" type="text" class="input-field w-full" />
                            <p v-if="form.errors.postal_code" class="text-xs text-red-500 mt-1">{{ form.errors.postal_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <input v-model="form.country" type="text" class="input-field w-full" />
                            <p v-if="form.errors.country" class="text-xs text-red-500 mt-1">{{ form.errors.country }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea v-model="form.notes" rows="3" class="input-field w-full"></textarea>
                            <p v-if="form.errors.notes" class="text-xs text-red-500 mt-1">{{ form.errors.notes }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <input type="checkbox" v-model="form.is_active" class="rounded" />
                        <label class="text-sm text-gray-700">Is Active</label>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Effective From</label>
                            <input v-model="form.effective_from" type="date" class="input-field w-full" />
                            <p v-if="form.errors.effective_from" class="text-xs text-red-500 mt-1">{{ form.errors.effective_from }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Effective To</label>
                            <input v-model="form.effective_to" type="date" class="input-field w-full" />
                            <p v-if="form.errors.effective_to" class="text-xs text-red-500 mt-1">{{ form.errors.effective_to }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                    <Link :href="route('admin.clients.show', client.id)" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Updating...' : 'Update Client' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
