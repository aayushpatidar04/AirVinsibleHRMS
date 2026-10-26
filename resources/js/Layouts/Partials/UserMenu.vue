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

const user = computed(() => page.props.auth?.user ?? null);
const roles = computed(() => page.props.auth?.roles ?? []);

const initials = computed(() => {
    const first = user.value?.first_name?.charAt(0) ?? "";
    const last = user.value?.last_name?.charAt(0) ?? "";

    return `${first}${last}`.toUpperCase();
});

const roleLabel = computed(() => {
    if (
        page.props.auth?.primary_role === "hr_recruiter" &&
        (roles.value.includes("interviewer") || user.value?.can_interview)
    ) {
        return "HR Manager · Interviewer";
    }

    return props.configuration.roleLabel;
});
</script>

<template>
    <div :class="[configuration.colors.sidebarBorder, 'border-t p-3']">
        <div class="flex items-center gap-3 rounded-xl bg-paper px-3 py-2.5">
            <div
                :class="[
                    configuration.colors.avatar,
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                ]"
            >
                {{ initials }}
            </div>

            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-semibold text-ink">
                    {{ user?.first_name }} {{ user?.last_name }}
                </p>

                <p
                    :class="[
                        configuration.colors.mutedText,
                        'truncate text-xs',
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

        <div class="mt-2 grid grid-cols-2 gap-2">
            <Link
                :href="route('profile.edit')"
                class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold text-ink-soft transition hover:bg-paper-warm hover:text-ink"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                    />
                </svg>
                Profile
            </Link>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold text-ink-soft transition hover:bg-red-50 hover:text-red-600"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"
                    />
                </svg>
                Sign out
            </Link>
        </div>
    </div>
</template>
