<script setup>
import { computed } from "vue";

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: null },
});

const styles = {
    // attendance
    present: ["Present", "bg-emerald-100 text-emerald-800"],
    half_day: ["Half day", "bg-amber-100 text-amber-800"],
    absent: ["Absent", "bg-red-100 text-red-700"],
    leave: ["On leave", "bg-sky-100 text-sky-800"],
    weekly_off: ["Weekly off", "bg-slate-100 text-slate-600"],
    holiday: ["Holiday", "bg-violet-100 text-violet-700"],
    incomplete: ["Incomplete", "bg-orange-100 text-orange-800"],
    pending: ["Pending", "bg-gray-100 text-gray-600"],
    // requests
    approved: ["Approved", "bg-emerald-100 text-emerald-800"],
    rejected: ["Rejected", "bg-red-100 text-red-700"],
    cancelled: ["Cancelled", "bg-gray-100 text-gray-500"],
    // payroll
    draft: ["Draft", "bg-gray-100 text-gray-600"],
    calculated: ["Calculated", "bg-sky-100 text-sky-800"],
    pending_review: ["Pending review", "bg-amber-100 text-amber-800"],
    pending_approval: ["Pending approval", "bg-orange-100 text-orange-800"],
    payslips_published: ["Payslips published", "bg-emerald-100 text-emerald-800"],
    closed: ["Closed", "bg-slate-200 text-slate-700"],
    held: ["Held", "bg-red-100 text-red-700"],
    open: ["Open", "bg-sky-100 text-sky-800"],
    published: ["Published", "bg-emerald-100 text-emerald-800"],
    hidden: ["Hidden", "bg-gray-100 text-gray-600"],
};

const pair = computed(() => styles[props.status] ?? [props.status, "bg-gray-100 text-gray-600"]);
</script>

<template>
    <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap', pair[1]]">
        {{ label ?? pair[0] }}
    </span>
</template>
