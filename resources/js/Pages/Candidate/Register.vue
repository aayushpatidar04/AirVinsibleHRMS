<script setup>
import { useForm, Head, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const props = defineProps({
    qrUuid: String,
    branch: Object,
    form: Object,
    fields: Array,
    profileOptions: Object,
});

const resumeFile = ref(null);
const fileError = ref("");
const dragging = ref(false);

const MAX_FILE_SIZE = 5 * 1024 * 1024;

// ─── Build field map for quick lookup ───
const fieldMap = computed(() => {
    const map = {};
    props.fields.forEach((f) => {
        map[f.id] = f;
        map[f.field_name] = f;
    });
    return map;
});

// ─── Initialize form data ───
const formData = useForm(
    Object.fromEntries(
        props.fields
            .filter((f) => f.field_type !== "file")
            .map((f) => [f.field_name, f.field_type === "checkbox" ? [] : ""]),
    ),
);

// ─── Compute visible fields based on parent conditions ───
const visibleFields = computed(() => {
    return props.fields.filter((field) => {
        // No parent = always visible
        if (!field.parent_field_id) return true;

        // Find parent field
        const parentField = fieldMap.value[field.parent_field_id];
        if (!parentField) return true; // Parent missing, show anyway

        // Get current parent value
        const parentValue = formData[parentField.field_name];

        // Check show_when condition
        if (!field.show_when) return true;

        const triggerValues = field.show_when[parentField.field_name] || [];
        return triggerValues.includes(parentValue);
    });
});

// ─── Check if a specific field is visible ───
const isFieldVisible = (field) => {
    return visibleFields.value.some((f) => f.id === field.id);
};

// ─── Watch parent values and reset hidden dependent fields ───
watch(
    () => ({ ...formData }),
    (newVal, oldVal) => {
        props.fields.forEach((field) => {
            if (!field.parent_field_id || !field.show_when) return;

            const parentField = fieldMap.value[field.parent_field_id];
            if (!parentField) return;

            const parentName = parentField.field_name;
            const triggerValues = field.show_when[parentName] || [];

            // If parent value changed and no longer matches, clear this field
            if (oldVal && newVal[parentName] !== oldVal[parentName]) {
                if (!triggerValues.includes(newVal[parentName])) {
                    formData[field.field_name] =
                        field.field_type === "checkbox" ? [] : "";
                }
            }
        });
    },
    { deep: true },
);

// ─── Progress through mandatory fields ───
const isAnswered = (field) => {
    if (field.field_type === "file") return !!resumeFile.value;
    const value = formData[field.field_name];
    return Array.isArray(value)
        ? value.length > 0
        : String(value ?? "").trim() !== "";
};
const requiredFields = computed(() =>
    visibleFields.value.filter((f) => f.is_mandatory),
);
const requiredDone = computed(
    () => requiredFields.value.filter(isAnswered).length,
);
const progress = computed(() =>
    requiredFields.value.length
        ? Math.round((requiredDone.value / requiredFields.value.length) * 100)
        : 0,
);

// ─── Resume upload ───
const pickFile = (file) => {
    fileError.value = "";
    if (!file) return;
    if (!/\.(pdf|docx?)$/i.test(file.name)) {
        fileError.value = "Please upload a PDF, DOC or DOCX file.";
        return;
    }
    if (file.size > MAX_FILE_SIZE) {
        fileError.value = "This file is larger than 5 MB.";
        return;
    }
    resumeFile.value = file;
};
const onPick = (e) => {
    pickFile(e.target.files[0]);
    e.target.value = "";
};
const onDrop = (e) => {
    dragging.value = false;
    pickFile(e.dataTransfer?.files?.[0]);
};

// ─── Option styling for radio / checkbox cards ───
const optionClass = (selected) =>
    selected
        ? "border-indigo-500 bg-indigo-50 text-indigo-700"
        : "border-indigo-100 bg-white text-ink-soft hover:border-indigo-300";

const submit = () => {
    const data = new FormData();

    // Only include visible fields
    visibleFields.value.forEach((f) => {
        if (f.field_type === "file") {
            if (resumeFile.value) {
                data.append(f.field_name, resumeFile.value);
            }
        } else {
            const value = formData[f.field_name];
            if (Array.isArray(value)) {
                value.forEach((v) => data.append(`${f.field_name}[]`, v));
            } else {
                data.append(f.field_name, value ?? "");
            }
        }
    });

    router.post(route("candidate.register.store", props.qrUuid), data, {
        forceFormData: true,
        onStart: () => (formData.processing = true),
        onFinish: () => (formData.processing = false),
        onSuccess: () => {
            formData.reset();
            resumeFile.value = null;
        },
        onError: (errors) => {
            formData.errors = errors;
        },
    });
};
</script>

<template>
    <Head :title="`Register – ${branch.name}`" />

    <div class="relative min-h-screen bg-paper">
        <div
            class="absolute inset-x-0 top-0 h-80 bg-dots [mask-image:linear-gradient(to_bottom,black,transparent)]"
            aria-hidden="true"
        ></div>

        <div class="relative mx-auto w-full max-w-3xl px-4 py-8 sm:py-12">
            <!-- Brand -->
            <div class="flex flex-col items-center text-center">
                <img
                    src="/assets/images/logo.png"
                    alt="Airvincible HRMS"
                    class="h-14 w-auto sm:h-16"
                />
                <p class="mt-2 font-display text-lg font-bold tracking-tight">
                    Airvincible <span class="text-indigo-600">HRMS</span>
                </p>

                <h1
                    class="mt-8 font-display text-2xl font-bold tracking-tight sm:text-3xl"
                >
                    Walk-In Registration
                </h1>
                <span
                    class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-white px-3.5 py-1.5 text-sm font-medium text-ink-soft shadow-soft"
                >
                    <svg
                        class="h-4 w-4 text-indigo-500"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    {{ branch.name
                    }}<span v-if="branch.city">, {{ branch.city }}</span>
                </span>
            </div>

            <!-- Form card -->
            <div
                class="surface mt-8 animate-card-in overflow-hidden shadow-lift"
            >
                <div
                    class="border-b border-indigo-100 bg-indigo-50/60 px-6 py-5"
                >
                    <h2 class="font-display text-lg font-bold">
                        {{ form.name }}
                    </h2>
                    <p
                        v-if="form.description"
                        class="mt-1 text-sm text-ink-soft"
                    >
                        {{ form.description }}
                    </p>

                    <div v-if="requiredFields.length" class="mt-4">
                        <div
                            class="flex items-center justify-between text-xs font-medium text-ink-soft"
                        >
                            <span
                                >{{ requiredDone }} of
                                {{ requiredFields.length }} required fields
                                done</span
                            >
                            <span>{{ progress }}%</span>
                        </div>
                        <div
                            class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-white"
                        >
                            <div
                                class="h-full rounded-full bg-indigo-600 transition-[width] duration-500 ease-out"
                                :style="{ width: progress + '%' }"
                            />
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <TransitionGroup
                        name="field-slide"
                        tag="div"
                        class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2"
                    >
                        <div
                            v-for="field in visibleFields"
                            :key="field.id"
                            :class="[
                                'transition-all duration-300',
                                field.parent_field_id
                                    ? 'rounded-r-xl border-l-4 border-l-violet-300 bg-violet-50/40 py-2 pl-4 pr-3'
                                    : '',
                            ]"
                            :style="
                                field.field_type === 'textarea' ||
                                field.field_type === 'file'
                                    ? 'grid-column: 1 / -1;'
                                    : ''
                            "
                        >
                            <label class="label">
                                {{ field.field_label }}
                                <span
                                    v-if="field.is_mandatory"
                                    class="text-red-500"
                                    >*</span
                                >
                                <span
                                    v-if="field.parent_field_id"
                                    class="ml-1 text-[11px] font-normal text-violet-600"
                                >
                                    (shown when
                                    {{
                                        fieldMap[field.parent_field_id]
                                            ?.field_label
                                    }}
                                    is
                                    {{
                                        field.show_when?.[
                                            fieldMap[field.parent_field_id]
                                                ?.field_name
                                        ]?.join(" or ")
                                    }})
                                </span>
                            </label>

                            <!-- Text / Email / Number / Date -->
                            <input
                                v-if="
                                    [
                                        'text',
                                        'email',
                                        'number',
                                        'date',
                                    ].includes(field.field_type)
                                "
                                v-model="formData[field.field_name]"
                                :type="field.field_type"
                                :placeholder="field.field_placeholder ?? ''"
                                class="input-field"
                                :class="{
                                    '!border-red-400':
                                        formData.errors[field.field_name],
                                }"
                            />

                            <!-- Phone -->
                            <input
                                v-else-if="field.field_type === 'phone'"
                                v-model="formData[field.field_name]"
                                type="tel"
                                :placeholder="field.field_placeholder ?? ''"
                                class="input-field"
                                :class="{
                                    '!border-red-400':
                                        formData.errors[field.field_name],
                                }"
                            />

                            <!-- Textarea -->
                            <textarea
                                v-else-if="field.field_type === 'textarea'"
                                v-model="formData[field.field_name]"
                                :placeholder="field.field_placeholder ?? ''"
                                rows="3"
                                class="input-field"
                                :class="{
                                    '!border-red-400':
                                        formData.errors[field.field_name],
                                }"
                            />

                            <!-- Dropdown -->
                            <select
                                v-else-if="field.field_type === 'dropdown'"
                                v-model="formData[field.field_name]"
                                class="input-field"
                                :class="{
                                    '!border-red-400':
                                        formData.errors[field.field_name],
                                }"
                            >
                                <option value="">-- Select --</option>
                                <option
                                    v-for="(label, key) in field.options ?? {}"
                                    :key="label"
                                    :value="label"
                                >
                                    {{ label }}
                                </option>
                            </select>

                            <!-- Radio -->
                            <div
                                v-else-if="field.field_type === 'radio'"
                                class="mt-1 flex flex-wrap gap-2"
                            >
                                <label
                                    v-for="(label, key) in field.options ?? {}"
                                    :key="key"
                                    :class="[
                                        optionClass(
                                            formData[field.field_name] === key,
                                        ),
                                        'flex cursor-pointer items-center gap-2 rounded-xl border px-3.5 py-2 text-sm font-medium transition',
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        :name="field.field_name"
                                        :value="key"
                                        v-model="formData[field.field_name]"
                                        class="border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    {{ label }}
                                </label>
                            </div>

                            <!-- Checkbox -->
                            <div
                                v-else-if="field.field_type === 'checkbox'"
                                class="mt-1 flex flex-wrap gap-2"
                            >
                                <label
                                    v-for="(label, key) in field.options ?? {}"
                                    :key="key"
                                    :class="[
                                        optionClass(
                                            formData[field.field_name].includes(
                                                key,
                                            ),
                                        ),
                                        'flex cursor-pointer items-center gap-2 rounded-xl border px-3.5 py-2 text-sm font-medium transition',
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="key"
                                        v-model="formData[field.field_name]"
                                        class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    {{ label }}
                                </label>
                            </div>

                            <!-- File Upload -->
                            <div
                                v-else-if="field.field_type === 'file'"
                                class="mt-1"
                            >
                                <label
                                    :class="[
                                        dragging
                                            ? 'border-indigo-500 bg-indigo-50'
                                            : 'border-indigo-200 bg-paper hover:border-indigo-400 hover:bg-indigo-50/60',
                                        'flex w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-7 text-center transition',
                                    ]"
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    @drop.prevent="onDrop"
                                >
                                    <span
                                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-soft"
                                    >
                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                                            />
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-sm text-ink-soft">
                                        <span
                                            class="font-semibold text-indigo-600"
                                            >Click to upload</span
                                        >
                                        or drag and drop
                                    </p>
                                    <p class="mt-1 text-xs text-ink-mute">
                                        PDF, DOC, DOCX (max 5 MB)
                                    </p>
                                    <input
                                        type="file"
                                        class="hidden"
                                        accept=".pdf,.doc,.docx"
                                        @change="onPick"
                                    />
                                </label>

                                <div
                                    v-if="resumeFile"
                                    class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5"
                                >
                                    <span
                                        class="flex min-w-0 items-center gap-2 text-sm font-medium text-emerald-700"
                                    >
                                        <svg
                                            class="h-4 w-4 shrink-0"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <span class="truncate">{{
                                            resumeFile.name
                                        }}</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="shrink-0 text-xs font-semibold text-emerald-700 hover:text-emerald-900"
                                        @click="resumeFile = null"
                                    >
                                        Remove
                                    </button>
                                </div>
                                <p v-if="fileError" class="error">
                                    {{ fileError }}
                                </p>
                            </div>

                            <p
                                v-if="formData.errors[field.field_name]"
                                class="error"
                                role="alert"
                            >
                                {{ formData.errors[field.field_name] }}
                            </p>
                        </div>
                    </TransitionGroup>

                    <p
                        v-if="visibleFields.length === 0"
                        class="py-8 text-center text-sm text-ink-mute"
                    >
                        No fields available.
                    </p>

                    <!-- Cooldown / general error -->
                    <div
                        v-if="formData.errors.cooldown"
                        class="mb-4 mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4"
                        role="alert"
                    >
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                            />
                        </svg>
                        <p class="text-sm font-medium text-red-700">
                            {{ formData.errors.cooldown }}
                        </p>
                    </div>

                    <div class="mt-6">
                        <button
                            type="submit"
                            :disabled="formData.processing"
                            class="btn-primary w-full !py-3.5 !text-base"
                        >
                            <svg
                                v-if="formData.processing"
                                class="h-4 w-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                            {{
                                formData.processing
                                    ? "Registering..."
                                    : "Submit Registration"
                            }}
                        </button>
                    </div>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-ink-mute">
                Your information is securely stored and used only for
                recruitment purposes.
            </p>
        </div>
    </div>
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
