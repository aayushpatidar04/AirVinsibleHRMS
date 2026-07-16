<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ round: Object })

const form = useForm({
    name: props.round.name,
    sequence_number: props.round.sequence_number,
    description: props.round.description ?? '',
    is_mandatory: props.round.is_mandatory,
    is_hr_round: props.round.is_hr_round,
    is_ops_round: props.round.is_ops_round,
    is_active: props.round.is_active,
})

const submit = () => form.put(route('admin.rounds.update', props.round.id))
</script>

<template>
    <AdminLayout>
        <div class="max-w-xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.rounds.show', round.id)" class="text-gray-400 hover:text-gray-600">←</Link>
                <h1 class="text-2xl font-bold text-gray-900">Edit Round</h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Round Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="input-field" />
                        <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label">Sequence Number <span class="text-red-500">*</span></label>
                        <input v-model.number="form.sequence_number" type="number" class="input-field" min="1" />
                        <p v-if="form.errors.sequence_number" class="error">{{ form.errors.sequence_number }}</p>
                    </div>
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="3" class="input-field" />
                </div>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_mandatory" type="checkbox" class="rounded text-indigo-600" />
                        <span class="text-sm text-gray-700">Mandatory round</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_hr_round" type="checkbox" class="rounded text-orange-500"
                            @change="form.is_ops_round = form.is_hr_round ? false : form.is_ops_round" />
                        <span class="text-sm text-gray-700 font-medium text-orange-700">HR Round</span>
                        <span class="text-xs text-gray-500">— Salary mandatory for TL/QA/AM/OM and Other profiles</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_ops_round" type="checkbox" class="rounded text-blue-500"
                            @change="form.is_hr_round = form.is_ops_round ? false : form.is_hr_round" />
                        <span class="text-sm text-gray-700 font-medium text-blue-700">OPS Round</span>
                        <span class="text-xs text-gray-500">— Capture an optional salary range for Advisor/Executive profiles</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="rounded text-green-600" />
                        <span class="text-sm text-gray-700">Round is active</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t">
                    <Link :href="route('admin.rounds.show', round.id)" class="btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>