<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
    configuration: { type: Object, required: true },
});
const emit = defineEmits(["open-sidebar"]);

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const fullName = computed(() => [user.value?.first_name, user.value?.last_name].filter(Boolean).join(" "));
const initials = computed(
    () => `${user.value?.first_name?.[0] ?? ""}${user.value?.last_name?.[0] ?? ""}`.toUpperCase() || "U"
);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-indigo-100 bg-white/80 px-5 backdrop-blur-md">
        <button type="button" aria-label="Open menu"
            class="-ml-1 rounded-lg p-2 text-ink-soft hover:bg-paper-warm md:hidden" @click="emit('open-sidebar')">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <p :class="[configuration.colors.headerText, 'font-display text-base font-bold']">
            {{ configuration.title }}
        </p>

        <span v-if="user?.branch?.name"
            class="hidden items-center gap-1.5 rounded-full bg-paper-warm px-3 py-1 text-xs font-medium text-ink-soft sm:inline-flex">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ user.branch.name }}
        </span>

        <Link :href="route('profile.edit')"
            class="ml-auto flex items-center gap-3 rounded-xl py-1 pl-1 pr-2 transition hover:bg-paper-warm sm:pr-3"
            title="Your profile">
            <span :class="[configuration.colors.avatar, 'flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold']">
                {{ initials }}
            </span>
            <span class="hidden text-left leading-tight sm:block">
                <span class="block text-sm font-semibold text-ink">{{ fullName }}</span>
                <span class="block text-xs text-ink-mute">{{ configuration.roleLabel }}</span>
            </span>
        </Link>
    </header>
</template>