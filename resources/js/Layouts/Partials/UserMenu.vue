<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
    configuration: {
        type: Object,
        required: true,
    },
});

const page = usePage();

const user = computed(() => {
    return page.props.auth?.user ?? null;
});

const roles = computed(() => {
    return page.props.auth?.roles ?? [];
});

const initials = computed(() => {
    const first = user.value?.first_name?.charAt(0) ?? "";
    const last = user.value?.last_name?.charAt(0) ?? "";

    return `${first}${last}`.toUpperCase();
});

const roleLabel = computed(() => {
    if (
        page.props.auth?.primary_role === "hr" &&
        (
            roles.value.includes("interviewer") ||
            user.value?.can_interview
        )
    ) {
        return "HR Manager · Interviewer";
    }

    return props.configuration.roleLabel;
});
</script>

<template>
    <div
        :class="[
            configuration.colors.sidebarBorder,
            'border-t px-4 py-4',
        ]"
    >
        <div class="mb-3 flex items-center gap-3">
            <div
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-sm font-bold text-white"
            >
                {{ initials }}
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-white">
                    {{ user?.first_name }}
                    {{ user?.last_name }}
                </p>

                <p
                    :class="[
                        configuration.colors.mutedText,
                        'text-xs',
                    ]"
                >
                    {{ roleLabel }}
                </p>

                <p
                    v-if="user?.branch?.name"
                    :class="[
                        configuration.colors.mutedText,
                        'truncate text-xs',
                    ]"
                >
                    {{ user.branch.name }}
                </p>
            </div>
        </div>

        <div class="flex gap-2">
            <Link
                :href="route('profile.edit')"
                class="flex-1 rounded-lg bg-white/10 px-2 py-1.5 text-center text-xs text-white/70 transition hover:bg-white/20 hover:text-white"
            >
                Profile
            </Link>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex-1 rounded-lg bg-white/10 px-2 py-1.5 text-center text-xs text-white/70 transition hover:bg-white/20 hover:text-white"
            >
                Sign out
            </Link>
        </div>
    </div>
</template>