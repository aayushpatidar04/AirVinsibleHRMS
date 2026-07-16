<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const visible = ref(false)
const msg = ref({ type: '', text: '' })

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) { msg.value = { type: 'success', text: flash.success }; visible.value = true }
        if (flash?.error) { msg.value = { type: 'error', text: flash.error }; visible.value = true }
        if (visible.value) setTimeout(() => (visible.value = false), 4000)
    },
    { immediate: true, deep: true }
)
</script>

<template>
    <Transition name="slide">
        <div v-if="visible" :class="[
            'fixed top-4 right-4 z-50 max-w-sm w-full rounded-lg shadow-lg p-4 flex items-start gap-3',
            msg.type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
        ]">
            <div :class="msg.type === 'success' ? 'text-green-500' : 'text-red-500'" class="text-xl leading-none">
                {{ msg.type === 'success' ? '✓' : '✕' }}
            </div>
            <p :class="msg.type === 'success' ? 'text-green-800' : 'text-red-800'" class="text-sm font-medium">
                {{ msg.text }}
            </p>
            <button @click="visible = false"
                class="ml-auto text-gray-400 hover:text-gray-600 text-lg leading-none">×</button>
        </div>
    </Transition>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: all .3s ease;
}

.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>