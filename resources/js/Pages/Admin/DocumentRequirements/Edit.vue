<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";

const page = usePage();

const props = defineProps({
    requirement: { type: Object, required: true },
});

const form = ref({
    name: props.requirement.name,
    slug: props.requirement.slug,
    description: props.requirement.description ?? "",
    is_required: props.requirement.is_required,
    sort_order: props.requirement.sort_order,
    is_active: props.requirement.is_active,
});

const submitting = ref(false);
const errors = ref({});

const submit = () => {
    submitting.value = true;
    errors.value = {};

    const data = { ...form.value };
    router.put(route("admin.document-requirements.update", props.requirement.id), data, {
        onError: (e) => { errors.value = e; submitting.value = false; },
        onSuccess: () => { submitting.value = false; },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Edit Document Requirement" />
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-ink">Edit Document Requirement</h1>
                <p class="mt-1 text-sm text-ink-mute">Update the document requirement details.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div>
                    <label class="label">Name <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" class="input-field" />
                    <p v-if="errors.name" class="error">{{ errors.name }}</p>
                </div>

                <div>
                    <label class="label">Slug <span class="text-red-500">*</span></label>
                    <input v-model="form.slug" type="text" class="input-field" />
                    <p v-if="errors.slug" class="error">{{ errors.slug }}</p>
                </div>

                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="3" class="input-field"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label class="label">Sort Order</label>
                        <input v-model="form.sort_order" type="number" min="0" class="input-field" />
                    </div>
                    <div class="flex items-center gap-3 pt-6">
                        <input v-model="form.is_required" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600" />
                                        <label class="text-sm font-medium text-ink">Mandatory document</label>
                    </div>
                    <div class="flex items-center gap-3 pt-6">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600" />
                                        <label class="text-sm font-medium text-ink">Active</label>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="submit" :disabled="submitting" class="btn-primary">{{ submitting ? 'Saving...' : 'Update' }}</button>
                    <Link :href="route('admin.document-requirements.index')" class="btn-secondary">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>