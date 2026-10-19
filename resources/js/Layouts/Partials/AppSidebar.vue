<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

import UserMenu from "@/Layouts/Partials/UserMenu.vue";

const props = defineProps({
    configuration: { type: Object, required: true },
    open: { type: Boolean, default: false },
});
const emit = defineEmits(["close"]);

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const currentPath = computed(() => page.url?.split("?")[0] ?? "");

const hasPermission = (permission) => !permission || permissions.value.includes(permission);
const routeExists = (name) => typeof route === "function" && route().has(name);

const navigation = computed(() =>
    props.configuration.navigation
        .filter((item) => hasPermission(item.permission))
        .filter((item) => routeExists(item.route))
        .map((item) => ({ ...item, href: route(item.route) }))
);

const normalizePath = (url) => {
    try {
        return new URL(url, window.location.origin).pathname;
    } catch {
        return url?.split("?")[0] ?? "";
    }
};

const isActive = (item) => {
    if (item.activePaths?.length) {
        return item.activePaths.some((path) => currentPath.value.startsWith(path));
    }
    const itemPath = normalizePath(item.href);
    return item.exact ? currentPath.value === itemPath : currentPath.value.startsWith(itemPath);
};

const close = () => emit("close");
</script>

<template>
    <aside :class="[
        configuration.colors.sidebar,
        configuration.colors.sidebarBorder,
        'fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r transition-transform duration-300 ease-out',
        open ? 'translate-x-0 shadow-lift' : '-translate-x-full md:translate-x-0',
    ]">
        <div :class="[configuration.colors.sidebarBorder, 'flex h-16 items-center justify-between border-b px-5']">
            <div class="flex min-w-0 items-center gap-2.5">
                <img src="/assets/images/logo.png" alt="" class="h-8 w-auto shrink-0" />
                <div class="min-w-0 leading-tight">
                    <p class="truncate font-display text-sm font-bold text-ink">Airvincible HRMS</p>
                    <p :class="[configuration.colors.mutedText, 'truncate text-xs']">{{ configuration.title }}</p>
                </div>
            </div>
            <button type="button" aria-label="Close menu" class="rounded-lg p-1 text-ink-mute hover:text-ink md:hidden"
                @click="close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <Link v-for="item in navigation" :key="item.route" :href="item.href"
                :aria-current="isActive(item) ? 'page' : undefined" :class="[
                    'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors',
                    isActive(item)
                        ? configuration.colors.activeItem
                        : `${configuration.colors.navText} ${configuration.colors.hoverItem}`,
                ]" @click="close">
                <span :class="[configuration.colors.accentBar,
                    'absolute -left-3 inset-y-2 w-1 rounded-r-full origin-center transition-transform duration-300',
                    isActive(item) ? 'scale-y-100' : 'scale-y-0']" />
                <svg class="h-5 w-5 shrink-0 transition-transform duration-200 group-hover:scale-110" fill="none"
                    stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                </svg>
                <span class="truncate">{{ item.name }}</span>
            </Link>
        </nav>

        <UserMenu :configuration="configuration" />
    </aside>
</template>