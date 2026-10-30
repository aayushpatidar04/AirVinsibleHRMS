<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    requirements: { type: Array, required: true },
});

const page = usePage();
const can = (p) => (page.props.auth?.permissions ?? []).includes(p);

const toggleActive = (req) => {
    router.put(
        route("admin.document-requirements.update", req.id),
        { ...req, is_active: !req.is_active },
        { preserveScroll: true }
    );
};

const toggleRequired = (req) => {
    router.put(
        route("admin.document-requirements.update", req.id),
        { ...req, is_required: !req.is_required },
        { preserveScroll: true }
    );
};

const deleteReq = (req) => {
    if (confirm(`Delete "${req.name}"?`)) {
        router.delete(route("admin.document-requirements.destroy", req.id));
    }
};

const restoreReq = (req) => {
    router.post(route("admin.document-requirements.restore", req.id));
};
</script>

<template>
    <AppLayout>
        <Head title="Document Requirements" />
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-ink">Document Requirements</h1>
                    <p class="mt-1 text-sm text-ink-mute">Define which documents candidates must submit before offer letter release.</p>
                </div>
                <Link v-if="can('documents.manage')" :href="route('admin.document-requirements.create')" class="btn-primary">
                    Add Requirement
                </Link>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Slug</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Required</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Active</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="req in requirements" :key="req.id" :class="{'opacity-50': !req.is_active && req.deleted_at}">
                            <td class="px-6 py-4">
                                <div class="font-medium text-ink">{{ req.name }}</div>
                                <div v-if="req.description" class="text-sm text-ink-mute">{{ req.description }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-mute">{{ req.slug }}</td>
                            <td class="px-6 py-4 text-center">
                                <button v-if="can('documents.manage')" @click="toggleRequired(req)" :class="['inline-flex h-6 w-11 items-center rounded-full transition', req.is_required ? 'bg-green-500' : 'bg-gray-300']">
                                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition', req.is_required ? 'translate-x-6' : 'translate-x-1']"></span>
                                </button>
                                <span v-else :class="['inline-flex h-6 w-11 items-center rounded-full bg-gray-300']">
                                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white', req.is_required ? 'translate-x-6' : 'translate-x-1']"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button v-if="can('documents.manage')" @click="toggleActive(req)" :class="['inline-flex h-6 w-11 items-center rounded-full transition', req.is_active ? 'bg-green-500' : 'bg-gray-300']">
                                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition', req.is_active ? 'translate-x-6' : 'translate-x-1']"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <template v-if="can('documents.manage')">
                                    <Link v-if="!req.deleted_at" :href="route('admin.document-requirements.edit', req.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <button v-if="!req.deleted_at" @click="deleteReq(req)" class="ml-3 text-red-600 hover:text-red-900">Delete</button>
                                    <button v-else @click="restoreReq(req)" class="ml-3 text-green-600 hover:text-green-900">Restore</button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>