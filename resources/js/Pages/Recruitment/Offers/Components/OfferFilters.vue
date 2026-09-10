<script setup>
import { computed, reactive, watch } from "vue";

import {
    CalendarRange,
    Filter,
    RotateCcw,
    Search,
    SlidersHorizontal,
} from "lucide-vue-next";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },

    options: {
        type: Object,
        default: () => ({}),
    },

    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["apply", "reset"]);

const form = reactive({
    search: props.filters.search ?? "",
    status: props.filters.status ?? "",
    approval_status: props.filters.approval_status ?? "",
    department: props.filters.department ?? "",
    designation: props.filters.designation ?? "",
    employment_type: props.filters.employment_type ?? "",
    generated_by: props.filters.generated_by ?? "",
    valid_from: props.filters.valid_from ?? "",
    valid_to: props.filters.valid_to ?? "",
    created_from: props.filters.created_from ?? "",
    created_to: props.filters.created_to ?? "",
    version: props.filters.version ?? "",
    per_page: Number(props.filters.per_page ?? 25),
});

const advancedOpen = reactive({
    value: false,
});

const activeFilterCount = computed(() => {
    return Object.entries(form).filter(([key, value]) => {
        if (key === "per_page") {
            return false;
        }

        return value !== "" && value !== null && value !== undefined;
    }).length;
});

function normalizeOptions(source) {
    if (!source) {
        return [];
    }

    if (Array.isArray(source)) {
        return source.map((item) => {
            if (typeof item === "string") {
                return {
                    value: item,
                    label: formatLabel(item),
                };
            }

            return {
                value: item.value ?? item.id ?? item.key,

                label: item.label ?? item.name ?? item.title ?? item.value,
            };
        });
    }

    return Object.entries(source).map(([value, label]) => ({
        value,
        label:
            typeof label === "object"
                ? (label.label ?? label.name ?? value)
                : label,
    }));
}

function formatLabel(value) {
    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function submit() {
    emit("apply", {
        ...form,
    });
}

function reset() {
    Object.assign(form, {
        search: "",
        status: "",
        approval_status: "",
        department: "",
        designation: "",
        employment_type: "",
        generated_by: "",
        valid_from: "",
        valid_to: "",
        created_from: "",
        created_to: "",
        version: "",
        per_page: 25,
    });

    emit("reset");
}

let searchTimer = null;

watch(
    () => form.search,
    () => {
        window.clearTimeout(searchTimer);

        searchTimer = window.setTimeout(submit, 500);
    },
);
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-4 px-5 py-5 lg:flex-row lg:items-end">
                <div class="min-w-0 flex-1">
                    <label
                        for="offer-search"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        Search offers
                    </label>

                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400"
                        />

                        <input
                            id="offer-search"
                            v-model="form.search"
                            type="search"
                            class="block w-full rounded-xl border-gray-300 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search offer number, candidate, email, phone or designation"
                        />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:w-auto lg:grid-cols-3">
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Offer Status
                        </label>

                        <select
                            v-model="form.status"
                            class="block w-full min-w-44 rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="submit"
                        >
                            <option value="">All statuses</option>

                            <option
                                v-for="option in normalizeOptions(
                                    options.statuses,
                                )"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Approval
                        </label>

                        <select
                            v-model="form.approval_status"
                            class="block w-full min-w-44 rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="submit"
                        >
                            <option value="">All approvals</option>

                            <option
                                v-for="option in normalizeOptions(
                                    options.approval_statuses,
                                )"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="button"
                            class="relative inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                            @click="advancedOpen.value = !advancedOpen.value"
                        >
                            <SlidersHorizontal class="h-4 w-4" />

                            More Filters

                            <span
                                v-if="activeFilterCount > 2"
                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700"
                            >
                                {{ activeFilterCount - 2 }}
                            </span>
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-white text-gray-500 shadow-sm hover:bg-gray-50 hover:text-gray-800"
                            title="Reset filters"
                            @click="reset"
                        >
                            <RotateCcw class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="advancedOpen.value"
                class="border-t border-gray-200 bg-gray-50/70 px-5 py-5"
            >
                <div class="mb-4 flex items-center gap-2">
                    <Filter class="h-4 w-4 text-gray-500" />

                    <h3 class="text-sm font-semibold text-gray-800">
                        Advanced Filters
                    </h3>
                </div>

                <div
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Department
                        </label>

                        <select
                            v-model="form.department"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All departments</option>

                            <option
                                v-for="department in options.departments"
                                :value="department"
                            >
                                {{ department }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Designation
                        </label>

                        <select
                            v-model="form.designation"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All designations</option>

                            <option
                                v-for="designation in options.designations"
                                :value="designation"
                            >
                                {{ designation }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Employment Type
                        </label>

                        <select
                            v-model="form.employment_type"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All types</option>

                            <option
                                v-for="option in normalizeOptions(
                                    options.employment_types,
                                )"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Created By
                        </label>

                        <select
                            v-model="form.generated_by"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All users</option>

                            <option
                                v-for="option in normalizeOptions(
                                    options.generators,
                                )"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Created From
                        </label>

                        <input
                            v-model="form.created_from"
                            type="date"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Created To
                        </label>

                        <input
                            v-model="form.created_to"
                            type="date"
                            :min="form.created_from || undefined"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Valid From
                        </label>

                        <input
                            v-model="form.valid_from"
                            type="date"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Valid To
                        </label>

                        <input
                            v-model="form.valid_to"
                            type="date"
                            :min="form.valid_from || undefined"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Version
                        </label>

                        <input
                            v-model.number="form.version"
                            type="number"
                            min="1"
                            step="1"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Any version"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Rows per page
                        </label>

                        <select
                            v-model.number="form.per_page"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        @click="reset"
                    >
                        <RotateCcw class="h-4 w-4" />

                        Clear
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="loading"
                    >
                        <CalendarRange class="h-4 w-4" />

                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>