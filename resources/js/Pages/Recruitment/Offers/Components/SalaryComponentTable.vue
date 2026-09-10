<script setup>
import {
    computed,
    nextTick,
    ref,
} from "vue";

import {
    ChevronDown,
    ChevronUp,
    Copy,
    GripVertical,
    Plus,
    Trash2,
} from "lucide-vue-next";

import useOfferSalary, {
    SALARY_CALCULATION_TYPES,
    SALARY_COMPONENT_TYPES,
    SALARY_FREQUENCIES,
} from "@/Composables/Recruitment/useOfferSalary";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },

    errors: {
        type: Object,
        default: () => ({}),
    },

    options: {
        type: Object,
        default: () => ({}),
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    currency: {
        type: String,
        default: "INR",
    },
});

const emit = defineEmits([
    "update:modelValue",
]);

const expandedRows = ref(new Set());

const components = computed({
    get() {
        return props.modelValue ?? [];
    },

    set(value) {
        emit("update:modelValue", value);
    },
});

const {
    calculatedComponents,
    formatCurrency,
} = useOfferSalary(components);

const componentTypeOptions = computed(() => {
    return normalizeOptions(
        props.options.salary_component_types,
        {
            earning: "Earning",
            deduction: "Deduction",
            employer_contribution:
                "Employer Contribution",
        },
    );
});

const calculationTypeOptions = computed(
    () => {
        return normalizeOptions(
            props.options.calculation_types,
            {
                fixed: "Fixed Amount",
                percentage: "Percentage",
            },
        );
    },
);

const frequencyOptions = computed(() => {
    return normalizeOptions(
        props.options.salary_frequencies,
        {
            monthly: "Monthly",
            quarterly: "Quarterly",
            half_yearly: "Half Yearly",
            annual: "Annual",
            one_time: "One Time",
        },
    );
});

const percentageBaseOptions = computed(
    () => {
        return components.value.map(
            (component, index) => ({
                value: component.row_key,
                label:
                    component.component_name ||
                    `Component ${index + 1}`,
            }),
        );
    },
);

function normalizeOptions(
    source,
    fallback,
) {
    const options =
        source &&
        Object.keys(source).length > 0
            ? source
            : fallback;

    return Object.entries(options).map(
        ([value, label]) => ({
            value,
            label,
        }),
    );
}

function createRowKey() {
    return `component-${Date.now()}-${Math.random()
        .toString(36)
        .slice(2, 9)}`;
}

function createComponent(
    overrides = {},
) {
    return {
        id: null,
        row_key: createRowKey(),

        component_name: "",

        component_type:
            SALARY_COMPONENT_TYPES.EARNING,

        calculation_type:
            SALARY_CALCULATION_TYPES.FIXED,

        percentage_of_component_id:
            null,

        percentage_of_row_key:
            null,

        amount: 0,
        percentage: null,

        frequency:
            SALARY_FREQUENCIES.MONTHLY,

        show_in_offer: true,
        affects_in_hand: true,
        is_taxable: true,

        sort_order:
            (components.value.length + 1) *
            10,

        description: null,

        ...overrides,
    };
}

function addComponent(
    componentType =
        SALARY_COMPONENT_TYPES.EARNING,
) {
    const component = createComponent({
        component_type: componentType,

        affects_in_hand:
            componentType !==
            SALARY_COMPONENT_TYPES.EMPLOYER_CONTRIBUTION,
    });

    components.value = [
        ...components.value,
        component,
    ];

    expandedRows.value.add(
        component.row_key,
    );
}

function duplicateComponent(index) {
    const source =
        components.value[index];

    if (!source) {
        return;
    }

    const duplicate = {
        ...source,

        id: null,

        row_key: createRowKey(),

        component_name:
            source.component_name
                ? `${source.component_name} Copy`
                : "",

        sort_order:
            (components.value.length + 1) *
            10,
    };

    const updated = [
        ...components.value,
    ];

    updated.splice(
        index + 1,
        0,
        duplicate,
    );

    components.value =
        normalizeSortOrders(updated);

    expandedRows.value.add(
        duplicate.row_key,
    );
}

function removeComponent(index) {
    const removing =
        components.value[index];

    if (!removing) {
        return;
    }

    const updated = components.value
        .filter(
            (_, rowIndex) =>
                rowIndex !== index,
        )
        .map((component) => {
            if (
                component.percentage_of_row_key ===
                removing.row_key
            ) {
                return {
                    ...component,
                    percentage_of_row_key:
                        null,
                    percentage_of_component_id:
                        null,
                };
            }

            return component;
        });

    components.value =
        normalizeSortOrders(updated);

    expandedRows.value.delete(
        removing.row_key,
    );
}

function moveComponent(
    index,
    direction,
) {
    const targetIndex =
        index + direction;

    if (
        targetIndex < 0 ||
        targetIndex >=
            components.value.length
    ) {
        return;
    }

    const updated = [
        ...components.value,
    ];

    const [row] = updated.splice(
        index,
        1,
    );

    updated.splice(
        targetIndex,
        0,
        row,
    );

    components.value =
        normalizeSortOrders(updated);
}

function normalizeSortOrders(rows) {
    return rows.map(
        (component, index) => ({
            ...component,
            sort_order:
                (index + 1) * 10,
        }),
    );
}

function updateComponent(
    index,
    field,
    value,
) {
    const updated = [
        ...components.value,
    ];

    const component = {
        ...updated[index],
        [field]: value,
    };

    if (
        field ===
            "calculation_type" &&
        value ===
            SALARY_CALCULATION_TYPES.FIXED
    ) {
        component.percentage = null;
        component.percentage_of_row_key =
            null;
        component.percentage_of_component_id =
            null;
    }

    if (
        field === "component_type" &&
        value ===
            SALARY_COMPONENT_TYPES.EMPLOYER_CONTRIBUTION
    ) {
        component.affects_in_hand =
            false;
    }

    updated[index] = component;

    components.value = updated;
}

function toggleExpanded(rowKey) {
    const expanded = new Set(
        expandedRows.value,
    );

    if (expanded.has(rowKey)) {
        expanded.delete(rowKey);
    } else {
        expanded.add(rowKey);
    }

    expandedRows.value = expanded;
}

function isExpanded(rowKey) {
    return expandedRows.value.has(
        rowKey,
    );
}

function errorFor(
    index,
    field,
) {
    return (
        props.errors[
            `salary_components.${index}.${field}`
        ] ?? null
    );
}

function calculatedRow(index) {
    return (
        calculatedComponents.value[index] ??
        {}
    );
}

function numberValue(event) {
    const value =
        event.target.value;

    if (
        value === "" ||
        value === null
    ) {
        return 0;
    }

    return Number(value);
}

async function focusNameInput() {
    await nextTick();

    const inputs =
        document.querySelectorAll(
            "[data-salary-component-name]",
        );

    inputs[
        inputs.length - 1
    ]?.focus();
}

function addAndFocus(type) {
    addComponent(type);
    focusNameInput();
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="components.length === 0"
            class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center"
        >
            <p
                class="text-sm font-medium text-gray-700"
            >
                No salary components added
            </p>

            <p
                class="mt-1 text-sm text-gray-500"
            >
                Add an earning, deduction, or
                employer contribution.
            </p>

            <button
                type="button"
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="disabled"
                @click="
                    addAndFocus(
                        SALARY_COMPONENT_TYPES.EARNING,
                    )
                "
            >
                <Plus class="h-4 w-4" />
                Add salary component
            </button>
        </div>

        <div
            v-else
            class="overflow-hidden rounded-xl border border-gray-200"
        >
            <div class="overflow-x-auto">
                <table
                    class="min-w-[1180px] w-full divide-y divide-gray-200"
                >
                    <thead
                        class="bg-gray-50"
                    >
                        <tr>
                            <th
                                class="w-10 px-2 py-3"
                            />

                            <th
                                class="w-[250px] min-w-[250px] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Component
                            </th>

                            <th
                                class="w-48 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Type
                            </th>

                            <th
                                class="w-44 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Calculation
                            </th>

                            <th
                                class="w-40 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Amount
                            </th>

                            <th
                                class="w-40 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Frequencyy
                            </th>

                            <th
                                class="w-40 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Monthly
                            </th>

                            <th
                                class="w-40 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Annual
                            </th>

                            <th
                                class="w-32 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200 bg-white"
                    >
                        <template
                            v-for="(
                                component,
                                index
                            ) in components"
                            :key="
                                component.row_key
                            "
                        >
                            <tr
                                class="align-top transition hover:bg-gray-50/70"
                            >
                                <td
                                    class="px-2 py-3"
                                >
                                    <div
                                        class="flex flex-col items-center gap-1"
                                    >
                                        <GripVertical
                                            class="h-4 w-4 text-gray-300"
                                        />

                                        <button
                                            type="button"
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40"
                                            :disabled="
                                                disabled ||
                                                index ===
                                                    0
                                            "
                                            @click="
                                                moveComponent(
                                                    index,
                                                    -1,
                                                )
                                            "
                                        >
                                            <ChevronUp
                                                class="h-3.5 w-3.5"
                                            />
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40"
                                            :disabled="
                                                disabled ||
                                                index ===
                                                    components.length -
                                                        1
                                            "
                                            @click="
                                                moveComponent(
                                                    index,
                                                    1,
                                                )
                                            "
                                        >
                                            <ChevronDown
                                                class="h-3.5 w-3.5"
                                            />
                                        </button>
                                    </div>
                                </td>

                                <td class="w-[250px] min-w-[250px] px-3 py-3">
                                    <input
                                        data-salary-component-name
                                        type="text"
                                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                        :class="{
                                            'border-red-400 focus:border-red-500 focus:ring-red-500':
                                                errorFor(
                                                    index,
                                                    'component_name',
                                                ),
                                        }"
                                        :value="
                                            component.component_name
                                        "
                                        :disabled="
                                            disabled
                                        "
                                        placeholder="Basic Salary"
                                        @input="
                                            updateComponent(
                                                index,
                                                'component_name',
                                                $event
                                                    .target
                                                    .value,
                                            )
                                        "
                                    />

                                    <p
                                        v-if="
                                            errorFor(
                                                index,
                                                'component_name',
                                            )
                                        "
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{
                                            errorFor(
                                                index,
                                                "component_name",
                                            )
                                        }}
                                    </p>
                                </td>

                                <td class="w-[200px] min-w-[200px] px-3 py-3"
                                >
                                    <select
                                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                        :value="
                                            component.component_type
                                        "
                                        :disabled="
                                            disabled
                                        "
                                        @change="
                                            updateComponent(
                                                index,
                                                'component_type',
                                                $event
                                                    .target
                                                    .value,
                                            )
                                        "
                                    >
                                        <option
                                            v-for="option in componentTypeOptions"
                                            :key="
                                                option.value
                                            "
                                            :value="
                                                option.value
                                            "
                                        >
                                            {{
                                                option.label
                                            }}
                                        </option>
                                    </select>
                                </td>

                                <td
                                    class="w-[170px] min-w-[170px] px-3 py-3"
                                >
                                    <select
                                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                        :value="
                                            component.calculation_type
                                        "
                                        :disabled="
                                            disabled
                                        "
                                        @change="
                                            updateComponent(
                                                index,
                                                'calculation_type',
                                                $event
                                                    .target
                                                    .value,
                                            )
                                        "
                                    >
                                        <option
                                            v-for="option in calculationTypeOptions"
                                            :key="
                                                option.value
                                            "
                                            :value="
                                                option.value
                                            "
                                        >
                                            {{
                                                option.label
                                            }}
                                        </option>
                                    </select>
                                </td>

                                <td
                                    class="w-[170px] min-w-[170px] px-3 py-3"
                                >
                                    <div
                                        v-if="
                                            component.calculation_type ===
                                            SALARY_CALCULATION_TYPES.PERCENTAGE
                                        "
                                        class="space-y-2"
                                    >
                                        <div
                                            class="relative"
                                        >
                                            <input
                                                type="number"
                                                min="0"
                                                max="1000"
                                                step="0.01"
                                                class="block w-full rounded-lg border-gray-300 pr-8 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                                :value="
                                                    component.percentage
                                                "
                                                :disabled="
                                                    disabled
                                                "
                                                @input="
                                                    updateComponent(
                                                        index,
                                                        'percentage',
                                                        numberValue(
                                                            $event,
                                                        ),
                                                    )
                                                "
                                            />

                                            <span
                                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-gray-500"
                                            >
                                                %
                                            </span>
                                        </div>

                                        <select
                                            class="block w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                            :value="
                                                component.percentage_of_row_key
                                            "
                                            :disabled="
                                                disabled
                                            "
                                            @change="
                                                updateComponent(
                                                    index,
                                                    'percentage_of_row_key',
                                                    $event
                                                        .target
                                                        .value ||
                                                        null,
                                                )
                                            "
                                        >
                                            <option
                                                value=""
                                            >
                                                Select base
                                            </option>

                                            <option
                                                v-for="option in percentageBaseOptions.filter(
                                                    (
                                                        option,
                                                    ) =>
                                                        option.value !==
                                                        component.row_key,
                                                )"
                                                :key="
                                                    option.value
                                                "
                                                :value="
                                                    option.value
                                                "
                                            >
                                                {{
                                                    option.label
                                                }}
                                            </option>
                                        </select>

                                        <p
                                            v-if="
                                                errorFor(
                                                    index,
                                                    'percentage',
                                                ) ||
                                                errorFor(
                                                    index,
                                                    'percentage_of_row_key',
                                                )
                                            "
                                            class="text-xs text-red-600"
                                        >
                                            {{
                                                errorFor(
                                                    index,
                                                    "percentage",
                                                ) ||
                                                errorFor(
                                                    index,
                                                    "percentage_of_row_key",
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-else
                                        class="relative"
                                    >
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500"
                                        >
                                            ₹
                                        </span>

                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="block w-full rounded-lg border-gray-300 pl-7 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                            :value="
                                                component.amount
                                            "
                                            :disabled="
                                                disabled
                                            "
                                            @input="
                                                updateComponent(
                                                    index,
                                                    'amount',
                                                    numberValue(
                                                        $event,
                                                    ),
                                                )
                                            "
                                        />
                                    </div>
                                </td>

                                <td
                                    class="w-[150px] min-w-[150px] px-3 py-3"
                                >
                                    <select
                                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                        :value="
                                            component.frequency
                                        "
                                        :disabled="
                                            disabled
                                        "
                                        @change="
                                            updateComponent(
                                                index,
                                                'frequency',
                                                $event
                                                    .target
                                                    .value,
                                            )
                                        "
                                    >
                                        <option
                                            v-for="option in frequencyOptions"
                                            :key="
                                                option.value
                                            "
                                            :value="
                                                option.value
                                            "
                                        >
                                            {{
                                                option.label
                                            }}
                                        </option>
                                    </select>
                                </td>

                                <td
                                    class="w-[150px] min-w-[150px] px-3 py-4 text-right text-sm font-medium text-gray-900"
                                >
                                    {{
                                        formatCurrency(
                                            calculatedRow(
                                                index,
                                            )
                                                .monthly_amount,
                                            currency,
                                        )
                                    }}
                                </td>

                                <td
                                    class="w-[150px] min-w-[150px] px-3 py-4 text-right text-sm font-medium text-gray-900"
                                >
                                    {{
                                        formatCurrency(
                                            calculatedRow(
                                                index,
                                            )
                                                .annual_amount,
                                            currency,
                                        )
                                    }}
                                </td>

                                <td
                                    class="w-[150px] min-w-[150px] px-3 py-3"
                                >
                                    <div
                                        class="flex justify-end gap-1"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800 disabled:opacity-40"
                                            :disabled="
                                                disabled
                                            "
                                            title="More settings"
                                            @click="
                                                toggleExpanded(
                                                    component.row_key,
                                                )
                                            "
                                        >
                                            <ChevronUp
                                                v-if="
                                                    isExpanded(
                                                        component.row_key,
                                                    )
                                                "
                                                class="h-4 w-4"
                                            />

                                            <ChevronDown
                                                v-else
                                                class="h-4 w-4"
                                            />
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 disabled:opacity-40"
                                            :disabled="
                                                disabled
                                            "
                                            title="Duplicate"
                                            @click="
                                                duplicateComponent(
                                                    index,
                                                )
                                            "
                                        >
                                            <Copy
                                                class="h-4 w-4"
                                            />
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                                            :disabled="
                                                disabled
                                            "
                                            title="Remove"
                                            @click="
                                                removeComponent(
                                                    index,
                                                )
                                            "
                                        >
                                            <Trash2
                                                class="h-4 w-4"
                                            />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-if="
                                    isExpanded(
                                        component.row_key,
                                    )
                                "
                                class="bg-gray-50/80"
                            >
                                <td />

                                <td
                                    colspan="8"
                                    class="px-4 py-4"
                                >
                                    <div
                                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                                    >
                                        <label
                                            class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3"
                                        >
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                :checked="
                                                    component.show_in_offer
                                                "
                                                :disabled="
                                                    disabled
                                                "
                                                @change="
                                                    updateComponent(
                                                        index,
                                                        'show_in_offer',
                                                        $event
                                                            .target
                                                            .checked,
                                                    )
                                                "
                                            />

                                            <span>
                                                <span
                                                    class="block text-sm font-medium text-gray-800"
                                                >
                                                    Show in
                                                    offer
                                                </span>

                                                <span
                                                    class="text-xs text-gray-500"
                                                >
                                                    Display in
                                                    salary
                                                    annexure
                                                </span>
                                            </span>
                                        </label>

                                        <label
                                            class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3"
                                        >
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                :checked="
                                                    component.affects_in_hand
                                                "
                                                :disabled="
                                                    disabled ||
                                                    component.component_type ===
                                                        SALARY_COMPONENT_TYPES.EMPLOYER_CONTRIBUTION
                                                "
                                                @change="
                                                    updateComponent(
                                                        index,
                                                        'affects_in_hand',
                                                        $event
                                                            .target
                                                            .checked,
                                                    )
                                                "
                                            />

                                            <span>
                                                <span
                                                    class="block text-sm font-medium text-gray-800"
                                                >
                                                    Affects
                                                    in-hand
                                                </span>

                                                <span
                                                    class="text-xs text-gray-500"
                                                >
                                                    Include in
                                                    net salary
                                                    calculation
                                                </span>
                                            </span>
                                        </label>

                                        <label
                                            class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3"
                                        >
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                :checked="
                                                    component.is_taxable
                                                "
                                                :disabled="
                                                    disabled
                                                "
                                                @change="
                                                    updateComponent(
                                                        index,
                                                        'is_taxable',
                                                        $event
                                                            .target
                                                            .checked,
                                                    )
                                                "
                                            />

                                            <span>
                                                <span
                                                    class="block text-sm font-medium text-gray-800"
                                                >
                                                    Taxable
                                                </span>

                                                <span
                                                    class="text-xs text-gray-500"
                                                >
                                                    Mark for
                                                    future
                                                    payroll
                                                </span>
                                            </span>
                                        </label>

                                        <div
                                            class="md:col-span-2 xl:col-span-1"
                                        >
                                            <label
                                                class="mb-1 block text-sm font-medium text-gray-700"
                                            >
                                                Description
                                            </label>

                                            <input
                                                type="text"
                                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                                :value="
                                                    component.description
                                                "
                                                :disabled="
                                                    disabled
                                                "
                                                placeholder="Internal or annexure note"
                                                @input="
                                                    updateComponent(
                                                        index,
                                                        'description',
                                                        $event
                                                            .target
                                                            .value,
                                                    )
                                                "
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <p
            v-if="errors.salary_components"
            class="text-sm text-red-600"
        >
            {{ errors.salary_components }}
        </p>

        <div
            class="flex flex-wrap items-center gap-2"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="disabled"
                @click="
                    addAndFocus(
                        SALARY_COMPONENT_TYPES.EARNING,
                    )
                "
            >
                <Plus class="h-4 w-4" />
                Add earning
            </button>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="disabled"
                @click="
                    addAndFocus(
                        SALARY_COMPONENT_TYPES.DEDUCTION,
                    )
                "
            >
                <Plus class="h-4 w-4" />
                Add deduction
            </button>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="disabled"
                @click="
                    addAndFocus(
                        SALARY_COMPONENT_TYPES.EMPLOYER_CONTRIBUTION,
                    )
                "
            >
                <Plus class="h-4 w-4" />
                Add employer contribution
            </button>
        </div>
    </div>
</template>