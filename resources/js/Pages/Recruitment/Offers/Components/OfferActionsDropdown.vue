<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";

import {
    EllipsisVertical,
    FileText,
    Pencil,
    RefreshCw,
    Send,
    Trash2,
} from "lucide-vue-next";

defineProps({
    offer: {
        type: Object,
        required: true,
    },

    permissions: {
        type: Object,
        default: () => ({}),
    },
});

defineEmits(["edit", "revision", "pdf", "send", "delete"]);

const open = ref(false);
const container = ref(null);

function closeOnOutsideClick(event) {
    if (!container.value?.contains(event.target)) {
        open.value = false;
    }
}

function closeOnEscape(event) {
    if (event.key === "Escape") {
        open.value = false;
    }
}

function perform(emit, action) {
    open.value = false;
    emit(action);
}

onMounted(() => {
    document.addEventListener("click", closeOnOutsideClick);

    document.addEventListener("keydown", closeOnEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", closeOnOutsideClick);

    document.removeEventListener("keydown", closeOnEscape);
});
</script>

<template>
    <div ref="container" class="relative">
        <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-800"
            aria-label="Offer actions"
            @click.stop="open = !open"
        >
            <EllipsisVertical class="h-5 w-5" />
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-40 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl"
            @click.stop
        >
            <button
                v-if="permissions.update"
                type="button"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50"
                @click="perform($emit, 'edit')"
            >
                <Pencil class="h-4 w-4 text-gray-400" />

                Edit Offer
            </button>

            <button
                v-if="permissions.create_revision"
                type="button"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50"
                @click="perform($emit, 'revision')"
            >
                <RefreshCw class="h-4 w-4 text-gray-400" />

                Create Revision
            </button>

            <button
                v-if="permissions.generate_pdf"
                type="button"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50"
                @click="perform($emit, 'pdf')"
            >
                <FileText class="h-4 w-4 text-gray-400" />

                Generate PDF
            </button>

            <button
                v-if="permissions.send"
                type="button"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50"
                @click="perform($emit, 'send')"
            >
                <Send class="h-4 w-4 text-gray-400" />

                Send Offer
            </button>

            <div
                v-if="
                    permissions.delete &&
                    (permissions.update ||
                        permissions.create_revision ||
                        permissions.generate_pdf ||
                        permissions.send)
                "
                class="my-1 border-t border-gray-100"
            />

            <button
                v-if="permissions.delete"
                type="button"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"
                @click="perform($emit, 'delete')"
            >
                <Trash2 class="h-4 w-4" />

                Delete Offer
            </button>

            <p
                v-if="
                    !permissions.update &&
                    !permissions.create_revision &&
                    !permissions.generate_pdf &&
                    !permissions.send &&
                    !permissions.delete
                "
                class="px-4 py-3 text-sm text-gray-500"
            >
                No actions available
            </p>
        </div>
    </div>
</template>
