<script setup>
import { computed, ref } from "vue";

import AppSidebar from "@/Layouts/Partials/AppSidebar.vue";
import AppHeader from "@/Layouts/Partials/AppHeader.vue";

import { panelConfigurations } from "@/Config/navigation";
import { usePanel } from "@/Composables/usePanel";

const sideOpen = ref(false);
const { resolvedPanel } = usePanel();

const configuration = computed(
    () => panelConfigurations[resolvedPanel.value] ?? panelConfigurations.employee
);
</script>

<template>
    <div class="flex min-h-screen bg-paper">
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
            <div v-if="sideOpen" class="fixed inset-0 z-30 bg-ink/40 backdrop-blur-sm md:hidden"
                @click="sideOpen = false" />
        </Transition>

        <AppSidebar :configuration="configuration" :open="sideOpen" @close="sideOpen = false" />

        <div class="flex min-w-0 flex-1 flex-col md:pl-60">
            <AppHeader :configuration="configuration" @open-sidebar="sideOpen = true" />

            <main class="flex-1 px-5 py-6 sm:px-8 sm:py-8">
                <div class="mx-auto w-full max-w-7xl animate-fade-up">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>