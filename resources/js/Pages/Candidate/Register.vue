<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { useForm, Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const props = defineProps({ qrUuid: String, branch: Object, form: Object, fields: Array, profileOptions: Object })

const resumeFile = ref(null)

// ─── Build field map for quick lookup ───
const fieldMap = computed(() => {
    const map = {}
    props.fields.forEach(f => {
        map[f.id] = f
        map[f.field_name] = f
    })
    return map
})

// ─── Initialize form data ───
const formData = useForm(
    Object.fromEntries(
        props.fields
            .filter(f => f.field_type !== 'file')
            .map(f => [f.field_name, f.field_type === 'checkbox' ? [] : ''])
    )
)

// ─── Compute visible fields based on parent conditions ───
const visibleFields = computed(() => {
    return props.fields.filter(field => {
        // No parent = always visible
        if (!field.parent_field_id) return true

        // Find parent field
        const parentField = fieldMap.value[field.parent_field_id]
        if (!parentField) return true // Parent missing, show anyway

        // Get current parent value
        const parentValue = formData[parentField.field_name]

        // Check show_when condition
        if (!field.show_when) return true

        const triggerValues = field.show_when[parentField.field_name] || []
        return triggerValues.includes(parentValue)
    })
})

// ─── Check if a specific field is visible ───
const isFieldVisible = (field) => {
    return visibleFields.value.some(f => f.id === field.id)
}

// ─── Watch parent values and reset hidden dependent fields ───
watch(() => ({ ...formData }), (newVal, oldVal) => {
    props.fields.forEach(field => {
        if (!field.parent_field_id || !field.show_when) return

        const parentField = fieldMap.value[field.parent_field_id]
        if (!parentField) return

        const parentName = parentField.field_name
        const triggerValues = field.show_when[parentName] || []

        // If parent value changed and no longer matches, clear this field
        if (oldVal && newVal[parentName] !== oldVal[parentName]) {
            if (!triggerValues.includes(newVal[parentName])) {
                formData[field.field_name] = field.field_type === 'checkbox' ? [] : ''
            }
        }
    })
}, { deep: true })

const submit = () => {
    const data = new FormData()

    // Only include visible fields
    visibleFields.value.forEach(f => {
        if (f.field_type === 'file') {
            if (resumeFile.value) {
                data.append(f.field_name, resumeFile.value)
            }
        } else {
            const value = formData[f.field_name]
            if (Array.isArray(value)) {
                value.forEach(v => data.append(`${f.field_name}[]`, v))
            } else {
                data.append(f.field_name, value ?? '')
            }
        }
    })

    router.post(route('candidate.register.store', props.qrUuid), data, {
        forceFormData: true,
        onStart: () => formData.processing = true,
        onFinish: () => formData.processing = false,
        onSuccess: () => {
            formData.reset()
            resumeFile.value = null
        },
        onError: (errors) => {
            formData.errors = errors
        }
    })
}
</script>

<template>
    <GuestLayout>

        <Head :title="`Register – ${branch.name}`" />

        <div class="sm:mx-auto sm:w-full sm:max-w-3xl px-4">
            <!-- Header -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-indigo-600 text-white text-3xl mb-4 shadow-lg">
                    ⚡</div>
                <h1 class="text-2xl font-bold text-gray-900">Walk-In Registration</h1>
                <p class="text-sm text-gray-500 mt-1">{{ branch.name }} · {{ branch.city }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Form Header -->
                <div class="bg-indigo-600 px-6 py-4">
                    <h2 class="text-white font-semibold">{{ form.name }}</h2>
                    <p v-if="form.description" class="text-indigo-200 text-xs mt-1">{{ form.description }}</p>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <TransitionGroup name="field-slide" tag="div"
                        class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">
                        <div v-for="field in visibleFields" :key="field.id" :class="[
                            'transition-all duration-300',
                            field.parent_field_id ? 'border-l-4 border-l-amber-400 pl-3 bg-amber-50/30 rounded-r-lg py-2 px-4' : ''
                        ]"
                            :style="field.field_type === 'textarea' || field.field_type === 'file' ? 'grid-column: 1 / -1;' : ''">

                            <label class="label">
                                {{ field.field_label }}
                                <span v-if="field.is_mandatory" class="text-red-500">*</span>
                                <span v-if="field.parent_field_id" class="text-[10px] text-amber-600 ml-1 font-normal">
                                    (shown when {{ fieldMap[field.parent_field_id]?.field_label }} is {{
                                        field.show_when?.[fieldMap[field.parent_field_id]?.field_name]?.join(' or ') }})
                                </span>
                            </label>

                            <!-- Text / Email / Number / Date -->
                            <input v-if="['text', 'email', 'number', 'date'].includes(field.field_type)"
                                v-model="formData[field.field_name]" :type="field.field_type"
                                :placeholder="field.field_placeholder ?? ''" class="input-field" />

                            <!-- Phone -->
                            <input v-else-if="field.field_type === 'phone'" v-model="formData[field.field_name]"
                                type="tel" :placeholder="field.field_placeholder ?? ''" class="input-field" />

                            <!-- Textarea -->
                            <textarea v-else-if="field.field_type === 'textarea'" v-model="formData[field.field_name]"
                                :placeholder="field.field_placeholder ?? ''" rows="3" class="input-field" />

                            <!-- Dropdown -->
                            <select v-else-if="field.field_type === 'dropdown'" v-model="formData[field.field_name]"
                                class="input-field">
                                <option value="">-- Select --</option>
                                <option v-for="(label, key) in (field.options ?? {})" :key="label" :value="label">{{
                                    label }}
                                </option>
                            </select>

                            <!-- Radio -->
                            <div v-else-if="field.field_type === 'radio'" class="space-y-2 mt-1">
                                <label v-for="(label, key) in (field.options ?? {})" :key="key"
                                    class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" :name="field.field_name" :value="key"
                                        v-model="formData[field.field_name]" class="text-indigo-600" />
                                    <span class="text-sm text-gray-700">{{ label }}</span>
                                </label>
                            </div>

                            <!-- Checkbox -->
                            <div v-else-if="field.field_type === 'checkbox'" class="space-y-2 mt-1">
                                <label v-for="(label, key) in (field.options ?? {})" :key="key"
                                    class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :value="key" v-model="formData[field.field_name]"
                                        class="rounded text-indigo-600" />
                                    <span class="text-sm text-gray-700">{{ label }}</span>
                                </label>
                            </div>

                            <!-- File Upload -->
                            <div v-else-if="field.field_type === 'file'" class="mt-1">
                                <div class="flex items-center justify-center w-full">
                                    <label
                                        class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <p class="text-2xl mb-2">📎</p>
                                            <p class="text-sm text-gray-500">
                                                <span class="font-medium text-indigo-600">Click to upload</span> or drag
                                                &
                                                drop
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX (max 5 MB)</p>
                                            <p v-if="resumeFile" class="text-xs text-green-600 font-medium mt-1">✓ {{
                                                resumeFile.name }}</p>
                                        </div>
                                        <input type="file" class="hidden" accept=".pdf,.doc,.docx"
                                            @change="resumeFile = $event.target.files[0]" />
                                    </label>
                                </div>
                            </div>

                            <p v-if="formData.errors[field.field_name]" class="error">
                                {{ formData.errors[field.field_name] }}
                            </p>
                        </div>
                    </TransitionGroup>

                    <p v-if="visibleFields.length === 0" class="text-center text-gray-400 py-8 text-sm">
                        No fields available.
                    </p>

                    <!-- In your public registration form, add error display -->
                    <div v-if="formData.errors.cooldown" class="bg-red-50 border border-red-200 rounded-lg p-4 mt-2 mb-4">
                        <p class="text-red-700 text-sm font-medium">{{ formData.errors.cooldown }}</p>
                    </div>

                    <div class="mt-6">
                        <button type="submit" :disabled="formData.processing"
                            class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-6 rounded-xl transition disabled:opacity-60 shadow-md text-sm">
                            <svg v-if="formData.processing" class="animate-spin h-4 w-4" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ formData.processing ? 'Registering...' : 'Submit Registration' }}
                        </button>
                    </div>
                </form>
            </div>

            <p class="text-center text-xs text-gray-400 mt-6">
                Your information is securely stored and used only for recruitment purposes.
            </p>
        </div>
    </GuestLayout>
</template>

<style scoped>
.field-slide-enter-active,
.field-slide-leave-active {
    transition: all 0.3s ease;
}

.field-slide-enter-from,
.field-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>