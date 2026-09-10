<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import FlashMessage from "@/Components/Common/FlashMessage.vue";
import AppSidebar from "@/Layouts/Partials/AppSidebar.vue";
import AppHeader from "@/Layouts/Partials/AppHeader.vue";

import { panelConfigurations } from "@/Config/navigation";
import { usePanel } from "@/Composables/usePanel";

const page = usePage();
const sideOpen = ref(false);

const { resolvedPanel } = usePanel();

const configuration = computed(() => {
    return (
        panelConfigurations[resolvedPanel.value] ??
        panelConfigurations.employee
    );
});

const closeSidebar = () => {
    sideOpen.value = false;
};

const openSidebar = () => {
    sideOpen.value = true;
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <FlashMessage />

        <div
            v-if="sideOpen"
            class="fixed inset-0 z-30 bg-black/50 md:hidden"
            @click="closeSidebar"
        />

        <AppSidebar
            :configuration="configuration"
            :open="sideOpen"
            @close="closeSidebar"
        />

        <div class="flex min-w-0 flex-1 flex-col md:pl-60">
            <AppHeader
                :configuration="configuration"
                @open-sidebar="openSidebar"
            />

            <main class="flex-1 p-5 sm:p-7">
                <slot />
            </main>
        </div>
    </div>
</template>