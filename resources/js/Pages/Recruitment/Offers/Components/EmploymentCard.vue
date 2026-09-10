<script setup>
import { BriefcaseBusiness, Building2 } from "lucide-vue-next";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },

    options: {
        type: Object,
        default: () => ({}),
    },

    disabled: {
        type: Boolean,
        default: false,
    },
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
                    label: item,
                };
            }

            return {
                value: item.value ?? item.id ?? item.key,

                label: item.label ?? item.name ?? item.title,
            };
        });
    }

    return Object.entries(source).map(([value, label]) => ({
        value,
        label,
    }));
}

function errorFor(field) {
    return props.form.errors?.[field];
}

function fieldClasses(field) {
    return [
        "block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500",

        errorFor(field)
            ? "border-red-400 focus:border-red-500 focus:ring-red-500"
            : "",
    ];
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700"
                >
                    <BriefcaseBusiness class="h-5 w-5" />
                </div>

                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        Employment Details
                    </h2>

                    <p class="text-sm text-gray-500">
                        Define the candidate’s role, work location and joining
                        conditions.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-3">
            <div>
                <label
                    for="designation"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Designation
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="designation"
                    v-model="form.designation"
                    :class="fieldClasses('designation')"
                    :disabled="disabled"
                    placeholder="Enter designation"
                />

                <p
                    v-if="errorFor('designation')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("designation") }}
                </p>
            </div>

            <div>
                <label
                    for="department"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Department
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="department"
                    v-model="form.department"
                    :class="fieldClasses('department')"
                    :disabled="disabled"
                    placeholder="Enter department"
                />

                <p
                    v-if="errorFor('department')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("department") }}
                </p>
            </div>

            <div>
                <label
                    for="branch_id"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Branch
                </label>

                <select
                    id="branch_id"
                    v-model="form.branch_id"
                    :class="fieldClasses('branch_id')"
                    :disabled="disabled"
                >
                    <option value="">Select branch</option>

                    <option
                        v-for="option in normalizeOptions(options.branches)"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>

                <p
                    v-if="errorFor('branch_id')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("branch_id") }}
                </p>
            </div>

            <div>
                <label
                    for="reporting_manager_id"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Reporting Manager
                </label>

                <select
                    id="reporting_manager_id"
                    v-model="form.reporting_manager_id"
                    :class="fieldClasses('reporting_manager_id')"
                    :disabled="disabled"
                >
                    <option value="">Select reporting manager</option>

                    <option
                        v-for="option in normalizeOptions(
                            options.reporting_managers,
                        )"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>

                <p
                    v-if="errorFor('reporting_manager_id')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("reporting_manager_id") }}
                </p>
            </div>

            <div>
                <label
                    for="employment_type"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Employment Type
                    <span class="text-red-500"> * </span>
                </label>

                <select
                    id="employment_type"
                    v-model="form.employment_type"
                    :class="fieldClasses('employment_type')"
                    :disabled="disabled"
                >
                    <option value="">Select employment type</option>

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

                <p
                    v-if="errorFor('employment_type')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("employment_type") }}
                </p>
            </div>

            <div>
                <label
                    for="probation_months"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Probation Period
                </label>

                <div class="relative">
                    <input
                        id="probation_months"
                        v-model.number="form.probation_months"
                        type="number"
                        min="0"
                        max="60"
                        step="1"
                        :class="fieldClasses('probation_months')"
                        class="pr-20"
                        :disabled="disabled"
                        placeholder="3"
                    />

                    <span
                        class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-gray-500"
                    >
                        months
                    </span>
                </div>

                <p
                    v-if="errorFor('probation_months')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("probation_months") }}
                </p>
            </div>

            <div>
                <label
                    for="joining_date"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Joining Date
                    <span class="text-red-500"> * </span>
                </label>

                <input
                    id="joining_date"
                    v-model="form.joining_date"
                    type="date"
                    :class="fieldClasses('joining_date')"
                    :disabled="disabled"
                />

                <p
                    v-if="errorFor('joining_date')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("joining_date") }}
                </p>
            </div>

            <div>
                <label
                    for="reporting_time"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Reporting Time
                </label>

                <input
                    id="reporting_time"
                    v-model="form.reporting_time"
                    type="time"
                    :class="fieldClasses('reporting_time')"
                    :disabled="disabled"
                />

                <p
                    v-if="errorFor('reporting_time')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("reporting_time") }}
                </p>
            </div>

            <div>
                <label
                    for="work_location"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Work Location
                    <span class="text-red-500"> * </span>
                </label>

                <div class="relative">
                    <Building2
                        class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                    />

                    <input
                        id="work_location"
                        v-model="form.work_location"
                        type="text"
                        :class="fieldClasses('work_location')"
                        class="pl-9"
                        :disabled="disabled"
                        placeholder="Indore, Madhya Pradesh"
                    />
                </div>

                <p
                    v-if="errorFor('work_location')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errorFor("work_location") }}
                </p>
            </div>

            <div class="sm:col-span-2 xl:col-span-3">
                <label
                    for="process"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Process / Business Unit
                </label>

                <input
                    id="process"
                    v-model="form.process_name"
                    type="text"
                    :class="fieldClasses('process')"
                    :disabled="disabled"
                    placeholder="Example: Customer Support, Software Development"
                />

                <p v-if="errorFor('process')" class="mt-1 text-xs text-red-600">
                    {{ errorFor("process") }}
                </p>
            </div>
        </div>
    </section>
</template>
