<script setup>
import { Head, useForm } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";
import RoleForm from "@/Pages/Admin/Roles/Partials/RoleForm.vue";

const props = defineProps({
    role: {
        type: Object,
        required: true,
    },

    permissionGroups: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.role.name,
    permissions: [...props.role.permissions],
});

const submit = () => {
    form.put(route("admin.roles.update", props.role.id));
};
</script>

<template>
    <Head :title="`Edit ${role.name}`" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <div>
                <h1 class="text-2xl font-bold capitalize text-gray-900">
                    Edit
                    {{ role.name.replaceAll("_", " ") }}
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update the permissions assigned to this role.
                </p>
            </div>

            <RoleForm
                :form="form"
                :permission-groups="permissionGroups"
                :is-system-role="role.is_system"
                submit-label="Save Changes"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
