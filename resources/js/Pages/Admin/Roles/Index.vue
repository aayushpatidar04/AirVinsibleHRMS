<script setup>
import { computed } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    roles: {
        type: Object,
        required: true,
    },

    systemRoles: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const permissions = computed(() => {
    return page.props.auth?.permissions ?? [];
});

const can = (permission) => {
    return permissions.value.includes(permission);
};

const deleteRole = (role) => {
    if (!role.can_delete) {
        return;
    }

    const confirmed = window.confirm(
        `Delete the "${role.name}" role? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    router.delete(route("admin.roles.destroy", role.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <AppLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Roles & Permissions
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Control which actions each application role can perform.
                    </p>
                </div>

                <Link
                    v-if="can('roles.create')"
                    :href="route('admin.roles.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                >
                    Create role
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Role
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Users
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Permissions
                                </th>

                                <th
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="role in roles.data"
                                :key="role.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-sm font-bold uppercase text-indigo-700"
                                        >
                                            {{ role.name.charAt(0) }}
                                        </div>

                                        <div>
                                            <p
                                                class="font-medium capitalize text-gray-900"
                                            >
                                                {{
                                                    role.name.replaceAll(
                                                        "_",
                                                        " ",
                                                    )
                                                }}
                                            </p>

                                            <div class="mt-1 flex gap-2">
                                                <span
                                                    v-if="role.is_system"
                                                    class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                                                >
                                                    System
                                                </span>

                                                <span
                                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
                                                >
                                                    {{ role.guard_name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="text-sm text-gray-700">
                                        {{ role.users_count }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="text-sm text-gray-700">
                                        {{ role.permissions_count }}
                                    </span>
                                </td>

                                <td
                                    class="whitespace-nowrap px-6 py-4 text-right"
                                >
                                    <div class="flex justify-end gap-3">
                                        <Link
                                            v-if="can('roles.update')"
                                            :href="
                                                route(
                                                    'admin.roles.edit',
                                                    role.id,
                                                )
                                            "
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            Edit
                                        </Link>

                                        <button
                                            v-if="
                                                can('roles.delete') &&
                                                !role.is_system
                                            "
                                            type="button"
                                            :disabled="!role.can_delete"
                                            class="text-sm font-medium text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:text-gray-400"
                                            :title="
                                                role.can_delete
                                                    ? 'Delete role'
                                                    : 'Remove this role from all users before deleting it'
                                            "
                                            @click="deleteRole(role)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!roles.data.length">
                                <td
                                    colspan="4"
                                    class="px-6 py-12 text-center text-sm text-gray-500"
                                >
                                    No roles found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="roles.links?.length > 3"
                    class="flex flex-wrap gap-2 border-t border-gray-200 px-6 py-4"
                >
                    <template v-for="link in roles.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            :class="[
                                'rounded-lg px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'border border-gray-300 text-gray-700 hover:bg-gray-50',
                            ]"
                            v-html="link.label"
                        />

                        <span
                            v-else
                            class="cursor-not-allowed rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-400"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
