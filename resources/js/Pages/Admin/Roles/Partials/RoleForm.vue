<script setup>
import { computed } from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },

    permissionGroups: {
        type: Array,
        default: () => [],
    },

    isSystemRole: {
        type: Boolean,
        default: false,
    },

    submitLabel: {
        type: String,
        default: "Save Role",
    },
});

const emit = defineEmits(["submit"]);

const selectedPermissions = computed(() => {
    return new Set(props.form.permissions ?? []);
});

const isSelected = (permission) => {
    return selectedPermissions.value.has(permission);
};

const togglePermission = (permission) => {
    if (isSelected(permission)) {
        props.form.permissions = props.form.permissions.filter(
            (item) => item !== permission,
        );

        return;
    }

    props.form.permissions = [...props.form.permissions, permission];
};

const isGroupSelected = (group) => {
    if (!group.permissions.length) {
        return false;
    }

    return group.permissions.every((permission) => isSelected(permission.name));
};

const isGroupPartiallySelected = (group) => {
    const count = group.permissions.filter((permission) =>
        isSelected(permission.name),
    ).length;

    return count > 0 && count < group.permissions.length;
};

const toggleGroup = (group) => {
    const permissionNames = group.permissions.map(
        (permission) => permission.name,
    );

    if (isGroupSelected(group)) {
        props.form.permissions = props.form.permissions.filter(
            (permission) => !permissionNames.includes(permission),
        );

        return;
    }

    props.form.permissions = [
        ...new Set([...props.form.permissions, ...permissionNames]),
    ];
};

const selectAll = () => {
    props.form.permissions = props.permissionGroups.flatMap((group) =>
        group.permissions.map((permission) => permission.name),
    );
};

const clearAll = () => {
    props.form.permissions = [];
};

const submit = () => {
    emit("submit");
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div>
                <label
                    for="role-name"
                    class="block text-sm font-semibold text-gray-900"
                >
                    Role name
                </label>

                <p class="mt-1 text-sm text-gray-500">
                    Use a short lowercase name such as recruitment_manager.
                </p>

                <input
                    id="role-name"
                    v-model="form.name"
                    type="text"
                    :disabled="isSystemRole"
                    class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                    placeholder="recruitment_manager"
                />

                <p v-if="isSystemRole" class="mt-2 text-sm text-amber-700">
                    System role names cannot be changed.
                </p>

                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                    {{ form.errors.name }}
                </p>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
        >
            <div
                class="flex flex-col gap-3 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="font-semibold text-gray-900">Permissions</h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Select the actions available to this role.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="clearAll"
                    >
                        Clear all
                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                        @click="selectAll"
                    >
                        Select all
                    </button>
                </div>
            </div>

            <div
                v-if="permissionGroups.length"
                class="divide-y divide-gray-200"
            >
                <div
                    v-for="group in permissionGroups"
                    :key="group.name"
                    class="p-6"
                >
                    <label class="mb-4 flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            :checked="isGroupSelected(group)"
                            :indeterminate="isGroupPartiallySelected(group)"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @change="toggleGroup(group)"
                        />

                        <span>
                            <span class="block font-semibold text-gray-900">
                                {{ group.label }}
                            </span>

                            <span class="block text-xs text-gray-500">
                                {{ group.permissions.length }}
                                permissions
                            </span>
                        </span>
                    </label>

                    <div
                        class="grid grid-cols-1 gap-3 pl-7 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        <label
                            v-for="permission in group.permissions"
                            :key="permission.id"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50"
                        >
                            <input
                                type="checkbox"
                                :checked="isSelected(permission.name)"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                @change="togglePermission(permission.name)"
                            />

                            <span class="min-w-0">
                                <span
                                    class="block text-sm font-medium text-gray-800"
                                >
                                    {{ permission.label }}
                                </span>

                                <span
                                    class="block truncate text-xs text-gray-500"
                                >
                                    {{ permission.name }}
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div v-else class="px-6 py-12 text-center text-sm text-gray-500">
                No permissions are available.
            </div>

            <p
                v-if="form.errors.permissions"
                class="px-6 pb-5 text-sm text-red-600"
            >
                {{ form.errors.permissions }}
            </p>
        </section>

        <div class="flex justify-end gap-3">
            <button
                type="button"
                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                @click="history.back()"
            >
                Cancel
            </button>

            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ form.processing ? "Saving..." : submitLabel }}
            </button>
        </div>
    </form>
</template>