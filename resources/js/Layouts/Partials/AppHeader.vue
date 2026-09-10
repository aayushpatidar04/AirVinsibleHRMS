<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
    configuration: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["open-sidebar"]);

const page = usePage();

const user = computed(() => {
    return page.props.auth?.user ?? null;
});

const openSidebar = () => {
    emit("open-sidebar");
};
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-14 items-center border-b border-gray-200 bg-white px-5"
    >
        <button
            type="button"
            class="mr-4 text-gray-500 hover:text-gray-700 md:hidden"
            @click="openSidebar"
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
            <p
                :class="[
                    configuration.colors.headerText,
                    'font-semibold',
                ]"
            >
                {{ configuration.title }}
            </p>

            <p
                v-if="user?.branch?.name"
                class="hidden text-xs text-gray-500 sm:block"
            >
                {{ user.branch.name }}
            </p>
        </div>

        <div class="ml-auto flex items-center gap-4">
            <span class="hidden text-sm text-gray-600 sm:inline">
                {{ user?.first_name }}
                {{ user?.last_name }}
            </span>

            <Link
                :href="route('profile.edit')"
                :class="[
                    configuration.colors.linkText,
                    'text-sm hover:underline',
                ]"
            >
                Profile
            </Link>
        </div>
    </header>
</template>