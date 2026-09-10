<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import FlashMessage from "@/Components/Common/FlashMessage.vue";

const page = usePage();

const user = computed(() => page.props.auth?.user);
const permissions = computed(() => page.props.auth?.permissions ?? []);
const sideOpen = ref(false);

const can = (permission) => {
    return permissions.value.includes(permission);
};

const navItems = computed(() => {
    const items = [
        {
            name: "Dashboard",
            href: route("hr.dashboard"),
            permission: "dashboard.hr.view",
            exact: true,
            icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
        },
        {
            name: "Candidates",
            href: route("recruitment.candidates.index"),
            permission: "candidates.view",
            icon: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
        },
        // {
        //     name: "Employees",
        //     href: route("hr.employees.index"),
        //     permission: "employees.view",
        //     icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z",
        // },
        // {
        //     name: "Interview Schedule",
        //     href: route("hr.interviews.index"),
        //     permission: "interviews.view-branch",
        //     icon: "M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z",
        // },
        {
            name: "My Interviews",
            href: route("interviewer.candidates.index"),
            permission: "interviews.view-assigned",
            icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01",
        },
        // {
        //     name: "Reports",
        //     href: route("hr.reports.recruitment"),
        //     permission: "recruitment-reports.view-branch",
        //     icon: "M9 17v-2a4 4 0 014-4h3m4 0h-4m4 0v4m0-4l-5 5M5 3a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V9l-6-6H5z",
        // },
    ];

    return items.filter((item) => can(item.permission));
});

const currentPath = computed(() => {
    return page.url?.split("?")[0] ?? "";
});

const pathFromUrl = (url) => {
    try {
        return new URL(url, window.location.origin).pathname;
    } catch {
        return url?.split("?")[0] ?? "";
    }
};

const isActive = (item) => {
    const itemPath = pathFromUrl(item.href);

    if (item.exact) {
        return currentPath.value === itemPath;
    }

    return currentPath.value.startsWith(itemPath);
};

const closeMobileSidebar = () => {
    sideOpen.value = false;
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <FlashMessage />

        <!-- Mobile overlay -->
        <div
            v-if="sideOpen"
            class="fixed inset-0 z-30 bg-gray-900/50 md:hidden"
            @click="closeMobileSidebar"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-violet-800 transition-transform duration-200',
                sideOpen
                    ? 'translate-x-0'
                    : '-translate-x-full md:translate-x-0',
            ]"
        >
            <div
                class="flex items-center justify-between border-b border-violet-700/60 px-5 py-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/20 font-bold text-white"
                    >
                        ⚡
                    </div>

                    <div>
                        <p class="text-sm font-bold text-white">HRMS</p>
                        <p class="text-xs text-violet-300">HR Panel</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="text-violet-200 hover:text-white md:hidden"
                    @click="closeMobileSidebar"
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
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                        isActive(item)
                            ? 'bg-white/20 text-white'
                            : 'text-violet-200 hover:bg-white/10 hover:text-white',
                    ]"
                    @click="closeMobileSidebar"
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

            <div class="border-t border-violet-700/60 px-4 py-4">
                <div class="mb-3 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-sm font-bold text-white"
                    >
                        {{ user?.first_name?.[0] }}
                        {{ user?.last_name?.[0] }}
                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-white">
                            {{ user?.first_name }} {{ user?.last_name }}
                        </p>

                        <p class="truncate text-xs text-violet-300">
                            {{ user?.designation || "HR Manager" }}
                        </p>

                        <p
                            v-if="user?.branch?.name"
                            class="truncate text-xs text-violet-300"
                        >
                            {{ user.branch.name }}
                        </p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Link
                        :href="route('profile.edit')"
                        class="flex-1 rounded-lg bg-white/10 px-2 py-1.5 text-center text-xs text-violet-200 transition hover:bg-white/20 hover:text-white"
                    >
                        Profile
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex-1 rounded-lg bg-white/10 px-2 py-1.5 text-center text-xs text-violet-200 transition hover:bg-white/20 hover:text-white"
                    >
                        Sign out
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col md:pl-64">
            <header
                class="sticky top-0 z-20 flex h-14 items-center border-b border-gray-200 bg-white px-4 sm:px-6"
            >
                <button
                    type="button"
                    class="mr-4 text-gray-500 hover:text-gray-700 md:hidden"
                    @click="sideOpen = true"
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
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>

                <div>
                    <p class="font-semibold text-violet-700">HR Panel</p>
                    <p
                        v-if="user?.branch?.name"
                        class="hidden text-xs text-gray-500 sm:block"
                    >
                        {{ user.branch.name }}
                    </p>
                </div>

                <div class="ml-auto flex items-center gap-4">
                    <span class="hidden text-sm text-gray-600 sm:inline">
                        {{ user?.first_name }} {{ user?.last_name }}
                    </span>

                    <Link
                        :href="route('profile.edit')"
                        class="text-sm font-medium text-violet-600 hover:underline"
                    >
                        Profile
                    </Link>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-7">
                <slot />
            </main>
        </div>
    </div>
</template>