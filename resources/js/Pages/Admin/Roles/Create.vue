<script setup>
import { Head, useForm } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";
import RoleForm from "@/Pages/Admin/Roles/Partials/RoleForm.vue";

defineProps({
    permissionGroups: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: "",
    permissions: [],
});

const submit = () => {
    form.post(route("admin.roles.store"));
};
</script>

<template>
    <Head title="Create Role" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Role</h1>

                <p class="mt-1 text-sm text-gray-500">
                    Create a role and select the actions it can perform.
                </p>
            </div>

            <RoleForm
                :form="form"
                :permission-groups="permissionGroups"
                submit-label="Create Role"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
