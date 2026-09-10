<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

import UserMenu from "@/Layouts/Partials/UserMenu.vue";

const props = defineProps({
    configuration: {
        type: Object,
        required: true,
    },

    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close"]);

const page = usePage();

const permissions = computed(() => {
    return page.props.auth?.permissions ?? [];
});

const currentPath = computed(() => {
    return page.url?.split("?")[0] ?? "";
});

const hasPermission = (permission) => {
    if (!permission) {
        return true;
    }

    return permissions.value.includes(permission);
};

const routeExists = (routeName) => {
    return typeof route === "function" && route().has(routeName);
};

const navigation = computed(() => {
    return props.configuration.navigation
        .filter((item) => hasPermission(item.permission))
        .filter((item) => routeExists(item.route))
        .map((item) => ({
            ...item,
            href: route(item.route),
        }));
});

const normalizePath = (url) => {
    try {
        return new URL(url, window.location.origin).pathname;
    } catch {
        return url?.split("?")[0] ?? "";
    }
};

const isActive = (item) => {
    if (item.activePaths?.length) {
        return item.activePaths.some((path) =>
            currentPath.value.startsWith(path)
        );
    }

    const itemPath = normalizePath(item.href);

    if (item.exact) {
        return currentPath.value === itemPath;
    }

    return currentPath.value.startsWith(itemPath);
};

const close = () => {
    emit("close");
};
</script>

<template>
    <aside
        :class="[
            configuration.colors.sidebar,
            'fixed inset-y-0 left-0 z-40 flex w-60 flex-col transition-transform duration-200',
            open
                ? 'translate-x-0'
                : '-translate-x-full md:translate-x-0',
        ]"
    >
        <div
            :class="[
                configuration.colors.sidebarBorder,
                'flex items-center justify-between border-b px-5 py-5',
            ]"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 font-bold text-white"
                >
                    ⚡
                </div>

                <div>
                    <p class="text-sm font-bold text-white">HRMS</p>

                    <p
                        :class="[
                            configuration.colors.mutedText,
                            'text-xs',
                        ]"
                    >
                        {{ configuration.title }}
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="text-white/70 hover:text-white md:hidden"
                @click="close"
            >
                <svg
                    class="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <Link
                v-for="item in navigation"
                :key="item.route"
                :href="item.href"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                    isActive(item)
                        ? 'bg-white/20 text-white'
                        : `${configuration.colors.navText} hover:bg-white/10 hover:text-white`,
                ]"
                @click="close"
            >
                <svg
                    class="h-5 w-5 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :d="item.icon"
                    />
                </svg>

                {{ item.name }}
            </Link>
        </nav>

        <UserMenu :configuration="configuration" />
    </aside>
</template>