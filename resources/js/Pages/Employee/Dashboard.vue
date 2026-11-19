<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import { formatMinutes, formatMoney } from "@/Utils/workforce";

defineProps({
    punchState: String,
    today: Object,
    month: Object,
    balances: Array,
    pending: Object,
    latestPayslip: Object,
    team: Object,
    hasClient: Boolean,
});

const stateText = (s) => ({ in: "You are punched in", break_start: "You are on a break", break_end: "You are punched in", out: "You have punched out" }[s] ?? "You have not punched in yet");
</script>

<template>
    <Head title="My dashboard" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900">My dashboard</h1>
                <Link :href="route('employee.punch')" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Punch in / out
                </Link>
            </div>

            <p v-if="!hasClient" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                You are not assigned to a client yet, so attendance and payroll rules cannot be applied. Please ask HR to complete your profile.
            </p>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Today</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ stateText(punchState) }}</p>
                    <div v-if="today" class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                        <Pill :status="today.status" />
                        <span v-if="today.first_in">In {{ today.first_in }}</span>
                        <span v-if="today.is_late" class="text-red-600">· late</span>
                        <span>· {{ formatMinutes(today.worked_minutes) }} worked</span>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 lg:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ month.label }}</p>
                    <div class="mt-3 grid grid-cols-3 gap-4 sm:grid-cols-6">
                        <div><p class="text-2xl font-bold text-emerald-600">{{ month.present }}</p><p class="text-xs text-gray-500">Present</p></div>
                        <div><p class="text-2xl font-bold text-red-600">{{ month.absent }}</p><p class="text-xs text-gray-500">Absent</p></div>
                        <div><p class="text-2xl font-bold text-sky-600">{{ month.leave }}</p><p class="text-xs text-gray-500">Leave</p></div>
                        <div><p class="text-2xl font-bold text-amber-600">{{ month.late }}</p><p class="text-xs text-gray-500">Late</p></div>
                        <div><p class="text-2xl font-bold text-orange-600">{{ month.exceptions }}</p><p class="text-xs text-gray-500">To fix</p></div>
                        <div><p class="text-2xl font-bold text-gray-800">{{ formatMinutes(month.worked_minutes) }}</p><p class="text-xs text-gray-500">Worked</p></div>
                    </div>
                    <Link :href="route('employee.attendance')" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:underline">View my attendance →</Link>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Leave balance</p>
                        <Link :href="route('employee.leave')" class="text-sm font-medium text-indigo-600 hover:underline">Apply for leave →</Link>
                    </div>
                    <div v-if="balances.length" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div v-for="b in balances" :key="b.leave_type_id" class="rounded-xl bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">{{ b.name }}</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ b.is_paid ? b.available : "—" }}</p>
                            <p v-if="b.pending" class="text-xs text-amber-600">{{ b.pending }} pending</p>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-gray-500">No leave policy applies to you yet.</p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Latest payslip</p>
                        <template v-if="latestPayslip">
                            <p class="mt-2 text-sm text-gray-600">{{ latestPayslip.period }}</p>
                            <p class="text-xl font-bold text-gray-900">₹ {{ formatMoney(latestPayslip.net) }}</p>
                            <Link :href="route('employee.payslips')" class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:underline">View payslips →</Link>
                        </template>
                        <p v-else class="mt-2 text-sm text-gray-500">Published payslips will appear here.</p>
                    </div>
                    <div v-if="pending.leave || pending.corrections" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 text-sm text-gray-600">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Waiting for approval</p>
                        <p v-if="pending.leave" class="mt-2">{{ pending.leave }} leave request(s)</p>
                        <p v-if="pending.corrections">{{ pending.corrections }} attendance correction(s)</p>
                    </div>
                </div>
            </div>

            <div v-if="team" class="rounded-2xl bg-indigo-50 p-5 ring-1 ring-indigo-100">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">My team ({{ team.size }})</p>
                <div class="mt-2 flex flex-wrap gap-6 text-sm text-indigo-900">
                    <Link :href="route('workforce.leave.index')" class="hover:underline"><b>{{ team.pending_leave }}</b> leave request(s) to approve</Link>
                    <Link :href="route('workforce.attendance.corrections')" class="hover:underline"><b>{{ team.pending_corrections }}</b> correction(s) to approve</Link>
                    <Link :href="route('workforce.attendance.index')" class="hover:underline">Team attendance →</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
