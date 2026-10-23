<script setup>
import { onUnmounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const message = ref(null)
let dismissTimeout

const validationMessages = (errors) =>
    Object.values(errors ?? {}).flatMap((error) =>
        (Array.isArray(error) ? error : [error]).filter(
            (message) => typeof message === 'string' && message.length > 0,
        ),
    )

// Replace your existing watch block with this safely guarded version
watch(
  () => [page?.props?.flash, page?.props?.errors], // Added optional chaining here
  ([flash, errors]) => {
    clearTimeout(dismissTimeout)
    
    if (flash?.success) {
      message.value = { type: 'success', text: flash.success, errors: [] }
    } else if (flash?.error) {
      message.value = { type: 'error', text: flash.error, errors: [] }
    } else {
      const messages = validationMessages(errors)
      message.value = messages.length 
        ? { type: 'validation', text: 'Please correct the following errors:', errors: messages } 
        : null
    }

    if (message.value) {
      dismissTimeout = setTimeout(() => {
        message.value = null
      }, 5000)
    }
  },
  { immediate: true, deep: true },
)


onUnmounted(() => clearTimeout(dismissTimeout))
</script>

<template>
    <Transition name="slide">
        <div
            v-if="message"
            role="alert"
            aria-live="assertive"
            :class="[
                'fixed right-4 top-4 z-50 flex w-full max-w-sm items-start gap-3 rounded-lg border p-4 shadow-lg',
                message.type === 'success'
                    ? 'border-green-200 bg-green-50'
                    : 'border-red-200 bg-red-50',
            ]"
        >
            <div
                :class="message.type === 'success' ? 'text-green-500' : 'text-red-500'"
                class="text-xl leading-none"
                aria-hidden="true"
            >
                {{ message.type === 'success' ? '✓' : '✕' }}
            </div>
            <div class="min-w-0 flex-1">
                <p
                    :class="message.type === 'success' ? 'text-green-800' : 'text-red-800'"
                    class="text-sm font-medium"
                >
                    {{ message.text }}
                </p>
                <ul
                    v-if="message.errors.length"
                    class="mt-2 list-inside list-disc space-y-1 text-sm text-red-800"
                >
                    <li v-for="(error, index) in message.errors" :key="`${index}-${error}`">
                        {{ error }}
                    </li>
                </ul>
            </div>
            <button
                type="button"
                class="ml-auto text-lg leading-none text-gray-400 hover:text-gray-600"
                aria-label="Dismiss message"
                @click="message = null"
            >
                ×
            </button>
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