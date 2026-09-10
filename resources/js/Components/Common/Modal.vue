<script setup>
defineProps({
    show: Boolean,
    title: String,
    subtitle: String,
    maxWidth: { type: String, default: 'lg' }
})
defineEmits(['close'])

const sizes = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

                <div :class="[
                    'relative bg-white rounded-2xl shadow-2xl w-full flex flex-col max-h-[90vh]',
                    sizes[maxWidth] ?? 'max-w-lg'
                ]">
                    <!-- Header -->
                    <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                            <small style="font-size: 0.65rem;">{{ subtitle }}</small>
                        </div>
                        <button @click="$emit('close')"
                            class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                    </div>

                    <!-- Body - Scrollable -->
                    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-4">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer"
                        class="flex-shrink-0 px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity .2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Hide scrollbar but keep scroll functionality */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>