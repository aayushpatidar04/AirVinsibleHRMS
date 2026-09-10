<script setup>
import {
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    Clock3,
    MapPin,
    Network,
    UserRoundCheck,
} from "lucide-vue-next";

defineProps({
    offer: {
        type: Object,
        required: true,
    },
});

function valueOrDash(value) {
    return value || "—";
}

function formatLabel(value) {
    if (!value) {
        return "—";
    }

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDate(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
}

const fields = [
    {
        key: "designation",
        label: "Designation",
        icon: BriefcaseBusiness,
    },
    {
        key: "department",
        label: "Department",
        icon: Building2,
    },
    {
        key: "branch",
        label: "Branch",
        icon: Network,
    },
    {
        key: "reporting_manager",
        label: "Reporting Manager",
        icon: UserRoundCheck,
    },
    {
        key: "employment_type",
        label: "Employment Type",
        icon: BriefcaseBusiness,
        formatter: formatLabel,
    },
    {
        key: "work_location",
        label: "Work Location",
        icon: MapPin,
    },
    {
        key: "joining_date",
        label: "Joining Date",
        icon: CalendarDays,
        formatter: formatDate,
    },
    {
        key: "reporting_time",
        label: "Reporting Time",
        icon: Clock3,
    },
    {
        key: "probation_months",
        label: "Probation",
        icon: CalendarDays,
        formatter: (value) =>
            value ? `${value} month${Number(value) === 1 ? "" : "s"}` : "—",
    },
    {
        key: "notice_period_days",
        label: "Notice Period",
        icon: CalendarDays,
        formatter: (value) =>
            value ? `${value} day${Number(value) === 1 ? "" : "s"}` : "—",
    },
    {
        key: "process",
        label: "Process / Business Unit",
        icon: Network,
    },
];
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
            <h2 class="text-base font-semibold text-gray-900">
                Employment Details
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Role, location, reporting and joining information.
            </p>
        </div>

        <div
            class="grid gap-x-8 gap-y-6 px-5 py-5 sm:grid-cols-2 xl:grid-cols-3 sm:px-6"
        >
            <div
                v-for="field in fields"
                :key="field.key"
                class="flex items-start gap-3"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
                >
                    <component :is="field.icon" class="h-4 w-4" />
                </div>

                <div class="min-w-0">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        {{ field.label }}
                    </p>

                    <p
                        class="mt-1 break-words text-sm font-medium text-gray-900"
                    >
                        {{
                            field.formatter
                                ? field.formatter(offer[field.key])
                                : valueOrDash(offer[field.key])
                        }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
