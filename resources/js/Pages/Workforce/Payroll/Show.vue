<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Common/Modal.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { formatMoney } from "@/Utils/workforce";

const props = defineProps({ run: Object, totals: Object, items: Array, adjustments: Array, adjustmentTypes: Object, actions: Array, can: Object, history: Array });

const open = ref(null);
const onlyIssues = ref(false);
const visible = computed(() => props.items.filter((i) => !onlyIssues.value || i.blocking || i.status === "held"));

const steps = [["draft", "Draft"], ["calculated", "Calculated"], ["pending_review", "Review"], ["pending_approval", "Approval"], ["approved", "Approved"], ["payslips_published", "Published"], ["closed", "Closed"]];
const stepIndex = computed(() => steps.findIndex((s) => s[0] === props.run.status));

const calculate = () => router.post(route("workforce.payroll.runs.calculate", props.run.id), {}, { preserveScroll: true });

const act = (a) => {
    let note = null;
    if (a.needs_note) {
        note = prompt(`${a.label} – please give a reason:`);
        if (!note) return;
    } else if (!confirm(`${a.label}?${a.key === "approve" ? " Attendance for this period will be locked and payslips generated." : ""}${a.key === "publish" ? " Employees will be able to see their payslips immediately." : ""}`)) {
        return;
    }
    router.post(route("workforce.payroll.runs.transition", { run: props.run.id, action: a.key }), { note }, { preserveScroll: true });
};

const hold = (item, on) => {
    const reason = on ? prompt(`Hold ${item.name} out of this run? Reason:`) : null;
    if (on && !reason) return;
    router.post(route("workforce.payroll.runs.items.hold", { run: props.run.id, item: item.id }), { hold: on, reason }, { preserveScroll: true });
};

const showAdj = ref(false);
const adj = useForm({ user_id: "", type: "incentive", label: "", amount: "", reason: "" });
const startAdj = (item) => { adj.reset(); adj.clearErrors(); adj.user_id = item?.user_id ?? ""; showAdj.value = true; };
const saveAdj = () => adj.post(route("workforce.payroll.runs.adjustments.store", props.run.id), { preserveScroll: true, onSuccess: () => (showAdj.value = false) });
const voidAdj = (a) => confirm("Remove this adjustment?") && router.delete(route("workforce.payroll.runs.adjustments.void", { run: props.run.id, adjustment: a.id }), { preserveScroll: true });

const num = (n) => (Math.round(n * 100) / 100).toString();
</script>

<template>
    <Head :title="run.run_number" />

    <AppLayout>
        <PageHeader :title="`${run.client} · ${run.period}`" :subtitle="`${run.run_number} · ${run.period_range} · version ${run.version}${run.calculated_at ? ' · calculated ' + run.calculated_at : ''}`">
            <Pill :status="run.status" :label="run.status_label" />
            <button v-if="can.calculate" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="calculate">{{ run.version ? "Recalculate" : "Calculate payroll" }}</button>
            <button v-for="a in actions" :key="a.key" :class="['rounded-lg px-4 py-2 text-sm font-semibold', a.danger ? 'border border-red-300 text-red-700 hover:bg-red-50' : 'bg-indigo-600 text-white hover:bg-indigo-700']" @click="act(a)">{{ a.label }}</button>
            <Link :href="route('workforce.payroll.index')" class="text-sm text-indigo-600 hover:underline">← All runs</Link>
        </PageHeader>

        <div class="mb-6 flex items-center gap-1 overflow-x-auto rounded-xl bg-white p-3 text-xs shadow-sm ring-1 ring-gray-100">
            <template v-for="(s, i) in steps" :key="s[0]">
                <span :class="['rounded-full px-3 py-1 font-semibold whitespace-nowrap', i < stepIndex ? 'bg-emerald-100 text-emerald-800' : i === stepIndex ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400']">{{ s[1] }}</span>
                <span v-if="i < steps.length - 1" class="text-gray-300">›</span>
            </template>
        </div>

        <div v-if="run.notes && run.status === 'calculated'" class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Sent back: {{ run.notes }}</div>

        <div v-if="run.version" class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"><p class="text-xs text-gray-500">Employees paid</p><p class="text-xl font-bold">{{ totals.employees }}</p><p v-if="totals.held" class="text-xs text-red-600">{{ totals.held }} held</p></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"><p class="text-xs text-gray-500">To fix</p><p :class="['text-xl font-bold', totals.blocking ? 'text-orange-600' : 'text-emerald-600']">{{ totals.blocking }}</p></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"><p class="text-xs text-gray-500">Gross</p><p class="text-xl font-bold">₹ {{ formatMoney(totals.gross) }}</p></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"><p class="text-xs text-gray-500">Deductions</p><p class="text-xl font-bold">₹ {{ formatMoney(totals.deductions) }}</p></div>
            <div class="rounded-xl bg-indigo-50 p-4 shadow-sm ring-1 ring-indigo-100 sm:col-span-2 lg:col-span-1"><p class="text-xs text-indigo-600">Net payable</p><p class="text-xl font-bold text-indigo-900">₹ {{ formatMoney(totals.net) }}</p></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"><p class="text-xs text-gray-500">Employer contributions</p><p class="text-xl font-bold">₹ {{ formatMoney(totals.employer) }}</p></div>
        </div>
        <p v-else class="mb-6 rounded-2xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-100">This run has not been calculated yet. Press <b>Calculate payroll</b> to pull in approved attendance, leave and salary structures.</p>

        <div v-if="items.length" class="mb-8">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Employees</h2>
                <label class="flex items-center gap-2 text-xs text-gray-600"><input v-model="onlyIssues" type="checkbox" class="rounded border-gray-300" /> Show only issues</label>
            </div>
            <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr><th class="px-4 py-3">Employee</th><th class="px-4 py-3 text-right">Paid days</th><th class="px-4 py-3 text-right">LOP</th><th class="px-4 py-3 text-right">Gross</th><th class="px-4 py-3 text-right">Deductions</th><th class="px-4 py-3 text-right">Net</th><th class="px-4 py-3" /></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template v-for="i in visible" :key="i.id">
                            <tr :class="['cursor-pointer hover:bg-gray-50', i.status === 'held' ? 'bg-red-50/40' : i.blocking ? 'bg-orange-50/50' : '']" @click="open = open === i.id ? null : i.id">
                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ i.name }}<p class="text-xs font-normal text-gray-400">{{ i.employee_id }} · {{ i.designation }}</p></td>
                                <td class="px-4 py-2.5 text-right">{{ num(i.payable_days) }}/{{ i.calendar_days }}</td>
                                <td class="px-4 py-2.5 text-right" :class="i.lop_days ? 'text-red-600' : 'text-gray-400'">{{ num(i.lop_days) }}</td>
                                <td class="px-4 py-2.5 text-right">{{ formatMoney(i.gross) }}</td>
                                <td class="px-4 py-2.5 text-right">{{ formatMoney(i.deductions_total) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold">{{ formatMoney(i.net) }}</td>
                                <td class="px-4 py-2.5 text-right"><Pill v-if="i.status === 'held'" status="held" /><span v-else-if="i.blocking" class="text-xs font-semibold text-orange-600">Fix</span></td>
                            </tr>
                            <tr v-if="open === i.id" class="bg-gray-50">
                                <td colspan="7" class="px-6 py-4">
                                    <div v-if="i.exceptions.length" class="mb-3 space-y-1">
                                        <p v-for="(e, k) in i.exceptions" :key="k" :class="['text-xs', e.blocking ? 'font-medium text-orange-700' : 'text-gray-500']">{{ e.blocking ? "⚠" : "ℹ" }} {{ e.message }}</p>
                                    </div>
                                    <p v-if="i.status === 'held'" class="mb-3 text-xs text-red-700">Held: {{ i.hold_reason }}</p>
                                    <div class="grid gap-6 md:grid-cols-3">
                                        <div>
                                            <p class="mb-1 text-xs font-semibold uppercase text-gray-500">Attendance</p>
                                            <dl class="space-y-0.5 text-xs text-gray-700">
                                                <div class="flex justify-between"><dt>Present</dt><dd>{{ num(i.present_days) }}</dd></div>
                                                <div class="flex justify-between"><dt>Absent</dt><dd>{{ num(i.absent_days) }}</dd></div>
                                                <div class="flex justify-between"><dt>Paid leave</dt><dd>{{ num(i.paid_leave_days) }}</dd></div>
                                                <div class="flex justify-between"><dt>Unpaid leave</dt><dd>{{ num(i.unpaid_leave_days) }}</dd></div>
                                                <div class="flex justify-between"><dt>Weekly off / holiday</dt><dd>{{ num(i.weekly_off_days) }} / {{ num(i.holiday_days) }}</dd></div>
                                                <div class="flex justify-between"><dt>Late / early</dt><dd>{{ i.late_count }} / {{ i.early_count }}</dd></div>
                                                <div class="flex justify-between"><dt>Worked / overtime</dt><dd>{{ i.worked_hours }}h / {{ i.overtime_hours }}h</dd></div>
                                            </dl>
                                        </div>
                                        <div>
                                            <p class="mb-1 text-xs font-semibold uppercase text-gray-500">Earnings</p>
                                            <dl class="space-y-0.5 text-xs text-gray-700"><div v-for="l in i.earnings" :key="l.code" class="flex justify-between"><dt>{{ l.name }}</dt><dd>{{ formatMoney(l.amount) }}</dd></div></dl>
                                        </div>
                                        <div>
                                            <p class="mb-1 text-xs font-semibold uppercase text-gray-500">Deductions</p>
                                            <dl class="space-y-0.5 text-xs text-gray-700"><div v-for="l in i.deductions" :key="l.code" class="flex justify-between"><dt>{{ l.name }}</dt><dd>{{ formatMoney(l.amount) }}</dd></div></dl>
                                            <p class="mb-1 mt-3 text-xs font-semibold uppercase text-gray-500">Employer contributions</p>
                                            <dl class="space-y-0.5 text-xs text-gray-700"><div v-for="l in i.employer" :key="l.code" class="flex justify-between"><dt>{{ l.name }}</dt><dd>{{ formatMoney(l.amount) }}</dd></div></dl>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex gap-3 text-xs">
                                        <Link :href="route('workforce.attendance.employee', { employee: i.user_id })" class="font-medium text-indigo-600 hover:underline">Open attendance</Link>
                                        <button v-if="can.adjust" class="font-medium text-gray-700 hover:underline" @click="startAdj(i)">Add adjustment</button>
                                        <button v-if="can.hold && i.status !== 'held'" class="font-medium text-red-600 hover:underline" @click="hold(i, true)">Hold</button>
                                        <button v-if="can.hold && i.status === 'held'" class="font-medium text-emerald-700 hover:underline" @click="hold(i, false)">Release</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="adjustments.length || can.adjust" class="mb-8">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Adjustments (incentives, bonuses, deductions)</h2>
                <button v-if="can.adjust && items.length" class="text-sm font-medium text-indigo-600 hover:underline" @click="startAdj(null)">+ Add adjustment</button>
            </div>
            <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="a in adjustments" :key="a.id">
                            <td class="px-4 py-2.5 font-medium text-gray-800">{{ a.employee }}<p class="text-xs font-normal text-gray-400">{{ a.employee_id }}</p></td>
                            <td class="px-4 py-2.5 text-gray-600">{{ a.type }} · {{ a.label }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-500">{{ a.reason }} <span class="text-gray-400">— {{ a.by }}</span></td>
                            <td :class="['px-4 py-2.5 text-right font-semibold', a.is_deduction ? 'text-red-600' : 'text-emerald-700']">{{ a.is_deduction ? "−" : "+" }}{{ formatMoney(a.amount) }}</td>
                            <td class="px-4 py-2.5 text-right"><button v-if="can.adjust" class="text-xs text-red-600 hover:underline" @click="voidAdj(a)">Remove</button></td>
                        </tr>
                        <tr v-if="!adjustments.length"><td class="px-4 py-6 text-center text-gray-500">No adjustments.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-2 text-sm font-semibold text-gray-800">History</h2>
            <ul class="space-y-1 rounded-2xl bg-white p-4 text-xs text-gray-600 shadow-sm ring-1 ring-gray-100">
                <li v-for="(h, k) in history" :key="k"><b class="capitalize">{{ h.event }}</b> by {{ h.by ?? "system" }} · {{ h.at }}<span v-if="h.note"> — "{{ h.note }}"</span></li>
                <li v-if="!history.length" class="text-gray-400">No activity yet.</li>
            </ul>
        </div>

        <Modal :show="showAdj" title="Add adjustment" subtitle="Added to or taken from this employee's pay for this run only." @close="showAdj = false">
            <form class="space-y-4 p-6" @submit.prevent="saveAdj">
                <div>
                    <label class="text-sm font-medium text-gray-700">Employee</label>
                    <select v-model="adj.user_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="" disabled>Choose…</option><option v-for="i in items" :key="i.user_id" :value="i.user_id">{{ i.name }} ({{ i.employee_id }})</option></select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-sm font-medium text-gray-700">Type</label><select v-model="adj.type" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option v-for="(l, k) in adjustmentTypes" :key="k" :value="k">{{ l }}</option></select></div>
                    <div><label class="text-sm font-medium text-gray-700">Amount (₹)</label><input v-model="adj.amount" type="number" step="0.01" min="0.01" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                </div>
                <div><label class="text-sm font-medium text-gray-700">Label on payslip</label><input v-model="adj.label" required maxlength="120" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <div><label class="text-sm font-medium text-gray-700">Reason</label><textarea v-model="adj.reason" rows="2" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p v-if="adj.errors.adjustment || adj.errors.reason" class="mt-1 text-xs text-red-600">{{ adj.errors.adjustment ?? adj.errors.reason }}</p></div>
                <div class="flex justify-end gap-3"><button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="showAdj = false">Cancel</button><button type="submit" :disabled="adj.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Add</button></div>
            </form>
        </Modal>
    </AppLayout>
</template>
