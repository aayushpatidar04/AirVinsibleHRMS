<script setup>
// Shared shell for public candidate pages (registration, interview lookup): logo on top, light dotted backdrop.
defineProps({ narrow: { type: Boolean, default: false } })
</script>

<template>
    <div class="relative min-h-screen bg-paper">
        <div class="absolute inset-x-0 top-0 h-80 bg-dots [mask-image:linear-gradient(to_bottom,black,transparent)]"
            aria-hidden="true"></div>

        <div :class="[narrow ? 'max-w-lg' : 'max-w-3xl', 'relative mx-auto flex min-h-screen w-full flex-col px-4 py-8 sm:py-12']">
            <div class="flex flex-col items-center text-center">
                <img src="/assets/images/logo.png" alt="Airvincible HRMS" class="h-14 w-auto sm:h-16" />
                <p class="mt-2 font-display text-lg font-bold tracking-tight">
                    Airvincible <span class="text-indigo-600">HRMS</span>
                </p>
            </div>

            <main class="mt-8 flex-1">
                <slot />
            </main>

            <p class="mt-10 text-center text-xs text-ink-mute">
                <slot name="footer">Powered by Airvincible HRMS</slot>
            </p>
        </div>
    </div>
</template>