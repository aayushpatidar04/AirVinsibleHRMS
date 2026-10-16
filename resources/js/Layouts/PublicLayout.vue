<script setup>
import PublicHeader from '@/Components/Public/PublicHeader.vue'
import PublicFooter from '@/Components/Public/PublicFooter.vue'
</script>

<template>
    <div class="flex min-h-screen flex-col bg-paper">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 btn-primary">
            Skip to content
        </a>
        <PublicHeader />
        <main id="main" class="flex-1">
            <slot />
        </main>
        <PublicFooter />
    </div>
</template>