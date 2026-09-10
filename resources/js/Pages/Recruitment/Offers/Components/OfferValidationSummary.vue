<script setup>
import { computed } from "vue";
import {
    AlertCircle,
    ChevronRight,
    X,
} from "lucide-vue-next";

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },

    show: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["close"]);

const errorItems = computed(() => {
    return Object.entries(props.errors ?? {}).flatMap(
        ([field, message]) => {
            if (Array.isArray(message)) {
                return message.map((item) => ({
                    field,
                    message: item,
                    label: formatFieldName(field),
                }));
            }

            return [
                {
                    field,
                    message,
                    label: formatFieldName(field),
                },
            ];
        },
    );
});

function formatFieldName(field) {
    const salaryMatch = field.match(
        /^salary_components\.(\d+)\.(.+)$/,
    );

    if (salaryMatch) {
        const rowNumber =
            Number(salaryMatch[1]) + 1;

        const fieldName = humanize(
            salaryMatch[2],
        );

        return `Salary Component ${rowNumber}: ${fieldName}`;
    }

    return humanize(field);
}

function humanize(value) {
    return String(value)
        .replaceAll(".", " → ")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        );
}

function scrollToField(field) {
    const escapedField =
        typeof CSS !== "undefined" &&
        CSS.escape
            ? CSS.escape(field)
            : field.replace(
                  /([.[\]#:$])/g,
                  "\\$1",
              );

    const target =
        document.querySelector(
            `[data-error-key="${escapedField}"]`,
        ) ??
        document.querySelector(
            `[name="${escapedField}"]`,
        );

    if (!target) {
        return;
    }

    target.scrollIntoView({
        behavior: "smooth",
        block: "center",
    });

    window.setTimeout(() => {
        target.focus?.();
    }, 400);
}
</script>

<template>
    <section
        v-if="
            show &&
            errorItems.length
        "
        id="offer-validation-summary"
        class="overflow-hidden rounded-2xl border border-red-300 bg-red-50 shadow-sm"
    >
        <div
            class="flex items-start justify-between gap-4 border-b border-red-200 px-5 py-4"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700"
                >
                    <AlertCircle
                        class="h-5 w-5"
                    />
                </div>

                <div>
                    <h2
                        class="font-semibold text-red-900"
                    >
                        Offer could not be saved
                    </h2>

                    <p
                        class="mt-1 text-sm text-red-700"
                    >
                        Please correct
                        {{ errorItems.length }}
                        invalid
                        {{
                            errorItems.length === 1
                                ? "field"
                                : "fields"
                        }}
                        listed below.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="rounded-lg p-2 text-red-500 transition hover:bg-red-100 hover:text-red-800"
                title="Hide errors"
                @click="emit('close')"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <div class="px-5 py-4">
            <ul class="space-y-2">
                <li
                    v-for="(
                        error,
                        index
                    ) in errorItems"
                    :key="`${error.field}-${index}`"
                >
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 rounded-xl border border-red-200 bg-white px-4 py-3 text-left transition hover:border-red-300 hover:bg-red-50"
                        @click="
                            scrollToField(
                                error.field,
                            )
                        "
                    >
                        <span
                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700"
                        >
                            {{ index + 1 }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span
                                class="block text-sm font-semibold text-red-900"
                            >
                                {{ error.label }}
                            </span>

                            <span
                                class="mt-0.5 block text-sm text-red-700"
                            >
                                {{ error.message }}
                            </span>
                        </span>

                        <ChevronRight
                            class="mt-1 h-4 w-4 shrink-0 text-red-400"
                        />
                    </button>
                </li>
            </ul>
        </div>
    </section>
</template>