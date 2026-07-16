<script setup>
defineProps({
    title: String,
    value: [String, Number],
    subtitle: { type: String, default: '' },
    color: { type: String, default: 'indigo' },
    icon: { type: String, default: '' },
})

const colors = {
    indigo: 'bg-indigo-500',
    green: 'bg-green-500',
    yellow: 'bg-yellow-500',
    red: 'bg-red-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    teal: 'bg-teal-500',
}
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div v-if="icon"
            :class="['h-12 w-12 rounded-xl flex items-center justify-center text-white text-xl flex-shrink-0', colors[color] ?? 'bg-indigo-500']">
            {{ icon }}
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">{{ title }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ value }}</p>
            <p v-if="subtitle" class="text-xs text-gray-400 mt-0.5">{{ subtitle }}</p>
        </div>
    </div>
</template>