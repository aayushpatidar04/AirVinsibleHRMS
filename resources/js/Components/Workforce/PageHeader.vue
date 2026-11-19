<script setup>
defineProps({ title: String, subtitle: String });
</script>

<template>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2"><slot /></div>
    </div>
</template>
