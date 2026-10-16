<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: String,
    type: { type: String, default: 'text' },
    error: String,
    placeholder: String,
    autocomplete: String,
    required: Boolean,
    autofocus: Boolean,
})
defineEmits(['update:modelValue'])

const id = `f-${Math.random().toString(36).slice(2, 8)}`
const show = ref(false)
const inputType = computed(() => (props.type === 'password' && show.value ? 'text' : props.type))
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <label :for="id" class="label">{{ label }}</label>
            <slot name="hint" />
        </div>
        <div class="relative">
            <input :id="id" :type="inputType" :value="modelValue" :placeholder="placeholder" :autocomplete="autocomplete"
                :required="required" :autofocus="autofocus" class="input-field" :class="[
                    type === 'password' ? 'pr-11' : '',
                    error ? '!border-red-400 focus:!ring-red-300' : '',
                ]" :aria-invalid="!!error" @input="$emit('update:modelValue', $event.target.value)" />
            <button v-if="type === 'password'" type="button" tabindex="-1"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-ink-mute hover:text-indigo-600 transition"
                :aria-label="show ? 'Hide password' : 'Show password'" @click="show = !show">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path v-if="!show" d="M2.04 12.32a1 1 0 010-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 010 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path v-else d="M3.98 8.22A10.48 10.48 0 001.93 12c1.29 3.88 4.95 6.75 10.07 6.75 1.6 0 3.1-.28 4.45-.8M6.23 6.23A10.45 10.45 0 0112 5.25c5.12 0 8.78 2.87 10.07 6.75a10.52 10.52 0 01-4.3 5.52M6.23 6.23L3 3m3.23 3.23l3.65 3.65m7.89 7.89L21 21m-3.23-3.23l-3.65-3.65m0 0a3 3 0 10-4.24-4.24m4.24 4.24L9.88 9.88" />
                </svg>
            </button>
        </div>
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1">
            <p v-if="error" class="error" role="alert">{{ error }}</p>
        </Transition>
    </div>
</template>